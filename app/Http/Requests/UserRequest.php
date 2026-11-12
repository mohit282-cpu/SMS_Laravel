<?php

namespace App\Http\Requests;

use App\Helpers\Qs;
use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
        $store =  [
            'name' => 'required|string|min:6|max:150',
            'password' => 'nullable|string|min:3|max:50',
            'user_type' => 'required',
            'gender' => 'required|string',
            'phone' => 'sometimes|nullable|string|min:6|max:20',
            'email' => 'sometimes|nullable|email|max:100|unique:users',
            'username' => 'sometimes|nullable|alpha_dash|min:8|max:100|unique:users',
            'photo' => 'sometimes|nullable|image|mimes:jpeg,gif,png,jpg|max:2048',
            'address' => 'required|string|min:6|max:120',
            'state_id' => 'required_without:province_id|nullable',
            'lga_id' => 'required_without:district_id|nullable',
            'province_id' => 'required_without:state_id|nullable|integer|exists:nepal_provinces,id',
            'district_id' => 'required_without:lga_id|nullable|integer|exists:nepal_districts,id',
            'local_level_id' => 'sometimes|nullable|integer|exists:nepal_local_levels,id',
            'ward_no' => 'sometimes|nullable|integer|min:1|max:35',
            'tole' => 'sometimes|nullable|string|max:150',
            'street' => 'sometimes|nullable|string|max:191',
            'nal_id' => 'required',
        ];
        $update =  [
            'name' => 'required|string|min:6|max:150',
            'gender' => 'required|string',
            'phone' => 'sometimes|nullable|string|min:6|max:20',
            'phone2' => 'sometimes|nullable|string|min:6|max:20',
            'email' => 'sometimes|nullable|email|max:100|unique:users,email,'.$this->user,
            'photo' => 'sometimes|nullable|image|mimes:jpeg,gif,png,jpg|max:2048',
            'address' => 'required|string|min:6|max:120',
            'state_id' => 'required_without:province_id|nullable',
            'lga_id' => 'required_without:district_id|nullable',
            'province_id' => 'required_without:state_id|nullable|integer|exists:nepal_provinces,id',
            'district_id' => 'required_without:lga_id|nullable|integer|exists:nepal_districts,id',
            'local_level_id' => 'sometimes|nullable|integer|exists:nepal_local_levels,id',
            'ward_no' => 'sometimes|nullable|integer|min:1|max:35',
            'tole' => 'sometimes|nullable|string|max:150',
            'street' => 'sometimes|nullable|string|max:191',
            'nal_id' => 'required',
        ];
        return ($this->method() === 'POST') ? $store : $update;
    }

    public function attributes()
    {
        return  [
            'nal_id' => 'Nationality',
            'state_id' => 'State',
            'lga_id' => 'LGA',
            'user_type' => 'User',
            'phone2' => 'Telephone',
        ];
    }

    protected function getValidatorInstance()
    {
        if($this->method() === 'POST'){
            $input = $this->all();

            $input['user_type'] = Qs::decodeHash($input['user_type']);

            $this->getInputSource()->replace($input);

        }

        if($this->method() === 'PUT'){
            $this->user = Qs::decodeHash($this->user);
        }

        return parent::getValidatorInstance();

    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->filled('province_id') || $this->filled('district_id') || $this->filled('local_level_id')) {
                $geoError = app(\App\Services\NepalLocationService::class)->validateHierarchy(
                    $this->filled('province_id') ? (int) $this->province_id : null,
                    $this->filled('district_id') ? (int) $this->district_id : null,
                    $this->filled('local_level_id') ? (int) $this->local_level_id : null,
                    $this->filled('ward_no') ? (int) $this->ward_no : null
                );
                if ($geoError !== null) {
                    $validator->errors()->add('district_id', $geoError);
                }
            }
        });
    }
}
