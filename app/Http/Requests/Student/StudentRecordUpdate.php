<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use App\Helpers\Qs;

class StudentRecordUpdate extends FormRequest
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
            'gender' => 'required|string',
            'phone' => 'sometimes|nullable|string|min:6|max:20',
            'email' => 'sometimes|nullable|email|max:100|unique:users,id',
            'photo' => 'sometimes|nullable|image|mimes:jpeg,gif,png,jpg|max:2048',
            'address' => 'required|string|min:6|max:120',
            'bg_id' => 'sometimes|nullable',
            'my_class_id' => 'required',
            'section_id' => 'required',
            'state_id' => 'required_without:province_id|nullable',
            'lga_id' => 'required_without:district_id|nullable',
            'province_id' => 'required_without:state_id|nullable|integer|exists:nepal_provinces,id',
            'district_id' => 'required_without:lga_id|nullable|integer|exists:nepal_districts,id',
            'local_level_id' => 'sometimes|nullable|integer|exists:nepal_local_levels,id',
            'municipality' => 'sometimes|nullable|string|max:150',
            'ward_no' => 'sometimes|nullable|integer|min:1|max:35',
            'tole' => 'sometimes|nullable|string|max:150',
            'street' => 'sometimes|nullable|string|max:191',
            'nal_id' => 'required',
            'my_parent_id' => 'sometimes|nullable',
            'parent_relationship' => 'sometimes|nullable|string|in:Father,Mother,Guardian,Other,father,mother,guardian,other',
            'dorm_id' => 'sometimes|nullable',
        ];
    }

    public function attributes()
    {
        return  [
            'nal_id' => 'Nationality',
            'dorm_id' => 'Dormitory',
            'state_id' => 'State',
            'lga_id' => 'LGA',
            'province_id' => 'Province',
            'district_id' => 'District',
            'local_level_id' => 'Local Government',
            'ward_no' => 'Ward No.',
            'tole' => 'Tole / Village',
            'street' => 'Street / Landmark',
            'bg_id' => 'Blood Group',
            'my_parent_id' => 'Parent',
            'parent_relationship' => 'Parent Relationship',
            'my_class_id' => 'Class',
            'section_id' => 'Section',
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

            $hasNepal = $this->filled('province_id') || $this->filled('district_id') || $this->filled('local_level_id') || $this->filled('ward_no');
            $hasLegacy = $this->filled('state_id') || $this->filled('lga_id');
            if ($hasNepal && $hasLegacy) {
                $validator->errors()->add('province_id', __('Cannot combine legacy state/LGA fields with Nepal province/district fields.'));
                return;
            }

            if ($hasNepal) {
                $requireActive = true;
                $srHash = $this->route('student');
                $srId = $srHash ? (Qs::decodeHash($srHash) ?: (is_numeric($srHash) ? (int) $srHash : null)) : null;
                if ($srId) {
                    $existing = \App\Models\StudentRecord::find($srId);
                    if (
                        $existing &&
                        (string) $existing->province_id === (string) $this->province_id &&
                        (string) $existing->district_id === (string) $this->district_id &&
                        (string) $existing->local_level_id === (string) $this->local_level_id
                    ) {
                        $requireActive = false;
                    }
                }

                $geoError = app(\App\Services\NepalLocationService::class)->validateHierarchy(
                    $this->filled('province_id') ? (int) $this->province_id : null,
                    $this->filled('district_id') ? (int) $this->district_id : null,
                    $this->filled('local_level_id') ? (int) $this->local_level_id : null,
                    $this->filled('ward_no') ? (int) $this->ward_no : null,
                    $requireActive
                );
                if ($geoError !== null) {
                    $validator->errors()->add('district_id', $geoError);
                }
            }
        });
    }
}
