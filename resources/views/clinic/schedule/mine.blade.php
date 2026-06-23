@extends('layouts.admin')

@section('title', 'My schedule')
@section('heading', $isDentist ? 'My schedule' : 'Dentist schedule')

@section('content')
    @php($base = $isDentist ? [] : ['dentist_id' => $dentist?->id])
    <div class="rounded-2xl bg-white border border-slate-200/60 p-5 shadow-soft mb-5">
        <form method="GET" action="{{ route('clinic.my-schedule') }}" class="flex flex-wrap items-end gap-3">
            @unless ($isDentist)
                <div>
                    <label for="dentist_id" class="block text-xs font-medium text-slate-500 mb-1">Dentist</label>
                    <select id="dentist_id" name="dentist_id" onchange="this.form.submit()" class="h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue">
                        @foreach ($dentists as $d)
                            <option value="{{ $d->id }}" @selected($dentist?->id === $d->id)>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endunless
            <div>
                <label for="date" class="block text-xs font-medium text-slate-500 mb-1">Date</label>
                <input id="date" type="date" name="date" value="{{ $date->toDateString() }}" onchange="this.form.submit()" class="h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue" />
            </div>
            <div class="flex gap-2">
                <a href="{{ route('clinic.my-schedule', $base + ['date' => $date->copy()->subDay()->toDateString()]) }}" class="h-10 px-3 inline-flex items-center rounded-lg border border-slate-200 text-sm hover:bg-slate-50">← Prev</a>
                <a href="{{ route('clinic.my-schedule', $base + ['date' => now()->toDateString()]) }}" class="h-10 px-3 inline-flex items-center rounded-lg border border-slate-200 text-sm hover:bg-slate-50">Today</a>
                <a href="{{ route('clinic.my-schedule', $base + ['date' => $date->copy()->addDay()->toDateString()]) }}" class="h-10 px-3 inline-flex items-center rounded-lg border border-slate-200 text-sm hover:bg-slate-50">Next →</a>
            </div>
        </form>
    </div>

    <h2 class="font-display text-lg font-bold mb-3">
        {{ $date->format('l, F j, Y') }}
        @if ($dentist) <span class="text-slate-400 font-normal text-sm">· {{ $dentist->name }}</span> @endif
    </h2>

    <div class="space-y-3">
        @forelse ($appointments as $appt)
            <div class="rounded-2xl bg-white border border-slate-200/60 p-4 shadow-soft flex items-center gap-4">
                <div class="text-center w-20 shrink-0">
                    <div class="font-display font-bold text-brand-blue">{{ $appt->scheduled_at->format('g:i') }}</div>
                    <div class="text-xs text-slate-400">{{ $appt->scheduled_at->format('A') }}</div>
                </div>
                <div class="flex-1 min-w-0 border-l border-slate-100 pl-4">
                    <div class="font-medium text-slate-800">{{ $appt->patient?->fullName() ?? '—' }}</div>
                    <div class="text-sm text-slate-500">{{ \Illuminate\Support\Str::limit($appt->proceduresLabel(), 45) }} · {{ $appt->duration_minutes }} min @if($appt->is_walk_in)<span class="text-amber-600">· walk-in</span>@endif</div>
                </div>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $appt->status->badgeClasses() }}">{{ $appt->status->label() }}</span>
                <div class="flex items-center gap-3 shrink-0">
                    <a href="{{ route('clinic.appointments.treatment', $appt) }}" class="text-sm font-medium text-brand-blue hover:underline">Treatment</a>
                    @if ($appt->patient)
                        <a href="{{ route('clinic.patients.show', $appt->patient) }}" class="text-sm text-slate-500 hover:underline">Record</a>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-2xl bg-white border border-slate-200/60 p-10 text-center text-slate-400 shadow-soft">
                No appointments scheduled for this day.
            </div>
        @endforelse
    </div>
@endsection
