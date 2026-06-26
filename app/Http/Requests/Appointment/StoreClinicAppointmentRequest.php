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
            // Walk-ins are recorded at the current time, so a slot is only required for scheduled bookings.
            'scheduled_at' => [Rule::requiredIf(fn () => ! $this->boolean('is_walk_in')), 'nullable', 'date'],
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

    /**
     * Require either an existing patient or a new walk-in name.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $this->filled('patient_id') && ! $this->filled('new_first_name')) {
                    $validator->errors()->add('patient_id', 'Select an existing patient or enter a walk-in name.');
                }
            },
        ];
    }
}
