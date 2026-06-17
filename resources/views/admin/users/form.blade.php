{{-- Shared create/edit form. Expects: $user (nullable), $roles, $isEdit (bool). --}}
@php($isEdit = $isEdit ?? false)

@if ($errors->any())
    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 text-red-600 px-4 py-3 text-sm">
        Please fix the highlighted fields below.
    </div>
@endif

<div class="grid sm:grid-cols-2 gap-5">
    <div>
        <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Full name</label>
        <input id="name" type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required
            class="w-full h-11 px-4 rounded-xl border @error('name') border-red-400 @else border-slate-200 @enderror focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 outline-none transition" />
        @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="username" class="block text-sm font-medium text-slate-700 mb-1">Username</label>
        <input id="username" type="text" name="username" value="{{ old('username', $user->username ?? '') }}" required
            class="w-full h-11 px-4 rounded-xl border @error('username') border-red-400 @else border-slate-200 @enderror focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 outline-none transition" />
        @error('username') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required
            class="w-full h-11 px-4 rounded-xl border @error('email') border-red-400 @else border-slate-200 @enderror focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 outline-none transition" />
        @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="role" class="block text-sm font-medium text-slate-700 mb-1">Role</label>
        <select id="role" name="role" required
            class="w-full h-11 px-3 rounded-xl border @error('role') border-red-400 @else border-slate-200 @enderror focus:border-brand-blue outline-none transition">
            @foreach ($roles as $value => $label)
                <option value="{{ $value }}" @selected(old('role', $user->role->value ?? 'patient') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('role') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="is_active" class="block text-sm font-medium text-slate-700 mb-1">Status</label>
        <select id="is_active" name="is_active" required
            class="w-full h-11 px-3 rounded-xl border border-slate-200 focus:border-brand-blue outline-none transition">
            <option value="1" @selected((string) old('is_active', (int) ($user->is_active ?? 1)) === '1')>Active</option>
            <option value="0" @selected((string) old('is_active', (int) ($user->is_active ?? 1)) === '0')>Suspended</option>
        </select>
        @error('is_active') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>
</div>

<div class="grid sm:grid-cols-2 gap-5 mt-5">
    <div>
        <label for="password" class="block text-sm font-medium text-slate-700 mb-1">
            Password @if ($isEdit) <span class="text-slate-400 font-normal">(leave blank to keep current)</span> @endif
        </label>
        <input id="password" type="password" name="password" autocomplete="new-password" @unless ($isEdit) required @endunless
            class="w-full h-11 px-4 rounded-xl border @error('password') border-red-400 @else border-slate-200 @enderror focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 outline-none transition" />
        @error('password') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        @include('partials.password-strength')
    </div>

    <div>
        <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">Confirm password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password"
            class="w-full h-11 px-4 rounded-xl border border-slate-200 focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 outline-none transition" />
    </div>
</div>

<div class="mt-7 flex items-center gap-3">
    <button type="submit" class="h-11 px-6 rounded-xl gradient-brand text-white font-semibold shadow-brand hover:opacity-90 transition">
        {{ $isEdit ? 'Save changes' : 'Create user' }}
    </button>
    <a href="{{ route('admin.users.index') }}" class="h-11 px-6 inline-flex items-center rounded-xl border border-slate-200 font-medium text-slate-600 hover:bg-slate-50 transition">Cancel</a>
</div>
