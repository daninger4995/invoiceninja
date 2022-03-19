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

namespace App\Services\Payment;

use App\Events\Invoice\InvoiceWasUpdated;
use App\Jobs\Invoice\InvoiceWorkflowSettings;
use App\Jobs\Ninja\TransactionLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentHash;
use App\Models\TransactionEvent;
use App\Utils\Ninja;
use App\Utils\Traits\MakesHash;

class UpdateInvoicePayment
{
    use MakesHash;

    public $payment;

    public $payment_hash;

    public function __construct(Payment $payment, PaymentHash $payment_hash)
    {
        $this->payment = $payment;
        $this->payment_hash = $payment_hash;
    }

    public function run()
    {
        $paid_invoices = $this->payment_hash->invoices();

        $invoices = Invoice::whereIn('id', $this->transformKeys(array_column($paid_invoices, 'invoice_id')))->withTrashed()->get();

        collect($paid_invoices)->each(function ($paid_invoice) use ($invoices) {

            $client = $this->payment->client->fresh();

            $invoice = $invoices->first(function ($inv) use ($paid_invoice) {
                return $paid_invoice->invoice_id == $inv->hashed_id;
            });

            if ($invoice->id == $this->payment_hash->fee_invoice_id) {
                $paid_amount = $paid_invoice->amount + $this->payment_hash->fee_total;
            } else {
                $paid_amount = $paid_invoice->amount;
            }

            $client
                ->service()
                ->updatePaidToDate($paid_amount)
                ->save();

            /* Need to determine here is we have an OVER payment - if YES only apply the max invoice amount */
            if($paid_amount > $invoice->partial && $paid_amount > $invoice->balance)
                $paid_amount = $invoice->balance;

            /*Improve performance here - 26-01-2022 - also change the order of events for invoice first*/
            $invoice->service() //caution what if we amount paid was less than partial - we wipe it!
                ->clearPartial()
                ->updateBalance($paid_amount * -1)
                ->updatePaidToDate($paid_amount)
                ->updateStatus()
                ->touchPdf()
                ->workFlow()
                ->save();

            /* Updates the company ledger */
            $this->payment
                 ->ledger()
                 ->updatePaymentBalance($paid_amount * -1);

            $client
                ->service()
                ->updateBalance($paid_amount * -1)
                ->save();

            $pivot_invoice = $this->payment->invoices->first(function ($inv) use ($paid_invoice) {
                return $inv->hashed_id == $paid_invoice->invoice_id;
            });

            /*update paymentable record*/
            $pivot_invoice->pivot->amount = $paid_amount;
            $pivot_invoice->pivot->save();

            $this->payment->applied += $paid_amount;

            $transaction = [
                'invoice' => $invoice->transaction_event(),
                'payment' => $this->payment->transaction_event(),
                'client' => $client->transaction_event(),
                'credit' => [],
                'metadata' => [],
            ];

            TransactionLog::dispatch(TransactionEvent::GATEWAY_PAYMENT_MADE, $transaction, $invoice->company->db);


        });
        
        /* Remove the event updater from within the loop to prevent race conditions */

        $this->payment->saveQuietly();

        $invoices->each(function ($invoice) {
            
            $invoice = $invoice->fresh();
            event(new InvoiceWasUpdated($invoice, $invoice->company, Ninja::eventVars(auth()->user() ? auth()->user()->id : null)));
        
        });

        return $this->payment->fresh();
    }
}
