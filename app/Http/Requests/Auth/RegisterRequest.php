<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize input before validation (case-insensitive email/username uniqueness).
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => is_string($this->email) ? strtolower(trim($this->email)) : $this->email,
            'username' => is_string($this->username) ? trim($this->username) : $this->username,
        ]);
    }

    /**
     * Public sign-up — always creates a Patient (role is forced in the controller).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'alpha_dash', 'min:3', 'max:30', Rule::unique('users', 'username')->whereNull('deleted_at')],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->whereNull('deleted_at')],
            'mobile' => ['required', 'string', 'max:30'],
            'gender' => ['required', Rule::in(['Male', 'Female', 'Other', 'Prefer not to say'])],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'address' => ['required', 'string', 'max:500'],
            'password' => ['required', 'confirmed', Password::defaults()],
            // Optional "refer a friend" code — validated leniently; an unknown
            // code is simply ignored rather than blocking sign-up.
            'referral_code' => ['nullable', 'string', 'max:20'],
            // Data Privacy Act (RA 10173) consent — the checkbox must be ticked.
            'consent' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.alpha_dash' => 'The username may only contain letters, numbers, dashes and underscores.',
            'email.unique' => 'An account with this email already exists.',
            'username.unique' => 'This username is already taken.',
            'consent.accepted' => 'You must agree to the data privacy consent to create an account.',
        ];
    }
}
