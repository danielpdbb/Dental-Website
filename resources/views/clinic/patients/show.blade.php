@extends('layouts.admin')

@section('title', $patient->fullName())
@section('heading', 'Patient record')

@section('content')
    @php
        $role = auth()->user()->role->value;
        $canRecommend = in_array($role, ['dentist', 'management']);
    @endphp

    <div class="flex items-center justify-between mb-5">
        <a href="{{ route('clinic.patients.index') }}" class="text-sm text-slate-500 hover:text-brand-blue">← All patients</a>
        <div class="flex gap-2">
            @can('update', $patient)
                <a href="{{ route('clinic.patients.edit', $patient) }}" class="h-9 px-4 inline-flex items-center rounded-lg border border-slate-200 text-sm font-medium hover:bg-slate-50 transition">Edit</a>
            @endcan
            @can('delete', $patient)
                <form method="POST" action="{{ route('clinic.patients.destroy', $patient) }}" onsubmit="return confirm('Remove this patient record?');">
                    @csrf @method('DELETE')
                    <button class="h-9 px-4 rounded-lg border border-red-200 text-red-500 text-sm font-medium hover:bg-red-50 transition">Delete</button>
                </form>
            @endcan
        </div>
    </div>

    <!-- Details -->
    <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
        <h2 class="font-display text-xl font-bold">{{ $patient->fullName() }}</h2>
        <div class="mt-4 grid sm:grid-cols-3 gap-4 text-sm">
            <div><div class="text-slate-400 text-xs uppercase tracking-wider">Date of birth</div>{{ $patient->date_of_birth?->format('M j, Y') ?? '—' }} @if($patient->date_of_birth)({{ (int) $patient->date_of_birth->age }} yrs)@endif</div>
            <div><div class="text-slate-400 text-xs uppercase tracking-wider">Gender</div>{{ $patient->gender ?? '—' }}</div>
            <div><div class="text-slate-400 text-xs uppercase tracking-wider">Blood type</div>{{ $patient->blood_type ?? '—' }}</div>
            <div><div class="text-slate-400 text-xs uppercase tracking-wider">Phone</div>{{ $patient->phone ?? '—' }}</div>
            <div><div class="text-slate-400 text-xs uppercase tracking-wider">Account</div>{{ $patient->user?->email ?? 'Walk-in (no login)' }}</div>
            <div><div class="text-slate-400 text-xs uppercase tracking-wider">Emergency</div>{{ $patient->emergency_contact_name ?? '—' }} {{ $patient->emergency_contact_phone }}</div>
            <div class="sm:col-span-3"><div class="text-slate-400 text-xs uppercase tracking-wider">Address</div>{{ $patient->address ?? '—' }}</div>
            <div class="sm:col-span-3"><div class="text-slate-400 text-xs uppercase tracking-wider">Medical history</div>{{ $patient->medical_history ?? '—' }}</div>
        </div>
    </div>

    <!-- Allergies -->
    <div class="mt-6 rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
        <h3 class="font-display text-lg font-bold">Allergies</h3>
        <div class="mt-3 flex flex-wrap gap-2">
            @forelse ($patient->allergies as $allergy)
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-medium {{ $allergy->severity->badgeClasses() }}">
                    {{ $allergy->name }} · {{ $allergy->severity->label() }}
                    @can('update', $patient)
                        <form method="POST" action="{{ route('clinic.patients.allergies.destroy', [$patient, $allergy]) }}">
                            @csrf @method('DELETE')
                            <button class="text-current opacity-60 hover:opacity-100">×</button>
                        </form>
                    @endcan
                </span>
            @empty
                <span class="text-sm text-slate-400">No known allergies recorded.</span>
            @endforelse
        </div>
        @can('update', $patient)
            <form method="POST" action="{{ route('clinic.patients.allergies.store', $patient) }}" class="mt-4 flex flex-wrap gap-2 items-end">
                @csrf
                <input type="text" name="name" placeholder="Allergy" required class="h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue" />
                <select name="severity" class="h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue">
                    @foreach (\App\Enums\AllergySeverity::options() as $val => $lbl)
                        <option value="{{ $val }}">{{ $lbl }}</option>
                    @endforeach
                </select>
                <input type="text" name="notes" placeholder="Notes (optional)" class="h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue" />
                <button class="h-10 px-4 rounded-lg bg-slate-800 text-white text-sm font-medium hover:bg-slate-700 transition">Add</button>
            </form>
        @endcan
    </div>

    <!-- Treatment history -->
    <div class="mt-6 rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
        <h3 class="font-display text-lg font-bold">Treatment history</h3>
        <div class="mt-3 space-y-3">
            @forelse ($patient->treatments as $treatment)
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                    <div>
                        <div class="font-medium">{{ $treatment->procedure_name }}</div>
                        <div class="text-xs text-slate-500 mt-0.5">
                            {{ $treatment->treatment_date->format('M j, Y') }}
                            · {{ $treatment->dentist?->name ?? 'Unknown dentist' }}
                            @if ($treatment->service) · {{ $treatment->service->name }} @endif
                        </div>
                        @if ($treatment->notes)<div class="text-sm text-slate-500 mt-1">{{ $treatment->notes }}</div>@endif
                    </div>
                    @can('update', $patient)
                        <form method="POST" action="{{ route('clinic.patients.treatments.destroy', [$patient, $treatment]) }}" onsubmit="return confirm('Remove this treatment?');">
                            @csrf @method('DELETE')
                            <button class="text-red-400 hover:text-red-600 text-sm">Remove</button>
                        </form>
                    @endcan
                </div>
            @empty
                <p class="text-sm text-slate-400">No treatments recorded yet.</p>
            @endforelse
        </div>
        @can('update', $patient)
            <form method="POST" action="{{ route('clinic.patients.treatments.store', $patient) }}" class="mt-4 grid sm:grid-cols-2 gap-3">
                @csrf
                <select name="dentist_id" required class="h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue">
                    <option value="">Attending dentist…</option>
                    @foreach ($dentists as $dentist)
                        <option value="{{ $dentist->id }}">{{ $dentist->name }}</option>
                    @endforeach
                </select>
                <select name="service_id" class="h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue">
                    <option value="">Service (optional)…</option>
                    @foreach ($services as $service)
                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                    @endforeach
                </select>
                <input type="text" name="procedure_name" placeholder="Procedure performed" required class="h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue" />
                <input type="date" name="treatment_date" value="{{ now()->toDateString() }}" required class="h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue" />
                <input type="text" name="notes" placeholder="Notes (optional)" class="sm:col-span-2 h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue" />
                <div><button class="h-10 px-4 rounded-lg bg-slate-800 text-white text-sm font-medium hover:bg-slate-700 transition">Record treatment</button></div>
            </form>
        @endcan
    </div>

    <!-- Procedure recommendations -->
    <div class="mt-6 rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
        <h3 class="font-display text-lg font-bold">Procedure recommendations</h3>
        <div class="mt-3 space-y-3">
            @forelse ($patient->recommendations as $rec)
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                    <div>
                        <div class="font-medium">{{ $rec->recommendation }}</div>
                        <div class="text-xs text-slate-500 mt-0.5">
                            {{ $rec->dentist?->name ?? 'Staff' }}
                            @if ($rec->service) · {{ $rec->service->name }} @endif
                        </div>
                        @if ($rec->notes)<div class="text-sm text-slate-500 mt-1">{{ $rec->notes }}</div>@endif
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $rec->status->badgeClasses() }}">{{ $rec->status->label() }}</span>
                        @if ($canRecommend)
                            <form method="POST" action="{{ route('clinic.patients.recommendations.status', [$patient, $rec]) }}">
                                @csrf @method('PATCH')
                                <select name="status" onchange="this.form.submit()" class="h-8 px-2 rounded-lg border border-slate-200 text-xs outline-none focus:border-brand-blue">
                                    @foreach (\App\Enums\RecommendationStatus::options() as $val => $lbl)
                                        <option value="{{ $val }}" @selected($rec->status->value === $val)>{{ $lbl }}</option>
                                    @endforeach
                                </select>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-400">No recommendations yet.</p>
            @endforelse
        </div>
        @if ($canRecommend)
            <form method="POST" action="{{ route('clinic.patients.recommendations.store', $patient) }}" class="mt-4 grid sm:grid-cols-2 gap-3">
                @csrf
                <input type="text" name="recommendation" placeholder="Recommended procedure" required class="sm:col-span-2 h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue" />
                <select name="service_id" class="h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue">
                    <option value="">Link a service (optional)…</option>
                    @foreach ($services as $service)
                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                    @endforeach
                </select>
                <input type="text" name="notes" placeholder="Notes (optional)" class="h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue" />
                <div><button class="h-10 px-4 rounded-lg bg-slate-800 text-white text-sm font-medium hover:bg-slate-700 transition">Add recommendation</button></div>
            </form>
        @endif
    </div>

    <!-- Appointments -->
    <div class="mt-6 rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
        <h3 class="font-display text-lg font-bold">Appointments</h3>
        <div class="mt-3 space-y-2">
            @forelse ($patient->appointments->sortByDesc('scheduled_at') as $appt)
                <div class="flex items-center justify-between text-sm border-b border-slate-100 pb-2">
                    <div>{{ $appt->scheduled_at->format('M j, Y g:i A') }} · {{ $appt->service?->name ?? '—' }} · {{ $appt->dentist?->name ?? '—' }}</div>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $appt->status->badgeClasses() }}">{{ $appt->status->label() }}</span>
                </div>
            @empty
                <p class="text-sm text-slate-400">No appointments.</p>
            @endforelse
        </div>
    </div>
@endsection
