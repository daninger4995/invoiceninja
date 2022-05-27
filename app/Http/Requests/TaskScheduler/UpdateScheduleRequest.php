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


namespace App\Http\Requests\TaskScheduler;

use App\Http\Requests\Request;
use App\Models\ScheduledJob;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class UpdateScheduleRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'paused' => 'sometimes|bool',
            'repeat_every' => 'sometimes|string|in:DAY,WEEK,BIWEEKLY,MONTH,3MONTHS,YEAR',
            'start_from' => 'sometimes',
            'scheduled_run'=>'sometimes'
        ];
    }

    public function prepareForValidation()
    {
        $request = $this->all();

        if (isset($request['start_from'])) {
            $request['scheduled_run'] = Carbon::parse((int)$request['start_from']);
            $request['start_from'] = Carbon::parse((int)$request['start_from']);
        }

        $this->replace($request);
    }
}