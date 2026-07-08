<?php

namespace App\Http\Requests\Appointment;

use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Appointment::class) ?? false;
    }

    /**
     * Patient self-booking — patient is derived from the logged-in user.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // One appointment can include several procedures (services).
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => [Rule::exists('services', 'id')->where('is_active', true)],
            'dentist_id' => ['required', Rule::exists('users', 'id')->where('role', 'dentist')],
            'scheduled_at' => ['required', 'date', 'after:now',
                'before:'.now()->addMonths(\App\Services\PredictiveScheduler::MAX_MONTHS_AHEAD)->addDay()->toDateString()],
            'parent_appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
