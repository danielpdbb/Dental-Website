@extends('layouts.admin')

@section('title', 'Patients')
@section('heading', 'Patient records')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5">
        <form method="GET" action="{{ route('clinic.patients.index') }}" class="flex gap-2">
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search name or phone"
                class="h-10 px-4 rounded-lg border border-slate-200 text-sm focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 outline-none w-64" />
            <button type="submit" class="h-10 px-4 rounded-lg bg-slate-800 text-white text-sm font-medium hover:bg-slate-700 transition">Search</button>
        </form>

        <a href="{{ route('clinic.patients.create') }}"
            class="h-10 px-4 inline-flex items-center gap-2 rounded-lg gradient-brand text-white text-sm font-semibold shadow-brand hover:opacity-90 transition">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New patient
        </a>
    </div>

    <div class="rounded-2xl bg-white border border-slate-200/60 shadow-soft overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-5 py-3 font-medium">Name</th>
                    <th class="px-5 py-3 font-medium">Phone</th>
                    <th class="px-5 py-3 font-medium">Account</th>
                    <th class="px-5 py-3 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($patients as $patient)
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-5 py-3 font-medium text-slate-800">{{ $patient->fullName() }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ $patient->phone ?? '—' }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ $patient->user?->email ?? 'Walk-in (no login)' }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('clinic.patients.show', $patient) }}" class="text-brand-blue hover:underline">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-10 text-center text-slate-400">No patients found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $patients->links() }}</div>
@endsection
