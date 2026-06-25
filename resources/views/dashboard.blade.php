@extends('layouts.app')

@section('title', "Dashboard — Bonoan's Dental Clinic")

@section('content')
    @php
        // Quick links per role -> [label, route name, description]
        $links = [
            'patient' => [
                ['Book an appointment', 'portal.appointments.create', 'Choose a service, dentist and time slot.'],
                ['My appointments', 'portal.appointments.index', 'View upcoming and past visits, or cancel.'],
                ['My record', 'portal.record', 'See your details, allergies and treatment history.'],
                ['Referrals', 'portal.referrals.index', 'Request and track referrals.'],
                ['Rewards', 'portal.rewards.index', 'Refer friends, earn points, save on bills.'],
            ],
            'receptionist' => [
                ['Appointments', 'clinic.appointments.index', 'Handle bookings, walk-ins and payments.'],
                ['Patients', 'clinic.patients.index', 'Manage patient records.'],
                ['Scheduling', 'clinic.scheduling', 'Find the next available slots.'],
                ['Referrals', 'clinic.referrals.index', 'Track referral status.'],
            ],
            'dentist' => [
                ['Patients', 'clinic.patients.index', 'Records, treatment history, recommendations.'],
            ],
            'management' => [
                ['Admin dashboard', 'admin.dashboard', 'Overview, users, services and analytics.'],
            ],
        ];
        $mine = $links[$user->role->value] ?? [];
    @endphp

    <div class="container mx-auto px-6 py-16 max-w-4xl">
        <div class="text-xs font-semibold tracking-widest text-brand-blue uppercase">{{ $user->role->label() }} portal</div>
        <h1 class="font-display text-4xl font-bold mt-2">Welcome, {{ $user->name }}</h1>
        <p class="mt-3 text-slate-500">Quick links to get you started.</p>

        @if (! empty($recommendations) && $recommendations->isNotEmpty())
            <div class="mt-10 rounded-2xl border border-brand-blue/20 bg-brand-blue/5 p-6">
                <h2 class="font-display text-lg font-bold">Recommendations from your dentist</h2>
                <p class="text-sm text-slate-500 mt-0.5">Suggestions your dentist reviewed and sent to you. These are guidance, not a final diagnosis.</p>
                <div class="mt-4 space-y-3">
                    @foreach ($recommendations as $rec)
                        <div class="rounded-xl bg-white border border-slate-200/60 p-4 flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="text-xs text-slate-400">{{ $rec->source->label() }} · {{ $rec->appointment->dentist?->name ?? 'Your dentist' }}</div>
                                <div class="font-semibold mt-0.5">{{ $rec->recommendation }}</div>
                                <div class="flex flex-wrap gap-2 mt-2 text-xs">
                                    @if ($rec->priority)<span class="px-2 py-0.5 rounded-full font-medium {{ $rec->priority->badgeClasses() }}">{{ $rec->priority->label() }} priority</span>@endif
                                    @if ($rec->suggested_at)<span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700">Suggested: {{ $rec->suggested_at->format('M j, Y · g:i A') }}</span>@endif
                                </div>
                            </div>
                            <a href="{{ route('portal.recommendations.print', $rec) }}" target="_blank" class="shrink-0 text-sm text-brand-blue hover:underline">Print</a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-10 grid sm:grid-cols-2 gap-5">
            @foreach ($mine as [$label, $routeName, $desc])
                <a href="{{ route($routeName) }}"
                    class="rounded-2xl border border-slate-200/60 bg-white p-6 shadow-soft hover:shadow-brand transition flex items-start gap-4 group">
                    <div class="h-10 w-10 rounded-xl gradient-brand flex items-center justify-center text-white shrink-0 group-hover:scale-110 transition">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                    <div>
                        <div class="font-display font-semibold">{{ $label }}</div>
                        <div class="text-sm text-slate-500 mt-0.5">{{ $desc }}</div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
@endsection
