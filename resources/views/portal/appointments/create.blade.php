@extends('layouts.app')

@section('title', "Book an appointment — Bonoan's Dental Clinic")

@section('content')
    <div class="container mx-auto px-6 py-12 max-w-2xl">
        @include('partials.portal-nav')

        <h1 class="font-display text-3xl font-bold">Book an appointment</h1>
        <p class="text-sm text-slate-500 mt-1">Clinic hours: Mon–Sat, {{ config('clinic.open_time') }}–{{ config('clinic.close_time') }}.</p>

        <div class="mt-6 rounded-2xl bg-white border border-slate-200/60 p-6 md:p-8 shadow-soft">
            <form method="POST" action="{{ route('portal.appointments.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="service_id" class="block text-sm font-medium text-slate-700 mb-1">Service</label>
                    <select id="service_id" name="service_id" required class="w-full h-11 px-3 rounded-xl border @error('service_id') border-red-400 @else border-slate-200 @enderror outline-none focus:border-brand-blue">
                        <option value="">— Select a service —</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}" @selected((string) old('service_id') === (string) $service->id)>{{ $service->name }} — ₱{{ number_format($service->price) }} ({{ $service->duration_minutes }} min)</option>
                        @endforeach
                    </select>
                    @error('service_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="dentist_id" class="block text-sm font-medium text-slate-700 mb-1">Dentist</label>
                    <select id="dentist_id" name="dentist_id" required class="w-full h-11 px-3 rounded-xl border @error('dentist_id') border-red-400 @else border-slate-200 @enderror outline-none focus:border-brand-blue">
                        <option value="">— Select a dentist —</option>
                        @foreach ($dentists as $dentist)
                            <option value="{{ $dentist->id }}" @selected((string) old('dentist_id') === (string) $dentist->id)>{{ $dentist->name }}</option>
                        @endforeach
                    </select>
                    @error('dentist_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="scheduled_at" class="block text-sm font-medium text-slate-700 mb-1">Preferred date &amp; time</label>
                    <input id="scheduled_at" type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}" required
                        class="w-full h-11 px-3 rounded-xl border @error('scheduled_at') border-red-400 @else border-slate-200 @enderror outline-none focus:border-brand-blue" />
                    @error('scheduled_at') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-slate-700 mb-1">Notes (optional)</label>
                    <textarea id="notes" name="notes" rows="2" class="w-full px-3 py-2 rounded-xl border border-slate-200 outline-none focus:border-brand-blue">{{ old('notes') }}</textarea>
                </div>

                <button class="w-full h-12 rounded-xl gradient-brand text-white font-semibold shadow-brand hover:opacity-90 transition">Confirm booking</button>
            </form>
        </div>
    </div>
@endsection
