@php
    /**
     * Reusable month calendar for picking an appointment date.
     * Day states from PredictiveScheduler::monthOverview():
     *   open (clickable), full (fully booked), closed (dentist off / clinic closed), past.
     * Navigation is capped: current month → +3 months. Disabled days are NOT clickable.
     *
     * Expects: $calMonth (Carbon, first of month), $calDays (Y-m-d => [status, free]),
     *          $calUrl, $calParams (query params to keep), $calTarget (htmx selector),
     *          $calSelected (Y-m-d|null)
     */
    $calSelected = $calSelected ?? null;
    $minMonth = now()->startOfMonth();
    $maxMonth = now()->copy()->addMonths(\App\Services\PredictiveScheduler::MAX_MONTHS_AHEAD)->startOfMonth();
    $prevMonth = $calMonth->copy()->subMonth();
    $nextMonth = $calMonth->copy()->addMonth();
    $navUrl = fn ($m) => $calUrl.'?'.http_build_query(array_merge($calParams, ['cal' => $m->format('Y-m')]));
    $dayUrl = fn ($d) => $calUrl.'?'.http_build_query(array_merge($calParams, ['cal' => $calMonth->format('Y-m'), 'date' => $d]));
    $lead = $calMonth->copy()->startOfMonth()->isoWeekday() - 1; // blanks before the 1st (Mon-first)
@endphp

<div class="rounded-2xl border border-slate-200/60 bg-white p-4">
    <div class="flex items-center justify-between mb-3">
        @if ($prevMonth->gte($minMonth))
            <button type="button" hx-get="{{ $navUrl($prevMonth) }}" hx-target="{{ $calTarget }}" hx-select="{{ $calTarget }}" hx-swap="outerHTML"
                class="h-8 w-8 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 inline-flex items-center justify-center">‹</button>
        @else
            <span class="h-8 w-8 rounded-lg border border-slate-100 text-slate-200 inline-flex items-center justify-center cursor-not-allowed">‹</span>
        @endif

        <div class="font-display font-bold">{{ $calMonth->format('F Y') }}</div>

        @if ($nextMonth->lte($maxMonth))
            <button type="button" hx-get="{{ $navUrl($nextMonth) }}" hx-target="{{ $calTarget }}" hx-select="{{ $calTarget }}" hx-swap="outerHTML"
                class="h-8 w-8 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 inline-flex items-center justify-center">›</button>
        @else
            <span class="h-8 w-8 rounded-lg border border-slate-100 text-slate-200 inline-flex items-center justify-center cursor-not-allowed" title="Booking is open up to 3 months ahead">›</span>
        @endif
    </div>

    <div class="grid grid-cols-7 gap-1 text-center text-[11px] font-medium text-slate-400 mb-1">
        @foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $dow)<div>{{ $dow }}</div>@endforeach
    </div>

    <div class="grid grid-cols-7 gap-1">
        @for ($i = 0; $i < $lead; $i++)<div></div>@endfor
        @foreach ($calDays as $dateKey => $info)
            @php
                $dayNo = (int) substr($dateKey, 8, 2);
                $isSelected = $calSelected === $dateKey;
            @endphp
            @if ($info['status'] === 'open')
                <button type="button" hx-get="{{ $dayUrl($dateKey) }}" hx-target="{{ $calTarget }}" hx-select="{{ $calTarget }}" hx-swap="outerHTML"
                    title="{{ $info['free'] }} slot(s) free"
                    class="h-10 rounded-lg text-sm font-medium transition {{ $isSelected ? 'gradient-brand text-white shadow-brand' : 'border border-emerald-200 bg-emerald-50/60 text-slate-700 hover:border-brand-blue hover:bg-brand-blue/10' }}">
                    {{ $dayNo }}
                </button>
            @elseif ($info['status'] === 'full')
                <div class="h-10 rounded-lg bg-amber-50 border border-amber-100 text-amber-400 text-sm font-medium inline-flex items-center justify-center cursor-not-allowed" title="Fully booked">{{ $dayNo }}</div>
            @elseif ($info['status'] === 'closed')
                <div class="h-10 rounded-lg bg-slate-50 text-slate-300 text-sm inline-flex items-center justify-center cursor-not-allowed" title="Unavailable / clinic closed">{{ $dayNo }}</div>
            @else
                <div class="h-10 rounded-lg text-slate-200 text-sm inline-flex items-center justify-center cursor-not-allowed">{{ $dayNo }}</div>
            @endif
        @endforeach
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-slate-500">
        <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded border border-emerald-200 bg-emerald-50 inline-block"></span> Available</span>
        <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded border border-amber-100 bg-amber-50 inline-block"></span> Fully booked</span>
        <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-slate-100 inline-block"></span> Unavailable / closed</span>
        <span class="ml-auto text-slate-400">Bookable up to 3 months ahead</span>
    </div>
</div>
