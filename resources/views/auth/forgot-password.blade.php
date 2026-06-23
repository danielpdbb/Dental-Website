@extends('layouts.app')

@section('title', "Forgot password — Bonoan's Dental Clinic")

@section('content')
    <div class="flex items-center justify-center px-6 py-16">
        <div class="w-full max-w-md">
            <div class="rounded-3xl bg-white p-8 md:p-10 shadow-brand border border-slate-200/60">
                <div class="text-center">
                    <h1 class="font-display text-3xl font-bold">Forgot your password?</h1>
                    <p class="mt-2 text-sm text-slate-500">No problem — enter your email and we'll send you a reset link.</p>
                </div>

                <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                            class="w-full h-11 px-4 rounded-xl border @error('email') border-red-400 @else border-slate-200 @enderror focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 outline-none transition" />
                        @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit"
                        class="w-full h-12 rounded-xl gradient-brand text-white font-semibold shadow-brand hover:opacity-90 transition">
                        Email password reset link
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-slate-500">
                    Remembered it?
                    <a href="{{ route('login') }}" class="font-semibold text-gradient-brand">Back to log in</a>
                </p>
            </div>
        </div>
    </div>
@endsection
