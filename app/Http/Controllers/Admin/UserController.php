<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * List users, with optional search and role filtering.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->when($request->string('search')->trim()->value(), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->string('role')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => UserRole::options(),
            'filters' => $request->only('search', 'role'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.create', [
            'roles' => UserRole::options(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());

        // Admin-created accounts are vouched for, so mark them verified now.
        $user->markEmailAsVerified();

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        // For patients, load their full profile so the clinic can review (not edit) it.
        $patient = $user->role === UserRole::Patient
            ? $user->patient()->with(['allergies', 'appointments.payments'])->first()
            : null;

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => UserRole::options(),
            'patient' => $patient,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        // Patients: only the account status may be changed here.
        if ($user->role === UserRole::Patient) {
            $user->update(['is_active' => $data['is_active']]);

            return redirect()
                ->route('admin.users.index')
                ->with('status', 'Patient status updated.');
        }

        // Don't overwrite the password unless a new one was supplied.
        if (empty($data['password'])) {
            unset($data['password']);
        }

        // Guard: a manager must not strip their own admin role and lock the door.
        if ($user->id === $request->user()->id && $data['role'] !== UserRole::Management->value) {
            return back()
                ->withInput()
                ->withErrors(['role' => 'You cannot change your own role.']);
        }

        $user->update($data);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->delete(); // soft delete

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User removed successfully.');
    }
}
