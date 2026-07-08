@extends('layouts.admin')

@section('title', 'New appointment')
@section('heading', 'New / walk-in appointment')

@section('content')
  <div id="appt-create" class="max-w-5xl">
    <form method="POST" action="{{ route('clinic.appointments.store') }}" id="appt-form" class="grid lg:grid-cols-2 gap-6 items-start" data-review="Create this appointment?">
        @csrf

        {{-- Left: who + what --}}
        <div class="rounded-2xl bg-white border border-slate-200/60 p-6 md:p-8 shadow-soft space-y-5">
            <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                <input type="checkbox" id="is_walk_in" name="is_walk_in" value="1" @checked($isWalkIn) data-review-label="Appointment type" data-display="Walk-in" data-display-unchecked="Scheduled"
                    hx-get="{{ route('clinic.appointments.create') }}" hx-target="#appt-create" hx-select="#appt-create" hx-swap="outerHTML" hx-trigger="change" hx-include="closest form"
                    class="rounded border-slate-300 text-brand-blue focus:ring-brand-blue/30" />
                This is a walk-in — pick the earliest free slot today (or recommend another date if today is full)
            </label>

            <div>
                <label for="patient_id" class="block text-sm font-medium text-slate-700 mb-1">Existing patient</label>
                <div class="grid sm:grid-cols-[minmax(0,.85fr)_minmax(0,1.4fr)] gap-2">
                    <div class="relative">
                        <svg class="h-4 w-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M21 21l-4-4"/></svg>
                        <input type="search" id="patient-search" autocomplete="off" placeholder="Search name or phone"
                            class="w-full h-11 pl-9 pr-3 rounded-xl border border-slate-200 outline-none focus:border-brand-blue text-sm">
                    </div>
                    <select id="patient_id" name="patient_id"
                        hx-get="{{ route('clinic.appointments.create') }}" hx-target="#appt-create" hx-select="#appt-create" hx-swap="outerHTML" hx-trigger="change" hx-include="closest form"
                        class="w-full h-11 px-3 rounded-xl border border-slate-200 outline-none focus:border-brand-blue">
                        <option value="">— Select a patient —</option>
                        @foreach ($patients as $patient)
                            <option value="{{ $patient->id }}" data-patient-search="{{ strtolower($patient->fullName().' '.$patient->phone) }}" @selected($selectedPatient?->id === $patient->id)>{{ $patient->fullName() }}{{ $patient->phone ? ' · '.$patient->phone : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <p id="patient-search-count" class="mt-1 text-xs text-slate-400">Type a name or phone number to filter the dropdown.</p>
                @unless ($selectedPatient)
                    <p class="mt-1 text-xs text-slate-400">Or enter a new walk-in patient below.</p>
                @endunless
            </div>

            @if ($selectedPatient)
                {{-- Locked summary — name/phone come from the selected patient record. --}}
                <div class="rounded-xl border border-brand-blue/30 bg-brand-blue/5 p-4">
                    <div class="grid sm:grid-cols-3 gap-3">
                        <div><label class="block text-xs text-slate-500 mb-1">First name</label><input value="{{ $selectedPatient->first_name }}" readonly class="w-full h-10 px-3 rounded-lg border border-brand-blue/20 bg-white/70 text-sm text-slate-600 cursor-not-allowed"></div>
                        <div><label class="block text-xs text-slate-500 mb-1">Last name</label><input value="{{ $selectedPatient->last_name }}" readonly class="w-full h-10 px-3 rounded-lg border border-brand-blue/20 bg-white/70 text-sm text-slate-600 cursor-not-allowed"></div>
                        <div><label class="block text-xs text-slate-500 mb-1">Phone</label><input value="{{ $selectedPatient->phone ?: 'No phone on file' }}" readonly class="w-full h-10 px-3 rounded-lg border border-brand-blue/20 bg-white/70 text-sm text-slate-600 cursor-not-allowed"></div>
                    </div>
                    <button type="button" id="change-patient"
                        hx-get="{{ route('clinic.appointments.create') }}" hx-target="#appt-create" hx-select="#appt-create" hx-swap="outerHTML"
                        hx-vals='{"patient_id": ""}' hx-include="closest form"
                        class="mt-3 h-8 px-3 rounded-lg border border-slate-200 bg-white text-xs font-medium text-slate-600 hover:bg-slate-50">Change patient</button>
                </div>
                {{-- Kept out of the walk-in name fields entirely so nothing conflicting is submitted. --}}
            @else
                <div class="grid sm:grid-cols-3 gap-3" id="walkin-fields">
                    <input type="text" id="new_first_name" name="new_first_name" value="{{ $newFirstName }}" placeholder="Walk-in first name" class="h-11 px-3 rounded-xl border border-slate-200 outline-none focus:border-brand-blue text-sm" />
                    <input type="text" id="new_last_name" name="new_last_name" value="{{ $newLastName }}" placeholder="Last name" class="h-11 px-3 rounded-xl border border-slate-200 outline-none focus:border-brand-blue text-sm" />
                    <input type="text" id="new_phone" name="new_phone" value="{{ $newPhone }}" placeholder="Phone" class="h-11 px-3 rounded-xl border @error('new_phone') border-red-400 @else border-slate-200 @enderror outline-none focus:border-brand-blue text-sm" />
                </div>
                <p id="walkin-required-hint" class="-mt-3 text-xs text-amber-600">Enter the patient's first name, last name, and phone number to enable Create appointment.</p>
            @endif
            @error('new_first_name') <p class="-mt-3 text-xs text-red-500">{{ $message }}</p> @enderror
            @error('new_last_name') <p class="-mt-3 text-xs text-red-500">{{ $message }}</p> @enderror
            @error('new_phone') <p class="-mt-3 text-xs text-red-500">{{ $message }}</p> @enderror
            @error('patient_id') <p class="-mt-2 text-xs text-red-500">{{ $message }}</p> @enderror

            {{-- Services: changing one refreshes the calendar + times --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Services / procedures (one or more)</label>
                <div class="grid gap-2">
                    @foreach ($services as $service)
                        <label class="flex items-center gap-2 rounded-xl border px-3 py-2.5 text-sm cursor-pointer transition {{ in_array($service->id, $selectedIds) ? 'border-brand-blue bg-brand-blue/5' : 'border-slate-200 hover:bg-slate-50' }}">
                            <input type="checkbox" name="service_ids[]" value="{{ $service->id }}" @checked(in_array($service->id, $selectedIds)) data-review-label="Services"
                                hx-get="{{ route('clinic.appointments.create') }}" hx-target="#appt-create" hx-select="#appt-create" hx-swap="outerHTML" hx-trigger="change" hx-include="closest form"
                                class="rounded border-slate-300 text-brand-blue focus:ring-brand-blue/30" />
                            <span class="flex-1">{{ $service->name }}</span>
                            <span class="text-slate-400 text-xs whitespace-nowrap">{{ $service->duration_minutes }}m · ₱{{ number_format($service->price, 2) }}</span>
                        </label>
                    @endforeach
                </div>
                @error('service_ids') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="dentist_id" class="block text-sm font-medium text-slate-700 mb-1">Dentist</label>
                <select id="dentist_id" name="dentist_id" required
                    hx-get="{{ route('clinic.appointments.create') }}" hx-target="#appt-create" hx-select="#appt-create" hx-swap="outerHTML" hx-trigger="change" hx-include="closest form"
                    class="w-full h-11 px-3 rounded-xl border border-slate-200 outline-none focus:border-brand-blue">
                    <option value="">— Select —</option>
                    @foreach ($dentists as $d)
                        <option value="{{ $d->id }}" @selected((string) ($dentist?->id ?? '') === (string) $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>

            @if ($followTargets->isNotEmpty())
                <div>
                    <label for="parent_appointment_id" class="block text-sm font-medium text-slate-700 mb-1">
                        Follow-up of a previous visit? <span class="text-slate-400 font-normal">(optional — charges consolidate on the original bill)</span>
                    </label>
                    <select id="parent_appointment_id" name="parent_appointment_id" class="w-full h-11 px-3 rounded-xl border border-slate-200 outline-none focus:border-brand-blue text-sm">
                        <option value="">— No, a new visit —</option>
                        @foreach ($followTargets as $ft)
                            <option value="{{ $ft->id }}" @selected((string) old('parent_appointment_id', request('parent_appointment_id')) === (string) $ft->id)>
                                #{{ $ft->id }} · {{ $ft->scheduled_at->format('M j, Y') }} — {{ \Illuminate\Support\Str::limit($ft->proceduresLabel(), 40) }}{{ $ft->balance() > 0 ? ' · ₱'.number_format($ft->balance(), 2).' due' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label for="notes" class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                <textarea id="notes" name="notes" rows="2" class="w-full px-3 py-2 rounded-xl border border-slate-200 outline-none focus:border-brand-blue">{{ old('notes', request('notes')) }}</textarea>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" id="appt-submit" class="h-11 px-6 rounded-xl gradient-brand text-white font-semibold shadow-brand hover:opacity-90 transition disabled:opacity-40 disabled:cursor-not-allowed">Create appointment</button>
                <a href="{{ route('clinic.appointments.index') }}" class="h-11 px-6 inline-flex items-center rounded-xl border border-slate-200 font-medium text-slate-600 hover:bg-slate-50 transition">Cancel</a>
            </div>
        </div>

        {{-- Right: calendar + time slots --}}
        <div id="schedule-section">
            @if (is_null($monthDays))
                <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft text-sm text-slate-400">
                    Select at least one service and a dentist on the left to see the availability calendar.
                </div>
            @else
                <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft space-y-4">
                    {{-- Next-free hint: what to recommend when the chosen day is packed --}}
                    @if ($nextFree)
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 px-4 py-2.5 text-sm text-emerald-700">
                            Earliest free slot for {{ $dentist->name }}: <strong>{{ $nextFree->format('D, M j · g:i A') }}</strong>
                        </div>
                    @else
                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm text-amber-700">
                            No free slot in the next 45 days for {{ $dentist->name }} — consider another dentist.
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Date — {{ $dentist->name }}&rsquo;s availability</label>
                        @include('partials._booking-calendar', [
                            'calMonth' => $calMonth,
                            'calDays' => $monthDays,
                            'calUrl' => route('clinic.appointments.create'),
                            'calParams' => array_filter(['service_ids' => $selectedIds, 'dentist_id' => $dentist->id, 'patient_id' => $selectedPatient?->id, 'is_walk_in' => $isWalkIn ? 1 : null, 'new_first_name' => $newFirstName ?: null, 'new_last_name' => $newLastName ?: null, 'new_phone' => $newPhone ?: null, 'parent_appointment_id' => request('parent_appointment_id'), 'notes' => request('notes')]),
                            'calTarget' => '#appt-create',
                            'calSelected' => $date->toDateString(),
                        ])
                    </div>

                    @if ($slots->isEmpty())
                        <div class="rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm text-slate-500">
                            {{ $dentist->name }} isn&rsquo;t available on {{ $date->format('l, M j') }} — pick a green day on the calendar{{ $nextFree ? ', or use the earliest free slot above' : '' }}.
                        </div>
                    @else
                        @include('partials._slot-tiles', ['slots' => $slots, 'duration' => $duration, 'date' => $date])
                    @endif
                </div>
            @endif

            <div id="walkin-note" class="hidden mt-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 text-sm px-4 py-3">
                Walk-in: pick any free slot above — today is shown by default so you can see what's open right now.
            </div>
        </div>
    </form>

    {{-- Placed INSIDE the htmx swap target so it re-binds fresh elements after
         every calendar/service/dentist swap (htmx re-runs inline scripts in
         swapped content; a script placed OUTSIDE the target would go stale). --}}
    <script>
    (function () {
        var walk = document.getElementById('is_walk_in');
        var note = document.getElementById('walkin-note');
        var form = document.getElementById('appt-form');
        var hint = document.getElementById('walkin-required-hint');
        var patientSelect = document.getElementById('patient_id');
        var patientSearch = document.getElementById('patient-search');
        var patientSearchCount = document.getElementById('patient-search-count');

        function filterPatients() {
            if (!patientSearch || !patientSelect) return;
            var query = patientSearch.value.trim().toLowerCase();
            var matches = 0;
            Array.prototype.forEach.call(patientSelect.options, function (option, index) {
                if (index === 0) return;
                var match = !query || (option.getAttribute('data-patient-search') || option.textContent.toLowerCase()).indexOf(query) !== -1;
                option.hidden = !match && !option.selected;
                if (match) matches++;
            });
            if (patientSearchCount) patientSearchCount.textContent = query ? matches + ' matching patient(s). Open the dropdown to select one.' : 'Type a name or phone number to filter the dropdown.';
        }
        if (patientSearch) patientSearch.addEventListener('input', filterPatients);

        function sync() {
            if (note) note.classList.toggle('hidden', !(walk && walk.checked));
        }
        if (walk) walk.addEventListener('change', sync);
        sync();

        // Require first/last name before submit ONLY when no existing patient is chosen —
        // shown proactively (not just after a failed submit).
        function requireWalkinFields() {
            var first = document.getElementById('new_first_name');
            var last = document.getElementById('new_last_name');
            var phone = document.getElementById('new_phone');
            var submit = document.getElementById('appt-submit');
            if (!first || !last) return true; // an existing patient is selected — nothing to require
            if (!first || !last || !phone) { if (submit) submit.disabled = false; return true; }
            var ok = first.value.trim() !== '' && last.value.trim() !== '' && phone.value.trim() !== '';
            first.classList.toggle('border-amber-400', !ok);
            last.classList.toggle('border-amber-400', !ok);
            phone.classList.toggle('border-amber-400', !ok);
            if (hint) hint.classList.toggle('hidden', ok);
            if (submit) submit.disabled = !ok;

            return ok;
        }
        ['new_first_name', 'new_last_name', 'new_phone'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.addEventListener('input', requireWalkinFields);
        });
        if (patientSelect) patientSelect.addEventListener('change', requireWalkinFields);
        requireWalkinFields();

        if (form) {
            form.addEventListener('submit', function (e) {
                if (!requireWalkinFields()) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    document.getElementById('new_first_name').scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, true); // capture: run before the review-modal's own submit interception
        }
    })();
    </script>
  </div>
@endsection
