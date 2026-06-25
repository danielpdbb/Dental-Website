@extends('layouts.admin')

@section('title', 'Patients')
@section('heading', 'Patient records')

@section('content')
  <div id="patients-list" hx-boost="true" hx-target="#patients-list" hx-select="#patients-list" hx-swap="outerHTML" hx-push-url="true">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5">
        <form method="GET" action="{{ route('clinic.patients.index') }}" class="flex flex-wrap gap-2">
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search name or phone"
                class="h-10 px-4 rounded-lg border border-slate-200 text-sm focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 outline-none w-64" />
            <select name="account" onchange="this.form.requestSubmit()" class="h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue">
                <option value="">All patients</option>
                <option value="registered" @selected(($filters['account'] ?? '') === 'registered')>With account</option>
                <option value="walkin" @selected(($filters['account'] ?? '') === 'walkin')>Walk-in (no login)</option>
            </select>
            <button type="submit" class="h-10 px-4 rounded-lg bg-slate-800 text-white text-sm font-medium hover:bg-slate-700 transition">Filter</button>
        </form>

        <a href="{{ route('clinic.patients.create') }}" hx-boost="false"
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
                    <th class="px-5 py-3 font-medium">Balance</th>
                    <th class="px-5 py-3 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($patients as $patient)
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                @if ($patient->user)
                                    @include('partials.avatar', ['user' => $patient->user, 'size' => 'h-9 w-9 text-xs'])
                                @else
                                    <span class="h-9 w-9 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-xs font-semibold shrink-0">{{ strtoupper(mb_substr($patient->first_name, 0, 1)) }}</span>
                                @endif
                                <span class="font-medium text-slate-800">{{ $patient->fullName() }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-slate-500">{{ $patient->phone ?? '—' }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ $patient->user?->email ?? 'Walk-in (no login)' }}</td>
                        <td class="px-5 py-3">
                            @if ($patient->outstandingBalance() > 0)
                                <span class="text-red-500 font-medium">₱{{ number_format($patient->outstandingBalance(), 2) }}</span>
                            @else
                                <span class="text-slate-400">₱0.00</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('clinic.patients.show', $patient) }}" hx-boost="false" class="text-brand-blue hover:underline">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">No patients found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $patients->links() }}</div>
  </div>
@endsection
