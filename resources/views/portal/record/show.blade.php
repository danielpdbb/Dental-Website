@extends('layouts.app')

@section('title', "My record — Bonoan's Dental Clinic")

@section('content')
    <div class="container mx-auto px-6 py-12 max-w-3xl">
        @include('partials.portal-nav')

        <h1 class="font-display text-3xl font-bold">My record</h1>
        <p class="text-sm text-slate-500 mt-1">This information is maintained by the clinic. Contact reception to update it.</p>

        <!-- Details -->
        <div class="mt-6 rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
            <div class="grid sm:grid-cols-2 gap-4 text-sm">
                <div><div class="text-slate-400 text-xs uppercase tracking-wider">Name</div>{{ $patient->fullName() }}</div>
                <div><div class="text-slate-400 text-xs uppercase tracking-wider">Date of birth</div>{{ $patient->date_of_birth?->format('M j, Y') ?? '—' }}</div>
                <div><div class="text-slate-400 text-xs uppercase tracking-wider">Phone</div>{{ $patient->phone ?? '—' }}</div>
                <div><div class="text-slate-400 text-xs uppercase tracking-wider">Blood type</div>{{ $patient->blood_type ?? '—' }}</div>
                <div class="sm:col-span-2"><div class="text-slate-400 text-xs uppercase tracking-wider">Medical history</div>{{ $patient->medical_history ?? '—' }}</div>
            </div>
        </div>

        <!-- Allergies -->
        <div class="mt-6 rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
            <h2 class="font-display text-lg font-bold">Allergies</h2>
            <div class="mt-3 flex flex-wrap gap-2">
                @forelse ($patient->allergies as $allergy)
                    <span class="px-3 py-1 rounded-full text-xs font-medium {{ $allergy->severity->badgeClasses() }}">{{ $allergy->name }} · {{ $allergy->severity->label() }}</span>
                @empty
                    <span class="text-sm text-slate-400">No known allergies on file.</span>
                @endforelse
            </div>
        </div>

        <!-- My dental chart -->
        <div class="mt-6 rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
            <h2 class="font-display text-lg font-bold">My dental chart</h2>
            <p class="text-xs text-slate-400 mt-0.5 mb-3">The latest recorded condition of each tooth. Tap a tooth to see what was done and its history.</p>
            @include('partials.teeth-chart', ['chartMode' => 'view', 'chartId' => 'tooth-mine', 'records' => $teeth, 'historyByFdi' => $teethHistory])
        </div>

        <!-- Treatment history (procedures from completed & paid visits) -->
        <div id="treatment-history" hx-boost="true" hx-target="#treatment-history" hx-select="#treatment-history" hx-swap="outerHTML" hx-push-url="true"
             class="mt-6 rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="font-display text-lg font-bold">Treatment history</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Procedures that have been completed and paid for.</p>
                </div>
                @if ($historyServices->isNotEmpty())
                    <form method="GET" action="{{ route('portal.record') }}">
                        <select name="service" onchange="this.form.requestSubmit()" class="h-9 px-3 min-w-[14rem] rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue">
                            <option value="">All procedures</option>
                            @foreach ($historyServices as $hs)
                                <option value="{{ $hs->service_id }}" @selected((int) $serviceFilter === (int) $hs->service_id)>{{ $hs->procedure_name }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif
            </div>
            <div class="mt-3 space-y-3">
                @forelse ($history as $proc)
                    @php
                        $visit = $proc->appointment;
                        $when = $proc->performed_at ?? $visit?->scheduled_at;
                    @endphp
                    <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                        <div class="min-w-0">
                            <div class="font-medium flex flex-wrap items-center gap-2">
                                {{ $proc->procedure_name }}
                                @if ($proc->toothLabel())
                                    <span class="px-1.5 py-0.5 rounded-md text-[10px] font-medium bg-brand-blue/10 text-brand-blue">{{ $proc->toothLabel() }}</span>
                                @endif
                            </div>
                            <div class="text-xs text-slate-500 mt-0.5">{{ $when?->format('M j, Y · g:i A') }} · {{ $proc->performer?->name ?? 'Dentist' }}</div>
                            @if ($proc->notes)<div class="text-sm text-slate-500 mt-1">{{ $proc->notes }}</div>@endif
                            <button type="button" class="hist-open mt-1 text-xs font-medium text-brand-blue hover:underline" data-target="hist-{{ $proc->id }}">View details →</button>
                        </div>
                        <span class="text-sm text-slate-600 whitespace-nowrap">₱{{ number_format($proc->price, 2) }}</span>
                    </div>

                    {{-- Detail modal for this treatment + its visit --}}
                    <div id="hist-{{ $proc->id }}" class="hist-modal fixed inset-0 z-[90] hidden items-center justify-center bg-slate-900/40 p-4">
                        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-5 max-h-[85vh] overflow-auto">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="font-display font-bold">Treatment details</h3>
                                <button type="button" class="hist-close text-slate-400 hover:text-slate-700 text-2xl leading-none">&times;</button>
                            </div>
                            <div class="text-sm space-y-1.5">
                                <div><span class="text-slate-400">Procedure:</span> <span class="font-medium">{{ $proc->procedure_name }}</span></div>
                                <div><span class="text-slate-400">Tooth:</span> {{ $proc->toothLabel() ?? 'Whole mouth' }}</div>
                                <div><span class="text-slate-400">Date &amp; time:</span> {{ ($when)?->format('l, M j, Y · g:i A') }}</div>
                                <div><span class="text-slate-400">Dentist:</span> {{ $proc->performer?->name ?? $visit?->dentist?->name ?? 'Dentist' }}</div>
                                <div><span class="text-slate-400">Fee:</span> ₱{{ number_format($proc->price, 2) }}</div>
                                @if ($proc->notes)<div><span class="text-slate-400">Clinical notes:</span> {{ $proc->notes }}</div>@endif
                                @if ($visit?->finding?->treatment_done_today)<div><span class="text-slate-400">Treatment done that day:</span> {{ $visit->finding->treatment_done_today }}</div>@endif
                                @if ($visit?->finding?->remarks)<div><span class="text-slate-400">Dentist remarks:</span> {{ $visit->finding->remarks }}</div>@endif
                            </div>
                            @if ($visit && $visit->procedures->count() > 1)
                                <div class="mt-4 pt-3 border-t border-slate-100">
                                    <div class="text-[11px] uppercase tracking-wider text-slate-400 mb-1.5">All procedures this visit</div>
                                    <div class="space-y-1 text-sm">
                                        @foreach ($visit->procedures as $vp)
                                            <div class="flex justify-between gap-3"><span>{{ $vp->procedure_name }}@if ($vp->toothLabel())<span class="text-slate-400"> · {{ $vp->toothLabel() }}</span>@endif</span><span class="text-slate-500 whitespace-nowrap">₱{{ number_format($vp->price, 2) }}</span></div>
                                        @endforeach
                                        <div class="flex justify-between font-semibold border-t border-slate-100 pt-1 mt-1"><span>Visit total</span><span>₱{{ number_format($visit->procedures->sum('price'), 2) }}</span></div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">No completed treatments yet.</p>
                @endforelse
            </div>
            <div class="mt-4">{{ $history->links() }}</div>
        </div>
    </div>

    @push('scripts')
    <script>
    // Delegated (survives htmx swaps of the history list).
    document.addEventListener('click', function (e) {
        var open = e.target.closest('.hist-open');
        if (open) {
            var m = document.getElementById(open.getAttribute('data-target'));
            if (m) { m.classList.remove('hidden'); m.classList.add('flex'); }
            return;
        }
        var modal = e.target.closest('.hist-modal');
        if (modal && (e.target === modal || e.target.closest('.hist-close'))) {
            modal.classList.add('hidden'); modal.classList.remove('flex');
        }
    });
    </script>
    @endpush
@endsection
