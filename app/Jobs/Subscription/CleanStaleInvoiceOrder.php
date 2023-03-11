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

namespace App\Jobs\Subscription;

use App\Libraries\MultiDB;
use App\Models\Invoice;
use App\Repositories\InvoiceRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CleanStaleInvoiceOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    /**
     * Create a new job instance.
     *
     */
    public function __construct()
    {
    }

    /**
     * @param InvoiceRepository $repo
     * @return void
     */
    public function handle(InvoiceRepository $repo) : void
    {
        if (! config('ninja.db.multi_db_enabled')) {
            Invoice::query()
                    ->withTrashed()
                    ->where('is_proforma', 1)
                    ->where('created_at', '<', now()->subHour())
                    ->cursor()
                    ->each(function ($invoice) use ($repo) {
                        $invoice->is_proforma = false;
                        $repo->delete($invoice);
                    });

            return;
        }


        foreach (MultiDB::$dbs as $db) {
            MultiDB::setDB($db);

            Invoice::query()
                    ->withTrashed()
                    ->where('is_proforma', 1)
                    ->where('created_at', '<', now()->subHour())
                    ->cursor()
                    ->each(function ($invoice) use ($repo) {
                        $invoice->is_proforma = false;
                        $repo->delete($invoice);
                    });
        }
    }

    public function failed($exception = null)
    {
    }
}
