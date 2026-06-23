@extends('layouts.admin')

@section('title', $patient->fullName())
@section('heading', 'Patient record')

@section('content')
    @php
        $role = auth()->user()->role->value;
        $canRecommend = in_array($role, ['dentist', 'management']);
        // Recommendations are gated until the patient has completed & paid for a visit.
        $hasPaidVisit = $patient->appointments->contains(fn ($a) => $a->status === \App\Enums\AppointmentStatus::Completed);
    @endphp

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

    <!-- Clinical intake (feeds the recommendation model) -->
    @php $intake = $patient->intake; @endphp
    <div class="mt-6 rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
        <h3 class="font-display text-lg font-bold">Clinical intake</h3>
        <p class="text-xs text-slate-400 mt-0.5">Symptoms, oral-health behaviors &amp; clinical indicators used for AI procedure suggestions.</p>
        @if ($canRecommend || in_array($role, ['receptionist']))
            <form method="POST" action="{{ route('clinic.patients.intake.save', $patient) }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <div class="text-xs uppercase tracking-wider text-slate-400 mb-2">Symptoms</div>
                    <div class="flex flex-wrap gap-3 text-sm">
                        @foreach (['toothache'=>'Toothache','sensitivity'=>'Sensitivity','bleeding_gums'=>'Bleeding gums','bad_breath'=>'Bad breath','swelling'=>'Swelling'] as $f => $lbl)
                            <label class="inline-flex items-center gap-2"><input type="checkbox" name="{{ $f }}" value="1" @checked($intake?->$f) class="rounded border-slate-300 text-brand-blue focus:ring-brand-blue/30" /> {{ $lbl }}</label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wider text-slate-400 mb-2">Oral-health behaviors</div>
                    <div class="grid sm:grid-cols-3 gap-3 text-sm">
                        <label class="flex flex-col gap-1">Brushing / day
                            <input type="number" name="brushing_per_day" min="0" max="10" value="{{ $intake?->brushing_per_day ?? 2 }}" class="h-9 px-2 rounded-lg border border-slate-200 outline-none focus:border-brand-blue" /></label>
                        <label class="flex flex-col gap-1">Sugar intake
                            <select name="sugar_level" class="h-9 px-2 rounded-lg border border-slate-200 outline-none focus:border-brand-blue">
                                @foreach (['low'=>'Low','medium'=>'Medium','high'=>'High'] as $v=>$l)<option value="{{ $v }}" @selected(($intake?->sugar_level ?? 'medium')===$v)>{{ $l }}</option>@endforeach
                            </select></label>
                        <label class="flex flex-col gap-1">Months since cleaning
                            <input type="number" name="months_since_cleaning" min="0" max="240" value="{{ $intake?->months_since_cleaning ?? 6 }}" class="h-9 px-2 rounded-lg border border-slate-200 outline-none focus:border-brand-blue" /></label>
                        <label class="inline-flex items-center gap-2 mt-1"><input type="checkbox" name="flosses" value="1" @checked($intake?->flosses) class="rounded border-slate-300 text-brand-blue focus:ring-brand-blue/30" /> Flosses</label>
                        <label class="inline-flex items-center gap-2 mt-1"><input type="checkbox" name="smoker" value="1" @checked($intake?->smoker) class="rounded border-slate-300 text-brand-blue focus:ring-brand-blue/30" /> Smoker</label>
                    </div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wider text-slate-400 mb-2">Clinical indicators</div>
                    <div class="grid sm:grid-cols-3 gap-3 text-sm">
                        <label class="inline-flex items-center gap-2"><input type="checkbox" name="visible_plaque" value="1" @checked($intake?->visible_plaque) class="rounded border-slate-300 text-brand-blue focus:ring-brand-blue/30" /> Visible plaque</label>
                        <label class="inline-flex items-center gap-2"><input type="checkbox" name="decay_observed" value="1" @checked($intake?->decay_observed) class="rounded border-slate-300 text-brand-blue focus:ring-brand-blue/30" /> Decay observed</label>
                        <label class="flex flex-col gap-1">Gum condition
                            <select name="gum_condition" class="h-9 px-2 rounded-lg border border-slate-200 outline-none focus:border-brand-blue">
                                @foreach (['healthy'=>'Healthy','gingivitis'=>'Gingivitis','periodontitis'=>'Periodontitis'] as $v=>$l)<option value="{{ $v }}" @selected(($intake?->gum_condition ?? 'healthy')===$v)>{{ $l }}</option>@endforeach
                            </select></label>
                        <label class="flex flex-col gap-1">Missing teeth
                            <input type="number" name="missing_teeth" min="0" max="32" value="{{ $intake?->missing_teeth ?? 0 }}" class="h-9 px-2 rounded-lg border border-slate-200 outline-none focus:border-brand-blue" /></label>
                        <label class="flex flex-col gap-1">Existing fillings
                            <input type="number" name="existing_fillings" min="0" max="32" value="{{ $intake?->existing_fillings ?? 0 }}" class="h-9 px-2 rounded-lg border border-slate-200 outline-none focus:border-brand-blue" /></label>
                    </div>
                </div>
                <input type="text" name="notes" placeholder="Notes (optional)" value="{{ $intake?->notes }}" class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue" />
                <button class="h-10 px-4 rounded-lg bg-slate-800 text-white text-sm font-medium hover:bg-slate-700 transition">Save intake</button>
            </form>
        @else
            <p class="mt-3 text-sm text-slate-400">No intake on file.</p>
        @endif
    </div>

    <!-- AI suggested procedures (regression) -->
    @if ($suggestions->isNotEmpty())
        <div class="mt-6 rounded-2xl bg-white border border-brand-blue/20 p-6 shadow-soft">
            <h3 class="font-display text-lg font-bold">Suggested procedures <span class="text-xs font-normal text-slate-400">· AI decision support (Regression)</span></h3>
            <p class="text-xs text-slate-400 mt-0.5">Based on the clinical intake. The dentist decides — accept to add as a recommendation.</p>
            <div class="mt-3 space-y-2">
                @foreach ($suggestions as $s)
                    @php $svc = $services->firstWhere('name', $s->service); @endphp
                    <div class="flex items-center gap-3">
                        <div class="w-40 shrink-0 text-sm font-medium">{{ $s->label }}</div>
                        <div class="flex-1 h-2.5 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full {{ $s->score >= 0.6 ? 'bg-brand-green' : ($s->score >= 0.35 ? 'bg-amber-400' : 'bg-slate-300') }}" style="width: {{ round($s->score * 100) }}%"></div>
                        </div>
                        <div class="w-12 text-right text-sm text-slate-500">{{ round($s->score * 100) }}%</div>
                        @if ($canRecommend)
                            <form method="POST" action="{{ route('clinic.patients.recommendations.store', $patient) }}">
                                @csrf
                                <input type="hidden" name="recommendation" value="{{ $s->label }}" />
                                @if ($svc)<input type="hidden" name="service_id" value="{{ $svc->id }}" />@endif
                                <input type="hidden" name="notes" value="Suggested by AI ({{ round($s->score * 100) }}% indicated)" />
                                <button class="h-8 px-3 rounded-lg bg-brand-blue/10 text-brand-blue text-xs font-semibold hover:bg-brand-blue/20 transition">Accept</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

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
                        <div class="flex items-center gap-3 shrink-0">
                            <a href="{{ route('clinic.patients.treatments.edit', [$patient, $treatment]) }}" class="text-brand-blue hover:underline text-sm">Edit</a>
                            <form method="POST" action="{{ route('clinic.patients.treatments.destroy', [$patient, $treatment]) }}" data-confirm="Remove this treatment record?">
                                @csrf @method('DELETE')
                                <button class="text-red-400 hover:text-red-600 text-sm">Remove</button>
                            </form>
                        </div>
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
        <div class="flex items-center justify-between">
            <h3 class="font-display text-lg font-bold">Procedure recommendations</h3>
            @if ($canRecommend && $hasPaidVisit && $patient->recommendations->isNotEmpty())
                <div class="flex gap-2">
                    <a href="{{ route('clinic.patients.recommendations.download', [$patient, 'pdf']) }}" class="h-9 px-3 inline-flex items-center rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-slate-50 transition">Download PDF</a>
                    <a href="{{ route('clinic.patients.recommendations.download', [$patient, 'xlsx']) }}" class="h-9 px-3 inline-flex items-center rounded-lg bg-brand-green/10 text-emerald-700 text-xs font-medium hover:bg-brand-green/20 transition">Excel</a>
                </div>
            @endif
        </div>
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
                            <a href="{{ route('clinic.patients.recommendations.edit', [$patient, $rec]) }}" class="text-brand-blue hover:underline text-xs">Edit</a>
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
        @if ($canRecommend && ! $hasPaidVisit)
            <p class="mt-4 text-sm text-slate-400 rounded-lg bg-slate-50 px-4 py-3">Recommendations can be added once the patient has completed and paid for a visit.</p>
        @endif
        @if ($canRecommend && $hasPaidVisit)
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
            @forelse ($appointments as $appt)
                <div class="flex items-center justify-between text-sm border-b border-slate-100 pb-2">
                    <div>{{ $appt->scheduled_at->format('M j, Y g:i A') }} · {{ $appt->service?->name ?? '—' }} · {{ $appt->dentist?->name ?? '—' }}</div>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $appt->status->badgeClasses() }}">{{ $appt->status->label() }}</span>
                </div>
            @empty
                <p class="text-sm text-slate-400">No appointments.</p>
            @endforelse
        </div>
        <div class="mt-4">{{ $appointments->links() }}</div>
    </div>
@endsection
