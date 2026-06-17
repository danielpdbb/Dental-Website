@extends('layouts.app')

@section('title', "Dashboard — Bonoan's Dental Clinic")

@section('content')
    @php
        // Feature lists per role — these become real modules later.
        $features = [
            'patient' => ['Book an appointment', 'View your schedule', 'Referrals', 'Cancel an appointment'],
            'receptionist' => ['Appointment handling', 'Walk-ins', 'Payments', 'Referral tracking', 'Predictive scheduling'],
            'dentist' => ['Patient records', 'Treatment history', 'Procedure recommendations'],
            'management' => ['Analytics', 'Reports', 'Service management', 'User management', 'Pricing management'],
        ];
        $mine = $features[$user->role->value] ?? [];
    @endphp

    <div class="container mx-auto px-6 py-16 max-w-4xl">
        <div class="text-xs font-semibold tracking-widest text-brand-blue uppercase">{{ $user->role->label() }} portal</div>
        <h1 class="font-display text-4xl font-bold mt-2">Welcome, {{ $user->name }}</h1>
        <p class="mt-3 text-slate-500">Here's what you'll be able to do here. These features are coming soon.</p>

        <div class="mt-10 grid sm:grid-cols-2 gap-5">
            @foreach ($mine as $feature)
                <div class="rounded-2xl border border-slate-200/60 bg-white p-6 shadow-soft flex items-center gap-4">
                    <div class="h-10 w-10 rounded-xl gradient-brand flex items-center justify-center text-white shrink-0">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <div class="font-display font-semibold">{{ $feature }}</div>
                        <div class="text-xs text-slate-400 mt-0.5">Coming soon</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
