<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Domain\Accountability\SupervisionProcessSurvey;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitSupervisorEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = ['komentar' => ['nullable', 'string', 'max:2000']];

        foreach (array_keys(SupervisionProcessSurvey::dimensions()) as $key) {
            $rules["jawaban.{$key}"] = ['required', 'integer', Rule::in(range(SupervisionProcessSurvey::MIN, SupervisionProcessSurvey::MAX))];
        }

        return $rules;
    }

    /**
     * @return array<string, int>
     */
    public function answers(): array
    {
        /** @var array<string, int> $jawaban */
        $jawaban = (array) $this->validated('jawaban');

        return $jawaban;
    }
}
