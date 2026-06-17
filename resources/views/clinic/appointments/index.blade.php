@extends('layouts.admin')

@section('title', 'Appointments')
@section('heading', 'Appointments')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5">
        <form method="GET" action="{{ route('clinic.appointments.index') }}" class="flex flex-wrap gap-2">
            <select name="status" class="h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue">
                <option value="">All statuses</option>
                @foreach ($statuses as $val => $lbl)
                    <option value="{{ $val }}" @selected(($filters['status'] ?? '') === $val)>{{ $lbl }}</option>
                @endforeach
            </select>
            <select name="dentist_id" class="h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue">
                <option value="">All dentists</option>
                @foreach ($dentists as $dentist)
                    <option value="{{ $dentist->id }}" @selected((string) ($filters['dentist_id'] ?? '') === (string) $dentist->id)>{{ $dentist->name }}</option>
                @endforeach
            </select>
            <input type="date" name="date" value="{{ $filters['date'] ?? '' }}" class="h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue" />
            <button class="h-10 px-4 rounded-lg bg-slate-800 text-white text-sm font-medium hover:bg-slate-700 transition">Filter</button>
        </form>

        <a href="{{ route('clinic.appointments.create') }}"
            class="h-10 px-4 inline-flex items-center gap-2 rounded-lg gradient-brand text-white text-sm font-semibold shadow-brand hover:opacity-90 transition">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New / walk-in
        </a>
    </div>

    <div class="rounded-2xl bg-white border border-slate-200/60 shadow-soft overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-5 py-3 font-medium">When</th>
                    <th class="px-5 py-3 font-medium">Patient</th>
                    <th class="px-5 py-3 font-medium">Service</th>
                    <th class="px-5 py-3 font-medium">Dentist</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Payment</th>
                    <th class="px-5 py-3 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($appointments as $appt)
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-5 py-3">{{ $appt->scheduled_at->format('M j, g:i A') }} @if($appt->is_walk_in)<span class="text-xs text-amber-600">(walk-in)</span>@endif</td>
                        <td class="px-5 py-3">{{ $appt->patient?->fullName() ?? '—' }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ $appt->service?->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ $appt->dentist?->name ?? '—' }}</td>
                        <td class="px-5 py-3"><span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $appt->status->badgeClasses() }}">{{ $appt->status->label() }}</span></td>
                        <td class="px-5 py-3">
                            @if ($appt->payment)
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $appt->payment->status->badgeClasses() }}">{{ $appt->payment->status->label() }}</span>
                            @else
                                <span class="text-xs text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('clinic.appointments.show', $appt) }}" class="text-brand-blue hover:underline">Manage</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-10 text-center text-slate-400">No appointments found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $appointments->links() }}</div>
@endsection
