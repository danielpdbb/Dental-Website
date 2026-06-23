@extends('layouts.admin')

@section('title', 'Current treatment')
@section('heading', 'Current treatment')

@php
    use App\Enums\AppointmentStatus;
    use App\Enums\ProcedureStatus;
    $open = ! in_array($appointment->status, [AppointmentStatus::ForBilling, AppointmentStatus::Billed, AppointmentStatus::Completed, AppointmentStatus::Cancelled, AppointmentStatus::NoShow], true);
    $performedCount = $appointment->procedures->where('status', ProcedureStatus::Performed)->count();
@endphp

@section('content')
    <a href="{{ route('clinic.my-schedule') }}" class="text-sm text-slate-500 hover:text-brand-blue">← Back to my schedule</a>

    <div class="mt-4 grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Session header --}}
            <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="font-display text-xl font-bold">{{ $appointment->patient?->fullName() ?? '—' }}</h2>
                        <p class="text-sm text-slate-500 mt-1">{{ $appointment->scheduled_at->format('l, M j, Y · g:i A') }}</p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-medium {{ $appointment->status->badgeClasses() }}">{{ $appointment->status->label() }}</span>
                </div>

                @unless ($open)
                    <div class="mt-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 text-sm px-4 py-3">
                        This session has been endorsed/billed and is read-only.
                    </div>
                @endunless
            </div>

            {{-- Procedures (current treatment) --}}
            <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
                <h3 class="font-display text-lg font-bold mb-1">Procedures</h3>
                <p class="text-xs text-slate-400 mb-4">Add the procedures for this visit, then mark each one performed.</p>

                <div class="border border-slate-100 rounded-xl divide-y divide-slate-100">
                    @forelse ($appointment->procedures as $proc)
                        <div class="flex items-center justify-between gap-3 px-4 py-3">
                            <div class="min-w-0">
                                <div class="font-medium text-sm">{{ $proc->procedure_name }}</div>
                                <div class="text-xs text-slate-400">
                                    ₱{{ number_format($proc->price, 2) }} · {{ $proc->duration_minutes }} min
                                    @if ($proc->performer) · by {{ $proc->performer->name }} @endif
                                </div>
                                @if ($proc->notes)<div class="text-xs text-slate-500 mt-0.5">{{ $proc->notes }}</div>@endif
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $proc->status->badgeClasses() }}">{{ $proc->status->label() }}</span>
                                @if ($open)
                                    <form method="POST" action="{{ route('clinic.appointments.treatment.toggle', [$appointment, $proc]) }}">
                                        @csrf @method('PATCH')
                                        <button class="text-xs font-medium {{ $proc->isPerformed() ? 'text-slate-500 hover:underline' : 'text-emerald-600 hover:underline' }}">
                                            {{ $proc->isPerformed() ? 'Undo' : 'Mark performed' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('clinic.appointments.treatment.remove', [$appointment, $proc]) }}" data-confirm="Remove this procedure?">
                                        @csrf @method('DELETE')
                                        <button class="text-xs text-red-500 hover:underline">Remove</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-6 text-sm text-slate-400 text-center">No procedures yet. Add one below.</div>
                    @endforelse
                </div>

                @if ($open)
                    <form method="POST" action="{{ route('clinic.appointments.treatment.add', $appointment) }}" class="mt-4 flex flex-wrap items-end gap-2">
                        @csrf
                        <div class="flex-1 min-w-[12rem]">
                            <label class="block text-xs font-medium text-slate-500 mb-1">Add procedure</label>
                            <select name="service_id" required class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue">
                                <option value="">— Select a service —</option>
                                @foreach ($services as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }} — ₱{{ number_format($s->price, 2) }} ({{ $s->duration_minutes }}m)</option>
                                @endforeach
                            </select>
                        </div>
                        <input type="text" name="notes" placeholder="Notes (optional)" class="h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue flex-1 min-w-[10rem]" />
                        <button class="h-10 px-4 rounded-lg bg-slate-800 text-white text-sm font-medium hover:bg-slate-700 transition">Add</button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Endorse --}}
        <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft h-fit">
            <h3 class="font-display text-lg font-bold">Endorse to reception</h3>
            <div class="mt-3 space-y-1 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">Procedures</span><span class="font-medium">{{ $appointment->procedures->count() }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Performed</span><span class="font-medium text-emerald-600">{{ $performedCount }}</span></div>
                <div class="flex justify-between border-t border-slate-100 pt-1.5 mt-1"><span class="text-slate-500">Total</span><span class="font-display font-bold">₱{{ number_format($appointment->total_amount, 2) }}</span></div>
            </div>

            @if ($open)
                <form method="POST" action="{{ route('clinic.appointments.treatment.endorse', $appointment) }}" class="mt-4" data-confirm="Endorse this session to reception for billing?">
                    @csrf
                    <button class="w-full h-11 rounded-xl gradient-brand text-white font-semibold shadow-brand hover:opacity-90 transition disabled:opacity-50" {{ $performedCount === 0 ? 'disabled' : '' }}>
                        Endorse for billing
                    </button>
                </form>
                @if ($performedCount === 0)
                    <p class="mt-2 text-xs text-slate-400">Mark at least one procedure performed to endorse.</p>
                @endif
            @else
                <p class="mt-4 text-sm text-emerald-600 font-medium">✓ Already endorsed</p>
            @endif
        </div>
    </div>
@endsection
