{{-- Available-time tiles for the front desk. Swapped in by htmx when the dentist,
     date or services change. Always renders #slots so hx-select can find it. --}}
<div id="slots">
    @if ($selected->isNotEmpty())
        <div class="mb-3 flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3 text-sm">
            <span class="text-slate-500">{{ $selected->count() }} service(s) · ~{{ $duration }} min total</span>
            <span class="font-display font-bold text-gradient-brand">₱{{ number_format($selected->sum('price'), 2) }}</span>
        </div>
    @endif

    <div class="text-sm font-medium text-slate-700 mb-2">Available times @if ($dentist && $selected->isNotEmpty())on {{ $date->format('l, M j') }}@endif</div>
    @error('scheduled_at') <p class="mb-2 text-xs text-red-500">{{ $message }}</p> @enderror

    @if (is_null($slots))
        <p class="text-sm text-slate-400">Select at least one service and a dentist to see available times.</p>
    @elseif ($slots->isEmpty())
        <div class="rounded-xl border border-slate-200/60 bg-slate-50 px-4 py-3 text-sm text-slate-500">
            The clinic is closed on {{ $date->format('l, M j') }}. Please choose another date (Mon–Sat).
        </div>
    @else
        <div class="grid grid-cols-3 sm:grid-cols-4 gap-2.5">
            @foreach ($slots as $slot)
                @if ($slot['available'])
                    <label class="cursor-pointer">
                        <input type="radio" name="scheduled_at" value="{{ $slot['time']->format('Y-m-d\TH:i') }}" class="peer sr-only" required @checked(request('pick') === $slot['time']->format('H:i')) />
                        <span class="block text-center rounded-xl border border-slate-200 py-2.5 text-sm font-medium text-slate-700 hover:border-brand-blue transition peer-checked:border-brand-blue peer-checked:bg-brand-blue/10 peer-checked:text-brand-blue">
                            {{ $slot['time']->format('g:i A') }}
                        </span>
                    </label>
                @else
                    <div class="text-center rounded-xl border border-slate-100 bg-slate-50 py-2.5 text-sm text-slate-300 line-through cursor-not-allowed" title="Unavailable">
                        {{ $slot['time']->format('g:i A') }}
                    </div>
                @endif
            @endforeach
        </div>
        <div class="mt-2 flex items-center gap-4 text-xs text-slate-400">
            <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded border border-slate-200 inline-block"></span> Available</span>
            <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded bg-slate-100 inline-block"></span> Unavailable</span>
        </div>
    @endif
</div>
