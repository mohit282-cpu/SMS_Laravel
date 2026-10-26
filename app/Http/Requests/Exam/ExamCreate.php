<?php

namespace App\Http\Requests\Exam;

use App\Rules\ValidBikramSambatDate;
use App\Services\BikramSambatService;
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
            'start_date_bs' => ['nullable', 'string', new ValidBikramSambatDate()],
            'end_date_bs' => ['nullable', 'string', new ValidBikramSambatDate()],
            'status' => 'nullable|in:draft,scheduled,ongoing,completed,published',
            'description' => 'nullable|string|max:500',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $startBs = $this->input('start_date_bs');
            $endBs = $this->input('end_date_bs');

            if ($startBs && $endBs && $validator->errors()->isEmpty()) {
                $bsService = app(BikramSambatService::class);
                try {
                    $startAd = $bsService->bsToAd($startBs)['ad_date'];
                    $endAd = $bsService->bsToAd($endBs)['ad_date'];
                    if ($endAd < $startAd) {
                        $validator->errors()->add('end_date_bs', __('Examination end date cannot be earlier than start date.'));
                    }
                } catch (\Throwable $e) {
                    $validator->errors()->add('start_date_bs', __('Invalid Bikram Sambat date range.'));
                }
            }
        });
    }
}
