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
            <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 flex items-center justify-between">
                <div>
                    <div class="text-sm text-red-600 font-medium">Outstanding balance</div>
                    <div class="font-display text-2xl font-bold text-red-600">₱{{ number_format($outstanding, 2) }}</div>
                </div>
                <span class="text-xs text-slate-500 max-w-[12rem] text-right">Pay online below, or settle at the clinic.</span>
            </div>
        @endif

        <h2 class="font-display text-lg font-bold mt-8">Upcoming</h2>
        <div class="mt-3 space-y-3">
            @forelse ($upcoming as $appt)
                <div class="rounded-2xl bg-white border border-slate-200/60 p-5 shadow-soft">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="font-medium">{{ $appt->service?->name ?? 'Appointment' }}</div>
                            <div class="text-sm text-slate-500 mt-0.5">{{ $appt->scheduled_at->format('l, M j, Y · g:i A') }} · {{ $appt->dentist?->name }}</div>
                            @if ($appt->balance() > 0)
                                <div class="text-xs text-red-500 mt-0.5">Balance: ₱{{ number_format($appt->balance(), 2) }}</div>
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
                </div>
            @empty
                <p class="text-sm text-slate-400">No upcoming appointments. <a href="{{ route('portal.appointments.create') }}" class="text-brand-blue hover:underline">Book one →</a></p>
            @endforelse
        </div>

        <h2 class="font-display text-lg font-bold mt-10">Past</h2>
        <div class="mt-3 space-y-2">
            @forelse ($past as $appt)
                <div class="border-b border-slate-100 py-2">
                    <div class="flex items-center justify-between text-sm">
                        <div>{{ $appt->scheduled_at->format('M j, Y') }} · {{ $appt->service?->name ?? '—' }} · {{ $appt->dentist?->name }}
                            @if ($appt->balance() > 0)<span class="text-red-500">· ₱{{ number_format($appt->balance(), 2) }} due</span>@endif
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $appt->status->badgeClasses() }}">{{ $appt->status->label() }}</span>
                    </div>
                    @include('portal.appointments._pay', ['appt' => $appt])
                </div>
            @empty
                <p class="text-sm text-slate-400">No past appointments.</p>
            @endforelse
        </div>
    </div>
@endsection
