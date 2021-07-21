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

namespace App\PaymentDrivers;

use App\Models\ClientGatewayToken;
use App\Models\GatewayType;
use App\Models\Payment;
use App\Models\PaymentHash;
use App\Models\SystemLog;
use App\PaymentDrivers\PayTrace\CreditCard;
use App\Utils\CurlUtils;
use App\Utils\Traits\MakesHash;

class PaytracePaymentDriver extends BaseDriver
{
    use MakesHash;
    
    public $refundable = true; 

    public $token_billing = true; 

    public $can_authorise_credit_card = true; 

    public $gateway; 

    public $payment_method; 

    public static $methods = [
        GatewayType::CREDIT_CARD => CreditCard::class, //maps GatewayType => Implementation class
    ];

    const SYSTEM_LOG_TYPE = SystemLog::TYPE_PAYTRACE; //define a constant for your gateway ie TYPE_YOUR_CUSTOM_GATEWAY - set the const in the SystemLog model

    public function init()
    {
        return $this; /* This is where you boot the gateway with your auth credentials*/
    }

    /* Returns an array of gateway types for the payment gateway */
    public function gatewayTypes(): array
    {
        $types = [];

            $types[] = GatewayType::CREDIT_CARD;

        return $types;
    }

    /* Sets the payment method initialized */
    public function setPaymentMethod($payment_method_id)
    {
        $class = self::$methods[$payment_method_id];
        $this->payment_method = new $class($this);
        return $this;
    }

    public function authorizeView(array $data)
    {
        return $this->payment_method->authorizeView($data); //this is your custom implementation from here
    }

    public function authorizeResponse($request)
    {
        return $this->payment_method->authorizeResponse($request);  //this is your custom implementation from here
    }

    public function processPaymentView(array $data)
    {
        return $this->payment_method->paymentView($data);  //this is your custom implementation from here
    }

    public function processPaymentResponse($request)
    {
        return $this->payment_method->paymentResponse($request); //this is your custom implementation from here
    }

    public function refund(Payment $payment, $amount, $return_client_response = false)
    {
        return $this->payment_method->yourRefundImplementationHere(); //this is your custom implementation from here
    }

    public function tokenBilling(ClientGatewayToken $cgt, PaymentHash $payment_hash)
    {
        return $this->payment_method->yourTokenBillingImplmentation(); //this is your custom implementation from here
    }

    public function processWebhookRequest(PaymentWebhookRequest $request, Payment $payment = null)
    {
    }

    /*Helpers*/

    public function getAuthToken()
    {

        $url = 'https://api.paytrace.com/oauth/token';
        $data = [
            'grant_type' => 'password',
            'username' => $this->company_gateway->getConfigField('username'),
            'password' => $this->company_gateway->getConfigField('password')
        ];

        $response = CurlUtils::post($url, $data, $headers = false);

        if($response)
        {
            $auth_data = json_decode($response);

            $headers = [];
            $headers[] = 'Content-type: application/json';
            $headers[] = 'Authorization: Bearer '.$auth_data->access_token;

            $response = CurlUtils::post('https://api.paytrace.com/v1/payment_fields/token/create', [], $headers);

            $response = json_decode($response);

            if($response)
                return $response->clientKey;

        }

        return false;
    }
}
