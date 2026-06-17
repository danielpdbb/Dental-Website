@extends('layouts.app')

@section('title', "Referrals — Bonoan's Dental Clinic")

@section('content')
    <div class="container mx-auto px-6 py-12 max-w-2xl">
        @include('partials.portal-nav')

        <h1 class="font-display text-3xl font-bold">Referrals</h1>

        <!-- Request form -->
        <div class="mt-6 rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
            <h2 class="font-display text-lg font-bold">Request a referral</h2>
            <form method="POST" action="{{ route('portal.referrals.store') }}" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label for="service_id" class="block text-sm font-medium text-slate-700 mb-1">Related service (optional)</label>
                    <select id="service_id" name="service_id" class="w-full h-11 px-3 rounded-xl border border-slate-200 outline-none focus:border-brand-blue">
                        <option value="">— None —</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}" @selected((string) old('service_id') === (string) $service->id)>{{ $service->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="reason" class="block text-sm font-medium text-slate-700 mb-1">Reason</label>
                    <textarea id="reason" name="reason" rows="3" required class="w-full px-3 py-2 rounded-xl border @error('reason') border-red-400 @else border-slate-200 @enderror outline-none focus:border-brand-blue">{{ old('reason') }}</textarea>
                    @error('reason') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <button class="h-11 px-6 rounded-xl gradient-brand text-white font-semibold shadow-brand hover:opacity-90 transition">Submit request</button>
            </form>
        </div>

        <!-- My referrals -->
        <h2 class="font-display text-lg font-bold mt-8">My referral requests</h2>
        <div class="mt-3 space-y-3">
            @forelse ($referrals as $referral)
                <div class="rounded-2xl bg-white border border-slate-200/60 p-5 shadow-soft flex items-start justify-between gap-4">
                    <div>
                        <div class="font-medium">{{ $referral->service?->name ?? 'General referral' }}</div>
                        <p class="text-sm text-slate-500 mt-1">{{ $referral->reason }}</p>
                        <div class="text-xs text-slate-400 mt-1">Requested {{ $referral->created_at->format('M j, Y') }}</div>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $referral->status->badgeClasses() }}">{{ $referral->status->label() }}</span>
                </div>
            @empty
                <p class="text-sm text-slate-400">You haven't requested any referrals yet.</p>
            @endforelse
        </div>
    </div>
@endsection
