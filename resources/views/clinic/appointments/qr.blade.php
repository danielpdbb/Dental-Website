@extends('layouts.admin')

@section('title', 'GCash QR')
@section('heading', 'Scan to pay with GCash')

@section('content')
    <a href="{{ route('clinic.appointments.show', $appointment) }}" class="text-sm text-slate-500 hover:text-brand-blue">← Back to appointment</a>

    <div class="mt-4 max-w-md mx-auto rounded-2xl bg-white border border-slate-200/60 p-8 shadow-soft text-center">
        <h2 class="font-display text-xl font-bold">{{ $appointment->patient?->fullName() }}</h2>
        <p class="text-sm text-slate-500 mt-1">Amount due</p>
        <div class="font-display text-3xl font-bold text-gradient-brand mt-1">₱{{ number_format($payment->amount, 2) }}</div>

        <div class="mt-6">
            @if ($qrUrl)
                <img src="{{ $qrUrl }}" alt="GCash QR code" class="mx-auto w-64 h-64 object-contain border border-slate-100 rounded-xl p-2" />
                @if ($testUrl)
                    <div class="mt-4 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-left text-sm">
                        <div class="font-semibold text-amber-800">⚠ Test mode — do NOT scan this QR</div>
                        <p class="text-amber-700 mt-1">In test mode the QR is real and scanning it would process a real transaction. To
                            <strong>simulate &amp; complete</strong> this QR payment, open the link below instead, approve it, then return here.</p>
                        <a href="{{ $testUrl }}" target="_blank" rel="noopener"
                           class="mt-2 inline-flex items-center gap-1.5 font-medium text-brand-blue hover:underline break-all">
                            Open the test simulation link →
                        </a>
                    </div>
                @else
                    <p class="mt-3 text-sm text-slate-500">Ask the patient to open <strong>GCash → Pay QR</strong> and scan this code.</p>
                @endif
            @else
                <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 text-sm px-4 py-6">
                    The QR image could not be displayed. The status is <strong>{{ $status ?? 'unknown' }}</strong>.
                </div>
            @endif
        </div>

        <div class="mt-6 flex gap-2 justify-center">
            <a href="{{ route('clinic.appointments.qr.check', [$appointment, $payment]) }}"
               class="h-11 px-5 inline-flex items-center rounded-xl gradient-brand text-white font-semibold shadow-brand hover:opacity-90 transition">
                I've paid — check status
            </a>
            <form method="POST" action="{{ route('clinic.appointments.qr.cancel', [$appointment, $payment]) }}" data-confirm="Cancel this QR payment?">
                @csrf
                <button class="h-11 px-5 rounded-xl border border-slate-200 text-slate-600 font-medium hover:bg-slate-50 transition">Cancel</button>
            </form>
        </div>

        <p class="mt-4 text-xs text-slate-400">Status auto-refreshes every 5 seconds. Test mode: no real money moves.</p>
    </div>

    {{-- Light auto-poll: reload the check endpoint periodically --}}
    <meta http-equiv="refresh" content="5;url={{ route('clinic.appointments.qr.check', [$appointment, $payment]) }}" />
@endsection
