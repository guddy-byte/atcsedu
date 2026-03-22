<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class GradeTheoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'grades' => ['required', 'array', 'min:1'],
            'grades.*.question_id' => ['required', 'integer', 'exists:questions,id'],
            'grades.*.awarded_points' => ['required', 'integer', 'min:0'],
            'grades.*.review_comment' => ['nullable', 'string'],
        ];
    }
}
