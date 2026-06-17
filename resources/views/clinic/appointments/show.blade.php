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
                    <form method="POST" action="{{ route('clinic.appointments.cancel', $appointment) }}" onsubmit="return confirm('Cancel this appointment?');" class="flex gap-2">
                        @csrf
                        <input type="text" name="reason" placeholder="Reason (optional)" class="h-9 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue" />
                        <button class="h-9 px-4 rounded-lg border border-slate-200 text-slate-600 text-sm font-medium hover:bg-slate-50 transition">Cancel appointment</button>
                    </form>
                </div>
            @endif
        </div>

        <!-- Payment -->
        <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
            <h3 class="font-display text-lg font-bold">Payment</h3>
            @if ($appointment->payment)
                <div class="mt-3 text-sm space-y-1">
                    <div class="text-2xl font-display font-bold">₱{{ number_format($appointment->payment->amount, 2) }}</div>
                    <div><span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $appointment->payment->status->badgeClasses() }}">{{ $appointment->payment->status->label() }}</span> · {{ $appointment->payment->method->label() }}</div>
                    @if ($appointment->payment->paid_at)<div class="text-xs text-slate-400">Paid {{ $appointment->payment->paid_at->format('M j, Y') }}</div>@endif
                </div>
            @else
                <p class="mt-2 text-sm text-slate-400">No payment recorded.</p>
            @endif

            <form method="POST" action="{{ route('clinic.appointments.payment.store', $appointment) }}" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Amount (₱)</label>
                    <input type="number" step="0.01" name="amount" value="{{ old('amount', $appointment->payment->amount ?? $appointment->service?->price) }}" required class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Method</label>
                    <select name="method" class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue">
                        @foreach ($methods as $val => $lbl)
                            <option value="{{ $val }}" @selected(optional($appointment->payment)->method?->value === $val)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
                    <select name="status" class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue">
                        @foreach ($paymentStatuses as $val => $lbl)
                            <option value="{{ $val }}" @selected(optional($appointment->payment)->status?->value === $val)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="w-full h-10 rounded-lg gradient-brand text-white text-sm font-semibold hover:opacity-90 transition">Save payment</button>
            </form>
        </div>
    </div>
@endsection
