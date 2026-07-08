<?php

namespace App\Http\Requests\Appointment;

use App\Models\Appointment;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClinicAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Appointment::class) ?? false;
    }

    /**
     * Front-desk booking. Either pick an existing patient (patient_id) OR tick
     * "walk-in" and supply a new patient's name.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'is_walk_in' => ['required', 'boolean'],
            'patient_id' => ['nullable', 'exists:patients,id'],
            'new_first_name' => ['nullable', 'string', 'max:255'],
            'new_last_name' => ['nullable', 'string', 'max:255'],
            'new_phone' => \App\Support\Phone::rules(),
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => [Rule::exists('services', 'id')->where('is_active', true)],
            'dentist_id' => ['required', Rule::exists('users', 'id')->where('role', 'dentist')],
            // Walk-ins also pick a real slot (today's next free one) to avoid conflicts.
            'scheduled_at' => ['required', 'date', 'after:-15 minutes',
                'before:'.now()->addMonths(\App\Services\PredictiveScheduler::MAX_MONTHS_AHEAD)->addDay()->toDateString()],
            'parent_appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_walk_in' => $this->boolean('is_walk_in')]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['new_phone.regex' => \App\Support\Phone::message()];
    }

    /** Require either an existing patient or a complete quick walk-in profile. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->filled('patient_id')) {
                    return;
                }

                foreach ([
                    'new_first_name' => 'First name is required for a walk-in patient.',
                    'new_last_name' => 'Last name is required for a walk-in patient.',
                    'new_phone' => 'Phone number is required for a walk-in patient.',
                ] as $field => $message) {
                    if (! $this->filled($field)) {
                        $validator->errors()->add($field, $message);
                    }
                }
            },
        ];
    }
}
