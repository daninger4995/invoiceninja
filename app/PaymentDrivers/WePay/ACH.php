<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2021. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://opensource.org/licenses/AAL
 */

namespace App\PaymentDrivers\WePay;

use App\Exceptions\PaymentFailed;
use App\Models\ClientGatewayToken;
use App\Models\GatewayType;
use App\Models\Payment;
use App\PaymentDrivers\WePayPaymentDriver;
use App\PaymentDrivers\WePay\WePayCommon;
use App\Utils\Traits\MakesHash;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ACH
{
    use MakesHash;
    use WePayCommon;

    public $wepay_payment_driver;

    public function __construct(WePayPaymentDriver $wepay_payment_driver)
    {
        $this->wepay_payment_driver = $wepay_payment_driver;
    }

    public function authorizeView($data)
    {
        $data['gateway'] = $this->wepay_payment_driver;

        return render('gateways.wepay.authorize.bank_transfer', $data);
    }


    public function authorizeResponse($request)
    {
        //https://developer.wepay.com/api/api-calls/credit_card#authorize
        $data = $request->all();
        // authorize the credit card
        
        nlog($data);
        /*
          '_token' => '1Fk5CRj34up5ntKPvrFyMIAJhDdUNF3boqT3iIN3',
          'company_gateway_id' => '39',
          'payment_method_id' => '1',
          'gateway_response' => NULL,
          'is_default' => NULL,
          'credit_card_id' => '180642154638',
          'q' => '/client/payment_methods',
          'method' => '1',
         */

        $response = $this->wepay_payment_driver->wepay->request('payment_bank/persist', [
            'client_id'          => config('ninja.wepay.client_id'),
            'client_secret'      => config('ninja.wepay.client_secret'),
            'payment_bank_id'     => (int)$data['bank_account_id'],
        ]);

        // display the response
        // nlog($response);
        
        if(in_array($response->state, ['new', 'pending', 'authorized'])){

            $this->storePaymentMethod($response, GatewayType::BANK_TRANSFER);

            return redirect()->route('client.payment_methods.index');

        }
    
        throw new PaymentFailed("There was a problem adding this payment method.", 400);
        
        /*
            {
                "payment_bank_id": 12345,
                "bank_name": "Wells Fargo",
                "account_last_four": "6789",
                "state": "authorized"
            }

            state options: new, pending, authorized, disabled.
        */    
    
    }  


/* If the bank transfer token is PENDING - we need to verify!! */
//

    public function verificationView(ClientGatewayToken $token)
    {
        $this->wepay_payment_driver->init();

        $data = [
            'token' => $token,
            'gateway' => $this->wepay_payment_driver,
        ];

        return render('gateways.wepay.authorize.verify', $data);
    }

    /**
    {
    "client_id": 1234,
    "client_secret": "b1fc2f68-4d1f-4a",
    "payment_bank_id": 12345,
    "type": "microdeposits",
    "microdeposits": [
        8,
        12
    ]
    }
     */
    public function processVerification(Request $request, ClientGatewayToken $token)
    {
        $transactions = $request->input('transactions');

        $transformed_transactions = [];

        foreach($transactions as $transaction)
            $transformed_transactions[] = (int)$transaction;

        try {
        
            $response = $this->wepay_payment_driver->wepay->request('payment_bank/verify', [
                'client_id'          => config('ninja.wepay.client_id'),
                'client_secret'      => config('ninja.wepay.client_secret'),
                'payment_bank_id'    => $token->token,
                'type'               => 'microdeposits',
                'microdeposits'      => $transformed_transactions,
            ]);
        
        }
        catch(\Exception $e){

            return redirect()->route('client.payment_methods.verification', ['payment_method' => $token->hashed_id, 'method' => GatewayType::BANK_TRANSFER])
                      ->with('error', $e->getMessage());
        
        }
        /*
        {
            "payment_bank_id": 12345,
            "bank_name": "Wells Fargo",
            "account_last_four": "6789",
            "state": "authorized"
        }
        */
       nlog($response);

       //$meta = $token->meta;
        if($response->state == "authorized")
        {
            $meta = $token->meta;
            $meta->state = $response->state;
            $token->meta;
            $token->save();

            return redirect()->route('client.payment_methods.index');

        }
        else{
         
          return redirect()->route('client.payment_methods.verification', ['payment_method' => $token->hashed_id, 'method' => GatewayType::BANK_TRANSFER])
                      ->with('error', ctrans('texts.verification_failed'));   
        }
    }

///////////////////////////////////////////////////////////////////////////////////////
    public function paymentView(array $data)
    {

        $data['gateway'] = $this->wepay_payment_driver;
        $data['currency'] = $this->wepay_payment_driver->client->getCurrencyCode();
        $data['payment_method_id'] = GatewayType::BANK_TRANSFER;
        $data['amount'] = $data['total']['amount_with_fee'];

        return render('gateways.wepay.bank_transfer', $data);
    }


    public function paymentResponse($request)
    {
        nlog($request->all());
        
        $token = ClientGatewayToken::find($this->decodePrimaryKey($request->input('source')));
        $token_meta = $token->meta;

        if($token_meta->state != "authorized")
            return redirect()->route('client.payment_methods.verification', ['payment_method' => $token->hashed_id, 'method' => GatewayType::BANK_TRANSFER]);

        $response = $this->wepay_payment_driver->wepay->request('checkout/create', array(
            'unique_id'           => Str::random(40),
            'account_id'          => $this->wepay_payment_driver->company_gateway->getConfigField('accountId'),
            'amount'              => $this->wepay_payment_driver->payment_hash->data->amount_with_fee,
            'currency'            => $this->wepay_payment_driver->client->getCurrencyCode(),
            'short_description'   => 'A vacation home rental',
            'type'                => 'goods',
            'payment_method'      => array(
                'type'            => 'payment_bank',
                'payment_bank'     => array(
                    'id'          => $token->token
                )
            )
        ));

                /* Merge all data and store in the payment hash*/
        $state = [
            'server_response' => $response,
            'payment_hash' => $request->payment_hash,
        ];

        $state = array_merge($state, $request->all());
        $this->wepay_payment_driver->payment_hash->data = array_merge((array) $this->wepay_payment_driver->payment_hash->data, $state); 
        $this->wepay_payment_driver->payment_hash->save();

        if(in_array($response->state, ['authorized', 'captured'])){
            //success
            nlog("success");
            $payment_status = $response->state == 'authorized' ? Payment::STATUS_COMPLETED : Payment::STATUS_PENDING;

            return $this->processSuccessfulPayment($response, $payment_status, GatewayType::BANK_TRANSFER);
        }

        if(in_array($response->state, ['released', 'cancelled', 'failed', 'expired'])){
            //some type of failure
            nlog("failure");

            $payment_status = $response->state == 'cancelled' ? Payment::STATUS_CANCELLED : Payment::STATUS_FAILED;

            $this->processUnSuccessfulPayment($response, $payment_status);
        }

    }

    private function storePaymentMethod($response, $payment_method_id)
    {

        $payment_meta = new \stdClass;
        $payment_meta->exp_month = (string) '';
        $payment_meta->exp_year = (string) '';
        $payment_meta->brand = (string) $response->bank_name;
        $payment_meta->last4 = (string) $response->account_last_four;
        $payment_meta->type = GatewayType::BANK_TRANSFER;
        $payment_meta->state = $response->state;

        $data = [
            'payment_meta' => $payment_meta,
            'token' => $response->payment_bank_id,
            'payment_method_id' => $payment_method_id,
        ];

        $this->wepay_payment_driver->storeGatewayToken($data);

    }     
}
