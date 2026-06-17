<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Controllers\Portal\RecordController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Everyone's own profile page (avatar, details, password).
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('profile.edit', [
            'user' => $user,
            'patient' => $user->role === UserRole::Patient ? RecordController::resolvePatient($user) : null,
        ]);
    }

    /**
     * Update profile details + avatar.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $isPatient = $user->role === UserRole::Patient;

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)->whereNull('deleted_at')],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];

        if ($isPatient) {
            $rules += [
                'mobile' => ['required', 'string', 'max:30'],
                'gender' => ['required', Rule::in(['Male', 'Female', 'Other', 'Prefer not to say'])],
                'date_of_birth' => ['required', 'date', 'before:today'],
                'address' => ['required', 'string', 'max:500'],
            ];
        }

        $data = $request->validate($rules);

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $user->avatar_path = $request->file('avatar')->store('avatars', 'public');
        }

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->save();

        if ($isPatient) {
            $user->patient()->update([
                'first_name' => Str::before($data['name'], ' ') ?: $data['name'],
                'last_name' => Str::contains($data['name'], ' ') ? Str::after($data['name'], ' ') : '',
                'phone' => $data['mobile'],
                'gender' => $data['gender'],
                'date_of_birth' => $data['date_of_birth'],
                'address' => $data['address'],
            ]);
        }

        return back()->with('status', 'Profile updated.');
    }

    /**
     * Change own password (requires the current one).
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update(['password' => $request->password]);

        return back()->with('status', 'Password updated.');
    }
}
