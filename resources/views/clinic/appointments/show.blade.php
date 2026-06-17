@extends('layouts.admin')

@section('title', 'Appointment')
@section('heading', 'Appointment details')

@section('content')
    <a href="{{ route('clinic.appointments.index') }}" class="text-sm text-slate-500 hover:text-brand-blue">← All appointments</a>

    <div class="mt-4 grid lg:grid-cols-3 gap-6">
        <!-- Details + status -->
        <div class="lg:col-span-2 rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="font-display text-xl font-bold">{{ $appointment->patient?->fullName() ?? '—' }}</h2>
                    <p class="text-sm text-slate-500 mt-1">{{ $appointment->scheduled_at->format('l, M j, Y · g:i A') }}</p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-medium {{ $appointment->status->badgeClasses() }}">{{ $appointment->status->label() }}</span>
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

            <div class="mt-3 space-y-1 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">Charge</span><span class="font-medium">₱{{ number_format($appointment->total_amount, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Paid</span><span class="font-medium text-emerald-600">₱{{ number_format($appointment->amountPaid(), 2) }}</span></div>
                <div class="flex justify-between border-t border-slate-100 pt-1.5 mt-1">
                    <span class="text-slate-500">Balance</span>
                    <span class="font-display font-bold {{ $appointment->balance() > 0 ? 'text-red-500' : 'text-emerald-600' }}">₱{{ number_format($appointment->balance(), 2) }}</span>
                </div>
            </div>

            @if ($appointment->payments->isNotEmpty())
                <div class="mt-4">
                    <div class="text-xs uppercase tracking-wider text-slate-400 mb-1">Payment history</div>
                    @foreach ($appointment->payments as $payment)
                        <div class="flex items-center justify-between text-xs border-b border-slate-100 py-1.5">
                            <span>₱{{ number_format($payment->amount, 2) }} · {{ $payment->method->label() }}
                                @if ($payment->paid_at)<span class="text-slate-400">{{ $payment->paid_at->format('M j') }}</span>@endif
                            </span>
                            <span class="px-2 py-0.5 rounded-full font-medium {{ $payment->status->badgeClasses() }}">{{ $payment->status->label() }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($appointment->balance() > 0)
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
            @else
                <p class="mt-4 text-sm font-medium text-emerald-600">✓ Fully paid</p>
            @endif
        </div>
    </div>
@endsection
