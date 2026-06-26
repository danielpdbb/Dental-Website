@extends('layouts.admin')

@section('title', $patient->fullName())
@section('heading', 'Patient record')

@section('content')
    @php $role = auth()->user()->role->value; @endphp

    <div class="flex items-center justify-between mb-5">
        <a href="{{ route('clinic.patients.index') }}" class="text-sm text-slate-500 hover:text-brand-blue">← All patients</a>
        <div class="flex gap-2">
            @can('update', $patient)
                <a href="{{ route('clinic.patients.edit', $patient) }}" class="h-9 px-4 inline-flex items-center rounded-lg border border-slate-200 text-sm font-medium hover:bg-slate-50 transition">Edit</a>
            @endcan
            @can('delete', $patient)
                <form method="POST" action="{{ route('clinic.patients.destroy', $patient) }}" data-confirm="Remove the record for {{ $patient->fullName() }}?">
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
            <div><div class="text-slate-400 text-xs uppercase tracking-wider">Outstanding balance</div><span class="font-semibold {{ $patient->outstandingBalance() > 0 ? 'text-red-500' : 'text-emerald-600' }}">₱{{ number_format($patient->outstandingBalance(), 2) }}</span></div>
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
                        <form method="POST" action="{{ route('clinic.patients.allergies.destroy', [$patient, $allergy]) }}"
                            data-confirm="Remove the allergy “{{ $allergy->name }}”?">
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

    <!-- Clinical summary (per visit) -->
    <div id="clinical-summary" hx-boost="true" hx-target="#clinical-summary" hx-select="#clinical-summary" hx-swap="outerHTML" hx-push-url="true"
         class="mt-6 rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
        <div class="flex items-start justify-between gap-3 flex-wrap">
            <div>
                <h3 class="font-display text-lg font-bold">Clinical summary</h3>
                <p class="text-xs text-slate-400 mt-0.5">What happened each visit — findings, treatment done, and the recommended follow-up.</p>
            </div>
            <form method="GET" action="{{ route('clinic.patients.show', $patient) }}" class="flex flex-wrap gap-2">
                <select name="cs_dentist" onchange="this.form.requestSubmit()" class="h-9 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue">
                    <option value="">All dentists</option>
                    @foreach ($csDentists as $d)
                        <option value="{{ $d->id }}" @selected($csDentist === $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
                <select name="cs_service" onchange="this.form.requestSubmit()" class="h-9 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue">
                    <option value="">All procedures</option>
                    @foreach ($csServices as $s)
                        <option value="{{ $s->id }}" @selected($csService === $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="appt_status" value="{{ $apptStatus }}">
            </form>
        </div>

        <div class="mt-4 space-y-3">
            @forelse ($clinicalVisits as $appt)
                @php $f = $appt->finding; $rec = $appt->recommendations->first(); $done = $appt->procedures; @endphp
                <article class="rounded-xl border border-slate-200 overflow-hidden">
                    <header class="flex items-center justify-between gap-3 bg-slate-50 px-4 py-2.5 border-b border-slate-100">
                        <div class="min-w-0">
                            <div class="font-semibold text-slate-800">{{ $appt->scheduled_at?->format('M j, Y') }}</div>
                            <div class="text-xs text-slate-500">{{ $appt->scheduled_at?->format('g:i A') }} · {{ $appt->dentist?->name ?? 'Dentist' }}</div>
                        </div>
                        <a href="{{ route('clinic.appointments.treatment', $appt) }}"
                           class="shrink-0 h-8 px-3 inline-flex items-center gap-1 rounded-lg bg-brand-blue text-white text-xs font-semibold hover:opacity-90 transition">
                            View visit
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </header>

                    <div class="grid sm:grid-cols-2 divide-y sm:divide-y-0 sm:divide-x divide-slate-100">
                        <div class="p-4">
                            <div class="text-[11px] uppercase tracking-wider text-slate-400 mb-1.5">Treatment done</div>
                            @if ($done->isNotEmpty())
                                <ul class="space-y-1 text-sm">
                                    @foreach ($done as $proc)
                                        <li class="flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>{{ $proc->procedure_name }}</li>
                                    @endforeach
                                </ul>
                            @elseif ($f?->treatment_done_today)
                                <div class="text-sm text-slate-700">{{ $f->treatment_done_today }}</div>
                            @else
                                <div class="text-sm text-slate-400">No procedure recorded.</div>
                            @endif
                        </div>
                        <div class="p-4">
                            <div class="text-[11px] uppercase tracking-wider text-slate-400 mb-1.5">Findings</div>
                            @if ($f)
                                <div class="flex flex-wrap gap-1.5 text-xs">
                                    @if ($f->cavity_found)<span class="px-2 py-0.5 rounded-full bg-red-100 text-red-600 font-medium">Cavity</span>@endif
                                    @if ($f->gum_inflammation !== 'none')<span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">Gum: {{ ucfirst($f->gum_inflammation) }}</span>@endif
                                    <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">Plaque: {{ ucfirst($f->plaque_level) }}</span>
                                    @if ($f->infection_signs !== 'none')<span class="px-2 py-0.5 rounded-full bg-purple-100 text-purple-700">Infection: {{ ucfirst($f->infection_signs) }}</span>@endif
                                    @if ($f->xray_needed)<span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">X-ray needed</span>@endif
                                </div>
                                @if ($f->remarks)<div class="text-sm text-slate-600 mt-1.5">{{ $f->remarks }}</div>@endif
                            @else
                                <div class="text-sm text-slate-400">No findings recorded.</div>
                            @endif
                        </div>
                    </div>

                    @if ($rec)
                        <div class="px-4 py-3 bg-brand-blue/5 border-t border-brand-blue/15">
                            <div class="text-[11px] uppercase tracking-wider text-brand-blue mb-0.5">Recommended follow-up</div>
                            <div class="text-sm font-semibold text-slate-800">{{ $rec->recommendation }}@if ($rec->priority) <span class="ml-1 text-xs font-medium px-1.5 py-0.5 rounded {{ $rec->priority->badgeClasses() }}">{{ $rec->priority->label() }}</span>@endif</div>
                            @if ($rec->suggested_at)<div class="text-xs text-emerald-700 mt-0.5">Suggested schedule: <span class="font-medium">{{ $rec->suggested_at->format('M j, Y · g:i A') }}</span></div>@endif
                            @include('partials._ai-disclaimer', ['kind' => 'recommendation'])
                        </div>
                    @endif
                </article>
            @empty
                <p class="text-sm text-slate-400 py-4 text-center">No clinical findings or recommendations recorded yet.</p>
            @endforelse
        </div>

        @if ($clinicalVisits->hasPages())
            <div class="mt-4">{{ $clinicalVisits->links() }}</div>
        @endif
    </div>

    <!-- Interactive dental chart (latest condition per tooth) -->
    <div class="mt-6 rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
        <h3 class="font-display text-lg font-bold mb-1">Dental chart</h3>
        <p class="text-xs text-slate-400 mb-4">Latest recorded condition per tooth across all visits. Click a tooth to see its last procedure and full history.</p>
        @include('partials.teeth-chart', ['chartMode' => 'view', 'chartId' => 'tooth-view', 'records' => $teeth, 'historyByFdi' => $teethHistory])
    </div>

    <!-- Appointments -->
    <div id="appt-list" hx-boost="true" hx-target="#appt-list" hx-select="#appt-list" hx-swap="outerHTML" hx-push-url="true"
         class="mt-6 rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
        <div class="flex items-center justify-between gap-3">
            <h3 class="font-display text-lg font-bold">Appointments</h3>
            <form method="GET" action="{{ route('clinic.patients.show', $patient) }}">
                <select name="appt_status" onchange="this.form.requestSubmit()" class="h-9 px-3 min-w-[12rem] rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue">
                    <option value="">All statuses</option>
                    @foreach ($apptStatuses as $val => $lbl)
                        <option value="{{ $val }}" @selected($apptStatus === $val)>{{ $lbl }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="cs_dentist" value="{{ $csDentist }}">
                <input type="hidden" name="cs_service" value="{{ $csService }}">
            </form>
        </div>
        <div class="mt-3 divide-y divide-slate-100">
            @forelse ($appointments as $appt)
                <div class="flex items-center justify-between gap-3 py-3">
                    <div class="min-w-0">
                        <div class="font-medium text-sm text-slate-800 truncate">{{ $appt->service?->name ?? '—' }}</div>
                        <div class="text-xs text-slate-500 mt-0.5">{{ $appt->scheduled_at->format('M j, Y · g:i A') }} · {{ $appt->dentist?->name ?? 'No dentist' }}</div>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $appt->status->badgeClasses() }}">{{ $appt->status->label() }}</span>
                        @if (in_array($role, ['dentist', 'management']))
                            <a href="{{ route('clinic.appointments.treatment', $appt) }}" hx-boost="false" class="h-8 px-3 inline-flex items-center gap-1 rounded-lg border border-brand-blue text-brand-blue text-xs font-semibold hover:bg-brand-blue/10 transition">View visit</a>
                        @elseif ($role === 'receptionist')
                            <a href="{{ route('clinic.appointments.show', $appt) }}" hx-boost="false" class="h-8 px-3 inline-flex items-center rounded-lg border border-brand-blue text-brand-blue text-xs font-semibold hover:bg-brand-blue/10 transition">View</a>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-400 py-3">No appointments.</p>
            @endforelse
        </div>
        <div class="mt-4">{{ $appointments->links() }}</div>
    </div>
@endsection
