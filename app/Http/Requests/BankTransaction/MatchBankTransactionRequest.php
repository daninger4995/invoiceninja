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

namespace App\Http\Requests\BankTransaction;

use App\Http\Requests\Request;
use App\Models\BankTransaction;
use App\Models\Expense;
use App\Models\Payment;

class MatchBankTransactionRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() : bool
    {
        return auth()->user()->isAdmin();
    }

    public function rules()
    {

        $rules = [
            'transactions' => 'bail|array',
            'transactions.*.invoice_ids' => 'nullable|string|sometimes',
        ];

        $rules['transactions.*.ninja_category_id'] = 'bail|nullable|sometimes|exists:expense_categories,id,company_id,'.auth()->user()->company()->id.',is_deleted,0';
        $rules['transactions.*.vendor_id'] = 'bail|nullable|sometimes|exists:vendors,id,company_id,'.auth()->user()->company()->id.',is_deleted,0';
        $rules['transactions.*.id'] = 'bail|required|exists:bank_transactions,id,company_id,'.auth()->user()->company()->id.',is_deleted,0';
        $rules['transactions.*.payment_id'] = 'bail|sometimes|nullable|exists:payments,id,company_id,'.auth()->user()->company()->id.',is_deleted,0';
        $rules['transactions.*.expense_id'] = 'bail|sometimes|nullable|exists:expenses,id,company_id,'.auth()->user()->company()->id.',is_deleted,0';

        return $rules;

    }

    public function prepareForValidation()
    {
        $inputs = $this->all();
        
        foreach($inputs['transactions'] as $key => $input)
        {
        
            if(array_key_exists('id', $inputs['transactions'][$key]))
                $inputs['transactions'][$key]['id'] = $this->decodePrimaryKey($input['id']);

            if(array_key_exists('ninja_category_id', $inputs['transactions'][$key]) && strlen($inputs['transactions'][$key]['ninja_category_id']) >= 1)
                $inputs['transactions'][$key]['ninja_category_id'] = $this->decodePrimaryKey($inputs['transactions'][$key]['ninja_category_id']);

            if(array_key_exists('vendor_id', $inputs['transactions'][$key]) && strlen($inputs['transactions'][$key]['vendor_id']) >= 1)
                $inputs['transactions'][$key]['vendor_id'] = $this->decodePrimaryKey($inputs['transactions'][$key]['vendor_id']);

            if(array_key_exists('payment_id', $inputs['transactions'][$key]) && strlen($inputs['transactions'][$key]['payment_id']) >= 1){
                $inputs['transactions'][$key]['payment_id'] = $this->decodePrimaryKey($inputs['transactions'][$key]['payment_id']);
                $p = Payment::withTrashed()->find($inputs['transactions'][$key]['payment_id']);

                /*Ensure we don't relink an existing payment*/
                if(!$p || is_numeric($p->transaction_id)){
                    unset($inputs['transactions'][$key]);
                }

            }

            if(array_key_exists('expense_id', $inputs['transactions'][$key]) && strlen($inputs['transactions'][$key]['expense_id']) >= 1){
                $inputs['transactions'][$key]['expense_id'] = $this->decodePrimaryKey($inputs['transactions'][$key]['expense_id']);
                $e = Expense::withTrashed()->find($inputs['transactions'][$key]['expense_id']);

                /*Ensure we don't relink an existing expense*/
                if(!$e || is_numeric($e->transaction_id))
                    unset($inputs['transactions'][$key]['expense_id']);

            }

        }

        $this->replace($inputs);

    }
}
