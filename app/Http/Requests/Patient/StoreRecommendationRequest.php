<?php

namespace App\Http\Requests\Patient;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class StoreRecommendationRequest extends FormRequest
{
    /**
     * Per spec, recommending procedures is a Dentist action (Management allowed too).
     */
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
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
