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
     * Normalize input before validation (case-insensitive email/username uniqueness),
     * compose the birthday from its Month/Day/Year dropdowns, and the address from
     * its PH-style parts (street / barangay / city / province / ZIP).
     */
    protected function prepareForValidation(): void
    {
        $merge = [
            'email' => is_string($this->email) ? strtolower(trim($this->email)) : $this->email,
            'username' => is_string($this->username) ? trim($this->username) : $this->username,
        ];

        if ($this->filled(['dob_year', 'dob_month', 'dob_day'])) {
            $y = (int) $this->dob_year;
            $m = (int) $this->dob_month;
            $d = (int) $this->dob_day;
            // Only compose when it's a REAL calendar date (rejects e.g. Feb 30).
            $merge['date_of_birth'] = checkdate($m, $d, $y)
                ? sprintf('%04d-%02d-%02d', $y, $m, $d)
                : null;
        }

        $parts = array_filter([
            trim((string) $this->address_street),
            $this->filled('address_barangay') ? 'Brgy. '.trim((string) $this->address_barangay) : null,
            trim((string) $this->address_city),
            trim((string) $this->address_province),
            trim((string) $this->address_zip),
        ]);
        if ($parts !== []) {
            $merge['address'] = implode(', ', $parts);
        }

        $this->merge($merge);
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
            'mobile' => \App\Support\Phone::rules(required: true),
            'gender' => ['required', Rule::in(['Male', 'Female', 'Other', 'Prefer not to say'])],
            // Composed from the Month/Day/Year dropdowns. No future dates, and patients
            // must be at least 9 years old to hold their own portal account.
            'dob_month' => ['required', 'integer', 'between:1,12'],
            'dob_day' => ['required', 'integer', 'between:1,31'],
            'dob_year' => ['required', 'integer', 'between:'.(now()->year - 120).','.now()->year],
            'date_of_birth' => ['required', 'date', 'before:today', 'before_or_equal:'.now()->subYears(9)->toDateString()],
            // PH-style address parts (composed into one address string).
            'address_street' => ['required', 'string', 'max:255'],
            'address_barangay' => ['required', 'string', 'max:120'],
            'address_city' => ['required', 'string', 'max:120'],
            'address_province' => ['required', 'string', 'max:120'],
            'address_zip' => ['nullable', 'string', 'max:10'],
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
            'mobile.regex' => \App\Support\Phone::message(),
            'date_of_birth.required' => 'Please pick a valid birthday (that day doesn\'t exist in that month).',
            'date_of_birth.before' => 'Your birthday can\'t be in the future.',
            'date_of_birth.before_or_equal' => 'You must be at least 9 years old to create an account. A parent or guardian can book for younger children at the clinic.',
            'address_street.required' => 'Please enter your house/unit number and street.',
            'address_barangay.required' => 'Please enter your barangay.',
            'address_city.required' => 'Please enter your city or municipality.',
            'address_province.required' => 'Please enter your province.',
            'email.unique' => 'An account with this email already exists.',
            'username.unique' => 'This username is already taken.',
            'consent.accepted' => 'You must agree to the data privacy consent to create an account.',
        ];
    }
}
