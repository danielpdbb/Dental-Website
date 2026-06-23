@extends('layouts.app')

@section('title', "Book an appointment — Bonoan's Dental Clinic")

@section('content')
    <div class="container mx-auto px-6 py-12 max-w-2xl">
        @include('partials.portal-nav')

        <h1 class="font-display text-3xl font-bold">Book an appointment</h1>
        <p class="text-sm text-slate-500 mt-1">Clinic hours: Mon–Sat, {{ \Illuminate\Support\Carbon::parse(config('clinic.open_time'))->format('g:i A') }}–{{ \Illuminate\Support\Carbon::parse(config('clinic.close_time'))->format('g:i A') }}. You can pick more than one service.</p>

        {{-- Step 1: choose service(s), dentist and date (reloads to show times) --}}
        <div class="mt-6 rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
            <form method="GET" action="{{ route('portal.appointments.create') }}">
                <label class="block text-xs font-medium text-slate-500 mb-2">Services (choose one or more)</label>
                <div class="grid sm:grid-cols-2 gap-2">
                    @foreach ($services as $s)
                        <label class="flex items-center gap-2 rounded-xl border px-3 py-2.5 text-sm cursor-pointer transition {{ in_array($s->id, $selectedIds) ? 'border-brand-blue bg-brand-blue/5' : 'border-slate-200 hover:bg-slate-50' }}">
                            <input type="checkbox" name="service_ids[]" value="{{ $s->id }}" onchange="this.form.submit()" @checked(in_array($s->id, $selectedIds))
                                class="rounded border-slate-300 text-brand-blue focus:ring-brand-blue/30" />
                            <span class="flex-1">{{ $s->name }}</span>
                            <span class="text-slate-400 text-xs whitespace-nowrap">{{ $s->duration_minutes }}m · ₱{{ number_format($s->price, 2) }}</span>
                        </label>
                    @endforeach
                </div>

                <div class="grid sm:grid-cols-2 gap-3 mt-4">
                    <div>
                        <label for="dentist_id" class="block text-xs font-medium text-slate-500 mb-1">Dentist</label>
                        <select id="dentist_id" name="dentist_id" onchange="this.form.submit()" class="w-full h-11 px-3 rounded-xl border border-slate-200 outline-none focus:border-brand-blue">
                            <option value="">Select…</option>
                            @foreach ($dentists as $d)
                                <option value="{{ $d->id }}" @selected($dentist?->id === $d->id)>{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="date" class="block text-xs font-medium text-slate-500 mb-1">Date</label>
                        <input id="date" type="date" name="date" value="{{ $date->toDateString() }}" min="{{ now()->toDateString() }}" onchange="this.form.submit()" class="w-full h-11 px-3 rounded-xl border border-slate-200 outline-none focus:border-brand-blue" />
                    </div>
                </div>
            </form>

            @if ($selected->isNotEmpty())
                <div class="mt-4 flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3 text-sm">
                    <span class="text-slate-500">{{ $selected->count() }} service(s) · ~{{ $totalDuration }} min total</span>
                    <span class="font-display font-bold text-gradient-brand">₱{{ number_format($totalPrice, 2) }}</span>
                </div>
            @endif
        </div>

        {{-- Step 2: pick a time tile --}}
        @if (is_null($slots))
            <p class="mt-6 text-sm text-slate-400">Select at least one service, a dentist and a date above to see available times.</p>
        @elseif ($slots->isEmpty())
            <div class="mt-6 rounded-2xl border border-slate-200/60 bg-white p-6 shadow-soft text-sm text-slate-500">
                The clinic is closed on {{ $date->format('l, M j') }}. Please choose another date (Mon–Sat).
            </div>
        @else
            <form method="POST" action="{{ route('portal.appointments.store') }}" class="mt-6" data-review="Confirm this booking?">
                @csrf
                @foreach ($selected as $s)
                    <input type="hidden" name="service_ids[]" value="{{ $s->id }}" />
                @endforeach
                <input type="hidden" name="dentist_id" value="{{ $dentist->id }}" />

                {{-- readonly fields so the review modal can show context --}}
                <div class="grid sm:grid-cols-2 gap-3">
                    <div>
                        <label for="display_service" class="block text-xs font-medium text-slate-500 mb-1">Services</label>
                        <input id="display_service" name="display_service" type="text" value="{{ $selected->pluck('name')->join(', ') }} (₱{{ number_format($totalPrice, 2) }})" readonly class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-slate-50 text-slate-600" />
                    </div>
                    <div>
                        <label for="display_dentist" class="block text-xs font-medium text-slate-500 mb-1">Dentist</label>
                        <input id="display_dentist" name="display_dentist" type="text" value="{{ $dentist->name }}" readonly class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-slate-50 text-slate-600" />
                    </div>
                </div>

                <div class="mt-5">
                    <div class="text-sm font-medium text-slate-700 mb-2">Available times on {{ $date->format('l, M j') }} <span class="text-xs text-slate-400">(needs ~{{ $totalDuration }} min)</span></div>
                    @error('scheduled_at') <p class="mb-2 text-xs text-red-500">{{ $message }}</p> @enderror
                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-2.5">
                        @foreach ($slots as $slot)
                            @if ($slot['available'])
                                <label class="cursor-pointer">
                                    <input type="radio" name="scheduled_at" value="{{ $slot['time']->format('Y-m-d\TH:i') }}" data-display="{{ $slot['time']->format('M j, Y · g:i A') }}" class="peer sr-only" required />
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
                    <div class="mt-2 flex items-center gap-4 text-xs text-slate-400">
                        <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded border border-slate-200 inline-block"></span> Available</span>
                        <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded bg-slate-100 inline-block"></span> Unavailable</span>
                    </div>
                </div>

                <div class="mt-5">
                    <label for="notes" class="block text-xs font-medium text-slate-500 mb-1">Notes (optional)</label>
                    <textarea id="notes" name="notes" rows="2" class="w-full px-3 py-2 rounded-xl border border-slate-200 outline-none focus:border-brand-blue">{{ old('notes') }}</textarea>
                </div>

                <button class="mt-5 w-full h-12 rounded-xl gradient-brand text-white font-semibold shadow-brand hover:opacity-90 transition">Confirm booking</button>
            </form>
        @endif
    </div>
@endsection
