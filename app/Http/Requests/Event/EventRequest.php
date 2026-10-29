<?php

namespace App\Http\Requests\Event;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;

class EventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
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
            'title' => 'required|string|max:191',
            'title_ne' => 'nullable|string|max:191',
            'category' => 'required|in:' . implode(',', array_keys(Event::CATEGORIES)),
            'start_date_bs' => 'required|regex:/^\d{4}-\d{2}-\d{2}$/',
            'end_date_bs' => 'nullable|regex:/^\d{4}-\d{2}-\d{2}$/',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'target_audience' => 'required|in:all,teachers,students,parents,classes,class_section',
            'my_class_id' => 'nullable|required_if:target_audience,classes,class_section|exists:my_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'location' => 'nullable|string|max:191',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
        ];
    }
}
