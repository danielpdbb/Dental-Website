<?php

namespace App\Http\Requests\Patient;

use App\Enums\RecommendationStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRecommendationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, [UserRole::Dentist, UserRole::Management], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'service_id' => ['nullable', 'exists:services,id'],
            'recommendation' => ['required', 'string', 'max:1000'],
            'status' => ['required', Rule::enum(RecommendationStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
