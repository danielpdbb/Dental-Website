@extends('layouts.admin')

@section('title', 'Scheduling')
@section('heading', 'Predictive scheduling')

@section('content')
  <div id="sched" hx-target="#sched" hx-select="#sched" hx-swap="outerHTML" hx-push-url="true">
    <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
        <p class="text-sm text-slate-500 mb-4">Pick a dentist and service, then a date on the calendar. Choose an existing patient to score the no-show risk against <em>their</em> history — useful when someone calls or walks in.</p>
        <form method="GET" action="{{ route('clinic.scheduling') }}"
              hx-get="{{ route('clinic.scheduling') }}" hx-target="#sched" hx-select="#sched" hx-swap="outerHTML" hx-push-url="true"
              class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <select name="patient_id" onchange="this.form.requestSubmit()" class="h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue lg:col-span-2">
                <option value="">Walk-in / no history…</option>
                @foreach ($patients as $patient)
                    <option value="{{ $patient->id }}" @selected((string) $selected['patient_id'] === (string) $patient->id)>{{ $patient->fullName() }}</option>
                @endforeach
            </select>
            <select name="dentist_id" onchange="this.form.requestSubmit()" class="h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue">
                <option value="">Dentist…</option>
                @foreach ($dentists as $dentist)
                    <option value="{{ $dentist->id }}" @selected((string) $selected['dentist_id'] === (string) $dentist->id)>{{ $dentist->name }}</option>
                @endforeach
            </select>
            <select name="service_id" onchange="this.form.requestSubmit()" class="h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue">
                <option value="">Service…</option>
                @foreach ($services as $service)
                    <option value="{{ $service->id }}" @selected((string) $selected['service_id'] === (string) $service->id)>{{ $service->name }} ({{ $service->duration_minutes }}m)</option>
                @endforeach
            </select>
        </form>

        @if ($patientContext)
            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm flex flex-wrap items-center gap-x-6 gap-y-1">
                <span class="font-semibold text-slate-700">{{ $patientContext['patient']->fullName() }}</span>
                <span class="text-slate-500">Prior visits: <span class="font-medium text-slate-700">{{ $patientContext['visits'] }}</span></span>
                <span class="text-slate-500">Missed before: <span class="font-medium {{ $patientContext['noShows'] > 0 ? 'text-red-500' : 'text-emerald-600' }}">{{ $patientContext['noShows'] }}</span></span>
                <span class="text-xs text-slate-400">Slots below are risk-scored for this patient.</span>
            </div>
        @endif
    </div>

    @if (! is_null($monthDays))
        <div class="mt-6 grid lg:grid-cols-2 gap-6 items-start">
            {{-- Calendar date picker (consistent with booking) --}}
            <div>
                <h3 class="font-display text-lg font-bold mb-3">Pick a date</h3>
                @include('partials._booking-calendar', [
                    'calMonth' => $calMonth,
                    'calDays' => $monthDays,
                    'calUrl' => route('clinic.scheduling'),
                    'calParams' => array_filter(['dentist_id' => $selected['dentist_id'], 'service_id' => $selected['service_id'], 'patient_id' => $selected['patient_id']]),
                    'calTarget' => '#sched',
                    'calSelected' => $fromDate->toDateString(),
                ])
            </div>

            {{-- Day grid: every slot with availability + risk --}}
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-display text-lg font-bold">{{ $fromDate->format('l, M j') }}</h3>
                    @if ($modelTrained)
                        <span class="text-xs text-slate-400">Risk-scored by the Decision Tree</span>
                    @else
                        <span class="text-xs text-amber-600">Train the model to see attendance scores</span>
                    @endif
                </div>

                @if ($suggestedAction)
                    <div class="mb-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        <span class="font-semibold">Suggested action:</span> {{ $suggestedAction }}
                    </div>
                @endif

                @if ($daySlots->isEmpty())
                    <div class="rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm text-slate-500">
                        The dentist isn&rsquo;t available on this date — pick a green day on the calendar.
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-2.5">
                        @foreach ($daySlots as $slot)
                            @if ($slot['status'] === 'free')
                                <a href="{{ route('clinic.appointments.create', array_filter(['patient_id' => $selected['patient_id'], 'dentist_id' => $selected['dentist_id'], 'service_ids' => [$selected['service_id']], 'date' => $slot['time']->toDateString(), 'pick' => $slot['time']->format('H:i')])) }}"
                                   hx-boost="false"
                                   class="rounded-xl border border-slate-200 bg-white p-3 shadow-soft hover:border-brand-blue hover:shadow-brand transition">
                                    <div class="font-medium text-brand-blue">{{ $slot['time']->format('g:i A') }} <span class="text-slate-300 font-normal">– {{ $slot['end']->format('g:i A') }}</span></div>
                                    @if (! is_null($slot['keep']))
                                        <span class="mt-1 inline-block px-2 py-0.5 rounded-full text-[10px] font-medium {{ $slot['risk'][1] }}">No-show: {{ $slot['noShow'] }}% ({{ $slot['risk'][0] }})</span>
                                    @else
                                        <div class="text-[11px] text-slate-400 mt-1">Click to book →</div>
                                    @endif
                                </a>
                            @elseif ($slot['status'] === 'taken')
                                <div class="rounded-xl border border-red-100 bg-red-50/60 p-3 cursor-not-allowed">
                                    <div class="font-medium text-red-300 line-through">{{ $slot['time']->format('g:i A') }}</div>
                                    <div class="text-[11px] text-red-300 mt-1">Already booked</div>
                                </div>
                            @else
                                <div class="rounded-xl border border-slate-100 bg-slate-50 p-3 cursor-not-allowed">
                                    <div class="font-medium text-slate-300">{{ $slot['time']->format('g:i A') }}</div>
                                    <div class="text-[11px] text-slate-300 mt-1">Passed</div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif

                @if ($modelTrained)
                    <div class="mt-3">@include('partials._ai-disclaimer', ['kind' => 'scheduling'])</div>
                @endif
            </div>
        </div>

        {{-- Best upcoming slots across the coming days --}}
        @if ($suggestions->isNotEmpty())
            <div class="mt-8">
                <h3 class="font-display text-lg font-bold mb-3">Best upcoming slots <span class="text-xs text-slate-400 font-normal">(next free, ranked by predicted attendance)</span></h3>
                <div class="grid sm:grid-cols-3 gap-3">
                    @foreach ($suggestions->take(6) as $slot)
                        <a href="{{ route('clinic.appointments.create', array_filter(['patient_id' => $selected['patient_id'], 'dentist_id' => $selected['dentist_id'], 'service_ids' => [$selected['service_id']], 'date' => $slot['time']->toDateString(), 'pick' => $slot['time']->format('H:i')])) }}"
                            hx-boost="false"
                            class="rounded-xl border bg-white p-4 shadow-soft hover:shadow-brand transition {{ $slot['recommended'] ? 'border-brand-green ring-1 ring-brand-green/30' : 'border-slate-200/60' }}">
                            <div class="flex items-center justify-between">
                                <div class="font-medium">{{ $slot['time']->format('D, M j') }}</div>
                                @if ($slot['recommended'])
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-brand-green/10 text-emerald-700">★ Best</span>
                                @endif
                            </div>
                            <div class="text-sm text-brand-blue">{{ $slot['time']->format('g:i A') }}</div>
                            @if (! is_null($slot['keep']))
                                <div class="mt-1.5"><span class="px-2 py-0.5 rounded-full text-[10px] font-medium {{ $slot['risk'][1] }}">No-show: {{ $slot['noShow'] }}% ({{ $slot['risk'][0] }})</span></div>
                                <div class="text-[11px] text-slate-400 mt-1">{{ round($slot['keep'] * 100) }}% likely to be kept</div>
                            @else
                                <div class="text-xs text-slate-400 mt-1">Click to book →</div>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
  </div>
@endsection
