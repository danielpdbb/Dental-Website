@extends('layouts.app')

@section('title', "Log in — Bonoan's Dental Clinic")

@section('content')
    <div class="flex items-center justify-center px-6 py-16">
        <div class="w-full max-w-md">
            <div class="rounded-3xl bg-white p-8 md:p-10 shadow-brand border border-slate-200/60">
                <div class="text-center">
                    <h1 class="font-display text-3xl font-bold">Welcome back</h1>
                    <p class="mt-2 text-sm text-slate-500">Log in to your patient portal.</p>
                </div>

                <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-4">
                    @csrf

                    <div>
                        <label for="login" class="block text-sm font-medium text-slate-700 mb-1">Email or username</label>
                        <input id="login" type="text" name="login" value="{{ old('login') }}" required autofocus autocomplete="username"
                            class="w-full h-11 px-4 rounded-xl border @error('login') border-red-400 @else border-slate-200 @enderror focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 outline-none transition" />
                        @error('login') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                        <input id="password" type="password" name="password" required autocomplete="current-password"
                            class="w-full h-11 px-4 rounded-xl border border-slate-200 focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 outline-none transition" />
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-blue focus:ring-brand-blue/30" />
                            Remember me
                        </label>
                        <a href="{{ route('password.request') }}" class="text-sm font-medium text-gradient-brand">Forgot password?</a>
                    </div>

                    <button type="submit"
                        class="w-full h-12 rounded-xl gradient-brand text-white font-semibold shadow-brand hover:opacity-90 transition">
                        Log in
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-slate-500">
                    Don't have an account?
                    <a href="{{ route('register') }}" class="font-semibold text-gradient-brand">Sign up</a>
                </p>
            </div>
        </div>
    </div>
@endsection
