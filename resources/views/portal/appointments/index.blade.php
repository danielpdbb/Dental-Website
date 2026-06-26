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
            @php $outstandingItems = $upcoming->filter(fn ($a) => $a->status === \App\Enums\AppointmentStatus::Billed && $a->balance() > 0); @endphp
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
                        @forelse ($outstandingItems as $a)
                            <div class="flex items-start justify-between gap-3 py-2.5 text-sm">
                                <div class="min-w-0">
                                    <div class="font-medium text-slate-800">{{ $a->proceduresLabel() }}</div>
                                    <div class="text-xs text-slate-500">{{ $a->scheduled_at->format('M j, Y') }} · {{ $a->dentist?->name ?? 'Dentist' }}</div>
                                    @if ($a->billingStatement)<div class="text-[11px] text-slate-400">{{ $a->billingStatement->statement_no }}</div>@endif
                                    <div class="text-[11px] text-slate-400">Charged ₱{{ number_format($a->total_amount, 2) }} · Paid ₱{{ number_format($a->amountPaid(), 2) }}</div>
                                </div>
                                <span class="font-semibold text-red-600 whitespace-nowrap">₱{{ number_format($a->balance(), 2) }}</span>
                            </div>
                        @empty
                            <p class="py-3 text-sm text-slate-400">No itemised balances.</p>
                        @endforelse
                    </div>
                    <div class="flex items-center justify-between border-t-2 border-slate-200 pt-2.5 mt-2 font-bold">
                        <span>Total due</span><span class="text-red-600">₱{{ number_format($outstanding, 2) }}</span>
                    </div>
                    <p class="mt-3 text-xs text-slate-400">Pay any bill online below, or settle at the clinic.</p>
                </div>
            </div>
            <script>
            (function () {
                var o = document.getElementById('ob-open'), m = document.getElementById('ob-modal'), c = document.getElementById('ob-close');
                if (!o || !m) return;
                o.addEventListener('click', function () { m.classList.remove('hidden'); m.classList.add('flex'); });
                c.addEventListener('click', function () { m.classList.add('hidden'); m.classList.remove('flex'); });
                m.addEventListener('click', function (e) { if (e.target === m) { m.classList.add('hidden'); m.classList.remove('flex'); } });
            })();
            </script>
        @endif

        @include('portal.appointments._recommendations')

        <h2 class="font-display text-lg font-bold mt-8">Current &amp; upcoming</h2>
        <div class="mt-3 space-y-3">
            @forelse ($upcoming as $appt)
                <div class="rounded-2xl bg-white border border-slate-200/60 p-5 shadow-soft">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="font-medium">{{ $appt->proceduresLabel() }}</div>
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
                <div class="rounded-2xl bg-white border border-slate-200/60 p-4 shadow-soft">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="font-medium text-slate-800">{{ $appt->proceduresLabel() }}</div>
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
