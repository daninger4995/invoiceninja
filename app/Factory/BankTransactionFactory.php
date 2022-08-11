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

namespace App\Factory;

use App\Models\BankTransaction;
use Illuminate\Support\Str;

class BankTransactionFactory
{
    public static function create(int $company_id, int $user_id, int $account_id) :BankTransaction
    {
        $bank_transaction = new BankTransaction;
        $bank_transaction->account_id = $account_id;
        $bank_transaction->user_id = $user_id;
        $bank_transaction->company_id = $company_id;

        $bank_transaction->amount = 0;
        $bank_transaction->currency_code = '';
        $bank_transaction->account_type = '';
        $bank_transaction->category_type = '';
        $bank_transaction->date = now()->format('Y-m-d');
        $bank_transaction->description = '';
        $bank_transaction->is_matched = 0;
        
        return $bank_transaction;
    }
}
