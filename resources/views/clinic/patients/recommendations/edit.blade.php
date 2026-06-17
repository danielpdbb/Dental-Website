@extends('layouts.admin')

@section('title', 'Edit recommendation')
@section('heading', 'Edit recommendation')

@section('content')
    <div class="max-w-2xl rounded-2xl bg-white border border-slate-200/60 p-6 md:p-8 shadow-soft">
        <a href="{{ route('clinic.patients.show', $patient) }}" class="text-sm text-slate-500 hover:text-brand-blue">← Back to {{ $patient->fullName() }}</a>

        <form method="POST" action="{{ route('clinic.patients.recommendations.update', [$patient, $recommendation]) }}" class="mt-4 space-y-4" data-review="Save changes to this recommendation?">
            @csrf
            @method('PUT')

            <div>
                <label for="recommendation" class="block text-sm font-medium text-slate-700 mb-1">Recommended procedure</label>
                <input id="recommendation" type="text" name="recommendation" value="{{ old('recommendation', $recommendation->recommendation) }}" required
                    class="w-full h-11 px-4 rounded-xl border @error('recommendation') border-red-400 @else border-slate-200 @enderror focus:border-brand-blue outline-none" />
                @error('recommendation') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label for="service_id" class="block text-sm font-medium text-slate-700 mb-1">Linked service (optional)</label>
                    <select id="service_id" name="service_id" class="w-full h-11 px-3 rounded-xl border border-slate-200 focus:border-brand-blue outline-none">
                        <option value="">None</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}" @selected(old('service_id', $recommendation->service_id) == $service->id)>{{ $service->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                    <select id="status" name="status" required class="w-full h-11 px-3 rounded-xl border border-slate-200 focus:border-brand-blue outline-none">
                        @foreach (\App\Enums\RecommendationStatus::options() as $val => $lbl)
                            <option value="{{ $val }}" @selected(old('status', $recommendation->status->value) === $val)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="notes" class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                <textarea id="notes" name="notes" rows="3" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:border-brand-blue outline-none">{{ old('notes', $recommendation->notes) }}</textarea>
            </div>

            <div class="flex items-center gap-3">
                <button class="h-11 px-6 rounded-xl gradient-brand text-white font-semibold shadow-brand hover:opacity-90 transition">Save changes</button>
                <a href="{{ route('clinic.patients.show', $patient) }}" class="h-11 px-6 inline-flex items-center rounded-xl border border-slate-200 font-medium text-slate-600 hover:bg-slate-50 transition">Cancel</a>
            </div>
        </form>
    </div>
@endsection
