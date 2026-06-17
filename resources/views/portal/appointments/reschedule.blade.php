@extends('layouts.app')

@section('title', "Reschedule appointment — Bonoan's Dental Clinic")

@section('content')
    <div class="container mx-auto px-6 py-12 max-w-2xl">
        @include('partials.portal-nav')

        <h1 class="font-display text-3xl font-bold">Reschedule appointment</h1>
        <p class="text-sm text-slate-500 mt-1">
            {{ $appointment->service?->name }} with {{ $appointment->dentist?->name }} —
            currently <span class="font-medium text-slate-700">{{ $appointment->scheduled_at->format('l, M j · g:i A') }}</span>.
        </p>

        {{-- Pick a new date --}}
        <div class="mt-6 rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
            <form method="GET" action="{{ route('portal.appointments.reschedule', $appointment) }}">
                <label for="date" class="block text-xs font-medium text-slate-500 mb-1">New date</label>
                <input id="date" type="date" name="date" value="{{ $date->toDateString() }}" min="{{ now()->toDateString() }}" onchange="this.form.submit()"
                    class="w-full sm:w-64 h-11 px-3 rounded-xl border border-slate-200 outline-none focus:border-brand-blue" />
            </form>
        </div>

        @if ($slots->isEmpty())
            <div class="mt-6 rounded-2xl border border-slate-200/60 bg-white p-6 shadow-soft text-sm text-slate-500">
                The clinic is closed on {{ $date->format('l, M j') }}. Please choose another date (Mon–Sat).
            </div>
        @else
            <form method="POST" action="{{ route('portal.appointments.reschedule.update', $appointment) }}" class="mt-6">
                @csrf
                @method('PUT')
                <div class="text-sm font-medium text-slate-700 mb-2">Available times on {{ $date->format('l, M j') }}</div>
                @error('scheduled_at') <p class="mb-2 text-xs text-red-500">{{ $message }}</p> @enderror
                <div class="grid grid-cols-3 sm:grid-cols-4 gap-2.5">
                    @foreach ($slots as $slot)
                        @if ($slot['available'])
                            <label class="cursor-pointer">
                                <input type="radio" name="scheduled_at" value="{{ $slot['time']->format('Y-m-d\TH:i') }}" class="peer sr-only" required />
                                <span class="block text-center rounded-xl border border-slate-200 py-2.5 text-sm font-medium text-slate-700 hover:border-brand-blue transition peer-checked:border-brand-blue peer-checked:bg-brand-blue/10 peer-checked:text-brand-blue">
                                    {{ $slot['time']->format('g:i A') }}
                                </span>
                            </label>
                        @else
                            <div class="text-center rounded-xl border border-slate-100 bg-slate-50 py-2.5 text-sm text-slate-300 line-through cursor-not-allowed" title="Unavailable">
                                {{ $slot['time']->format('g:i A') }}
                            </div>
                        @endif
                    @endforeach
                </div>

                <div class="mt-5 flex items-center gap-3">
                    <button class="h-12 px-6 rounded-xl gradient-brand text-white font-semibold shadow-brand hover:opacity-90 transition">Confirm new time</button>
                    <a href="{{ route('portal.appointments.index') }}" class="h-12 px-6 inline-flex items-center rounded-xl border border-slate-200 font-medium text-slate-600 hover:bg-slate-50 transition">Cancel</a>
                </div>
            </form>
        @endif
    </div>
@endsection
