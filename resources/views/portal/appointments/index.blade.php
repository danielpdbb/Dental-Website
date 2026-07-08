@extends('layouts.app')

@section('title', "My appointments — Bonoan's Dental Clinic")

@section('content')
    <div class="container mx-auto px-6 py-12 max-w-3xl">
        @include('partials.portal-nav')

        <div class="flex items-center justify-between">
            <h1 class="font-display text-3xl font-bold">My appointments</h1>
            <a href="{{ route('portal.appointments.create') }}" class="h-10 px-4 inline-flex items-center rounded-lg gradient-brand text-white text-sm font-semibold shadow-brand hover:opacity-90 transition">Book new</a>
        </div>

        @if ($outstanding > 0)
            <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 flex items-center justify-between gap-3">
                <div>
                    <div class="text-sm text-red-600 font-medium">Outstanding balance</div>
                    <div class="font-display text-2xl font-bold text-red-600">₱{{ number_format($outstanding, 2) }}</div>
                </div>
                <button type="button" id="ob-open" class="shrink-0 h-9 px-3 rounded-lg bg-white border border-red-200 text-red-600 text-xs font-semibold hover:bg-red-100 transition">Show breakdown</button>
            </div>

            <div id="ob-modal" class="fixed inset-0 z-[90] hidden items-center justify-center bg-slate-900/40 p-4">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-5 max-h-[85vh] overflow-auto">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-display font-bold">Outstanding balance breakdown</h3>
                        <button type="button" id="ob-close" class="text-slate-400 hover:text-slate-700 text-2xl leading-none">&times;</button>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse ($outstandingBills as $a)
                            <details class="group py-3">
                              <summary class="cursor-pointer list-none flex items-start justify-between gap-3 text-sm">
                                <div class="min-w-0">
                                    <div class="font-medium text-slate-800 flex items-center gap-1.5">
                                        {{ $a->proceduresLabel() }}
                                        @if ($a->parent_appointment_id)<span class="px-1.5 py-0.5 rounded-full text-[9px] font-medium bg-brand-blue/10 text-brand-blue">Follow-up</span>@endif
                                    </div>
                                    <div class="text-xs text-slate-500">{{ $a->scheduled_at->format('M j, Y') }} · {{ $a->dentist?->name ?? 'Dentist' }}</div>
                                    @if ($a->billingStatement)<div class="text-[11px] text-slate-400">{{ $a->billingStatement->statement_no }}</div>@endif
                                    <div class="text-[11px] text-slate-400">Charged ₱{{ number_format($a->total_amount, 2) }} · Paid ₱{{ number_format($a->amountPaid(), 2) }}</div>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="font-semibold text-red-600 whitespace-nowrap block">₱{{ number_format($a->balance(), 2) }}</span>
                                    <span class="text-[11px] text-brand-blue">Pay this →</span>
                                </div>
                              </summary>
                              <div class="mt-3 rounded-xl bg-slate-50 border border-slate-100 p-3">
                                @if ($a->billingStatement?->items?->isNotEmpty())
                                  <div class="space-y-1 text-xs">
                                    @foreach ($a->billingStatement->items as $item)
                                      <div class="flex justify-between gap-3"><span class="text-slate-600">{{ $item->description }}</span><span class="font-medium">₱{{ number_format((float) $item->line_total, 2) }}</span></div>
                                    @endforeach
                                  </div>
                                @endif
                                @if ($a->payments->where('status', \App\Enums\PaymentStatus::Paid)->isNotEmpty())
                                  <div class="mt-3 pt-2 border-t border-slate-200 text-xs">
                                    <div class="font-medium text-slate-600 mb-1">Payments already received</div>
                                    @foreach ($a->payments->where('status', \App\Enums\PaymentStatus::Paid)->sortByDesc('paid_at') as $payment)
                                      <div class="flex justify-between text-slate-500"><span>{{ $payment->paid_at?->format('M j, Y') }} · {{ $payment->method->label() }}</span><span>− ₱{{ number_format((float) $payment->amount, 2) }}</span></div>
                                    @endforeach
                                  </div>
                                @endif
                                <form method="POST" action="{{ route('portal.appointments.pay', $a) }}" class="mt-3 pt-3 border-t border-slate-200 flex items-end gap-2">
                                  @csrf
                                  <div class="flex-1"><label class="block text-[11px] text-slate-500 mb-1">Amount to pay</label><input type="number" name="amount" min="1" step="0.01" max="{{ $a->balance() }}" value="{{ number_format($a->balance(), 2, '.', '') }}" class="w-full h-9 px-2 rounded-lg border border-slate-200 bg-white text-sm"></div>
                                  <button class="h-9 px-3 rounded-lg gradient-brand text-white text-xs font-semibold">Pay online</button>
                                </form>
                                <button type="button" data-jump-to="appt-{{ $a->id }}" class="mt-2 text-xs text-brand-blue hover:underline">Open full bill</button>
                              </div>
                            </details>
                        @empty
                            <p class="py-3 text-sm text-slate-400">No itemised balances.</p>
                        @endforelse
                    </div>
                    <div class="flex items-center justify-between border-t-2 border-slate-200 pt-2.5 mt-2 font-bold">
                        <span>Total due</span><span class="text-red-600">₱{{ number_format($outstanding, 2) }}</span>
                    </div>
                    <p class="mt-3 text-xs text-slate-400">Tap a bill above to jump straight to it and pay — no need to scroll.</p>
                </div>
            </div>
            <script>
            (function () {
                var o = document.getElementById('ob-open'), m = document.getElementById('ob-modal'), c = document.getElementById('ob-close');
                if (!o || !m) return;
                function hide() { m.classList.add('hidden'); m.classList.remove('flex'); }
                o.addEventListener('click', function () { m.classList.remove('hidden'); m.classList.add('flex'); });
                c.addEventListener('click', hide);
                m.addEventListener('click', function (e) { if (e.target === m) hide(); });

                m.querySelectorAll('[data-jump-to]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var target = document.getElementById(btn.getAttribute('data-jump-to'));
                        hide();
                        if (target) {
                            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            target.classList.add('ring-2', 'ring-brand-blue');
                            setTimeout(function () { target.classList.remove('ring-2', 'ring-brand-blue'); }, 2000);
                        }
                    });
                });
            })();
            </script>
        @endif

        @include('portal.appointments._recommendations')

        <h2 class="font-display text-lg font-bold mt-8">Current &amp; upcoming</h2>
        <div class="mt-3 space-y-3">
            @forelse ($upcoming as $appt)
                <div id="appt-{{ $appt->id }}" class="scroll-mt-24 rounded-2xl bg-white border border-slate-200/60 p-5 shadow-soft {{ request('scrollTo') == $appt->id ? 'ring-2 ring-brand-blue' : '' }}">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="font-medium flex items-center gap-2 flex-wrap">
                                {{ $appt->proceduresLabel() }}
                                @if ($appt->parent_appointment_id)
                                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-brand-blue/10 text-brand-blue">Follow-up</span>
                                @endif
                            </div>
                            <div class="text-sm text-slate-500 mt-0.5">{{ $appt->scheduled_at->format('l, M j, Y · g:i A') }} · {{ $appt->dentist?->name }}</div>
                            @if ($appt->status->value === 'billed' && $appt->scheduled_at->isPast())
                                <div class="text-xs text-amber-600 mt-0.5">This visit already happened — shown here because it still has a balance.</div>
                            @endif
                            @if ($appt->status->value === 'billed')
                                <div class="text-xs text-slate-500 mt-0.5">Charged ₱{{ number_format($appt->total_amount, 2) }} · Paid ₱{{ number_format($appt->amountPaid(), 2) }}
                                    @if ($appt->balance() > 0)<span class="text-red-500 font-medium"> · Balance ₱{{ number_format($appt->balance(), 2) }}</span>@endif
                                </div>
                            @endif
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $appt->status->badgeClasses() }}">{{ $appt->status->label() }}</span>
                            @if ($appt->isCancellable())
                                <a href="{{ route('portal.appointments.reschedule', $appt) }}" class="text-sm text-brand-blue hover:underline">Reschedule</a>
                                <form method="POST" action="{{ route('portal.appointments.cancel', $appt) }}" data-confirm="Cancel this appointment?">
                                    @csrf
                                    <button class="text-sm text-red-500 hover:underline">Cancel</button>
                                </form>
                            @endif
                        </div>
                    </div>
                    @include('portal.appointments._pay', ['appt' => $appt])

                    {{-- Stage-1 pre-visit assessment (the AI suggestion it produces is shown
                         only to the dentist/management, not the patient). --}}
                    @if (in_array($appt->status->value, ['booked', 'in_treatment'], true))
                        <div class="mt-3 border-t border-slate-100 pt-3">
                            <details>
                                <summary class="cursor-pointer text-sm text-brand-blue hover:underline list-none">
                                    {{ $appt->intake ? 'Update my pre-visit assessment' : 'Fill the pre-visit assessment' }}
                                </summary>
                                <p class="text-xs text-slate-400 mt-2">Answer a few questions so your dentist can prepare. Your dentist will decide the treatment during your visit.</p>
                                @include('clinic.appointments._intake-form', ['appointment' => $appt])
                            </details>
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-sm text-slate-400">No upcoming appointments. <a href="{{ route('portal.appointments.create') }}" class="text-brand-blue hover:underline">Book one →</a></p>
            @endforelse
        </div>

        <div class="flex items-center justify-between mt-10">
            <h2 class="font-display text-lg font-bold">Past</h2>
            <form method="GET" action="{{ route('portal.appointments.index') }}">
                <select name="past_status" onchange="this.form.submit()" class="h-9 px-3 min-w-[11rem] rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue">
                    <option value="">All</option>
                    @foreach (['completed' => 'Completed', 'cancelled' => 'Cancelled', 'no_show' => 'No-show'] as $val => $lbl)
                        <option value="{{ $val }}" @selected(($pastStatus ?? '') === $val)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="mt-3 space-y-3">
            @forelse ($past as $appt)
                <div id="appt-{{ $appt->id }}" class="scroll-mt-24 rounded-2xl bg-white border border-slate-200/60 p-4 shadow-soft">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="font-medium text-slate-800 flex items-center gap-2 flex-wrap">
                                {{ $appt->proceduresLabel() }}
                                @if ($appt->parent_appointment_id)
                                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-brand-blue/10 text-brand-blue">Follow-up</span>
                                @endif
                            </div>
                            <div class="text-sm text-slate-500 mt-0.5">{{ $appt->scheduled_at->format('l, M j, Y · g:i A') }}</div>
                            <div class="text-xs text-slate-400 mt-0.5">{{ $appt->dentist?->name ?? 'Dentist' }}</div>
                        </div>
                        <div class="flex flex-col items-end gap-1 shrink-0">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $appt->status->badgeClasses() }}">{{ $appt->status->label() }}</span>
                            @if ($appt->balance() > 0)
                                <span class="text-xs font-medium text-red-500">₱{{ number_format($appt->balance(), 2) }} due</span>
                            @endif
                        </div>
                    </div>
                    @include('portal.appointments._pay', ['appt' => $appt])
                </div>
            @empty
                <p class="text-sm text-slate-400">No past appointments.</p>
            @endforelse
        </div>
        <div class="mt-5">{{ $past->links() }}</div>
    </div>
@endsection
