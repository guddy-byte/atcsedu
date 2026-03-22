<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:objective,theory'],
            'prompt' => ['required', 'string'],
            'points' => ['nullable', 'integer', 'min:1'],
            'position' => ['nullable', 'integer', 'min:1'],
            'options' => ['nullable', 'array'],
            'options.*.label' => ['nullable', 'string', 'max:10'],
            'options.*.option_text' => ['required_with:options', 'string'],
            'options.*.is_correct' => ['nullable', 'boolean'],
            'options.*.position' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $type = $this->input('type');
                $options = $this->input('options', []);

                if ($type === 'objective') {
                    if (count($options) < 2) {
                        $validator->errors()->add('options', 'Objective questions need at least two options.');

                        return;
                    }

                    $correctCount = collect($options)->where('is_correct', true)->count();

                    if ($correctCount < 1) {
                        $validator->errors()->add('options', 'Objective questions need one correct option.');
                    }
                }
            },
        ];
    }
}
