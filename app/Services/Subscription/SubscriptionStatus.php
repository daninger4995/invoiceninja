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

namespace App\Services\Subscription;

use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Support\Carbon;
use App\Models\RecurringInvoice;
use App\Services\AbstractService;

class SubscriptionStatus extends AbstractService
{
    public function __construct(public Subscription $subscription, protected RecurringInvoice $recurring_invoice) {}
    
    /** @var bool $is_trial */
    public bool $is_trial = false;
    
    /** @var bool $is_refundable */
    public bool $is_refundable = false;
    
    /** @var bool $is_in_good_standing */
    public bool $is_in_good_standing = false;

    public function run(): self
    {
        $this->checkTrial()
            ->checkRefundable()
            ->checkInGoodStanding();

        return $this;
    }
    
    public function getProRataRatio():float
    {
        //calculate how much used.

        $subscription_interval_end_date = Carbon::parse($this->recurring_invoice->next_send_date_client);
        $subscription_interval_start_date = $subscription_interval_end_date->copy()->subDays($this->recurring_invoice->subscription->service()->getDaysInFrequency())->subDay();

        $primary_invoice =Invoice::query()
                                ->where('company_id', $this->recurring_invoice->company_id)
                                ->where('client_id', $this->recurring_invoice->client_id)
                                ->where('recurring_id', $this->recurring_invoice->id)
                                ->whereIn('status_id', [Invoice::STATUS_PAID])
                                ->whereBetween('date', [$subscription_interval_start_date, $subscription_interval_end_date])
                                ->where('is_deleted', 0)
                                ->where('is_proforma', 0)
                                ->orderBy('id', 'desc')
                                ->first();

        if(!$primary_invoice)
            return 0;
        
        $subscription_start_date = Carbon::parse($primary_invoice->date)->startOfDay();

        $days_of_subscription_used = $subscription_start_date->copy()->diffInDays(now());

        return $days_of_subscription_used / $this->recurring_invoice->subscription->service()->getDaysInFrequency();

    }

    /**
     * checkInGoodStanding
     *
     * @return self
     */
    private function checkInGoodStanding(): self
    {

        $this->is_in_good_standing = Invoice::query()
                                     ->where('company_id', $this->recurring_invoice->company_id)
                                     ->where('client_id', $this->recurring_invoice->client_id)
                                     ->where('recurring_id', $this->recurring_invoice->id)
                                     ->where('is_deleted', 0)
                                     ->where('is_proforma', 0)
                                     ->whereIn('status_id', [Invoice::STATUS_SENT, Invoice::STATUS_PARTIAL])
                                     ->where('balance', '>', 0)
                                     ->doesntExist();

        return $this;

    }
    
    /**
     * checkTrial
     *
     * @return self
     */
    private function checkTrial(): self
    {

        if(!$this->subscription->trial_enabled)
            return $this->setIsTrial(false);

        $primary_invoice = $this->recurring_invoice
                            ->invoices()
                            ->where('is_deleted', 0)
                            ->where('is_proforma', 0)
                            ->orderBy('id', 'asc')
                            ->first();

        if($primary_invoice && Carbon::parse($primary_invoice->date)->addSeconds($this->subscription->trial_duration)->lte(now()->startOfDay()->addSeconds($primary_invoice->client->timezone_offset()))) {
                return $this->setIsTrial(true);
        }

        $this->setIsTrial(false);

        return $this;

    }

    /**
     * Determines if this subscription
     * is eligible for a refund.
     *
     * @return self
     */
    private function checkRefundable(): self
    {
        if(!$this->recurring_invoice->subscription->refund_period || $this->recurring_invoice->subscription->refund_period === 0)
            return $this->setRefundable(false);
    
        $primary_invoice = $this->recurring_invoice
                                ->invoices()
                                ->where('is_deleted', 0)
                                ->where('is_proforma', 0)
                                ->orderBy('id', 'desc')
                                ->first();

        if($primary_invoice &&
        $primary_invoice->status_id == Invoice::STATUS_PAID &&
        Carbon::parse($primary_invoice->date)->addSeconds($this->recurring_invoice->subscription->refund_period)->lte(now()->startOfDay()->addSeconds($primary_invoice->client->timezone_offset()))
        ){
            return $this->setRefundable(true);
        }

        return $this->setRefundable(false);

    }

    /**
     * setRefundable
     *
     * @param  bool $refundable
     * @return self
     */
    private function setRefundable(bool $refundable): self
    {
        $this->is_refundable = $refundable;

        return $this;
    }

    /**
     * Sets the is_trial flag
     *
     * @param  bool $is_trial
     * @return self
     */
    private function setIsTrial(bool $is_trial): self
    {
        $this->is_trial = $is_trial;

        return $this;
    }

}
