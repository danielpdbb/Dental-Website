{{-- Online "pay now" form for an appointment with a balance. Expects $appt. --}}
@if ($appt->balance() > 0)
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
@endif
