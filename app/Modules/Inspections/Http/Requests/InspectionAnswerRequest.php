<?php

namespace App\Modules\Inspections\Http\Requests;

use App\Modules\Inspections\Enums\AnswerResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InspectionAnswerRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'checklist_item_id' => ['required', 'integer'],
            'unit_id' => ['nullable', 'integer'],
            'result' => ['required', Rule::enum(AnswerResult::class)],
            'comment' => ['nullable', 'string', 'max:1000'],
            'photos' => ['array', 'max:6'],
            'photos.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['result' => __('result'), 'comment' => __('comment'), 'photos.*' => __('photo')];
    }
}
