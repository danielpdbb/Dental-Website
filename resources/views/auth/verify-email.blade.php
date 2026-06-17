@extends('layouts.app')

@section('title', "Verify your email — Bonoan's Dental Clinic")

@section('content')
    <div class="flex items-center justify-center px-6 py-16">
        <div class="w-full max-w-md">
            <div class="rounded-3xl bg-white p-8 md:p-10 shadow-brand border border-slate-200/60 text-center">
                <div class="mx-auto h-14 w-14 rounded-2xl gradient-brand flex items-center justify-center text-white">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="4" width="20" height="16" rx="2" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2 7l10 7 10-7" />
                    </svg>
                </div>

                <h1 class="mt-5 font-display text-2xl font-bold">Verify your email</h1>
                <p class="mt-3 text-sm text-slate-500">
                    Your account is <span class="font-semibold text-slate-700">inactive</span> until you confirm your email.
                    We've sent a verification link to <span class="font-semibold">{{ auth()->user()->email }}</span>.
                    Click it to activate your account.
                </p>

                <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
                    @csrf
                    <button type="submit"
                        class="w-full h-12 rounded-xl gradient-brand text-white font-semibold shadow-brand hover:opacity-90 transition">
                        Resend verification email
                    </button>
                </form>

                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="text-sm text-slate-500 hover:text-brand-blue transition">
                        Log out
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
