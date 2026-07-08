@extends('layouts.app')

@section('title', "Book an appointment — Bonoan's Dental Clinic")

@section('content')
    <div class="container mx-auto px-6 py-12 max-w-5xl">
        @include('partials.portal-nav')

        <h1 class="font-display text-3xl font-bold">Book an appointment</h1>
        <p class="text-sm text-slate-500 mt-1">Pick your services and dentist, then choose a date on the calendar to see the times. You can book up to 3 months ahead.</p>

        @include('portal.appointments._recommendations')

        {{-- Booking area — async: every choice swaps just this block via htmx.
             Left column = services/dentist picker; right column = calendar + times, so
             there's no long scroll to get from choices to confirming a slot. --}}
        <div id="booking">
        <div class="mt-6 grid lg:grid-cols-2 gap-6 items-start">

            {{-- Left: services + dentist --}}
            <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
                <form method="GET" action="{{ route('portal.appointments.create') }}"
                      hx-get="{{ route('portal.appointments.create') }}" hx-target="#booking" hx-select="#booking" hx-swap="outerHTML" hx-push-url="true"
                      hx-indicator="#booking-loading">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="h-6 w-6 rounded-full gradient-brand text-white text-xs font-bold inline-flex items-center justify-center">1</span>
                        <label class="text-sm font-semibold text-slate-700">Services <span class="text-slate-400 font-normal">(choose one or more)</span></label>
                    </div>
                    <div class="grid gap-2">
                        @foreach ($services as $s)
                            <label class="flex items-center gap-2 rounded-xl border px-3 py-2.5 text-sm cursor-pointer transition {{ in_array($s->id, $selectedIds) ? 'border-brand-blue bg-brand-blue/5' : 'border-slate-200 hover:bg-slate-50' }}">
                                <input type="checkbox" name="service_ids[]" value="{{ $s->id }}" onchange="this.form.requestSubmit()" @checked(in_array($s->id, $selectedIds)) data-review-label="Services"
                                    class="rounded border-slate-300 text-brand-blue focus:ring-brand-blue/30" />
                                <span class="flex-1">{{ $s->name }}</span>
                                <span class="text-slate-400 text-xs whitespace-nowrap">{{ $s->duration_minutes }}m · ₱{{ number_format($s->price, 2) }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="flex items-center gap-2 mt-5 mb-2">
                        <span class="h-6 w-6 rounded-full gradient-brand text-white text-xs font-bold inline-flex items-center justify-center">2</span>
                        <label for="dentist_id" class="text-sm font-semibold text-slate-700">Dentist</label>
                    </div>
                    <select id="dentist_id" name="dentist_id" onchange="this.form.requestSubmit()" class="w-full h-11 px-3 rounded-xl border border-slate-200 outline-none focus:border-brand-blue">
                        <option value="">Select a dentist…</option>
                        @foreach ($dentists as $d)
                            <option value="{{ $d->id }}" @selected($dentist?->id === $d->id)>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </form>

                @if ($selected->isNotEmpty())
                    <div class="mt-4 flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3 text-sm">
                        <span class="text-slate-500">{{ $selected->count() }} service(s) · needs ~{{ $totalDuration }} min</span>
                        <span class="font-display font-bold text-gradient-brand">₱{{ number_format($totalPrice, 2) }}</span>
                    </div>
                @endif

                {{-- Decision-Tree recommended slot --}}
                @if (! empty($recommended))
                    <div class="mt-4 rounded-xl border border-brand-blue/30 bg-brand-blue/5 p-4">
                        <div class="text-xs text-brand-blue font-medium flex items-center gap-1.5">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            Recommended time
                        </div>
                        <div class="font-semibold mt-1">{{ $recommended['time']->format('l, M j · g:i A') }} · {{ $recommended['dentist']->name }}</div>
                        <div class="text-xs text-slate-500 mt-0.5">Chosen for availability and a strong on-time-attendance pattern.</div>
                        <a href="{{ route('portal.appointments.create', array_merge(['service_ids' => $selectedIds], ['dentist_id' => $recommended['dentist']->id, 'date' => $recommended['time']->toDateString(), 'pick' => $recommended['time']->format('H:i')])) }}#book"
                            class="mt-2 inline-flex h-9 px-3 items-center rounded-lg bg-brand-blue text-white text-xs font-semibold hover:opacity-90">Use this slot</a>
                    </div>
                @endif

                @if ($followTargets->isNotEmpty())
                    <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-xs text-slate-500">
                        Booking a follow-up (e.g. a braces adjustment)? Pick a time on the right, then choose which visit it follows up in step 4.
                    </div>
                @endif
            </div>

            {{-- Right: calendar + time slots --}}
            <div>
                @if (is_null($monthDays))
                    <div class="rounded-2xl border border-slate-200/60 bg-white p-6 shadow-soft text-sm text-slate-400">
                        Select at least one service and a dentist on the left to see {{ $dentist?->name ?? 'the dentist' }}&rsquo;s calendar.
                    </div>
                @else
                    <div class="flex items-center gap-2 mb-2">
                        <span class="h-6 w-6 rounded-full gradient-brand text-white text-xs font-bold inline-flex items-center justify-center">3</span>
                        <span class="text-sm font-semibold text-slate-700">Pick a date <span class="text-slate-400 font-normal">— {{ $dentist->name }}&rsquo;s availability</span></span>
                    </div>
                    @include('partials._booking-calendar', [
                        'calMonth' => $calMonth,
                        'calDays' => $monthDays,
                        'calUrl' => route('portal.appointments.create'),
                        'calParams' => ['service_ids' => $selectedIds, 'dentist_id' => $dentist->id],
                        'calTarget' => '#booking',
                        'calSelected' => $date?->toDateString(),
                    ])

                    <div class="mt-4">
                    @if (! is_null($slots))
                        @if ($slots->isEmpty())
                            <div class="rounded-2xl border border-slate-200/60 bg-white p-6 shadow-soft text-sm text-slate-500">
                                {{ $dentist->name }} isn&rsquo;t available on {{ $date->format('l, M j') }}. Please pick another date on the calendar.
                            </div>
                        @else
                            <form method="POST" action="{{ route('portal.appointments.store') }}" id="book" data-review="Confirm this booking?">
                                @csrf
                                @foreach ($selected as $s)
                                    <input type="hidden" name="service_ids[]" value="{{ $s->id }}" />
                                @endforeach
                                <input type="hidden" name="dentist_id" value="{{ $dentist->id }}" />

                                <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
                                    <div class="flex items-center gap-2 mb-3">
                                        <span class="h-6 w-6 rounded-full gradient-brand text-white text-xs font-bold inline-flex items-center justify-center">4</span>
                                        <span class="text-sm font-semibold text-slate-700">Choose a time</span>
                                    </div>

                                    @include('partials._slot-tiles', ['slots' => $slots, 'duration' => max(15, $totalDuration), 'date' => $date])

                                    {{-- readonly context so the review modal can show it --}}
                                    <input type="hidden" name="display_service" value="{{ $selected->pluck('name')->join(', ') }} (₱{{ number_format($totalPrice, 2) }})" />
                                    <input type="hidden" name="display_dentist" value="{{ $dentist->name }}" />

                                    @if ($followTargets->isNotEmpty())
                                        <div class="mt-5">
                                            <label for="parent_appointment_id" class="block text-xs font-medium text-slate-500 mb-1">
                                                Follow-up of a previous visit? <span class="text-slate-300">(optional — keeps charges on one bill)</span>
                                            </label>
                                            <select id="parent_appointment_id" name="parent_appointment_id" class="w-full h-11 px-3 rounded-xl border border-slate-200 outline-none focus:border-brand-blue text-sm">
                                                <option value="">— No, this is a new visit —</option>
                                                @foreach ($followTargets as $ft)
                                                    <option value="{{ $ft->id }}" @selected($followId === $ft->id)>
                                                        {{ $ft->scheduled_at->format('M j, Y') }} — {{ \Illuminate\Support\Str::limit($ft->proceduresLabel(), 40) }}{{ $ft->balance() > 0 ? ' · ₱'.number_format($ft->balance(), 2).' still due' : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif

                                    <div class="mt-4">
                                        <label for="notes" class="block text-xs font-medium text-slate-500 mb-1">Notes (optional)</label>
                                        <textarea id="notes" name="notes" rows="2" class="w-full px-3 py-2 rounded-xl border border-slate-200 outline-none focus:border-brand-blue">{{ old('notes') }}</textarea>
                                    </div>

                                    <button class="mt-5 w-full h-12 rounded-xl gradient-brand text-white font-semibold shadow-brand hover:opacity-90 transition">Confirm booking</button>
                                </div>
                            </form>
                        @endif
                    @else
                        <p class="text-sm text-slate-400">Tap an <span class="text-emerald-600 font-medium">available (green)</span> day on the calendar to see its time slots.</p>
                    @endif
                    </div>
                @endif
            </div>
        </div>

        <div id="booking-loading" class="htmx-indicator text-center text-sm text-slate-400 mt-4">Updating availability…</div>
        </div>
    </div>
@endsection
