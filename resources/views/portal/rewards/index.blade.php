@extends('layouts.app')

@section('title', "Rewards — Bonoan's Dental Clinic")

@section('content')
    <div class="container mx-auto px-6 py-12 max-w-3xl">
        @include('partials.portal-nav')

        <h1 class="font-display text-3xl font-bold">Refer a friend &amp; earn</h1>
        <p class="mt-2 text-slate-500">Share your code with family and friends. When they complete their first visit,
            you both get rewarded — and points become real pesos off your next bill.</p>

        {{-- Balance --}}
        <div class="mt-6 grid sm:grid-cols-2 gap-4">
            <div class="rounded-2xl gradient-brand p-6 text-white shadow-brand">
                <div class="text-white/80 text-sm font-medium">Your rewards balance</div>
                <div class="mt-1 font-display text-4xl font-bold">{{ number_format($points) }} <span class="text-xl font-semibold">pts</span></div>
                <div class="mt-1 text-white/90 text-sm">= ₱{{ number_format($pesoValue, 2) }} off future bills</div>
            </div>
            <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft flex flex-col justify-center">
                <div class="text-xs uppercase tracking-wider text-slate-400">How it works</div>
                <ul class="mt-2 space-y-1.5 text-sm text-slate-600">
                    <li class="flex gap-2"><span class="text-brand-green font-bold">1.</span> Share your code below.</li>
                    <li class="flex gap-2"><span class="text-brand-green font-bold">2.</span> Your friend signs up with it.</li>
                    <li class="flex gap-2"><span class="text-brand-green font-bold">3.</span> They complete their first visit.</li>
                    <li class="flex gap-2"><span class="text-brand-green font-bold">4.</span> You earn <strong>{{ config('rewards.referrer_points') }} pts</strong>, they get <strong>{{ config('rewards.welcome_points') }} pts</strong>.</li>
                </ul>
            </div>
        </div>

        {{-- Share code --}}
        <div class="mt-4 rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
            <div class="text-xs uppercase tracking-wider text-slate-400">Your referral code</div>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <span id="ref-code" class="font-display text-2xl font-bold tracking-wider text-gradient-brand">{{ $code }}</span>
                <button type="button" data-copy="{{ $code }}"
                    class="h-9 px-3 rounded-lg border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50 transition">Copy code</button>
            </div>

            <div class="mt-4">
                <label class="block text-xs text-slate-500 mb-1">Or share your sign-up link</label>
                <div class="flex flex-wrap items-center gap-2">
                    <input type="text" readonly value="{{ $shareUrl }}" id="ref-link"
                        class="flex-1 min-w-[16rem] h-10 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm text-slate-600 outline-none" />
                    <button type="button" data-copy="{{ $shareUrl }}"
                        class="h-10 px-4 rounded-lg gradient-brand text-white text-sm font-semibold hover:opacity-90 transition">Copy link</button>
                </div>
            </div>
            <p class="mt-3 text-xs text-slate-400">
                Redeem points at the clinic or online — minimum {{ config('rewards.min_redeem_points') }} pts,
                up to {{ config('rewards.max_redeem_percent') }}% of any single bill.
            </p>
        </div>

        {{-- Referrals made --}}
        <h2 class="font-display text-lg font-bold mt-8">Friends you've referred</h2>
        <div class="mt-3 rounded-2xl bg-white border border-slate-200/60 shadow-soft overflow-hidden">
            @forelse ($referrals as $r)
                <div class="flex items-center justify-between px-5 py-3 {{ ! $loop->last ? 'border-b border-slate-100' : '' }}">
                    <div>
                        <div class="font-medium text-sm">{{ $r->referred?->name ?? 'New patient' }}</div>
                        <div class="text-xs text-slate-400">Joined {{ $r->created_at->format('M j, Y') }}</div>
                    </div>
                    <div class="text-right">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $r->status->badgeClasses() }}">{{ $r->status->label() }}</span>
                        @if ($r->status === \App\Enums\ReferralSignupStatus::Rewarded)
                            <div class="text-xs text-emerald-600 mt-0.5">+{{ $r->referrer_points }} pts</div>
                        @endif
                    </div>
                </div>
            @empty
                <p class="px-5 py-6 text-sm text-slate-400 text-center">No referrals yet. Share your code to get started!</p>
            @endforelse
        </div>

        {{-- Points history --}}
        <h2 class="font-display text-lg font-bold mt-8">Points history</h2>
        <div class="mt-3 rounded-2xl bg-white border border-slate-200/60 shadow-soft overflow-hidden">
            @forelse ($transactions as $tx)
                <div class="flex items-center justify-between px-5 py-3 {{ ! $loop->last ? 'border-b border-slate-100' : '' }}">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $tx->type->badgeClasses() }}">{{ $tx->type->label() }}</span>
                            <span class="text-sm text-slate-600">{{ $tx->description }}</span>
                        </div>
                        <div class="text-xs text-slate-400 mt-0.5">{{ $tx->created_at->format('M j, Y · g:i A') }}</div>
                    </div>
                    <div class="font-display font-bold text-sm {{ $tx->points >= 0 ? 'text-emerald-600' : 'text-slate-500' }}">
                        {{ $tx->points >= 0 ? '+' : '' }}{{ number_format($tx->points) }}
                    </div>
                </div>
            @empty
                <p class="px-5 py-6 text-sm text-slate-400 text-center">No points activity yet.</p>
            @endforelse
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-copy]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var text = btn.getAttribute('data-copy');
            navigator.clipboard.writeText(text).then(function () {
                var original = btn.textContent;
                btn.textContent = 'Copied!';
                setTimeout(function () { btn.textContent = original; }, 1500);
            });
        });
    });
</script>
@endpush
