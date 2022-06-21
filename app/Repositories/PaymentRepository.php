<?php
/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2022. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Repositories;

use App\Events\Payment\PaymentWasCreated;
use App\Events\Payment\PaymentWasDeleted;
use App\Jobs\Credit\ApplyCreditPayment;
use App\Jobs\Ninja\TransactionLog;
use App\Libraries\Currency\Conversion\CurrencyApi;
use App\Models\Client;
use App\Models\Credit;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\TransactionEvent;
use App\Utils\Ninja;
use App\Utils\Traits\MakesHash;
use App\Utils\Traits\SavesDocuments;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * PaymentRepository.
 */
class PaymentRepository extends BaseRepository
{
    use MakesHash;
    use SavesDocuments;

    protected $credit_repo;

    public function __construct(CreditRepository $credit_repo)
    {
        $this->credit_repo = $credit_repo;
    }

    /**
     * Saves and updates a payment. //todo refactor to handle refunds and payments.
     *
     * @param array   $data the request object
     * @param Payment $payment The Payment object
     * @return Payment|null Payment $payment
     */
    public function save(array $data, Payment $payment): ?Payment
    {
        return $this->applyPayment($data, $payment);
    }

    /**
     * Handles a positive payment request.
     * @param  array $data      The data object
     * @param  Payment $payment The $payment entity
     * @return Payment          The updated/created payment object
     */
    private function applyPayment(array $data, Payment $payment): ?Payment
    {
        $is_existing_payment = true;
        $client = false;

        //check currencies here and fill the exchange rate data if necessary
        if (! $payment->id) {
            $payment = $this->processExchangeRates($data, $payment);

            /* This is needed here otherwise the ->fill() overwrites anything that exists*/
            if ($payment->exchange_rate != 1) {
                unset($data['exchange_rate']);
            }

            $is_existing_payment = false;
            $client = Client::where('id', $data['client_id'])->withTrashed()->first();

            /*We only update the paid to date ONCE per payment*/
            if (array_key_exists('invoices', $data) && is_array($data['invoices']) && count($data['invoices']) > 0) {
                if ($data['amount'] == '') {
                    $data['amount'] = array_sum(array_column($data['invoices'], 'amount'));
                }

                $client->service()->updatePaidToDate($data['amount'])->save();
            }
            // elseif($data['amount'] >0){
            else {
                //this fixes an edge case with unapplied payments
                $client->service()->updatePaidToDate($data['amount'])->save();
            }

            if (array_key_exists('credits', $data) && is_array($data['credits']) && count($data['credits']) > 0) {
                $_credit_totals = array_sum(array_column($data['credits'], 'amount'));

                $client->service()->updatePaidToDate($_credit_totals)->save();
            }
        }

        /*Fill the payment*/
        $payment->fill($data);
        $payment->is_manual = true;
        $payment->status_id = Payment::STATUS_COMPLETED;

        if (! $payment->currency_id && $client) {
            if (property_exists($client->settings, 'currency_id')) {
                $payment->currency_id = $client->settings->currency_id;
            } else {
                $payment->currency_id = $client->company->settings->currency_id;
            }
        }

        $payment->save();

        /*Save documents*/
        if (array_key_exists('documents', $data)) {
            $this->saveDocuments($data['documents'], $payment);
        }

        /*Ensure payment number generated*/
        if (! $payment->number || strlen($payment->number) == 0) {
            $payment->number = $payment->client->getNextPaymentNumber($payment->client, $payment);
        }

        /*Set local total variables*/
        $invoice_totals = 0;
        $credit_totals = 0;

        /*Iterate through invoices and apply payments*/
        if (array_key_exists('invoices', $data) && is_array($data['invoices']) && count($data['invoices']) > 0) {
            $invoice_totals = array_sum(array_column($data['invoices'], 'amount'));

            $invoices = Invoice::whereIn('id', array_column($data['invoices'], 'invoice_id'))->get();

            $payment->invoices()->saveMany($invoices);

            //todo optimize this into a single query
            foreach ($data['invoices'] as $paid_invoice) {
                $invoice = Invoice::withTrashed()->whereId($paid_invoice['invoice_id'])->first();

                if ($invoice) {
                    $invoice = $invoice->service()
                                       ->markSent()
                                       ->applyPayment($payment, $paid_invoice['amount'])
                                       ->save();
                }
            }
        } else {
            //payment is made, but not to any invoice, therefore we are applying the payment to the clients paid_to_date only
            //01-07-2020 i think we were duplicating the paid to date here.
            //$payment->client->service()->updatePaidToDate($payment->amount)->save();
        }

        if (array_key_exists('credits', $data) && is_array($data['credits'])) {
            $credit_totals = array_sum(array_column($data['credits'], 'amount'));

            $credits = Credit::whereIn('id', $this->transformKeys(array_column($data['credits'], 'credit_id')))->get();
            $payment->credits()->saveMany($credits);

            //todo optimize into a single query
            foreach ($data['credits'] as $paid_credit) {
                $credit = Credit::withTrashed()->find($this->decodePrimaryKey($paid_credit['credit_id']));

                if ($credit) {
                    $credit = $credit->service()->markSent()->save();
                    ApplyCreditPayment::dispatchNow($credit, $payment, $paid_credit['amount'], $credit->company);
                }
            }
        }

        if (! $is_existing_payment && ! $this->import_mode) {
            if (array_key_exists('email_receipt', $data) && $data['email_receipt'] == 'true') {
                $payment->service()->sendEmail();
            } elseif (! array_key_exists('email_receipt', $data) && $payment->client->getSetting('client_manual_payment_notification')) {
                $payment->service()->sendEmail();
            }

            event(new PaymentWasCreated($payment, $payment->company, Ninja::eventVars(auth()->user() ? auth()->user()->id : null)));
        }

        $payment->applied += ($invoice_totals - $credit_totals); //wont work because - check tests

        $payment->save();

        $transaction = [
            'invoice' => [],
            'payment' => $payment->transaction_event(),
            'client' => $payment->client->transaction_event(),
            'credit' => [],
            'metadata' => [],
        ];

        TransactionLog::dispatch(TransactionEvent::PAYMENT_MADE, $transaction, $payment->company->db);

        return $payment->refresh();
    }

    /**
     * If the client is paying in a currency other than
     * the company currency, we need to set a record.
     * @param $data
     * @param $payment
     * @return
     */
    public function processExchangeRates($data, $payment)
    {
        if (array_key_exists('exchange_rate', $data) && isset($data['exchange_rate']) && $data['exchange_rate'] != 1) {
            return $payment;
        }

        $client = Client::withTrashed()->find($data['client_id']);

        $client_currency = $client->getSetting('currency_id');
        $company_currency = $client->company->settings->currency_id;

        if ($company_currency != $client_currency) {
            $exchange_rate = new CurrencyApi();

            $payment->exchange_rate = $exchange_rate->exchangeRate($client_currency, $company_currency, Carbon::parse($payment->date));
            $payment->exchange_currency_id = $company_currency;
            $payment->currency_id = $client_currency;

            return $payment;
        }

        $payment->currency_id = $company_currency;

        return $payment;
    }

    public function delete($payment)
    {
        //cannot double delete a payment
        if ($payment->is_deleted) {
            return;
        }

        $payment = $payment->service()->deletePayment();

        event(new PaymentWasDeleted($payment, $payment->company, Ninja::eventVars(auth()->user() ? auth()->user()->id : null)));

        return $payment;
        //return parent::delete($payment);
    }

    public function restore($payment)
    {
        //we cannot restore a deleted payment.
        if ($payment->is_deleted) {
            return;
        }

        return parent::restore($payment);
    }
}
