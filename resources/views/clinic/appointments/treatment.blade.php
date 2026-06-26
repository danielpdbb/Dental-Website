@extends('layouts.admin')

@section('title', 'Current treatment')
@section('heading', 'Current treatment')

@php
    use App\Enums\AppointmentStatus;
    use App\Enums\ProcedureStatus;
    $open = ! in_array($appointment->status, [AppointmentStatus::ForBilling, AppointmentStatus::Billed, AppointmentStatus::Completed, AppointmentStatus::Cancelled, AppointmentStatus::NoShow], true);
    $canEdit = $canEdit ?? true;        // is this the assigned dentist / management?
    $editable = $open && $canEdit;      // may write to this visit
    $performedCount = $appointment->procedures->where('status', ProcedureStatus::Performed)->count();
@endphp

@section('content')
    <a href="{{ route('clinic.my-schedule') }}" class="text-sm text-slate-500 hover:text-brand-blue">← Back to my schedule</a>

    <div class="mt-4 grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Session header --}}
            <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="font-display text-xl font-bold">{{ $appointment->patient?->fullName() ?? '—' }}</h2>
                        <p class="text-sm text-slate-500 mt-1">{{ $appointment->scheduled_at->format('l, M j, Y · g:i A') }}</p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-medium {{ $appointment->status->badgeClasses() }}">{{ $appointment->status->label() }}</span>
                </div>

                @if (! $canEdit)
                    <div class="mt-4 rounded-xl bg-slate-50 border border-slate-200 text-slate-600 text-sm px-4 py-3">
                        Read-only view — this visit is handled by {{ $appointment->dentist?->name ?? 'another dentist' }}. You can review the record but not edit it.
                    </div>
                @elseif (! $open)
                    <div class="mt-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 text-sm px-4 py-3">
                        This session has been endorsed/billed and is read-only.
                    </div>
                @endif
            </div>

            {{-- Stage 1: pre-visit assessment + possible current treatment --}}
            <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
                <h3 class="font-display text-lg font-bold mb-1">Pre-visit assessment</h3>
                <p class="text-xs text-slate-400 mb-4">The patient&rsquo;s answers and the AI&rsquo;s possible current treatment — to help you prepare. You make the final decision.</p>

                @if ($appointment->intake)
                    <div class="grid sm:grid-cols-2 gap-x-6 gap-y-1.5 text-sm">
                        <div class="flex justify-between"><span class="text-slate-500">Main concern</span><span class="font-medium">{{ $appointment->intake->main_concern ?? '—' }}</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Pain level</span><span class="font-medium">{{ $appointment->intake->pain_level }}/10</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Brushing/day</span><span class="font-medium">{{ $appointment->intake->brushing_per_day }}</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Months since cleaning</span><span class="font-medium">{{ $appointment->intake->months_since_cleaning }}</span></div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        @foreach (['toothache' => 'Toothache', 'sensitivity' => 'Sensitivity', 'bleeding_gums' => 'Bleeding gums', 'bad_breath' => 'Bad breath', 'swelling' => 'Swelling', 'smoker' => 'Smoker'] as $f => $lbl)
                            @if ($appointment->intake->$f)
                                <span class="px-2 py-0.5 rounded-full text-xs bg-amber-100 text-amber-700">{{ $lbl }}</span>
                            @endif
                        @endforeach
                    </div>

                    @if ($stage1)
                        <div class="mt-4 rounded-xl border border-brand-blue/30 bg-brand-blue/5 p-4">
                            <div class="text-xs text-brand-blue font-medium">AI suggestion — possible current treatment <span class="text-slate-400 font-normal">(staff only)</span></div>
                            <div class="font-semibold mt-1">{{ $stage1->recommendation }}</div>
                            @if ($stage1->confidence !== null)<div class="text-xs text-slate-500 mt-0.5">Confidence {{ round($stage1->confidence * 100) }}%</div>@endif
                            @include('partials._ai-disclaimer', ['kind' => 'recommendation'])
                            @if ($editable && $stage1->linked_service_id && ! $appointment->procedures->contains('service_id', $stage1->linked_service_id))
                                <form method="POST" action="{{ route('appointments.pre-visit.add', [$appointment, $stage1]) }}" class="mt-2">
                                    @csrf
                                    <button class="h-9 px-3 rounded-lg bg-brand-blue text-white text-xs font-medium hover:opacity-90">+ Add suggested treatment to this visit</button>
                                </form>
                            @endif
                        </div>
                    @endif

                    {{-- Dentist/management may revise the assessment on initial diagnosis. --}}
                    @if ($editable)
                        <details class="mt-3">
                            <summary class="cursor-pointer text-sm text-brand-blue hover:underline list-none">Edit assessment</summary>
                            <p class="text-xs text-slate-400 mt-2">Adjust the answers from your initial diagnosis — the suggestion will be regenerated.</p>
                            @include('clinic.appointments._intake-form', ['appointment' => $appointment])
                        </details>
                    @endif
                @else
                    <p class="text-sm text-slate-400">The patient hasn&rsquo;t filled the pre-visit assessment.</p>
                    <details class="mt-2">
                        <summary class="cursor-pointer text-sm text-brand-blue hover:underline list-none">Fill it on their behalf</summary>
                        @include('clinic.appointments._intake-form', ['appointment' => $appointment])
                    </details>
                @endif
            </div>

            {{-- Procedures (current treatment) --}}
            <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
                <h3 class="font-display text-lg font-bold mb-1">Procedures</h3>
                <p class="text-xs text-slate-400 mb-4">Add the procedures for this visit, then mark each one performed.</p>

                <div class="border border-slate-100 rounded-xl divide-y divide-slate-100">
                    @forelse ($appointment->procedures as $proc)
                        <div class="flex items-center justify-between gap-3 px-4 py-3">
                            <div class="min-w-0">
                                <div class="font-medium text-sm flex items-center gap-2">
                                    {{ $proc->procedure_name }}
                                    @if ($proc->toothLabel())
                                        <span class="px-1.5 py-0.5 rounded-md text-[10px] font-medium bg-brand-blue/10 text-brand-blue">{{ $proc->toothLabel() }}</span>
                                    @endif
                                </div>
                                <div class="text-xs text-slate-400">
                                    ₱{{ number_format($proc->price, 2) }} · {{ $proc->duration_minutes }} min
                                    @if ($proc->performer) · by {{ $proc->performer->name }} @endif
                                </div>
                                @if ($proc->notes)<div class="text-xs text-slate-500 mt-0.5">{{ $proc->notes }}</div>@endif
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $proc->status->badgeClasses() }}">{{ $proc->status->label() }}</span>
                                @if ($editable)
                                    <form method="POST" action="{{ route('clinic.appointments.treatment.toggle', [$appointment, $proc]) }}">
                                        @csrf @method('PATCH')
                                        <button class="text-xs font-medium {{ $proc->isPerformed() ? 'text-slate-500 hover:underline' : 'text-emerald-600 hover:underline' }}">
                                            {{ $proc->isPerformed() ? 'Undo' : 'Mark performed' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('clinic.appointments.treatment.remove', [$appointment, $proc]) }}" data-confirm="Remove this procedure?">
                                        @csrf @method('DELETE')
                                        <button class="text-xs text-red-500 hover:underline">Remove</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-6 text-sm text-slate-400 text-center">No procedures yet. Add one below.</div>
                    @endforelse
                </div>

                @if ($editable)
                    <form method="POST" action="{{ route('clinic.appointments.treatment.add', $appointment) }}" class="mt-4 flex flex-wrap items-end gap-2">
                        @csrf
                        <div class="flex-1 min-w-[12rem]">
                            <label class="block text-xs font-medium text-slate-500 mb-1">Add procedure</label>
                            <select name="service_id" required class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue">
                                <option value="">— Select a service —</option>
                                @foreach ($services as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }} — ₱{{ number_format($s->price, 2) }} ({{ $s->duration_minutes }}m)</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="min-w-[9rem]">
                            <label class="block text-xs font-medium text-slate-500 mb-1">Tooth <span class="text-slate-300 font-normal">(optional)</span></label>
                            <select name="tooth_fdi" class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue">
                                <option value="">— Whole mouth —</option>
                                @foreach (\App\Models\ToothRecord::FDI_UNIVERSAL as $fdi => $uni)
                                    <option value="{{ $fdi }}">Tooth {{ $fdi }} (Univ {{ $uni }})</option>
                                @endforeach
                            </select>
                        </div>
                        <input type="text" name="notes" placeholder="Notes (optional)" class="h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue flex-1 min-w-[10rem]" />
                        <button class="h-10 px-4 rounded-lg bg-slate-800 text-white text-sm font-medium hover:bg-slate-700 transition">Add</button>
                    </form>
                @endif
            </div>

            {{-- Interactive dental chart --}}
            <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
                <h3 class="font-display text-lg font-bold mb-1">Dental chart</h3>
                <p class="text-xs text-slate-400 mb-4">Click a tooth to record its condition, link it to a procedure on this visit, and add treatment/medicine/observations. Toggle <span class="font-medium text-brand-blue">full patient history</span> to see every visit&rsquo;s state — you can still record for this visit.</p>
                @include('partials.teeth-chart', [
                    'chartMode' => $editable ? 'edit' : 'view',
                    'chartId' => 'tooth-rx',
                    'records' => $teethRecords,
                    'recordsAll' => $teethAll,
                    'historyByFdi' => $teethHistory,
                    'procedures' => $editable ? $teethProcedures : [],
                    'saveUrl' => $editable ? route('clinic.appointments.teeth.store', $appointment) : null,
                ])
            </div>

            {{-- Stage 2: clinical findings → next-visit recommendation --}}
            <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
                <h3 class="font-display text-lg font-bold mb-1">Clinical findings &amp; next visit</h3>
                <p class="text-xs text-slate-400 mb-4">Record what you observed; the system suggests the recommended next visit for you to verify.</p>

                @php $f = $appointment->finding; @endphp
                @if (! $editable)
                    @if ($f)
                        <div class="grid sm:grid-cols-2 gap-x-6 gap-y-1.5 text-sm">
                            <div class="flex justify-between"><span class="text-slate-500">Cavity found</span><span class="font-medium">{{ $f->cavity_found ? 'Yes' : 'No' }}</span></div>
                            <div class="flex justify-between"><span class="text-slate-500">Gum inflammation</span><span class="font-medium">{{ ucfirst($f->gum_inflammation) }}</span></div>
                            <div class="flex justify-between"><span class="text-slate-500">Plaque level</span><span class="font-medium">{{ ucfirst($f->plaque_level) }}</span></div>
                            <div class="flex justify-between"><span class="text-slate-500">Infection signs</span><span class="font-medium">{{ ucfirst($f->infection_signs) }}</span></div>
                            <div class="flex justify-between"><span class="text-slate-500">X-ray needed</span><span class="font-medium">{{ $f->xray_needed ? 'Yes' : 'No' }}</span></div>
                            @if ($f->treatment_done_today)<div class="flex justify-between"><span class="text-slate-500">Treatment today</span><span class="font-medium">{{ $f->treatment_done_today }}</span></div>@endif
                        </div>
                        @if ($f->remarks)<p class="mt-2 text-sm text-slate-500">{{ $f->remarks }}</p>@endif
                    @else
                        <p class="text-sm text-slate-400">No clinical findings recorded for this visit.</p>
                    @endif
                @else
                <form method="POST" action="{{ route('clinic.appointments.findings.save', $appointment) }}" class="grid sm:grid-cols-2 gap-3 text-sm">
                    @csrf
                    <label class="flex items-center gap-2"><input type="checkbox" name="cavity_found" value="1" @checked($f?->cavity_found) class="rounded border-slate-300"> Cavity found</label>
                    <label class="flex items-center gap-2"><input type="checkbox" name="tooth_mobility" value="1" @checked($f?->tooth_mobility) class="rounded border-slate-300"> Tooth mobility</label>
                    <label class="flex items-center gap-2"><input type="checkbox" name="xray_needed" value="1" @checked($f?->xray_needed) class="rounded border-slate-300"> X-ray needed</label>
                    <div></div>
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Gum inflammation</label>
                        <select name="gum_inflammation" class="w-full h-9 px-2 rounded-lg border border-slate-200">
                            @foreach (['none' => 'None', 'mild' => 'Mild', 'moderate' => 'Moderate', 'severe' => 'Severe'] as $v => $l)
                                <option value="{{ $v }}" @selected(($f?->gum_inflammation ?? 'none') === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Plaque level</label>
                        <select name="plaque_level" class="w-full h-9 px-2 rounded-lg border border-slate-200">
                            @foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'] as $v => $l)
                                <option value="{{ $v }}" @selected(($f?->plaque_level ?? 'low') === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Infection signs</label>
                        <select name="infection_signs" class="w-full h-9 px-2 rounded-lg border border-slate-200">
                            @foreach (['none' => 'None', 'possible' => 'Possible', 'present' => 'Present'] as $v => $l)
                                <option value="{{ $v }}" @selected(($f?->infection_signs ?? 'none') === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Treatment done today</label>
                        <input type="text" name="treatment_done_today" value="{{ $f?->treatment_done_today }}" class="w-full h-9 px-2 rounded-lg border border-slate-200" placeholder="e.g. Temporary filling" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs text-slate-500 mb-1">Dentist remarks</label>
                        <textarea name="remarks" rows="2" class="w-full px-2 py-1.5 rounded-lg border border-slate-200">{{ $f?->remarks }}</textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <button class="h-10 px-4 rounded-lg bg-slate-800 text-white text-sm font-medium hover:bg-slate-700 transition">Save findings &amp; generate next-visit suggestion</button>
                    </div>
                </form>
                @endif

                @if ($stage2)
                    <div class="mt-5">
                        @include('clinic.appointments._recommendation-review', ['rec' => $stage2, 'canEdit' => $canEdit])
                    </div>
                @endif
            </div>
        </div>

        {{-- Endorse --}}
        <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft h-fit">
            <h3 class="font-display text-lg font-bold">Endorse to reception</h3>
            <div class="mt-3 space-y-1 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">Procedures</span><span class="font-medium">{{ $appointment->procedures->count() }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Performed</span><span class="font-medium text-emerald-600">{{ $performedCount }}</span></div>
                <div class="flex justify-between border-t border-slate-100 pt-1.5 mt-1"><span class="text-slate-500">Total</span><span class="font-display font-bold">₱{{ number_format($appointment->total_amount, 2) }}</span></div>
            </div>

            @if ($editable)
                @php $unperformed = $appointment->procedures->count() - $performedCount; @endphp
                <button type="button" id="endorse-open" class="mt-4 w-full h-11 rounded-xl gradient-brand text-white font-semibold shadow-brand hover:opacity-90 transition disabled:opacity-50" {{ $performedCount === 0 ? 'disabled' : '' }}>
                    Endorse for billing
                </button>
                @if ($performedCount === 0)
                    <p class="mt-2 text-xs text-slate-400">Mark at least one procedure performed to endorse.</p>
                @endif

                {{-- Endorse confirmation (warns about unperformed procedures + read-only lock) --}}
                <div id="endorse-modal" class="fixed inset-0 z-[95] hidden items-center justify-center bg-slate-900/40 p-4">
                    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-5">
                        <h4 class="font-display text-lg font-bold">Endorse to reception?</h4>
                        <p class="text-sm text-slate-500 mt-1">This sends the visit to reception for billing. <strong class="text-slate-700">Once endorsed, the treatment record becomes read-only and can no longer be edited.</strong></p>

                        @if ($unperformed > 0)
                            <div class="mt-3 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                ⚠ <strong>{{ $unperformed }}</strong> procedure(s) are <strong>not marked performed</strong>. Only the <strong>{{ $performedCount }}</strong> performed procedure(s) will be billed — the rest are dropped.
                            </div>
                            <label class="mt-3 flex items-start gap-2 text-sm text-slate-600">
                                <input type="checkbox" id="endorse-confirm-chk" class="mt-0.5 rounded border-slate-300">
                                <span>I confirm that only the procedures marked performed were completed, and the others were not.</span>
                            </label>
                        @endif

                        <div class="mt-4 flex justify-end gap-2">
                            <button type="button" id="endorse-cancel" class="h-10 px-4 rounded-lg border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancel</button>
                            <form method="POST" action="{{ route('clinic.appointments.treatment.endorse', $appointment) }}">
                                @csrf
                                <button type="submit" id="endorse-proceed" class="h-10 px-4 rounded-lg gradient-brand text-white text-sm font-semibold hover:opacity-90 disabled:opacity-50" {{ $unperformed > 0 ? 'disabled' : '' }}>Proceed &amp; endorse</button>
                            </form>
                        </div>
                    </div>
                </div>
                <script>
                (function () {
                    var open = document.getElementById('endorse-open');
                    var modal = document.getElementById('endorse-modal');
                    var cancel = document.getElementById('endorse-cancel');
                    var chk = document.getElementById('endorse-confirm-chk');
                    var proceed = document.getElementById('endorse-proceed');
                    if (!open || !modal) return;
                    function show() { modal.classList.remove('hidden'); modal.classList.add('flex'); }
                    function hide() { modal.classList.add('hidden'); modal.classList.remove('flex'); }
                    open.addEventListener('click', show);
                    cancel.addEventListener('click', hide);
                    modal.addEventListener('click', function (e) { if (e.target === modal) hide(); });
                    if (chk && proceed) chk.addEventListener('change', function () { proceed.disabled = !chk.checked; });
                })();
                </script>
            @elseif (! $open)
                <p class="mt-4 text-sm text-emerald-600 font-medium">✓ Already endorsed</p>
            @endif
        </div>
    </div>
@endsection
