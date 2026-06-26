{{-- Compact dentist-recommended follow-ups: a slim summary with "Book recommended"
     (pre-fills the booking form) and "View all" (opens a modal). Expects $recommendations. --}}
@if (! empty($recommendations) && $recommendations->isNotEmpty())
    @php
        $bookUrl = function ($rec) {
            $p = ['dentist_id' => $rec->appointment->dentist_id];
            if ($rec->linked_service_id) { $p['service_ids'] = [$rec->linked_service_id]; }
            if ($rec->suggested_at) { $p['date'] = $rec->suggested_at->toDateString(); $p['pick'] = $rec->suggested_at->format('H:i'); }
            return route('portal.appointments.create', $p).'#book';
        };
        $next = $recommendations->first();
        $count = $recommendations->count();
    @endphp

    <div class="mt-5 rounded-2xl border border-brand-blue/25 bg-brand-blue/5 px-5 py-4" data-recs>
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="min-w-0">
                <div class="text-sm font-semibold text-slate-700">
                    You have {{ $count }} dentist-recommended follow-up{{ $count > 1 ? 's' : '' }}
                </div>
                <div class="text-sm mt-1"><span class="text-slate-500">Recommended next:</span> <span class="font-medium">{{ $next->recommendation }}</span></div>
                @if ($next->suggested_at)
                    <div class="text-xs text-emerald-700 mt-0.5">Suggested schedule: {{ $next->suggested_at->format('l, M j, Y · g:i A') }}</div>
                @endif
                @include('partials._ai-disclaimer', ['kind' => 'recommendation'])
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ $bookUrl($next) }}" class="h-9 px-3 inline-flex items-center rounded-lg gradient-brand text-white text-xs font-semibold hover:opacity-90 transition">Book recommended</a>
                @if ($count > 1)
                    <button type="button" class="recs-open h-9 px-3 inline-flex items-center rounded-lg border border-brand-blue/40 text-brand-blue text-xs font-semibold hover:bg-brand-blue/10 transition">View all</button>
                @endif
            </div>
        </div>

        {{-- Modal with the full list --}}
        <div class="recs-modal fixed inset-0 z-[90] hidden items-center justify-center bg-slate-900/40 p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-5 max-h-[85vh] overflow-auto">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-display font-bold">Recommended follow-ups</h3>
                    <button type="button" class="recs-close text-slate-400 hover:text-slate-700 text-2xl leading-none">&times;</button>
                </div>
                <div class="space-y-3">
                    @foreach ($recommendations as $rec)
                        <div class="rounded-xl border border-slate-200/70 p-4 flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="font-semibold">{{ $rec->recommendation }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    {{ $rec->appointment->dentist?->name ?? 'Your dentist' }}
                                    @if ($rec->service) · {{ $rec->service->name }} @endif
                                    @if ($rec->priority) · {{ $rec->priority->label() }} priority @endif
                                </div>
                                @if ($rec->suggested_at)
                                    <div class="text-xs text-emerald-700 mt-1">Suggested: {{ $rec->suggested_at->format('l, M j, Y · g:i A') }}</div>
                                @endif
                            </div>
                            <div class="shrink-0 flex flex-col items-stretch gap-1.5">
                                <a href="{{ $bookUrl($rec) }}" class="h-8 px-3 inline-flex items-center justify-center rounded-lg gradient-brand text-white text-xs font-semibold hover:opacity-90">Book this</a>
                                <a href="{{ route('portal.recommendations.print', $rec) }}" target="_blank" class="text-xs text-brand-blue hover:underline text-center">Print</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const wrap = document.currentScript.previousElementSibling;
        if (!wrap || wrap.dataset.recsInit) return;
        wrap.dataset.recsInit = '1';
        const modal = wrap.querySelector('.recs-modal');
        const openBtn = wrap.querySelector('.recs-open');
        const closeBtn = wrap.querySelector('.recs-close');
        const show = () => { modal.classList.remove('hidden'); modal.classList.add('flex'); };
        const hide = () => { modal.classList.add('hidden'); modal.classList.remove('flex'); };
        if (openBtn) openBtn.addEventListener('click', show);
        if (closeBtn) closeBtn.addEventListener('click', hide);
        if (modal) modal.addEventListener('click', e => { if (e.target === modal) hide(); });
    })();
    </script>
@endif
