{{-- Calendar + slot-tile reschedule picker — swapped into #reschedule-box on the
     appointment page. Confirming posts to the existing PUT reschedule route. --}}
<div id="reschedule-box" class="mt-3 rounded-xl border border-slate-200 bg-slate-50/60 p-4">
    <div class="flex items-center justify-between mb-3">
        <div class="text-sm font-semibold text-slate-700">Change date &amp; time</div>
        <button type="button" data-reschedule-close class="text-xs text-slate-400 hover:text-slate-600">Cancel</button>
    </div>

    @include('partials._booking-calendar', [
        'calMonth' => $calMonth,
        'calDays' => $monthDays,
        'calUrl' => route('clinic.appointments.reschedule.form', $appointment),
        'calParams' => [],
        'calTarget' => '#reschedule-box',
        'calSelected' => $date->toDateString(),
    ])

    <form method="POST" action="{{ route('clinic.appointments.reschedule', $appointment) }}" class="mt-4" data-confirm="Reschedule this appointment?">
        @csrf @method('PUT')
        @if ($slots->isEmpty())
            <div class="rounded-xl bg-white border border-slate-200 px-4 py-3 text-sm text-slate-500">
                {{ $appointment->dentist?->name }} isn&rsquo;t available on {{ $date->format('l, M j') }} — pick a green day above.
            </div>
        @else
            @include('partials._slot-tiles', ['slots' => $slots, 'duration' => $duration, 'date' => $date])
            <button class="mt-3 h-10 px-5 rounded-lg gradient-brand text-white text-sm font-semibold shadow-brand hover:opacity-90">Confirm new time</button>
        @endif
    </form>
</div>
