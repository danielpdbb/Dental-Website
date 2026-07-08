@extends('layouts.admin')

@section('title', 'My schedule')
@section('heading', $isDentist ? 'My schedule' : 'Dentist schedule')

@section('content')
    @php($base = $isDentist ? [] : ['dentist_id' => $dentist?->id])
    <div class="rounded-2xl bg-white border border-slate-200/60 p-5 shadow-soft mb-5">
        <form method="GET" action="{{ route('clinic.my-schedule') }}" class="flex flex-wrap items-end gap-3">
            <input type="hidden" name="view" value="{{ $viewMode }}">
            @unless ($isDentist)
                <div>
                    <label for="dentist_id" class="block text-xs font-medium text-slate-500 mb-1">Dentist</label>
                    <select id="dentist_id" name="dentist_id" onchange="this.form.submit()" class="h-10 px-3 min-w-[14rem] rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue">
                        @foreach ($dentists as $d)
                            <option value="{{ $d->id }}" @selected($dentist?->id === $d->id)>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endunless
            <div>
                <label for="date" class="block text-xs font-medium text-slate-500 mb-1">Date</label>
                <input id="date" type="date" name="date" value="{{ $date->toDateString() }}" onchange="this.form.submit()" class="h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue" />
            </div>
            <div class="flex gap-2">
                <a href="{{ route('clinic.my-schedule', $base + ['date' => $date->copy()->subDay()->toDateString()]) }}" class="h-10 px-3 inline-flex items-center rounded-lg border border-slate-200 text-sm hover:bg-slate-50">← Prev</a>
                <a href="{{ route('clinic.my-schedule', $base + ['date' => now()->toDateString()]) }}" class="h-10 px-3 inline-flex items-center rounded-lg border border-slate-200 text-sm hover:bg-slate-50">Today</a>
                <a href="{{ route('clinic.my-schedule', $base + ['date' => $date->copy()->addDay()->toDateString()]) }}" class="h-10 px-3 inline-flex items-center rounded-lg border border-slate-200 text-sm hover:bg-slate-50">Next →</a>
            </div>
            <div class="inline-flex rounded-lg bg-slate-100 p-1 text-xs">
                <a href="{{ route('clinic.my-schedule', $base + ['view' => 'month', 'date' => $date->toDateString()]) }}" class="px-3 py-1.5 rounded-md {{ $viewMode === 'month' ? 'bg-white text-brand-blue shadow-sm' : 'text-slate-500' }}">Month</a>
                <a href="{{ route('clinic.my-schedule', $base + ['view' => 'day', 'date' => $date->toDateString()]) }}" class="px-3 py-1.5 rounded-md {{ $viewMode === 'day' ? 'bg-white text-brand-blue shadow-sm' : 'text-slate-500' }}">Day</a>
            </div>
            <div class="ml-auto text-sm text-slate-400">{{ $appointments->count() }} appointment(s)</div>
        </form>
    </div>

    <h2 class="font-display text-lg font-bold mb-3">
        {{ $viewMode === 'month' ? $date->format('F Y') : $date->format('l, F j, Y') }}
        @if ($dentist) <span class="text-slate-400 font-normal text-sm">· {{ $dentist->name }}</span> @endif
    </h2>

    @if ($viewMode === 'month')
        <div class="rounded-2xl bg-white border border-slate-200/60 shadow-soft overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
                <a href="{{ route('clinic.my-schedule', $base + ['view' => 'month', 'date' => $date->copy()->subMonth()->toDateString()]) }}" class="h-8 px-3 inline-flex items-center rounded-lg border border-slate-200 text-xs text-slate-600 hover:bg-slate-50">Previous month</a>
                <span class="text-sm font-semibold text-slate-700">{{ $date->format('F Y') }}</span>
                <a href="{{ route('clinic.my-schedule', $base + ['view' => 'month', 'date' => $date->copy()->addMonth()->toDateString()]) }}" class="h-8 px-3 inline-flex items-center rounded-lg border border-slate-200 text-xs text-slate-600 hover:bg-slate-50">Next month</a>
            </div>
            <div class="grid grid-cols-7 bg-slate-50 border-b border-slate-100 text-center text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                @foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $weekday)<div class="py-2">{{ $weekday }}</div>@endforeach
            </div>
            <div class="grid grid-cols-7">
                @foreach ($monthDays as $day)
                    <div class="min-h-32 p-1.5 border-r border-b border-slate-100 {{ $day['inMonth'] ? 'bg-white' : 'bg-slate-50/70' }}">
                        <div class="text-xs font-semibold mb-1 {{ $day['date']->isToday() ? 'h-6 w-6 rounded-full gradient-brand text-white inline-flex items-center justify-center' : ($day['inMonth'] ? 'text-slate-600' : 'text-slate-300') }}">{{ $day['date']->day }}</div>
                        <div class="space-y-1">
                            @foreach ($day['tiles']->take(3) as $t)
                                <button type="button" data-tile="{{ $t['id'] }}" class="cal-tile w-full text-left rounded-md border px-1.5 py-1 overflow-hidden {{ $t['isFollowUp'] ? 'border-brand-blue/30 bg-brand-blue/10' : 'border-emerald-200 bg-emerald-50' }}">
                                    <div class="text-[10px] font-semibold truncate">{{ $t['time'] }} · {{ $t['patient'] }}</div>
                                    <div class="text-[9px] text-slate-500 truncate">{{ $t['procedures'] }}{{ $t['isFollowUp'] ? ' · Follow-up' : '' }}</div>
                                </button>
                            @endforeach
                            @if ($day['tiles']->count() > 3)<a href="{{ route('clinic.my-schedule', $base + ['view' => 'day', 'date' => $day['date']->toDateString()]) }}" class="block text-[10px] text-brand-blue hover:underline">+{{ $day['tiles']->count() - 3 }} more</a>@endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @if ($appointments->isNotEmpty()) @include('clinic.schedule._modal', ['tiles' => $tiles]) @endif
    @elseif ($appointments->isEmpty())
        <div class="rounded-2xl bg-white border border-slate-200/60 p-10 text-center text-slate-400 shadow-soft">
            No appointments scheduled for this day.
        </div>
    @else
        {{-- Google-Calendar-style day grid: a time gutter + one lane of appointment tiles. --}}
        <div class="rounded-2xl bg-white border border-slate-200/60 shadow-soft overflow-hidden">
            <div class="grid" style="grid-template-columns: 4.5rem 1fr;">
                {{-- Time gutter --}}
                <div class="border-r border-slate-100">
                    @for ($r = 0; $r < $rows; $r++)
                        @php($mins = $gridStartMin + $r * 30)
                        <div class="h-14 flex items-start justify-end pr-2 pt-0.5 text-[11px] text-slate-400 border-b border-slate-50 {{ $mins % 60 === 0 ? '' : 'text-slate-300' }}">
                            @if ($mins % 60 === 0){{ \Carbon\Carbon::createFromTime(0,0)->addMinutes($mins)->format('g A') }}@endif
                        </div>
                    @endfor
                </div>

                {{-- Tile lane --}}
                <div class="relative">
                    <div class="grid" style="grid-template-rows: repeat({{ $rows }}, 3.5rem);">
                        @for ($r = 0; $r < $rows; $r++)
                            <div class="border-b border-slate-50"></div>
                        @endfor
                    </div>
                    <div class="absolute inset-0 grid gap-1 p-1" style="grid-template-rows: repeat({{ $rows }}, 3.5rem);">
                        @foreach ($tiles as $t)
                            <button type="button" data-tile="{{ $t['id'] }}"
                                class="cal-tile text-left rounded-lg border px-3 py-1.5 overflow-hidden transition hover:shadow-md hover:z-10 {{ $t['isFollowUp'] ? 'border-brand-blue/40 bg-brand-blue/10' : 'border-emerald-200 bg-emerald-50' }}"
                                style="grid-row: {{ $t['row'] }} / span {{ $t['span'] }};">
                                <div class="text-xs font-semibold text-slate-700 truncate">{{ $t['time'] }} · {{ $t['patient'] }}</div>
                                <div class="text-[11px] text-slate-500 truncate">{{ $t['procedures'] }}</div>
                                @if ($t['isFollowUp'])<span class="inline-block mt-0.5 px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-brand-blue text-white">Follow-up</span>@endif
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Detail modal (client-side only — data already on the page) --}}
        <div id="tile-modal" class="fixed inset-0 z-[95] hidden items-center justify-center bg-slate-900/40 p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-5">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div>
                        <h4 id="tm-patient" class="font-display text-lg font-bold">—</h4>
                        <p id="tm-time" class="text-sm text-slate-500 mt-0.5"></p>
                    </div>
                    <span id="tm-status" class="px-2.5 py-0.5 rounded-full text-xs font-medium"></span>
                </div>
                <div id="tm-followup" class="hidden rounded-lg bg-brand-blue/10 text-brand-blue text-xs px-3 py-2 mb-3"></div>
                <div class="space-y-1.5 text-sm">
                    <div><span class="text-slate-400">Procedures:</span> <span id="tm-procedures" class="font-medium"></span></div>
                    <div><span class="text-slate-400">Duration:</span> <span id="tm-duration"></span> min</div>
                </div>
                <div id="tm-badges" class="mt-2 flex flex-wrap gap-1.5"></div>
                <div class="mt-5 flex gap-2">
                    <a id="tm-treatment" href="#" class="flex-1 h-10 inline-flex items-center justify-center rounded-lg gradient-brand text-white text-sm font-semibold hover:opacity-90">Treatment →</a>
                    <a id="tm-patient-link" href="#" class="flex-1 h-10 inline-flex items-center justify-center rounded-lg border border-slate-200 text-slate-600 text-sm font-medium hover:bg-slate-50">View patient</a>
                </div>
                <button type="button" id="tm-close" class="mt-3 w-full h-9 text-xs text-slate-400 hover:text-slate-600">Close</button>
            </div>
        </div>

        <script>
        (function () {
            var data = @json($tiles->keyBy('id'));
            var modal = document.getElementById('tile-modal');
            function show() { modal.classList.remove('hidden'); modal.classList.add('flex'); }
            function hide() { modal.classList.add('hidden'); modal.classList.remove('flex'); }

            document.querySelectorAll('.cal-tile').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var t = data[btn.getAttribute('data-tile')];
                    if (!t) return;
                    document.getElementById('tm-patient').textContent = t.patient;
                    document.getElementById('tm-time').textContent = t.time + ' · ' + t.duration + ' min' + (t.isWalkIn ? ' · Walk-in' : '');
                    var statusEl = document.getElementById('tm-status');
                    statusEl.textContent = t.status;
                    statusEl.className = 'px-2.5 py-0.5 rounded-full text-xs font-medium ' + t.statusClasses;
                    document.getElementById('tm-procedures').textContent = t.procedures;
                    document.getElementById('tm-duration').textContent = t.duration;

                    var fu = document.getElementById('tm-followup');
                    if (t.isFollowUp) { fu.classList.remove('hidden'); fu.textContent = 'This is a follow-up of the ' + (t.parentDate || 'earlier') + ' visit — charges consolidate on that bill.'; }
                    else fu.classList.add('hidden');

                    var badges = document.getElementById('tm-badges');
                    badges.innerHTML = '';
                    var add = function (text, cls) {
                        var s = document.createElement('span');
                        s.className = 'px-2 py-0.5 rounded-full text-[11px] font-medium ' + cls;
                        s.textContent = text;
                        badges.appendChild(s);
                    };
                    add(t.hasAssessment ? 'Assessment ✓' : 'Assessment pending', t.hasAssessment ? 'bg-brand-green/10 text-emerald-700' : 'bg-slate-100 text-slate-400');
                    if (t.hasNextVisit) add('Next-visit: ' + t.hasNextVisit, 'bg-brand-blue/10 text-brand-blue');

                    document.getElementById('tm-treatment').href = t.treatmentUrl;
                    var patientLink = document.getElementById('tm-patient-link');
                    if (t.patientUrl) { patientLink.href = t.patientUrl; patientLink.classList.remove('hidden'); }
                    else { patientLink.classList.add('hidden'); }

                    show();
                });
            });
            document.getElementById('tm-close').addEventListener('click', hide);
            modal.addEventListener('click', function (e) { if (e.target === modal) hide(); });
        })();
        </script>
    @endif
@endsection
