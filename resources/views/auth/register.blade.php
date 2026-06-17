@extends('layouts.app')

@section('title', "Create your account — Bonoan's Dental Clinic")

@section('content')
    <div class="flex items-center justify-center px-6 py-16">
        <div class="w-full max-w-lg">
            <div class="rounded-3xl bg-white p-8 md:p-10 shadow-brand border border-slate-200/60">
                <div class="text-center">
                    <h1 class="font-display text-3xl font-bold">Create your account</h1>
                    <p class="mt-2 text-sm text-slate-500">Sign up to book appointments and view your records.</p>
                </div>

                <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-4" novalidate>
                    @csrf

                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Full name</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                            class="w-full h-11 px-4 rounded-xl border @error('name') border-red-400 @else border-slate-200 @enderror focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 outline-none transition" />
                        @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label for="username" class="block text-sm font-medium text-slate-700 mb-1">Username</label>
                            <input id="username" type="text" name="username" value="{{ old('username') }}" required autocomplete="username"
                                class="w-full h-11 px-4 rounded-xl border @error('username') border-red-400 @else border-slate-200 @enderror focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 outline-none transition" />
                            @error('username') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                                class="w-full h-11 px-4 rounded-xl border @error('email') border-red-400 @else border-slate-200 @enderror focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 outline-none transition" />
                            @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label for="mobile" class="block text-sm font-medium text-slate-700 mb-1">Mobile number</label>
                            <input id="mobile" type="text" name="mobile" value="{{ old('mobile') }}" required autocomplete="tel" placeholder="0917-000-0000"
                                class="w-full h-11 px-4 rounded-xl border @error('mobile') border-red-400 @else border-slate-200 @enderror focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 outline-none transition" />
                            @error('mobile') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="gender" class="block text-sm font-medium text-slate-700 mb-1">Gender</label>
                            <select id="gender" name="gender" required
                                class="w-full h-11 px-4 rounded-xl border @error('gender') border-red-400 @else border-slate-200 @enderror focus:border-brand-blue outline-none transition">
                                <option value="">Select…</option>
                                @foreach (['Male', 'Female', 'Other', 'Prefer not to say'] as $g)
                                    <option value="{{ $g }}" @selected(old('gender') === $g)>{{ $g }}</option>
                                @endforeach
                            </select>
                            @error('gender') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="date_of_birth" class="block text-sm font-medium text-slate-700 mb-1">Date of birth</label>
                        <input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" required max="{{ now()->toDateString() }}"
                            class="w-full h-11 px-4 rounded-xl border @error('date_of_birth') border-red-400 @else border-slate-200 @enderror focus:border-brand-blue outline-none transition" />
                        @error('date_of_birth') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="address" class="block text-sm font-medium text-slate-700 mb-1">Address</label>
                        <textarea id="address" name="address" rows="2" required
                            class="w-full px-4 py-2 rounded-xl border @error('address') border-red-400 @else border-slate-200 @enderror focus:border-brand-blue outline-none transition">{{ old('address') }}</textarea>
                        @error('address') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                            <input id="password" type="password" name="password" required autocomplete="new-password"
                                class="w-full h-11 px-4 rounded-xl border @error('password') border-red-400 @else border-slate-200 @enderror focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 outline-none transition" />
                            @error('password') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">Confirm password</label>
                            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                                class="w-full h-11 px-4 rounded-xl border border-slate-200 focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 outline-none transition" />
                        </div>
                    </div>
                    @include('partials.password-strength')

                    {{-- Data Privacy Act consent --}}
                    <div class="pt-2">
                        <label class="flex items-start gap-2.5 text-sm text-slate-600">
                            <input id="consent" type="checkbox" name="consent" value="1" @checked(old('consent')) class="mt-0.5 rounded border-slate-300 text-brand-blue focus:ring-brand-blue/30" />
                            <span>
                                I agree to the clinic collecting and processing my personal information, and I have read the
                                <a href="#" id="open-consent" class="font-semibold text-gradient-brand">Data Privacy Consent</a>.
                            </span>
                        </label>
                        @error('consent') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <button id="registerBtn" type="submit" disabled
                        class="w-full h-12 rounded-xl gradient-brand text-white font-semibold shadow-brand hover:opacity-90 transition disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:opacity-50">
                        Create account
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-slate-500">
                    Already have an account?
                    <a href="{{ route('login') }}" class="font-semibold text-gradient-brand">Log in</a>
                </p>
            </div>
        </div>
    </div>

    {{-- Consent modal --}}
    <div id="consent-modal" class="fixed inset-0 z-[110] hidden items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl shadow-brand w-full max-w-lg max-h-[85vh] flex flex-col">
            <div class="p-6 border-b border-slate-100">
                <h3 class="font-display text-xl font-bold">Data Privacy Consent</h3>
                <p class="text-xs text-slate-400 mt-1">In compliance with the Data Privacy Act of 2012 (Republic Act No. 10173)</p>
            </div>
            <div class="p-6 overflow-y-auto text-sm text-slate-600 space-y-3">
                <p>By creating an account with <strong>Bonoan's Dental Clinic</strong>, you consent to our collection,
                   use, storage, and processing of your personal and sensitive personal information (such as your name,
                   contact details, date of birth, gender, address, and dental/medical history).</p>
                <p>Your information will be used <strong>solely within the scope of the clinic's business and dental
                   procedures</strong> — including appointment scheduling, treatment records, billing, referrals, and
                   communicating with you about your care. We will not sell or share your data with third parties for
                   unrelated purposes.</p>
                <p>Pursuant to the <strong>Data Privacy Act of 2012 (RA 10173)</strong> and its Implementing Rules and
                   Regulations, you have the right to be informed, to access, to object, to correct, and to request the
                   erasure or blocking of your personal data, as well as the right to data portability and to lodge a
                   complaint with the National Privacy Commission.</p>
                <p>We implement reasonable organizational, physical, and technical security measures to protect your
                   information against unauthorized access, loss, or misuse. Records are retained only for as long as
                   necessary to fulfil the purposes stated above or as required by law.</p>
                <p>By ticking the consent box, you confirm that you have read and understood this notice and freely give
                   your consent.</p>
            </div>
            <div class="p-4 border-t border-slate-100 flex justify-end">
                <button type="button" id="close-consent" class="h-10 px-5 rounded-lg gradient-brand text-white text-sm font-semibold hover:opacity-90 transition">I understand</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        var cb = document.getElementById('consent');
        var btn = document.getElementById('registerBtn');
        function sync() { btn.disabled = !cb.checked; }
        cb.addEventListener('change', sync);
        sync();

        var modal = document.getElementById('consent-modal');
        function open(e) { if (e) e.preventDefault(); modal.classList.remove('hidden'); modal.classList.add('flex'); }
        function close() { modal.classList.add('hidden'); modal.classList.remove('flex'); }
        document.getElementById('open-consent').addEventListener('click', open);
        document.getElementById('close-consent').addEventListener('click', close);
        modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
    })();
</script>
@endpush
