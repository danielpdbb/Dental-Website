@extends('layouts.admin')

@section('title', 'New appointment')
@section('heading', 'New / walk-in appointment')

@section('content')
    <div class="max-w-2xl rounded-2xl bg-white border border-slate-200/60 p-6 md:p-8 shadow-soft">
        <form method="POST" action="{{ route('clinic.appointments.store') }}" id="appt-form" class="space-y-5" data-review="Create this appointment?">
            @csrf

            <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                <input type="checkbox" id="is_walk_in" name="is_walk_in" value="1" @checked(old('is_walk_in')) class="rounded border-slate-300 text-brand-blue focus:ring-brand-blue/30" />
                This is a walk-in (recorded at the current time)
            </label>

            <div>
                <label for="patient_id" class="block text-sm font-medium text-slate-700 mb-1">Existing patient</label>
                <select id="patient_id" name="patient_id" class="w-full h-11 px-3 rounded-xl border border-slate-200 outline-none focus:border-brand-blue">
                    <option value="">— Select a patient —</option>
                    @foreach ($patients as $patient)
                        <option value="{{ $patient->id }}" @selected((string) old('patient_id', $prefill['patient_id'] ?? '') === (string) $patient->id)>{{ $patient->fullName() }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-400">Or enter a new walk-in patient below.</p>
            </div>

            <div class="grid sm:grid-cols-3 gap-3">
                <input type="text" name="new_first_name" value="{{ old('new_first_name') }}" placeholder="Walk-in first name" class="h-11 px-3 rounded-xl border border-slate-200 outline-none focus:border-brand-blue text-sm" />
                <input type="text" name="new_last_name" value="{{ old('new_last_name') }}" placeholder="Last name" class="h-11 px-3 rounded-xl border border-slate-200 outline-none focus:border-brand-blue text-sm" />
                <input type="text" name="new_phone" value="{{ old('new_phone') }}" placeholder="Phone" class="h-11 px-3 rounded-xl border @error('new_phone') border-red-400 @else border-slate-200 @enderror outline-none focus:border-brand-blue text-sm" />
            </div>
            @error('new_phone') <p class="-mt-3 text-xs text-red-500">{{ $message }}</p> @enderror
            @error('patient_id') <p class="-mt-2 text-xs text-red-500">{{ $message }}</p> @enderror

            {{-- Services: changing one re-renders available times via htmx --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Services / procedures (one or more)</label>
                <div class="grid sm:grid-cols-2 gap-2">
                    @foreach ($services as $service)
                        <label class="flex items-center gap-2 rounded-xl border px-3 py-2.5 text-sm cursor-pointer transition {{ in_array($service->id, $selectedIds) ? 'border-brand-blue bg-brand-blue/5' : 'border-slate-200 hover:bg-slate-50' }}">
                            <input type="checkbox" name="service_ids[]" value="{{ $service->id }}" @checked(in_array($service->id, $selectedIds))
                                hx-get="{{ route('clinic.appointments.create') }}" hx-target="#slots" hx-select="#slots" hx-swap="outerHTML" hx-trigger="change" hx-include="closest form"
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
                    hx-get="{{ route('clinic.appointments.create') }}" hx-target="#slots" hx-select="#slots" hx-swap="outerHTML" hx-trigger="change" hx-include="closest form"
                    class="w-full h-11 px-3 rounded-xl border border-slate-200 outline-none focus:border-brand-blue">
                    <option value="">— Select —</option>
                    @foreach ($dentists as $d)
                        <option value="{{ $d->id }}" @selected((string) ($dentist?->id ?? '') === (string) $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Schedule (hidden for walk-ins) --}}
            <div id="schedule-section">
                <div class="mb-3">
                    <label for="date" class="block text-sm font-medium text-slate-700 mb-1">Date</label>
                    <input id="date" type="date" name="date" value="{{ $date->toDateString() }}" min="{{ now()->toDateString() }}"
                        hx-get="{{ route('clinic.appointments.create') }}" hx-target="#slots" hx-select="#slots" hx-swap="outerHTML" hx-trigger="change" hx-include="closest form"
                        class="w-full h-11 px-3 rounded-xl border border-slate-200 outline-none focus:border-brand-blue" />
                </div>

                @include('clinic.appointments._slots')
            </div>

            <div id="walkin-note" class="hidden rounded-xl bg-amber-50 border border-amber-200 text-amber-700 text-sm px-4 py-3">
                Walk-in: the appointment will be recorded at the current time with the selected dentist.
            </div>

            <div>
                <label for="notes" class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                <textarea id="notes" name="notes" rows="2" class="w-full px-3 py-2 rounded-xl border border-slate-200 outline-none focus:border-brand-blue">{{ old('notes') }}</textarea>
            </div>

            <div class="flex items-center gap-3">
                <button class="h-11 px-6 rounded-xl gradient-brand text-white font-semibold shadow-brand hover:opacity-90 transition">Create appointment</button>
                <a href="{{ route('clinic.appointments.index') }}" class="h-11 px-6 inline-flex items-center rounded-xl border border-slate-200 font-medium text-slate-600 hover:bg-slate-50 transition">Cancel</a>
            </div>
        </form>
    </div>

    <script>
    (function () {
        const walk = document.getElementById('is_walk_in');
        const sched = document.getElementById('schedule-section');
        const note = document.getElementById('walkin-note');
        if (!walk) return;
        function sync() {
            const on = walk.checked;
            sched.classList.toggle('hidden', on);
            note.classList.toggle('hidden', !on);
            // Disabled radios aren't submitted and don't trigger "required".
            sched.querySelectorAll('input[name="scheduled_at"]').forEach(r => { r.disabled = on; });
        }
        walk.addEventListener('change', sync);
        document.addEventListener('htmx:afterSettle', sync); // keep state after a slot refresh
        sync();
    })();
    </script>
@endsection
