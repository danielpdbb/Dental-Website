@php
    /**
     * Time-slot tiles for one day. Expects: $slots (from PredictiveScheduler::daySlots —
     * each: time, end, status free|taken|past), $duration (min), $date (Carbon),
     * optional $pick (H:i preselect). Free slots are radios named scheduled_at; selecting
     * one highlights every tile it consumes and shows the exact window below.
     */
    $pick = $pick ?? request('pick');
@endphp

<div data-slot-wrap data-duration="{{ (int) $duration }}">
    <div class="text-sm font-medium text-slate-700 mb-1">
        Available times on {{ $date->format('l, M j, Y') }}
        <span class="text-xs text-slate-400 font-normal">(each booking consumes ~{{ $duration }} min)</span>
    </div>
    @error('scheduled_at') <p class="mb-2 text-xs text-red-500">{{ $message }}</p> @enderror

    <div class="grid grid-cols-3 sm:grid-cols-4 gap-2.5 mt-2">
        @foreach ($slots as $slot)
            @if ($slot['status'] === 'free')
                <label class="cursor-pointer" data-slot-tile data-start24="{{ $slot['time']->format('H:i') }}">
                    <input type="radio" name="scheduled_at" value="{{ $slot['time']->format('Y-m-d\TH:i') }}" class="peer sr-only slot-radio" required data-review-label="Schedule"
                        data-range="{{ $slot['time']->format('g:i A') }} – {{ $slot['end']->format('g:i A') }}"
                        data-start24="{{ $slot['time']->format('H:i') }}" data-end24="{{ $slot['end']->format('H:i') }}"
                        data-display="{{ $slot['time']->format('M j, Y · g:i A') }} – {{ $slot['end']->format('g:i A') }}"
                        @checked($pick === $slot['time']->format('H:i')) />
                    <span class="slot-tile-face block text-center rounded-xl border border-slate-200 py-2.5 text-sm font-medium text-slate-700 transition peer-checked:border-brand-blue peer-checked:bg-brand-blue/10 peer-checked:text-brand-blue">
                        {{ $slot['time']->format('g:i A') }}
                    </span>
                </label>
            @elseif ($slot['status'] === 'taken')
                <div class="text-center rounded-xl border border-red-100 bg-red-50/70 py-2.5 text-sm text-red-300 line-through cursor-not-allowed" title="Already booked">
                    {{ $slot['time']->format('g:i A') }}
                </div>
            @else
                <div class="text-center rounded-xl border border-slate-100 bg-slate-50 py-2.5 text-sm text-slate-300 cursor-not-allowed" title="Time has passed">
                    {{ $slot['time']->format('g:i A') }}
                </div>
            @endif
        @endforeach
    </div>

    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-slate-500">
        <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded border border-slate-200 inline-block"></span> Available</span>
        <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-full border-2 border-brand-blue bg-brand-blue/20 inline-block"></span> Consumed by your selection</span>
        <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded border border-red-100 bg-red-50 inline-block"></span> Already booked</span>
        <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-slate-100 inline-block"></span> Passed</span>
    </div>

    {{-- Consumption summary, e.g. "Selected: Jul 15, 2026 · 9:00 AM – 11:00 AM (120 min)" --}}
    <div data-slot-summary class="hidden mt-3 rounded-xl bg-brand-blue/5 border border-brand-blue/20 px-4 py-2.5 text-sm text-brand-blue font-medium"></div>
</div>

<script>
if (!window._slotSummaryWired) {
    window._slotSummaryWired = true;
    // Delegated + reads duration/values fresh from data-* attributes every time, so it
    // never goes stale after an htmx swap re-renders this partial with new services/duration.
    document.addEventListener('change', function (e) {
        if (!e.target.classList || !e.target.classList.contains('slot-radio')) return;
        var radio = e.target;
        var wrap = radio.closest('[data-slot-wrap]');
        if (!wrap) return;
        var duration = wrap.getAttribute('data-duration') || '0';

        var out = wrap.querySelector('[data-slot-summary]');
        if (out) {
            out.textContent = 'Selected: ' + radio.getAttribute('data-display') + ' (' + duration + ' min)';
            out.classList.remove('hidden');
        }

        // Highlight every tile within [start, end) of the chosen slot.
        var start = radio.getAttribute('data-start24');
        var end = radio.getAttribute('data-end24');
        wrap.querySelectorAll('[data-slot-tile]').forEach(function (tile) {
            var t = tile.getAttribute('data-start24');
            var face = tile.querySelector('.slot-tile-face');
            var within = t >= start && t < end;
            face.classList.toggle('ring-2', within);
            face.classList.toggle('ring-brand-blue/40', within);
            face.classList.toggle('bg-brand-blue/5', within);
        });
    });
}
</script>
