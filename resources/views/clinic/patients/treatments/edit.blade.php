@extends('layouts.admin')

@section('title', 'Edit treatment')
@section('heading', 'Edit treatment')

@section('content')
    <div class="max-w-2xl rounded-2xl bg-white border border-slate-200/60 p-6 md:p-8 shadow-soft">
        <a href="{{ route('clinic.patients.show', $patient) }}" class="text-sm text-slate-500 hover:text-brand-blue">← Back to {{ $patient->fullName() }}</a>

        <form method="POST" action="{{ route('clinic.patients.treatments.update', [$patient, $treatment]) }}" class="mt-4 grid sm:grid-cols-2 gap-4" data-review="Save changes to this treatment?">
            @csrf
            @method('PUT')

            <div>
                <label for="dentist_id" class="block text-sm font-medium text-slate-700 mb-1">Attending dentist</label>
                <select id="dentist_id" name="dentist_id" required class="w-full h-11 px-3 rounded-xl border @error('dentist_id') border-red-400 @else border-slate-200 @enderror focus:border-brand-blue outline-none">
                    @foreach ($dentists as $dentist)
                        <option value="{{ $dentist->id }}" @selected(old('dentist_id', $treatment->dentist_id) == $dentist->id)>{{ $dentist->name }}</option>
                    @endforeach
                </select>
                @error('dentist_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="service_id" class="block text-sm font-medium text-slate-700 mb-1">Service (optional)</label>
                <select id="service_id" name="service_id" class="w-full h-11 px-3 rounded-xl border border-slate-200 focus:border-brand-blue outline-none">
                    <option value="">None</option>
                    @foreach ($services as $service)
                        <option value="{{ $service->id }}" @selected(old('service_id', $treatment->service_id) == $service->id)>{{ $service->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2">
                <label for="procedure_name" class="block text-sm font-medium text-slate-700 mb-1">Procedure performed</label>
                <input id="procedure_name" type="text" name="procedure_name" value="{{ old('procedure_name', $treatment->procedure_name) }}" required
                    class="w-full h-11 px-4 rounded-xl border @error('procedure_name') border-red-400 @else border-slate-200 @enderror focus:border-brand-blue outline-none" />
                @error('procedure_name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="treatment_date" class="block text-sm font-medium text-slate-700 mb-1">Date</label>
                <input id="treatment_date" type="date" name="treatment_date" value="{{ old('treatment_date', $treatment->treatment_date->format('Y-m-d')) }}" max="{{ now()->toDateString() }}" required
                    class="w-full h-11 px-4 rounded-xl border @error('treatment_date') border-red-400 @else border-slate-200 @enderror focus:border-brand-blue outline-none" />
                @error('treatment_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="notes" class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                <textarea id="notes" name="notes" rows="3" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:border-brand-blue outline-none">{{ old('notes', $treatment->notes) }}</textarea>
            </div>

            <div class="sm:col-span-2 flex items-center gap-3">
                <button class="h-11 px-6 rounded-xl gradient-brand text-white font-semibold shadow-brand hover:opacity-90 transition">Save changes</button>
                <a href="{{ route('clinic.patients.show', $patient) }}" class="h-11 px-6 inline-flex items-center rounded-xl border border-slate-200 font-medium text-slate-600 hover:bg-slate-50 transition">Cancel</a>
            </div>
        </form>
    </div>
@endsection
