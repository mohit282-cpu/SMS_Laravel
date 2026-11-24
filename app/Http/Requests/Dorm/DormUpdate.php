<?php

namespace App\Http\Requests\Dorm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DormUpdate extends FormRequest
{

    public function authorize()
    {
        return true;
    }


    public function rules()
    {
        $dormId = $this->dorm ?: $this->route('dorm');
        return [
            'name' => ['required', 'string', Rule::unique('dorms', 'name')->ignore($dormId)],
        ];
    }

}
