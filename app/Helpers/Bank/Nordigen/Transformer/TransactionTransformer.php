<?php
/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2023. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Helpers\Bank\Nordigen\Transformer;

use App\Helpers\Bank\BankRevenueInterface;
use App\Models\BankIntegration;
use App\Utils\Traits\AppSetup;
use Illuminate\Support\Facades\Cache;
use Log;

/**
{
  "transactions": {
    "booked": [
      {
        "transactionId": "string",
        "debtorName": "string",
        "debtorAccount": {
          "iban": "string"
        },
        "transactionAmount": {
          "currency": "string",
          "amount": "328.18"
        },
        "bankTransactionCode": "string",
        "bookingDate": "date",
        "valueDate": "date",
        "remittanceInformationUnstructured": "string"
      },
      {
        "transactionId": "string",
        "transactionAmount": {
          "currency": "string",
          "amount": "947.26"
        },
        "bankTransactionCode": "string",
        "bookingDate": "date",
        "valueDate": "date",
        "remittanceInformationUnstructured": "string"
      }
    ],
    "pending": [
      {
        "transactionAmount": {
          "currency": "string",
          "amount": "99.20"
        },
        "valueDate": "date",
        "remittanceInformationUnstructured": "string"
      }
    ]
  }
}
*/

class TransactionTransformer implements BankRevenueInterface
{
    use AppSetup;

    public function transform($transactionResponse)
    {
        $data = [];

        if (!array_key_exists('transactions', $transactionResponse) || !array_key_exists('booked', $transactionResponse["transactions"]))
            throw new \Exception('invalid dataset');

        foreach ($transactionResponse["transactions"]["booked"] as $transaction) {
            $data[] = $this->transformTransaction($transaction);
        }

        return $data;
    }

    public function transformTransaction($transaction)
    {

        if (!array_key_exists('transactionId', $transaction) || !array_key_exists('transactionAmount', $transaction))
            throw new \Exception('invalid dataset');

        // description could be in varios places
        $description = '';
        if (array_key_exists('bank_remittanceInformationStructured', $transaction))
            $description = $transaction["bank_remittanceInformationStructured"];
        else if (array_key_exists('bank_remittanceInformationStructuredArray', $transaction))
            $description = implode($transaction["bank_remittanceInformationStructured"], '\r\n');
        else if (array_key_exists('remittanceInformationUnstructured', $transaction))
            $description = $transaction["remittanceInformationUnstructured"];
        else
            Log::warning("Missing description for the following transaction: " . json_encode($transaction));

        return [
            'transaction_id' => $transaction["transactionId"],
            'amount' => abs((int) $transaction["transactionAmount"]["amount"]),
            'currency_id' => $this->convertCurrency($transaction["transactionAmount"]["currency"]),
            'category_id' => 0, // TODO: institution specific keys like: GUTSCHRIFT, ABSCHLUSS, MONATSABSCHLUSS etc
            'category_type' => array_key_exists('additionalInformation', $transaction) ? $transaction["additionalInformation"] : '', // TODO: institution specific keys like: GUTSCHRIFT, ABSCHLUSS, MONATSABSCHLUSS etc
            'date' => $transaction["bookingDate"],
            'description' => $description,
            // 'description' => `IBAN: ${elem . json["bank_debtorAccount"] && elem . json["bank_debtorAccount"]["iban"] ? elem . json["bank_debtorAccount"]["iban"] : ' -'}\nVerwendungszweck: ${elem . json["bank_remittanceInformationStructured"] || ' -'}\nName: ${elem . json["bank_debtorName"] || ' -'}`, // 2 fields to get data from (structured and structuredArray (have to be joined))
            // TODO: debitor name & iban & bic
            'base_type' => (int) $transaction["transactionAmount"]["amount"] <= 0 ? 'DEBIT' : 'CREDIT',
        ];

    }

    private function convertCurrency(string $code)
    {

        $currencies = Cache::get('currencies');

        if (!$currencies) {
            $this->buildCache(true);
        }

        $currency = $currencies->filter(function ($item) use ($code) {
            return $item->code == $code;
        })->first();

        if ($currency)
            return $currency->id;

        return 1;

    }

}


