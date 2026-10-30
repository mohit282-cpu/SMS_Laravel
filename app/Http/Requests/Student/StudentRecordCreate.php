<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use App\Helpers\Qs;

class StudentRecordCreate extends FormRequest
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
            'name' => 'required|string|min:6|max:150',
            'adm_no' => 'sometimes|nullable|alpha_num|min:3|max:150|unique:student_records',
            'gender' => 'required|string',
            'year_admitted' => 'required|string',
            'phone' => 'sometimes|nullable|string|min:6|max:20',
            'email' => 'sometimes|nullable|email|max:100|unique:users',
            'photo' => 'sometimes|nullable|image|mimes:jpeg,gif,png,jpg|max:2048',
            'address' => 'required|string|min:6|max:120',
            'bg_id' => 'sometimes|nullable',
            'state_id' => 'required',
            'lga_id' => 'required',
            'nal_id' => 'required',
            'my_class_id' => 'required',
            'section_id' => 'required',
            'my_parent_id' => 'sometimes|nullable',
            'dorm_id' => 'sometimes|nullable',
            'parent_name' => 'sometimes|nullable|string|min:3|max:100',
            'parent_phone' => 'sometimes|nullable|string|min:6|max:25',
            'parent_email' => 'sometimes|nullable|email|max:100',
            'parent_relationship' => 'sometimes|nullable|string|in:Father,Mother,Guardian,Other,father,mother,guardian,other',
            'parent_address' => 'sometimes|nullable|string|min:3|max:255',
        ];
    }

    public function attributes()
    {
        return  [
            'section_id' => 'Section',
            'nal_id' => 'Nationality',
            'my_class_id' => 'Class',
            'dorm_id' => 'Dormitory',
            'state_id' => 'State',
            'lga_id' => 'LGA',
            'bg_id' => 'Blood Group',
            'my_parent_id' => 'Parent',
            'parent_name' => 'Parent Full Name',
            'parent_phone' => 'Parent Mobile Number',
            'parent_email' => 'Parent Email',
            'parent_relationship' => 'Parent Relationship',
            'parent_address' => 'Parent Address',
        ];
    }

    protected function getValidatorInstance()
    {
        $input = $this->all();

        $input['my_parent_id'] = !empty($input['my_parent_id']) ? (Qs::decodeHash($input['my_parent_id']) ?: (is_numeric($input['my_parent_id']) ? (int)$input['my_parent_id'] : null)) : null;

        $this->getInputSource()->replace($input);

        return parent::getValidatorInstance();
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->filled('my_class_id') && $this->filled('section_id')) {
                $exists = \App\Models\Section::where('id', $this->section_id)
                    ->where('my_class_id', $this->my_class_id)
                    ->exists();
                if (!$exists) {
                    $validator->errors()->add('section_id', __('The selected section does not belong to the selected class.'));
                }
            }
        });
    }
}
