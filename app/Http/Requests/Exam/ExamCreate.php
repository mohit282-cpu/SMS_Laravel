<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;

class ExamCreate extends FormRequest
{

    public function authorize()
    {
        return true;
    }


    public function rules()
    {
        return [
            'name' => 'required|string|max:100',
            'term' => 'required|numeric|min:1|max:10',
            'exam_type' => 'nullable|string|max:50',
            'start_date_bs' => 'nullable|string|max:10',
            'end_date_bs' => 'nullable|string|max:10',
            'status' => 'nullable|in:draft,scheduled,ongoing,completed,published',
            'description' => 'nullable|string|max:500',
        ];
    }

}
