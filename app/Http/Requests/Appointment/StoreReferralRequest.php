<?php

namespace App\Http\Requests\Appointment;

use App\Models\Referral;
use Illuminate\Foundation\Http\FormRequest;

class StoreReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Referral::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Only used by staff; patients have their patient derived from login.
            'patient_id' => ['nullable', 'exists:patients,id'],
            'service_id' => ['nullable', 'exists:services,id'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
