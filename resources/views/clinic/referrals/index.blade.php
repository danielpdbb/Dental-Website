@extends('layouts.admin')

@section('title', 'Referrals')
@section('heading', 'Referral tracking')

@section('content')
    <form method="GET" action="{{ route('clinic.referrals.index') }}" class="flex gap-2 mb-5">
        <select name="status" class="h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue">
            <option value="">All statuses</option>
            @foreach ($statuses as $val => $lbl)
                <option value="{{ $val }}" @selected(($filters['status'] ?? '') === $val)>{{ $lbl }}</option>
            @endforeach
        </select>
        <button class="h-10 px-4 rounded-lg bg-slate-800 text-white text-sm font-medium hover:bg-slate-700 transition">Filter</button>
    </form>

    <div class="space-y-4">
        @forelse ($referrals as $referral)
            <div class="rounded-2xl bg-white border border-slate-200/60 p-5 shadow-soft">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="font-medium">{{ $referral->patient?->fullName() ?? '—' }}
                            @if ($referral->service)<span class="text-slate-400">·</span> {{ $referral->service->name }}@endif
                        </div>
                        <p class="text-sm text-slate-500 mt-1">{{ $referral->reason }}</p>
                        <div class="text-xs text-slate-400 mt-1">Requested {{ $referral->created_at->format('M j, Y') }} by {{ $referral->requester?->name ?? 'patient' }}</div>
                        @if ($referral->notes)<div class="text-xs text-slate-500 mt-1">Note: {{ $referral->notes }}</div>@endif
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $referral->status->badgeClasses() }}">{{ $referral->status->label() }}</span>
                </div>

                <form method="POST" action="{{ route('clinic.referrals.update', $referral) }}" class="mt-3 flex flex-wrap gap-2 items-center">
                    @csrf @method('PATCH')
                    <select name="status" class="h-9 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue">
                        @foreach ($statuses as $val => $lbl)
                            <option value="{{ $val }}" @selected($referral->status->value === $val)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="notes" value="{{ $referral->notes }}" placeholder="Tracking note" class="h-9 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue flex-1 min-w-48" />
                    <button class="h-9 px-4 rounded-lg bg-slate-800 text-white text-sm font-medium hover:bg-slate-700 transition">Update</button>
                </form>
            </div>
        @empty
            <p class="text-sm text-slate-400">No referrals found.</p>
        @endforelse
    </div>

    <div class="mt-4">{{ $referrals->links() }}</div>
@endsection
