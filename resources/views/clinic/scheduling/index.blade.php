@extends('layouts.admin')

@section('title', 'Scheduling')
@section('heading', 'Predictive scheduling')

@section('content')
    <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
        <p class="text-sm text-slate-500 mb-4">Pick a dentist and service to see the next available slots (based on clinic hours and existing bookings).</p>
        <form method="GET" action="{{ route('clinic.scheduling') }}" class="grid sm:grid-cols-4 gap-3">
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
            <button class="h-10 px-4 rounded-lg bg-slate-800 text-white text-sm font-medium hover:bg-slate-700 transition">Find slots</button>
        </form>
    </div>

    @if ($selected['dentist_id'] && $selected['service_id'])
        <div class="mt-6">
            <h3 class="font-display text-lg font-bold mb-3">Suggested slots</h3>
            @if ($suggestions->isEmpty())
                <p class="text-sm text-slate-400">No free slots found in the next few weeks.</p>
            @else
                <div class="grid sm:grid-cols-3 gap-3">
                    @foreach ($suggestions as $slot)
                        <a href="{{ route('clinic.appointments.create', ['dentist_id' => $selected['dentist_id'], 'service_id' => $selected['service_id'], 'scheduled_at' => $slot->format('Y-m-d\TH:i')]) }}"
                            class="rounded-xl border border-slate-200/60 bg-white p-4 shadow-soft hover:shadow-brand transition">
                            <div class="font-medium">{{ $slot->format('D, M j') }}</div>
                            <div class="text-sm text-brand-blue">{{ $slot->format('g:i A') }}</div>
                            <div class="text-xs text-slate-400 mt-1">Click to book →</div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
@endsection
