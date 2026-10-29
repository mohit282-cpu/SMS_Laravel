<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class PaymentCreate extends FormRequest
{

    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'required|string|min:3',
            'amount' => 'required|numeric|min:0',
            'period_type' => 'sometimes|nullable|in:one_time,monthly',
            'fee_category' => 'sometimes|nullable|string|max:50',
            'bs_year' => 'sometimes|nullable|integer|between:2000,2090',
            'bs_month' => 'sometimes|nullable|integer|between:1,12',
            'due_date_bs' => 'sometimes|nullable|string|max:10',
        ];
    }

}
