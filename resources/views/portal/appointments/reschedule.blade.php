@extends('layouts.app')

@section('title', "Reschedule appointment — Bonoan's Dental Clinic")

@section('content')
    <div class="container mx-auto px-6 py-12 max-w-2xl">
        @include('partials.portal-nav')

        <h1 class="font-display text-3xl font-bold">Reschedule appointment</h1>
        <p class="text-sm text-slate-500 mt-1">
            {{ $appointment->proceduresLabel() }} with {{ $appointment->dentist?->name }} —
            currently <span class="font-medium text-slate-700">{{ $appointment->scheduled_at->format('l, M j · g:i A') }}</span>.
        </p>

        <div id="reschedule">
            <div class="mt-6">
                @include('partials._booking-calendar', [
                    'calMonth' => $calMonth,
                    'calDays' => $monthDays,
                    'calUrl' => route('portal.appointments.reschedule', $appointment),
                    'calParams' => [],
                    'calTarget' => '#reschedule',
                    'calSelected' => $date->toDateString(),
                ])
            </div>

            <div class="mt-4">
                @if ($slots->isEmpty())
                    <div class="rounded-2xl border border-slate-200/60 bg-white p-6 shadow-soft text-sm text-slate-500">
                        {{ $appointment->dentist?->name }} isn&rsquo;t available on {{ $date->format('l, M j') }}. Please pick another date on the calendar.
                    </div>
                @else
                    <form method="POST" action="{{ route('portal.appointments.reschedule.update', $appointment) }}" class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft" data-review="Confirm this new time?">
                        @csrf
                        @method('PUT')
                        @include('partials._slot-tiles', ['slots' => $slots, 'duration' => $duration, 'date' => $date])

                        <div class="mt-5 flex items-center gap-3">
                            <button class="h-12 px-6 rounded-xl gradient-brand text-white font-semibold shadow-brand hover:opacity-90 transition">Confirm new time</button>
                            <a href="{{ route('portal.appointments.index') }}" class="h-12 px-6 inline-flex items-center rounded-xl border border-slate-200 font-medium text-slate-600 hover:bg-slate-50 transition">Cancel</a>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection
