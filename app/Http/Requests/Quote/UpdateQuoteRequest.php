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

namespace App\Http\Requests\Quote;

use App\Http\Requests\Request;
use App\Utils\Traits\ChecksEntityStatus;
use App\Utils\Traits\CleanLineItems;
use App\Utils\Traits\MakesHash;
use Illuminate\Validation\Rule;

class UpdateQuoteRequest extends Request
{
    use MakesHash;
    use CleanLineItems;
    use ChecksEntityStatus;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        return $user->can('edit', $this->quote);
    }

    public function rules()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $rules = [];

        if ($this->file('documents') && is_array($this->file('documents'))) {
            $rules['documents.*'] = $this->file_validation;
        } elseif ($this->file('documents')) {
            $rules['documents'] = $this->file_validation;
        }

        if ($this->file('file') && is_array($this->file('file'))) {
            $rules['file.*'] = $this->file_validation;
        } elseif ($this->file('file')) {
            $rules['file'] = $this->file_validation;
        }


        $rules['number'] = ['bail', 'sometimes', 'nullable', Rule::unique('quotes')->where('company_id', $user->company()->id)->ignore($this->quote->id)];

        $rules['client_id'] = ['bail', 'sometimes', Rule::in([$this->quote->client_id])];

        $rules['line_items'] = 'array';
        $rules['discount'] = 'sometimes|numeric';
        $rules['is_amount_discount'] = ['boolean'];
        $rules['exchange_rate'] = 'bail|sometimes|numeric';

        return $rules;
    }

    public function prepareForValidation()
    {
        $input = $this->all();

        $input = $this->decodePrimaryKeys($input);

        $input['id'] = $this->quote->id;

        if (isset($input['line_items'])) {
            $input['line_items'] = isset($input['line_items']) ? $this->cleanItems($input['line_items']) : [];
        }

        if (array_key_exists('documents', $input)) {
            unset($input['documents']);
        }

        if (array_key_exists('exchange_rate', $input) && is_null($input['exchange_rate'])) {
            $input['exchange_rate'] = 1;
        }


        $this->replace($input);
    }
}
