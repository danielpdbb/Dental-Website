@extends('layouts.admin')

@section('title', 'Appointment')
@section('heading', 'Appointment details')

@section('content')
    <div class="flex items-center justify-between">
        <a href="{{ route('clinic.appointments.index') }}" class="text-sm text-slate-500 hover:text-brand-blue">← All appointments</a>
        <a href="{{ route('clinic.appointments.treatment', $appointment) }}" class="text-sm font-medium text-brand-blue hover:underline">Record treatment →</a>
    </div>

    <div class="mt-4 grid lg:grid-cols-3 gap-6">
        <!-- Details + status -->
        <div class="lg:col-span-2 rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="font-display text-xl font-bold">{{ $appointment->patient?->fullName() ?? '—' }}</h2>
                    <p class="text-sm text-slate-500 mt-1">{{ $appointment->scheduled_at->format('l, M j, Y · g:i A') }}</p>
                </div>
                <div class="flex flex-col items-end gap-1.5">
                    <span class="px-3 py-1 rounded-full text-xs font-medium {{ $appointment->status->badgeClasses() }}">{{ $appointment->status->label() }}</span>
                    @if ($risk)
                        <span class="px-3 py-1 rounded-full text-xs font-medium {{ $risk['classes'] }}" title="{{ round($risk['keep'] * 100) }}% predicted to be kept">
                            No-show risk: {{ $risk['label'] }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="mt-4 grid sm:grid-cols-2 gap-4 text-sm">
                <div><div class="text-slate-400 text-xs uppercase tracking-wider">Service</div>{{ $appointment->service?->name ?? '—' }}</div>
                <div><div class="text-slate-400 text-xs uppercase tracking-wider">Dentist</div>{{ $appointment->dentist?->name ?? '—' }}</div>
                <div><div class="text-slate-400 text-xs uppercase tracking-wider">Duration</div>{{ $appointment->duration_minutes }} min</div>
                <div><div class="text-slate-400 text-xs uppercase tracking-wider">Type</div>{{ $appointment->is_walk_in ? 'Walk-in' : 'Booked' }}</div>
                @if ($appointment->notes)<div class="sm:col-span-2"><div class="text-slate-400 text-xs uppercase tracking-wider">Notes</div>{{ $appointment->notes }}</div>@endif
                @if ($appointment->cancelled_at)
                    <div class="sm:col-span-2 text-slate-500"><div class="text-slate-400 text-xs uppercase tracking-wider">Cancelled</div>{{ $appointment->cancelled_at->format('M j, Y') }} by {{ $appointment->canceller?->name }} — {{ $appointment->cancellation_reason }}</div>
                @endif
            </div>

            {{-- Procedures (current treatment) --}}
            <div class="mt-5">
                <div class="text-slate-400 text-xs uppercase tracking-wider mb-2">Procedures ({{ $appointment->procedures->count() }})</div>
                <div class="border border-slate-100 rounded-xl divide-y divide-slate-100">
                    @forelse ($appointment->procedures as $proc)
                        <div class="flex items-center justify-between px-4 py-2.5 text-sm">
                            <div class="flex items-center gap-2">
                                <span class="font-medium">{{ $proc->procedure_name }}</span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $proc->status->badgeClasses() }}">{{ $proc->status->label() }}</span>
                            </div>
                            <span class="text-slate-600">₱{{ number_format($proc->price, 2) }}</span>
                        </div>
                    @empty
                        <div class="px-4 py-3 text-sm text-slate-400">No procedures listed.</div>
                    @endforelse
                </div>
            </div>

            {{-- Regression recommendations (read-only summary) --}}
            @if ($appointment->recommendations->isNotEmpty())
                <div class="mt-5">
                    <div class="text-slate-400 text-xs uppercase tracking-wider mb-2">AI recommendations</div>
                    <div class="space-y-2">
                        @foreach ($appointment->recommendations as $rec)
                            <div class="rounded-xl border border-slate-100 p-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="text-xs text-slate-400">{{ $rec->source->label() }}</div>
                                        <div class="font-medium text-sm">{{ $rec->recommendation }}</div>
                                        <div class="flex flex-wrap gap-2 mt-1.5 text-xs">
                                            @if ($rec->priority)<span class="px-2 py-0.5 rounded-full font-medium {{ $rec->priority->badgeClasses() }}">{{ $rec->priority->label() }}</span>@endif
                                            @if ($rec->follow_up_weeks)<span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">~{{ $rec->follow_up_weeks }}w</span>@endif
                                            @if ($rec->suggested_at)<span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700">{{ $rec->suggested_at->format('M j, Y g:i A') }}</span>@endif
                                        </div>
                                    </div>
                                    <span class="shrink-0 px-2.5 py-0.5 rounded-full text-xs font-medium {{ $rec->status->badgeClasses() }}">{{ $rec->status->label() }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($appointment->status->value === 'booked')
                <div class="mt-5 flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('clinic.appointments.complete', $appointment) }}">
                        @csrf
                        <button class="h-9 px-4 rounded-lg bg-brand-green/10 text-emerald-700 text-sm font-medium hover:bg-brand-green/20 transition">Mark completed</button>
                    </form>
                    <form method="POST" action="{{ route('clinic.appointments.no-show', $appointment) }}">
                        @csrf
                        <button class="h-9 px-4 rounded-lg bg-red-50 text-red-600 text-sm font-medium hover:bg-red-100 transition">Mark no-show</button>
                    </form>
                    <form method="POST" action="{{ route('clinic.appointments.cancel', $appointment) }}" data-confirm="Cancel this appointment?" class="flex gap-2">
                        @csrf
                        <input type="text" name="reason" placeholder="Reason (optional)" class="h-9 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue" />
                        <button class="h-9 px-4 rounded-lg border border-slate-200 text-slate-600 text-sm font-medium hover:bg-slate-50 transition">Cancel appointment</button>
                    </form>
                </div>
                <form method="POST" action="{{ route('clinic.appointments.reschedule', $appointment) }}" class="mt-3 flex flex-wrap items-end gap-2">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Reschedule to</label>
                        <input type="datetime-local" name="scheduled_at" value="{{ $appointment->scheduled_at->format('Y-m-d\TH:i') }}"
                            class="h-9 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue" />
                    </div>
                    <button class="h-9 px-4 rounded-lg bg-slate-800 text-white text-sm font-medium hover:bg-slate-700 transition">Reschedule</button>
                </form>
            @endif
        </div>

        <!-- Billing -->
        <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
            <h3 class="font-display text-lg font-bold">Billing</h3>

            @if ($appointment->status === \App\Enums\AppointmentStatus::ForBilling)
                @php $performed = $appointment->procedures->where('status', \App\Enums\ProcedureStatus::Performed); @endphp
                <form method="POST" action="{{ route('clinic.appointments.billing.store', $appointment) }}" class="mt-3 space-y-3" data-confirm="Create the itemised billing statement?">
                    @csrf
                    <div class="rounded-lg border border-slate-100 divide-y divide-slate-100 text-sm">
                        @foreach ($performed as $proc)
                            <div class="flex justify-between px-3 py-1.5"><span>{{ $proc->procedure_name }}</span><span>₱{{ number_format($proc->price, 2) }}</span></div>
                        @endforeach
                        <div class="flex justify-between px-3 py-1.5 font-medium"><span>Subtotal</span><span>₱{{ number_format($performed->sum('price'), 2) }}</span></div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Discount (₱, optional)</label>
                        <input type="number" step="0.01" min="0" name="discount" value="0" class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue" />
                    </div>
                    <input type="text" name="notes" placeholder="Statement notes (optional)" class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue" />
                    <button class="w-full h-10 rounded-lg gradient-brand text-white text-sm font-semibold hover:opacity-90 transition">Create itemised statement</button>
                </form>
            @endif

            @if ($appointment->billingStatement)
                @php $statement = $appointment->billingStatement; @endphp
                <div class="mt-3 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500">
                    Statement <span class="font-medium text-slate-700">{{ $statement->statement_no }}</span>
                    · {{ $statement->issued_at?->format('M j, Y') }}
                    @if ($statement->invoice_no)<br>Invoice <span class="font-medium text-emerald-700">{{ $statement->invoice_no }}</span> · {{ $statement->paid_at?->format('M j, Y') }}@endif
                </div>
                @if ($statement->items->isNotEmpty())
                    <div class="mt-3">@include('clinic.billing._items', ['statement' => $statement, 'appointment' => $appointment])</div>
                @endif
                <div class="mt-2 flex gap-2">
                    <a href="{{ route('clinic.appointments.billing.print', [$appointment, 'bill']) }}" target="_blank" class="flex-1 h-9 inline-flex items-center justify-center rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-slate-50">Print bill</a>
                    @if ($statement->invoice_no)
                        <a href="{{ route('clinic.appointments.billing.print', [$appointment, 'invoice']) }}" target="_blank" class="flex-1 h-9 inline-flex items-center justify-center rounded-lg bg-brand-green/10 text-emerald-700 text-xs font-medium hover:bg-brand-green/20">Print invoice</a>
                    @endif
                </div>
            @endif

            <div class="mt-3 space-y-1 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">Charge</span><span class="font-medium">₱{{ number_format($appointment->total_amount, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Paid</span><span class="font-medium text-emerald-600">₱{{ number_format($appointment->amountPaid(), 2) }}</span></div>
                <div class="flex justify-between border-t border-slate-100 pt-1.5 mt-1">
                    <span class="text-slate-500">Balance</span>
                    <span class="font-display font-bold {{ $appointment->balance() > 0 ? 'text-red-500' : 'text-emerald-600' }}">₱{{ number_format($appointment->balance(), 2) }}</span>
                </div>
            </div>

            @php $history = $appointment->payments->where('status', '!=', \App\Enums\PaymentStatus::Pending); @endphp
            @if ($history->isNotEmpty())
                <div class="mt-4">
                    <div class="text-xs uppercase tracking-wider text-slate-400 mb-1">Payment history</div>
                    @foreach ($history as $payment)
                        <div class="flex items-center justify-between text-xs border-b border-slate-100 py-1.5">
                            <span>₱{{ number_format($payment->amount, 2) }} · {{ $payment->method->label() }}
                                @if ($payment->paid_at)<span class="text-slate-400">{{ $payment->paid_at->format('M j') }}</span>@endif
                            </span>
                            <span class="px-2 py-0.5 rounded-full font-medium {{ $payment->status->badgeClasses() }}">{{ $payment->status->label() }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($appointment->isPayable())
                <form method="POST" action="{{ route('clinic.appointments.payment.store', $appointment) }}" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Amount (₱) — partial allowed</label>
                        <input type="number" step="0.01" name="amount" value="{{ old('amount', number_format($appointment->balance(), 2, '.', '')) }}" required class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Method</label>
                        <select name="method" class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue">
                            @foreach ($methods as $val => $lbl)
                                <option value="{{ $val }}">{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <input type="hidden" name="status" value="paid" />
                    <button class="w-full h-10 rounded-lg gradient-brand text-white text-sm font-semibold hover:opacity-90 transition">Record payment</button>
                </form>

                <div class="mt-2 flex items-center gap-2">
                    <span class="flex-1 border-t border-slate-100"></span>
                    <span class="text-xs text-slate-400">or</span>
                    <span class="flex-1 border-t border-slate-100"></span>
                </div>
                <form method="POST" action="{{ route('clinic.appointments.qr.generate', $appointment) }}" class="mt-2">
                    @csrf
                    <button class="w-full h-10 rounded-lg bg-brand-blue/10 text-brand-blue text-sm font-semibold hover:bg-brand-blue/20 transition inline-flex items-center justify-center gap-1.5">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path stroke-linecap="round" d="M14 14h3m4 0h0m-7 4v3m4-7v7m3-3h0"/></svg>
                        Show GCash QR
                    </button>
                </form>
            @elseif ($appointment->status === \App\Enums\AppointmentStatus::Completed)
                <p class="mt-4 text-sm font-medium text-emerald-600">✓ Fully paid</p>
            @elseif (in_array($appointment->status, [\App\Enums\AppointmentStatus::Booked, \App\Enums\AppointmentStatus::InTreatment, \App\Enums\AppointmentStatus::ForBilling], true))
                <p class="mt-4 text-sm text-slate-400">Payment opens once the billing statement is created.</p>
            @endif

            {{-- Rewards credit (only for patients with a portal account & points) --}}
            @if ($rewardPoints > 0)
                <div class="mt-5 pt-4 border-t border-slate-100">
                    <div class="flex items-center justify-between">
                        <span class="text-xs uppercase tracking-wider text-slate-400">Rewards balance</span>
                        <span class="text-sm font-semibold text-emerald-600">{{ number_format($rewardPoints) }} pts</span>
                    </div>
                    @if ($rewardMax > 0 && $appointment->isPayable())
                        <form method="POST" action="{{ route('clinic.appointments.redeem-rewards', $appointment) }}"
                              class="mt-2 flex items-end gap-2" data-confirm="Apply this patient's rewards credit to the bill?">
                            @csrf
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-slate-500 mb-1">Apply credit (₱) — up to ₱{{ number_format($rewardMax, 2) }}</label>
                                <input type="number" step="0.01" min="1" max="{{ number_format($rewardMax, 2, '.', '') }}" name="amount"
                                    value="{{ number_format($rewardMax, 2, '.', '') }}"
                                    class="w-full h-10 px-3 rounded-lg border border-emerald-200 bg-emerald-50/40 text-sm outline-none focus:border-brand-green" />
                            </div>
                            <button class="h-10 px-4 rounded-lg bg-brand-green/10 text-emerald-700 text-sm font-semibold hover:bg-brand-green/20 transition">Apply</button>
                        </form>
                        <p class="mt-1 text-xs text-slate-400">Max {{ config('rewards.max_redeem_percent') }}% of the bill can be paid with points.</p>
                    @else
                        <p class="mt-1 text-xs text-slate-400">No credit can be applied to this bill right now.</p>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endsection
