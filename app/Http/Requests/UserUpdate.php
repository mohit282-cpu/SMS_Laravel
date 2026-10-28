<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdate extends FormRequest
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
        $userId = $this->user() ? $this->user()->id : null;

        return [
            'name' => 'sometimes|nullable|string|min:3|max:150',
            'phone' => 'sometimes|nullable|string|min:6|max:20',
            'phone2' => 'sometimes|nullable|string|min:6|max:20',
            'email' => ['sometimes', 'nullable', 'email', 'max:100', Rule::unique('users', 'email')->ignore($userId)],
            'username' => ['sometimes', 'nullable', 'alpha_dash', 'min:3', 'max:100', Rule::unique('users', 'username')->ignore($userId)],
            'photo' => 'sometimes|nullable|image|mimes:jpeg,gif,png,jpg|max:2048',
            'address' => 'required|string|min:6|max:120',
            'locale' => 'sometimes|nullable|in:ne,en',
        ];
    }

    public function attributes()
    {
        return  [
            'nal_id' => 'Nationality',
            'state_id' => 'State',
            'lga_id' => 'LGA',
            'phone2' => 'Telephone',
        ];
    }
}
