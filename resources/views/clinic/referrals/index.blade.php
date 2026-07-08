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

                {{-- Referral letter: fill the form once → a formal PDF the patient can view --}}
                <details class="mt-3 rounded-xl border border-slate-100 bg-slate-50/60 px-4 py-3">
                    <summary class="cursor-pointer list-none text-sm font-medium text-brand-blue hover:underline">
                        {{ $referral->hasLetter() ? '✉ Referral letter '.$referral->letter_no.' (issued '.$referral->letter_issued_at->format('M j').') — edit / reissue' : '✉ Prepare referral letter' }}
                    </summary>
                    <form method="POST" action="{{ route('clinic.referrals.letter', $referral) }}" class="mt-3 grid sm:grid-cols-2 gap-2 text-sm">
                        @csrf
                        <div>
                            <label class="block text-[11px] text-slate-500 mb-1">Refer to (doctor's name) *</label>
                            <input type="text" name="referred_to_name" required value="{{ $referral->referred_to_name }}" placeholder="e.g. Dr. Maria Santos" class="w-full h-9 px-2 rounded-lg border border-slate-200">
                        </div>
                        <div>
                            <label class="block text-[11px] text-slate-500 mb-1">Clinic / hospital</label>
                            <input type="text" name="referred_to_clinic" value="{{ $referral->referred_to_clinic }}" placeholder="e.g. Region 1 Medical Center — Dental" class="w-full h-9 px-2 rounded-lg border border-slate-200">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-[11px] text-slate-500 mb-1">Address</label>
                            <input type="text" name="referred_to_address" value="{{ $referral->referred_to_address }}" placeholder="Street, city, province" class="w-full h-9 px-2 rounded-lg border border-slate-200">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-[11px] text-slate-500 mb-1">Clinical notes for the letter (history, findings, treatments so far)</label>
                            <textarea name="letter_notes" rows="2" class="w-full px-2 py-1.5 rounded-lg border border-slate-200">{{ $referral->letter_notes }}</textarea>
                        </div>
                        <div class="sm:col-span-2 flex flex-wrap items-center gap-2">
                            <button class="h-9 px-4 rounded-lg gradient-brand text-white text-xs font-semibold hover:opacity-90">{{ $referral->hasLetter() ? 'Reissue letter' : 'Generate & issue letter' }}</button>
                            @if ($referral->hasLetter())
                                <a href="{{ route('clinic.referrals.letter.print', $referral) }}" target="_blank" class="h-9 px-3 inline-flex items-center rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-white">View PDF</a>
                            @endif
                            <span class="text-[11px] text-slate-400">Issuing notifies the patient — they can view it in their portal.</span>
                        </div>
                    </form>
                </details>
            </div>
        @empty
            <p class="text-sm text-slate-400">No referrals found.</p>
        @endforelse
    </div>

    <div class="mt-4">{{ $referrals->links() }}</div>
@endsection
