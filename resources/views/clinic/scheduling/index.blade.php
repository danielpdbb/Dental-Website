@extends('layouts.admin')

@section('title', 'Scheduling')
@section('heading', 'Predictive scheduling')

@section('content')
    <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
        <p class="text-sm text-slate-500 mb-4">Pick a dentist and service to see the next available slots. Choose an existing patient to score the no-show risk against <em>their</em> history — useful when someone calls or walks in.</p>
        <form method="GET" action="{{ route('clinic.scheduling') }}" class="grid sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <select name="patient_id" class="h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue lg:col-span-2">
                <option value="">Walk-in / no history…</option>
                @foreach ($patients as $patient)
                    <option value="{{ $patient->id }}" @selected((string) $selected['patient_id'] === (string) $patient->id)>{{ $patient->fullName() }}</option>
                @endforeach
            </select>
            <select name="dentist_id" required class="h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue">
                <option value="">Dentist…</option>
                @foreach ($dentists as $dentist)
                    <option value="{{ $dentist->id }}" @selected((string) $selected['dentist_id'] === (string) $dentist->id)>{{ $dentist->name }}</option>
                @endforeach
            </select>
            <select name="service_id" required class="h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue">
                <option value="">Service…</option>
                @foreach ($services as $service)
                    <option value="{{ $service->id }}" @selected((string) $selected['service_id'] === (string) $service->id)>{{ $service->name }} ({{ $service->duration_minutes }}m)</option>
                @endforeach
            </select>
            <input type="date" name="date" value="{{ $selected['date'] }}" class="h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue" />
            <button class="h-10 px-4 rounded-lg bg-slate-800 text-white text-sm font-medium hover:bg-slate-700 transition lg:col-span-5">Find slots</button>
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

    @if ($selected['dentist_id'] && $selected['service_id'])
        <div class="mt-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-display text-lg font-bold">Suggested slots</h3>
                @if ($modelTrained)
                    <span class="text-xs text-slate-400">Ranked by predicted attendance (Decision Tree)</span>
                @else
                    <span class="text-xs text-amber-600">Run <code>php artisan ml:scheduling:train</code> to rank by attendance likelihood</span>
                @endif
            </div>
            @if ($modelTrained)
                <div class="mb-3">@include('partials._ai-disclaimer', ['kind' => 'scheduling'])</div>
            @endif
            @if ($suggestedAction)
                <div class="mb-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    <span class="font-semibold">Suggested action:</span> {{ $suggestedAction }}
                </div>
            @endif
            @if ($suggestions->isEmpty())
                <p class="text-sm text-slate-400">No free slots found in the next few weeks.</p>
            @else
                <div class="grid sm:grid-cols-3 gap-3">
                    @foreach ($suggestions as $slot)
                        <a href="{{ route('clinic.appointments.create', array_filter(['patient_id' => $selected['patient_id'], 'dentist_id' => $selected['dentist_id'], 'service_id' => $selected['service_id'], 'scheduled_at' => $slot['time']->format('Y-m-d\TH:i')])) }}"
                            class="rounded-xl border bg-white p-4 shadow-soft hover:shadow-brand transition {{ $slot['recommended'] ? 'border-brand-green ring-1 ring-brand-green/30' : 'border-slate-200/60' }}">
                            <div class="flex items-center justify-between">
                                <div class="font-medium">{{ $slot['time']->format('D, M j') }}</div>
                                @if ($slot['recommended'])
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-brand-green/10 text-emerald-700">★ Best</span>
                                @endif
                            </div>
                            <div class="text-sm text-brand-blue">{{ $slot['time']->format('g:i A') }}</div>
                            @if (! is_null($slot['keep']))
                                <div class="flex items-center gap-2 mt-1.5">
                                    @if ($slot['risk'])
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-medium {{ $slot['risk'][1] }}">No-show: {{ $slot['noShow'] }}% ({{ $slot['risk'][0] }})</span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-slate-400 mt-1">{{ round($slot['keep'] * 100) }}% likely to be kept</div>
                            @else
                                <div class="text-xs text-slate-400 mt-1">Click to book →</div>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
@endsection
