@extends(auth()->user()->isStaff() ? 'layouts.admin' : 'layouts.app')

@section('title', "My profile — Bonoan's Dental Clinic")
@section('heading', 'My profile')

@section('content')
    <div class="max-w-2xl mx-auto px-6 py-10 md:py-6">
        @unless (auth()->user()->isStaff())
            <h1 class="font-display text-3xl font-bold mb-6">My profile</h1>
        @endunless

        @if ($errors->any() && ! auth()->user()->isStaff())
            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 text-red-600 px-4 py-3 text-sm">
                Please fix the highlighted fields below.
            </div>
        @endif

        {{-- Details + avatar --}}
        <div class="rounded-2xl bg-white border border-slate-200/60 p-6 md:p-8 shadow-soft">
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf
                @method('PATCH')

                <div class="flex items-center gap-4">
                    <span id="avatarPreviewWrap">@include('partials.avatar', ['user' => $user, 'size' => 'h-20 w-20 text-2xl'])</span>
                    <div>
                        <label for="avatar" class="block text-sm font-medium text-slate-700 mb-1">Profile photo</label>
                        <input id="avatar" type="file" name="avatar" accept="image/*"
                            class="block text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200" />
                        <p class="text-xs text-slate-400 mt-1">JPG, PNG or WEBP, up to 2&nbsp;MB.</p>
                        @error('avatar') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Full name</label>
                        <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required
                            class="w-full h-11 px-4 rounded-xl border @error('name') border-red-400 @else border-slate-200 @enderror focus:border-brand-blue outline-none transition" />
                        @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required
                            class="w-full h-11 px-4 rounded-xl border @error('email') border-red-400 @else border-slate-200 @enderror focus:border-brand-blue outline-none transition" />
                        @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <span class="block text-sm font-medium text-slate-700 mb-1">Username</span>
                    <div class="h-11 px-4 rounded-xl border border-slate-200 bg-slate-50 text-slate-500 flex items-center">{{ $user->username }}
                        <span class="ml-2 text-xs text-slate-400">(cannot be changed)</span>
                    </div>
                </div>

                @if ($patient)
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label for="mobile" class="block text-sm font-medium text-slate-700 mb-1">Mobile number</label>
                            <input id="mobile" type="text" name="mobile" value="{{ old('mobile', $patient->phone) }}" required
                                class="w-full h-11 px-4 rounded-xl border @error('mobile') border-red-400 @else border-slate-200 @enderror focus:border-brand-blue outline-none transition" />
                            @error('mobile') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="gender" class="block text-sm font-medium text-slate-700 mb-1">Gender</label>
                            <select id="gender" name="gender" required
                                class="w-full h-11 px-4 rounded-xl border @error('gender') border-red-400 @else border-slate-200 @enderror focus:border-brand-blue outline-none transition">
                                @foreach (['Male', 'Female', 'Other', 'Prefer not to say'] as $g)
                                    <option value="{{ $g }}" @selected(old('gender', $patient->gender) === $g)>{{ $g }}</option>
                                @endforeach
                            </select>
                            @error('gender') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="date_of_birth" class="block text-sm font-medium text-slate-700 mb-1">Date of birth</label>
                            <input id="date_of_birth" type="date" name="date_of_birth" max="{{ now()->toDateString() }}"
                                value="{{ old('date_of_birth', $patient->date_of_birth?->format('Y-m-d')) }}" required
                                class="w-full h-11 px-4 rounded-xl border @error('date_of_birth') border-red-400 @else border-slate-200 @enderror focus:border-brand-blue outline-none transition" />
                            @error('date_of_birth') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="address" class="block text-sm font-medium text-slate-700 mb-1">Address</label>
                            <input id="address" type="text" name="address" value="{{ old('address', $patient->address) }}" required
                                class="w-full h-11 px-4 rounded-xl border @error('address') border-red-400 @else border-slate-200 @enderror focus:border-brand-blue outline-none transition" />
                            @error('address') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @endif

                <button class="h-11 px-6 rounded-xl gradient-brand text-white font-semibold shadow-brand hover:opacity-90 transition">Save profile</button>
            </form>
        </div>

        {{-- Change password --}}
        <div class="mt-6 rounded-2xl bg-white border border-slate-200/60 p-6 md:p-8 shadow-soft">
            <h2 class="font-display text-lg font-bold">Change password</h2>
            <form method="POST" action="{{ route('profile.password') }}" class="mt-4 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label for="current_password" class="block text-sm font-medium text-slate-700 mb-1">Current password</label>
                    <input id="current_password" type="password" name="current_password" autocomplete="current-password"
                        class="w-full h-11 px-4 rounded-xl border @error('current_password') border-red-400 @else border-slate-200 @enderror focus:border-brand-blue outline-none transition" />
                    @error('current_password') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700 mb-1">New password</label>
                        <input id="password" type="password" name="password" autocomplete="new-password"
                            class="w-full h-11 px-4 rounded-xl border @error('password') border-red-400 @else border-slate-200 @enderror focus:border-brand-blue outline-none transition" />
                        @error('password') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">Confirm new password</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password"
                            class="w-full h-11 px-4 rounded-xl border border-slate-200 focus:border-brand-blue outline-none transition" />
                    </div>
                </div>
                @include('partials.password-strength')
                <button class="h-11 px-6 rounded-xl border border-slate-200 font-semibold text-slate-700 hover:bg-slate-50 transition">Update password</button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        var input = document.getElementById('avatar');
        var wrap = document.getElementById('avatarPreviewWrap');
        if (!input) return;
        input.addEventListener('change', function () {
            if (input.files && input.files[0]) {
                var url = URL.createObjectURL(input.files[0]);
                wrap.innerHTML = '<img src="' + url + '" class="h-20 w-20 rounded-full object-cover border border-slate-200" alt="Preview" />';
            }
        });
    })();
</script>
@endpush
