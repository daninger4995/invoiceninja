<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2021. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\PaymentDrivers\Eway;

use App\Exceptions\PaymentFailed;
use App\Jobs\Util\SystemLogger;
use App\Models\ClientGatewayToken;
use App\Models\GatewayType;
use App\Models\Payment;
use App\Models\PaymentHash;
use App\Models\PaymentType;
use App\Models\SystemLog;
use App\PaymentDrivers\EwayPaymentDriver;
use App\PaymentDrivers\Eway\ErrorCode;
use App\Utils\Traits\MakesHash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CreditCard
{
    use MakesHash;

    public $eway_driver;

    public function __construct(EwayPaymentDriver $eway_driver)
    {
        $this->eway_driver = $eway_driver;
    }

    public function authorizeView($data)
    {

        $data['gateway'] = $this->eway_driver;
        $data['api_key'] = $this->eway_driver->company_gateway->getConfigField('apiKey');
        $data['public_api_key'] = $this->eway_driver->company_gateway->getConfigField('publicApiKey');

        return render('gateways.eway.authorize', $data);

    }

    public function authorizeResponse($request)
    {

        $token = $this->createEwayToken($request->input('securefieldcode'));

        return redirect()->route('client.payment_methods.index');

    }

    private function createEwayToken($securefieldcode)
    {
        $transaction = [
            'Reference' => $this->eway_driver->client->number,
            'Title' => '',
            'FirstName' => $this->eway_driver->client->contacts()->first()->present()->first_name(),
            'LastName' => $this->eway_driver->client->contacts()->first()->present()->last_name(),
            'CompanyName' => $this->eway_driver->client->name,
            'Street1' => $this->eway_driver->client->address1,
            'Street2' => $this->eway_driver->client->address2,
            'City' => $this->eway_driver->client->city,
            'State' => $this->eway_driver->client->state,
            'PostalCode' => $this->eway_driver->client->postal_code,
            'Country' => $this->eway_driver->client->country->iso_3166_2,
            'Phone' => $this->eway_driver->client->phone,
            'Email' => $this->eway_driver->client->contacts()->first()->email,
            "Url" => $this->eway_driver->client->website,
            'Method' => \Eway\Rapid\Enum\PaymentMethod::CREATE_TOKEN_CUSTOMER,
            'SecuredCardData' => $securefieldcode,
        ];

        $response = $this->eway_driver->init()->eway->createCustomer(\Eway\Rapid\Enum\ApiMethod::DIRECT, $transaction);

        $response_status = ErrorCode::getStatus($response->ResponseMessage);

        if(!$response_status['success']){

            $this->eway_driver->sendFailureMail($response_status['message']);

            throw new PaymentFailed($response_status['message'], 400);
        }

        //success
        $cgt = [];
        $cgt['token'] = strval($response->Customer->TokenCustomerID);
        $cgt['payment_method_id'] = GatewayType::CREDIT_CARD;

        $payment_meta = new \stdClass;
        $payment_meta->exp_month = $response->Customer->CardDetails->ExpiryMonth;
        $payment_meta->exp_year = $response->Customer->CardDetails->ExpiryYear;
        $payment_meta->brand = 'CC';
        $payment_meta->last4 = substr($response->Customer->CardDetails->Number, -4);;
        $payment_meta->type = GatewayType::CREDIT_CARD;

        $cgt['payment_meta'] = $payment_meta;

        $token = $this->eway_driver->storeGatewayToken($cgt, []);

        return $token;
    }

    public function paymentView($data)
    {
    
        $data['gateway'] = $this->eway_driver;
        $data['public_api_key'] = $this->eway_driver->company_gateway->getConfigField('publicApiKey');

        return render('gateways.eway.pay', $data);

    }

    public function paymentResponse($request)
    {

        $state = [
            'server_response' => $request->all(),
        ];

        $this->eway_driver->payment_hash->data = array_merge((array) $this->eway_driver->payment_hash->data, $state);
        $this->eway_driver->payment_hash->save();

        if(boolval($request->input('store_card')))
        {
            $token = $this->createEwayToken($request->input('securefieldcode'));
            $payment = $this->tokenBilling($token->token, $this->eway_driver->payment_hash);

            return redirect()->route('client.payments.show', ['payment' => $this->encodePrimaryKey($payment->id)]);

        }

        if($request->token){

            $payment = $this->tokenBilling($request->token, $this->eway_driver->payment_hash);

            return redirect()->route('client.payments.show', ['payment' => $this->encodePrimaryKey($payment->id)]);

        }

        $transaction = [
            'Payment' => [
                'TotalAmount' => $this->convertAmountForEway(),
            ],
            'TransactionType' => \Eway\Rapid\Enum\TransactionType::PURCHASE,
            'SecuredCardData' => $request->input('securefieldcode'),
        ];

        $response = $this->eway_driver->init()->eway->createTransaction(\Eway\Rapid\Enum\ApiMethod::DIRECT, $transaction);

        $response_status = ErrorCode::getStatus($response->ResponseMessage);

        if(!$response_status['success']){

            $this->logResponse($response, false);

            $this->eway_driver->sendFailureMail($response_status['message']);

            throw new PaymentFailed($response_status['message'], 400);
        }

        $this->logResponse($response, true);

        $payment = $this->storePayment($response);

        return redirect()->route('client.payments.show', ['payment' => $this->encodePrimaryKey($payment->id)]);

    }

    private function storePayment($response)
    {
        $amount = array_sum(array_column($this->eway_driver->payment_hash->invoices(), 'amount')) + $this->eway_driver->payment_hash->fee_total;

        $payment_record = [];
        $payment_record['amount'] = $amount;
        $payment_record['payment_type'] = PaymentType::CREDIT_CARD_OTHER;
        $payment_record['gateway_type_id'] = GatewayType::CREDIT_CARD;
        $payment_record['transaction_reference'] = $response->TransactionID;

        $payment = $this->eway_driver->createPayment($payment_record);

        return $payment;
    }

    private function convertAmountForEway($amount = false)
    {
    
        if(!$amount)
            $amount = array_sum(array_column($this->eway_driver->payment_hash->invoices(), 'amount')) + $this->eway_driver->payment_hash->fee_total;

        if(in_array($this->eway_driver->client->currency()->code, ['VND', 'JPY', 'KRW', 'GNF', 'IDR', 'PYG', 'RWF', 'UGX', 'VUV', 'XAF', 'XPF']))
            return $amount;

        return $amount * 100;
    }

    private function logResponse($response, $success = true)
    {

        $logger_message = [
            'server_response' => $response,
        ];

        SystemLogger::dispatch(
            $logger_message,
            SystemLog::CATEGORY_GATEWAY_RESPONSE,
            $success ? SystemLog::EVENT_GATEWAY_SUCCESS : SystemLog::EVENT_GATEWAY_FAILURE,
            SystemLog::TYPE_EWAY,
            $this->eway_driver->client,
            $this->eway_driver->client->company,
        );

    }


    public function tokenBilling($token, $payment_hash)
    {
        $amount = array_sum(array_column($payment_hash->invoices(), 'amount')) + $payment_hash->fee_total;

        $transaction = [
            'Customer' => [
                'TokenCustomerID' => $token,
            ],
            'Payment' => [
                'TotalAmount' => $this->convertAmountForEway($amount),
            ],
            'TransactionType' => \Eway\Rapid\Enum\TransactionType::RECURRING,
        ];

        $response = $this->eway_driver->init()->eway->createTransaction(\Eway\Rapid\Enum\ApiMethod::DIRECT, $transaction);

        $response_status = ErrorCode::getStatus($response->ResponseMessage);

        if(!$response_status['success']){

            $this->logResponse($response, false);

            $this->eway_driver->sendFailureMail($response_status['message']);

            throw new PaymentFailed($response_status['message'], 400);
        }

        $this->logResponse($response, true);

        $payment = $this->storePayment($response);

        return $payment;
    }
}