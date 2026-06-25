{{-- Online "pay now" + rewards-credit forms — only once the clinic has issued the
     billing statement (status Billed). Expects $appt, optionally $rewardPeso/$minRedeemPeso. --}}
@if (in_array($appt->status, [\App\Enums\AppointmentStatus::Booked, \App\Enums\AppointmentStatus::InTreatment, \App\Enums\AppointmentStatus::ForBilling], true))
    <div class="mt-3 pt-3 border-t border-slate-100 text-xs text-slate-400">
        Your bill will be available here once your visit is completed and billed.
    </div>
@endif

{{-- Itemised bill (transparent line items) once the statement has been issued. --}}
@if ($appt->billingStatement && $appt->billingStatement->items->isNotEmpty()
    && in_array($appt->status, [\App\Enums\AppointmentStatus::Billed, \App\Enums\AppointmentStatus::Completed], true))
    <div class="mt-3 pt-3 border-t border-slate-100">
        <div class="text-xs uppercase tracking-wider text-slate-400 mb-2">Your itemised bill</div>
        @include('clinic.billing._items', ['statement' => $appt->billingStatement, 'appointment' => $appt])
        @if ($appt->billingStatement->invoice_no)
            <a href="{{ route('portal.appointments.invoice', $appt) }}" target="_blank" class="mt-2 inline-block text-sm font-medium text-emerald-700 hover:underline">✓ Paid — print invoice →</a>
        @endif
    </div>
@endif

@if ($appt->isPayable())
    @php
        $rewardPeso = $rewardPeso ?? 0;
        $minRedeemPeso = $minRedeemPeso ?? 0;
        // How much rewards credit can be applied to THIS bill (mirror of RewardService).
        $pct = (int) config('rewards.max_redeem_percent');
        $alreadyRewards = $appt->payments
            ->where('status', \App\Enums\PaymentStatus::Paid)
            ->where('method', \App\Enums\PaymentMethod::Rewards)
            ->sum('amount');
        $capRemaining = max(0, ($appt->total_amount * $pct / 100) - $alreadyRewards);
        $maxRedeem = floor(max(0, min($rewardPeso, $appt->balance(), $capRemaining)) * 100) / 100;
        $canRedeem = config('rewards.enabled') && $maxRedeem >= max(0.01, $minRedeemPeso);
    @endphp

    <form method="POST" action="{{ route('portal.appointments.pay', $appt) }}" class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap items-end gap-2">
        @csrf
        <div>
            <label class="block text-xs text-slate-500 mb-1">Amount to pay (₱)</label>
            <input type="number" step="0.01" min="1" max="{{ $appt->balance() }}" name="amount"
                value="{{ number_format($appt->balance(), 2, '.', '') }}"
                class="h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue w-36" />
        </div>
        <button class="h-10 px-4 rounded-lg gradient-brand text-white text-sm font-semibold hover:opacity-90 transition inline-flex items-center gap-1.5">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path stroke-linecap="round" d="M2 10h20"/></svg>
            Pay online
        </button>
        <span class="text-xs text-slate-400 self-center">You can pay part of the balance.</span>
    </form>

    @if ($canRedeem)
        <form method="POST" action="{{ route('portal.appointments.redeem', $appt) }}" class="mt-2 flex flex-wrap items-end gap-2"
              data-confirm="Apply rewards credit to this bill?">
            @csrf
            <div>
                <label class="block text-xs text-emerald-600 mb-1">Use rewards credit (₱)</label>
                <input type="number" step="0.01" min="1" max="{{ number_format($maxRedeem, 2, '.', '') }}" name="amount"
                    value="{{ number_format($maxRedeem, 2, '.', '') }}"
                    class="h-10 px-3 rounded-lg border border-emerald-200 bg-emerald-50/40 text-sm outline-none focus:border-brand-green w-36" />
            </div>
            <button class="h-10 px-4 rounded-lg bg-brand-green/10 text-emerald-700 text-sm font-semibold hover:bg-brand-green/20 transition inline-flex items-center gap-1.5">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 12v-2"/><circle cx="12" cy="12" r="9"/></svg>
                Apply credit
            </button>
            <span class="text-xs text-slate-400 self-center">Up to ₱{{ number_format($maxRedeem, 2) }} ({{ $pct }}% of the bill max).</span>
        </form>
    @endif
@endif
