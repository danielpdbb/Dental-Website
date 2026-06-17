@extends('layouts.admin')

@section('title', 'New appointment')
@section('heading', 'New / walk-in appointment')

@section('content')
    <div class="max-w-2xl rounded-2xl bg-white border border-slate-200/60 p-6 md:p-8 shadow-soft">
        <form method="POST" action="{{ route('clinic.appointments.store') }}" class="space-y-4">
            @csrf

            <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                <input type="checkbox" name="is_walk_in" value="1" @checked(old('is_walk_in')) class="rounded border-slate-300 text-brand-blue focus:ring-brand-blue/30" />
                This is a walk-in (no prior booking)
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
                <input type="text" name="new_phone" value="{{ old('new_phone') }}" placeholder="Phone" class="h-11 px-3 rounded-xl border border-slate-200 outline-none focus:border-brand-blue text-sm" />
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label for="service_id" class="block text-sm font-medium text-slate-700 mb-1">Service</label>
                    <select id="service_id" name="service_id" required class="w-full h-11 px-3 rounded-xl border border-slate-200 outline-none focus:border-brand-blue">
                        <option value="">— Select —</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}" @selected((string) old('service_id', $prefill['service_id'] ?? '') === (string) $service->id)>{{ $service->name }} ({{ $service->duration_minutes }} min)</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="dentist_id" class="block text-sm font-medium text-slate-700 mb-1">Dentist</label>
                    <select id="dentist_id" name="dentist_id" required class="w-full h-11 px-3 rounded-xl border border-slate-200 outline-none focus:border-brand-blue">
                        <option value="">— Select —</option>
                        @foreach ($dentists as $dentist)
                            <option value="{{ $dentist->id }}" @selected((string) old('dentist_id', $prefill['dentist_id'] ?? '') === (string) $dentist->id)>{{ $dentist->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="scheduled_at" class="block text-sm font-medium text-slate-700 mb-1">Date &amp; time</label>
                <input id="scheduled_at" type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', $prefill['scheduled_at'] ?? '') }}" required
                    class="w-full h-11 px-3 rounded-xl border @error('scheduled_at') border-red-400 @else border-slate-200 @enderror outline-none focus:border-brand-blue" />
                @error('scheduled_at') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
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
@endsection
