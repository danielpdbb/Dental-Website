@extends('layouts.admin')

@section('title', 'Availability')
@section('heading', 'Working hours & availability')

@section('content')
    @php
        $dayNames = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
        $clinicOpenDays = config('clinic.open_days');
        $timeOptions = collect(range(7 * 60, 20 * 60, 30))->mapWithKeys(fn ($minutes) => [
            sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60) => \Carbon\Carbon::createFromTime(0, 0)->addMinutes($minutes)->format('g:i A'),
        ]);
        // Overrides as a JS-friendly map for the calendar widget: Y-m-d => {off, label}.
        $overrideMap = $overrides->mapWithKeys(fn ($o) => [$o->date->toDateString() => [
            'off' => $o->is_off, 'label' => $o->windowLabel(), 'note' => $o->note,
            'start' => $o->start_time ? substr($o->start_time, 0, 5) : '09:00',
            'end' => $o->end_time ? substr($o->end_time, 0, 5) : '12:00',
        ]]);
    @endphp

    <div class="max-w-3xl space-y-6">
        @if ($canPick)
            <form method="GET" action="{{ route('clinic.availability') }}" class="flex items-end gap-2">
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Dentist</label>
                    <select name="dentist_id" onchange="this.form.submit()" class="h-10 px-3 min-w-[18rem] w-full rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue">
                        @foreach ($dentists as $d)
                            <option value="{{ $d->id }}" @selected($dentist->id === $d->id)>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        @endif

        {{-- Specific dates FIRST — the calendar makes one-off blocks/overrides fast to spot and add --}}
        <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
            <h2 class="font-display text-lg font-bold">Specific dates</h2>
            <p class="text-xs text-slate-400 mt-0.5 mb-4">Click a date to block it (leave, seminar, emergency) or give it custom hours for just that day. Blocked days show as unavailable in every booking calendar.</p>

            <div class="grid sm:grid-cols-2 gap-5">
                {{-- Mini calendar: click a day to select it below --}}
                <div id="ov-calendar" class="rounded-xl border border-slate-200/60 p-3"></div>

                {{-- The add/edit form for whichever date is selected --}}
                <form method="POST" action="{{ route('clinic.availability.override') }}" class="space-y-3" id="ov-form">
                    @csrf
                    <input type="hidden" name="dentist_id" value="{{ $dentist->id }}">
                    <input type="hidden" name="date" id="ov-date" value="{{ old('date') }}">
                    <div class="rounded-lg border border-brand-blue/20 bg-brand-blue/5 px-3 py-2">
                        <div class="text-[11px] font-medium text-brand-blue">Selected calendar date</div>
                        <div id="ov-date-label" class="text-sm font-semibold text-slate-700">Choose a date from the calendar</div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Type</label>
                        <select name="mode" id="ov-mode" class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue">
                            <option value="off">Whole day off (unavailable)</option>
                            <option value="custom">Custom hours open</option>
                        </select>
                    </div>
                    <div id="ov-times" class="hidden grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] text-slate-400 mb-1">Opens</label>
                            <select id="ov-start" name="start" class="w-full h-10 px-2 rounded-lg border border-slate-200 bg-white text-sm">
                                @foreach ($timeOptions as $value => $label)<option value="{{ $value }}" @selected(old('start', '09:00') === $value)>{{ $label }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] text-slate-400 mb-1">Closes</label>
                            <select id="ov-end" name="end" class="w-full h-10 px-2 rounded-lg border border-slate-200 bg-white text-sm">
                                @foreach ($timeOptions as $value => $label)<option value="{{ $value }}" @selected(old('end', '12:00') === $value)>{{ $label }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Reason <span class="text-slate-300 font-normal">(optional)</span></label>
                        <input type="text" name="note" placeholder="e.g. Seminar, leave, half-day" class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue">
                    </div>
                    @error('date')<p class="text-xs text-red-500">{{ $message }}</p>@enderror
                    @error('end')<p class="text-xs text-red-500">{{ $message }}</p>@enderror
                    <button id="ov-save" disabled class="w-full h-10 rounded-lg gradient-brand text-white text-sm font-semibold shadow-brand hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed">Save this date</button>
                </form>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-slate-500">
                <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-red-100 border border-red-200 inline-block"></span> Day off</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-amber-100 border border-amber-200 inline-block"></span> Custom hours</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded border border-brand-blue bg-brand-blue/10 inline-block"></span> Selected</span>
            </div>

            <div class="mt-4 divide-y divide-slate-100">
                <div class="text-xs uppercase tracking-wider text-slate-400 mb-1">Upcoming blocks &amp; overrides</div>
                @forelse ($overrides as $ov)
                    <div class="flex items-center justify-between gap-3 py-2.5 text-sm">
                        <div>
                            <span class="font-medium text-slate-700">{{ $ov->date->format('D, M j, Y') }}</span>
                            <span class="ml-2 px-2 py-0.5 rounded-full text-xs {{ $ov->is_off ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-700' }}">{{ $ov->windowLabel() }}</span>
                            @if ($ov->note)<span class="ml-2 text-xs text-slate-400">{{ $ov->note }}</span>@endif
                        </div>
                        <form method="POST" action="{{ route('clinic.availability.override.remove', $ov) }}" data-confirm="Remove this date override?">
                            @csrf @method('DELETE')
                            <button class="text-xs text-red-500 hover:underline">Remove</button>
                        </form>
                    </div>
                @empty
                    <p class="py-3 text-sm text-slate-400">No upcoming date blocks or custom-hour days.</p>
                @endforelse
            </div>
        </div>

        {{-- Weekly template --}}
        <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
            <h2 class="font-display text-lg font-bold">Weekly schedule — {{ $dentist->name }}</h2>
            <p class="text-xs text-slate-400 mt-0.5 mb-4">Clinic default is {{ \Carbon\Carbon::parse($clinicDefault[0])->format('g:i A') }}–{{ \Carbon\Carbon::parse($clinicDefault[1])->format('g:i A') }}. Set a day to <strong>Custom hours open</strong> for different hours (e.g. mornings only) or <strong>Off</strong> for a recurring day off.</p>

            <form method="POST" action="{{ route('clinic.availability.weekly') }}" class="space-y-2">
                @csrf
                <input type="hidden" name="dentist_id" value="{{ $dentist->id }}">
                @foreach ($dayNames as $wd => $name)
                    @php
                        $rule = $weekly->get($wd);
                        $mode = $rule ? ($rule->is_off ? 'off' : 'custom') : 'default';
                        $closedByClinic = ! in_array($wd, $clinicOpenDays, true);
                    @endphp
                    <div class="flex flex-wrap items-center gap-3 rounded-xl border border-slate-100 px-4 py-3" data-day-row>
                        <div class="w-24 text-sm font-medium text-slate-700 shrink-0">{{ $name }}</div>
                        <select name="days[{{ $wd }}][mode]" class="h-10 px-3 min-w-[11rem] rounded-lg border border-slate-200 text-sm" onchange="this.closest('[data-day-row]').querySelector('[data-times]').classList.toggle('hidden', this.value !== 'custom')">
                            <option value="default" @selected($mode === 'default')>{{ $closedByClinic ? 'Clinic closed' : 'Clinic default hours' }}</option>
                            <option value="custom" @selected($mode === 'custom')>Custom hours open</option>
                            <option value="off" @selected($mode === 'off')>Off (unavailable)</option>
                        </select>
                        <div data-times class="flex items-center gap-2 {{ $mode === 'custom' ? '' : 'hidden' }}">
                            <div class="flex items-center gap-1.5 rounded-lg border border-slate-200 px-2 h-10">
                                <span class="text-[11px] text-slate-400">Opens</span>
                                 <select name="days[{{ $wd }}][start]" class="h-8 px-1 border-0 bg-white text-sm focus:outline-none">
                                     @foreach ($timeOptions as $value => $label)<option value="{{ $value }}" @selected(($rule?->start_time ? substr($rule->start_time, 0, 5) : '09:00') === $value)>{{ $label }}</option>@endforeach
                                 </select>
                            </div>
                            <span class="text-slate-300">–</span>
                            <div class="flex items-center gap-1.5 rounded-lg border border-slate-200 px-2 h-10">
                                <span class="text-[11px] text-slate-400">Closes</span>
                                 <select name="days[{{ $wd }}][end]" class="h-8 px-1 border-0 bg-white text-sm focus:outline-none">
                                     @foreach ($timeOptions as $value => $label)<option value="{{ $value }}" @selected(($rule?->end_time ? substr($rule->end_time, 0, 5) : '17:00') === $value)>{{ $label }}</option>@endforeach
                                 </select>
                            </div>
                        </div>
                        @if ($mode === 'off')
                            <span class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-600">Off</span>
                        @endif
                    </div>
                @endforeach
                <button class="mt-2 h-10 px-5 rounded-lg gradient-brand text-white text-sm font-semibold shadow-brand hover:opacity-90">Save weekly schedule</button>
            </form>
        </div>
    </div>

    <script>
    (function () {
        var overrides = @json($overrideMap);
        var container = document.getElementById('ov-calendar');
        var dateInput = document.getElementById('ov-date');
        var dateLabel = document.getElementById('ov-date-label');
        var saveButton = document.getElementById('ov-save');
        var modeSelect = document.getElementById('ov-mode');
        var timesBox = document.getElementById('ov-times');
        var startSelect = document.getElementById('ov-start');
        var endSelect = document.getElementById('ov-end');
        if (!container) return;

        var view = new Date(); view.setDate(1); view.setHours(0, 0, 0, 0);
        var minMonth = new Date(view);
        var maxMonth = new Date(view.getFullYear(), view.getMonth() + 6, 1);

        function fmtKey(y, m, d) {
            return y + '-' + String(m + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');
        }

        function syncSelectedDate() {
            var key = dateInput.value;
            if (dateLabel) dateLabel.textContent = key
                ? new Date(key + 'T00:00:00').toLocaleDateString('en-PH', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
                : 'Choose a date from the calendar';
            if (saveButton) saveButton.disabled = !key;
        }

        function render() {
            var y = view.getFullYear(), m = view.getMonth();
            var first = new Date(y, m, 1);
            var daysInMonth = new Date(y, m + 1, 0).getDate();
            var lead = (first.getDay() + 6) % 7; // Mon-first
            var todayKey = fmtKey(new Date().getFullYear(), new Date().getMonth(), new Date().getDate());

            var html = '<div class="flex items-center justify-between mb-3">';
            html += '<button type="button" data-nav="-1" class="h-8 w-8 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50">‹</button>';
            html += '<div class="font-display font-bold text-sm">' + first.toLocaleString('en-US', { month: 'long', year: 'numeric' }) + '</div>';
            html += '<button type="button" data-nav="1" class="h-8 w-8 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50">›</button></div>';
            html += '<div class="grid grid-cols-7 gap-1 text-center text-[10px] font-medium text-slate-400 mb-1">';
            ['Mo','Tu','We','Th','Fr','Sa','Su'].forEach(function (d) { html += '<div>' + d + '</div>'; });
            html += '</div><div class="grid grid-cols-7 gap-1">';
            for (var i = 0; i < lead; i++) html += '<div></div>';
            for (var d = 1; d <= daysInMonth; d++) {
                var key = fmtKey(y, m, d);
                var isPast = key < todayKey;
                var ov = overrides[key];
                var cls = 'h-9 rounded-lg text-xs font-medium inline-flex items-center justify-center ';
                if (isPast) { cls += 'text-slate-200 cursor-not-allowed'; }
                else if (dateInput.value === key) { cls += 'border-2 border-brand-blue bg-brand-blue/10 text-brand-blue cursor-pointer'; }
                else if (ov && ov.off) { cls += 'bg-red-100 text-red-600 border border-red-200 cursor-pointer hover:opacity-80'; }
                else if (ov) { cls += 'bg-amber-100 text-amber-700 border border-amber-200 cursor-pointer hover:opacity-80'; }
                else { cls += 'border border-slate-100 text-slate-600 cursor-pointer hover:border-brand-blue'; }
                html += '<button type="button" ' + (isPast ? 'disabled' : 'data-pick="' + key + '"') + ' class="' + cls + '" title="' + (ov ? ov.label : '') + '">' + d + '</button>';
            }
            html += '</div>';
            container.innerHTML = html;

            container.querySelectorAll('[data-nav]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var dir = parseInt(btn.getAttribute('data-nav'), 10);
                    var next = new Date(view.getFullYear(), view.getMonth() + dir, 1);
                    if (next < minMonth || next > maxMonth) return;
                    view = next; render();
                });
            });
            container.querySelectorAll('[data-pick]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var key = btn.getAttribute('data-pick');
                    dateInput.value = key;
                    syncSelectedDate();
                    var ov = overrides[key];
                    modeSelect.value = ov && !ov.off ? 'custom' : 'off';
                    if (ov && !ov.off) { startSelect.value = ov.start; endSelect.value = ov.end; }
                    timesBox.classList.toggle('hidden', modeSelect.value !== 'custom');
                    render();
                    document.getElementById('ov-form').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                });
            });
        }
        modeSelect.addEventListener('change', function () { timesBox.classList.toggle('hidden', modeSelect.value !== 'custom'); });
        syncSelectedDate();
        render();
    })();
    </script>
@endsection
