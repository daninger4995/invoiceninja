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

namespace App\Http\Controllers;

use App\Http\Requests\Payments\PaymentNotificationWebhookRequest;
use App\Libraries\MultiDB;
use App\Models\Client;
use App\Models\CompanyGateway;
use Auth;

class PaymentNotificationWebhookController extends Controller
{
    public function __invoke(PaymentNotificationWebhookRequest $request, string $company_key, string $company_gateway_id, string $client_hash)
    {

        $company_gateway = CompanyGateway::find($this->decodePrimaryKey($company_gateway_id));
        $client = Client::find($this->decodePrimaryKey($client_hash));

        return $company_gateway
                ->driver($client)
                ->processWebhookRequest($request);
    }
}
