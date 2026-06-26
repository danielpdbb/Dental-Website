@extends('layouts.admin')

@section('title', 'Appointments')
@section('heading', 'Appointments')

@section('content')
  <div id="appt-index" hx-target="#appt-index" hx-select="#appt-index" hx-swap="outerHTML" hx-push-url="true">
    @php
        $tabMeta = [
            'active' => ['Active', 'Booked, in-treatment & awaiting billing'],
            'billed' => ['Billed', 'Awaiting payment'],
            'finished' => ['Finished', 'Completed, no-show & cancelled'],
        ];
        $base = array_filter(['dentist_id' => $filters['dentist_id'] ?? null, 'date' => $filters['date'] ?? null, 'q' => $q]);
    @endphp

    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-4">
        {{-- Tabs (separate the billed queue from finished history) --}}
        <div class="inline-flex rounded-xl bg-slate-100 p-1 text-sm">
            @foreach ($tabMeta as $key => [$label, $hint])
                <a href="{{ route('clinic.appointments.index', array_merge($base, ['tab' => $key])) }}" hx-boost="true"
                   title="{{ $hint }}"
                   class="px-3.5 py-1.5 rounded-lg font-medium transition inline-flex items-center gap-1.5 {{ $tab === $key ? 'bg-white shadow-soft text-slate-800' : 'text-slate-500 hover:text-slate-700' }}">
                    {{ $label }}
                    <span class="text-[10px] px-1.5 py-0.5 rounded-full {{ $tab === $key ? 'bg-brand-blue/10 text-brand-blue' : 'bg-slate-200 text-slate-500' }}">{{ $counts[$key] }}</span>
                </a>
            @endforeach
        </div>

        <a href="{{ route('clinic.appointments.create') }}" hx-boost="false"
            class="h-10 px-4 inline-flex items-center gap-2 rounded-lg gradient-brand text-white text-sm font-semibold shadow-brand hover:opacity-90 transition self-start lg:self-auto">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New / walk-in
        </a>
    </div>

    {{-- Filters: live patient search (server-side, scales to thousands) + dentist + date --}}
    <form method="GET" action="{{ route('clinic.appointments.index') }}" class="flex flex-wrap items-end gap-2 mb-5"
          hx-get="{{ route('clinic.appointments.index') }}" hx-target="#appt-index" hx-select="#appt-index" hx-swap="outerHTML" hx-push-url="true"
          hx-trigger="input changed delay:300ms from:find input[name=q], change">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="relative">
            <svg class="h-4 w-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M21 21l-4-4"/></svg>
            <input type="search" name="q" value="{{ $q }}" placeholder="Search patient name or phone…" autocomplete="off"
                class="h-10 w-72 pl-9 pr-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue" />
        </div>
        <select name="dentist_id" class="h-10 px-3 min-w-[12rem] rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue">
            <option value="">All dentists</option>
            @foreach ($dentists as $dentist)
                <option value="{{ $dentist->id }}" @selected((string) ($filters['dentist_id'] ?? '') === (string) $dentist->id)>{{ $dentist->name }}</option>
            @endforeach
        </select>
        <input type="date" name="date" value="{{ $filters['date'] ?? '' }}" class="h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue" />
        @if ($q || ! empty($filters['dentist_id']) || ! empty($filters['date']))
            <a href="{{ route('clinic.appointments.index', ['tab' => $tab]) }}" hx-boost="true" class="h-10 px-3 inline-flex items-center rounded-lg border border-slate-200 text-slate-500 text-sm hover:bg-slate-50">Clear</a>
        @endif
    </form>

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
                        <td class="px-5 py-3 whitespace-nowrap">{{ $appt->scheduled_at->format('M j, g:i A') }} @if($appt->is_walk_in)<span class="text-xs text-amber-600">(walk-in)</span>@endif</td>
                        <td class="px-5 py-3 font-medium text-slate-800">{{ $appt->patient?->fullName() ?? '—' }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ \Illuminate\Support\Str::limit($appt->proceduresLabel(), 40) }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ $appt->dentist?->name ?? '—' }}</td>
                        <td class="px-5 py-3"><span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $appt->status->badgeClasses() }}">{{ $appt->status->label() }}</span></td>
                        <td class="px-5 py-3">
                            @if ($appt->balance() > 0)
                                <span class="text-xs font-medium text-red-500">₱{{ number_format($appt->balance(), 2) }} due</span>
                            @elseif ($appt->total_amount > 0 && $appt->amountPaid() > 0)
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-brand-green/10 text-emerald-700">Paid</span>
                            @else
                                <span class="text-xs text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('clinic.appointments.show', $appt) }}" hx-boost="false" class="text-brand-blue hover:underline">Manage</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-10 text-center text-slate-400">No {{ $tab }} appointments{{ $q ? ' matching “'.$q.'”' : '' }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $appointments->links() }}</div>
  </div>
@endsection
