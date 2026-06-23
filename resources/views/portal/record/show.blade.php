@extends('layouts.app')

@section('title', "My record — Bonoan's Dental Clinic")

@section('content')
    <div class="container mx-auto px-6 py-12 max-w-3xl">
        @include('partials.portal-nav')

        <h1 class="font-display text-3xl font-bold">My record</h1>
        <p class="text-sm text-slate-500 mt-1">This information is maintained by the clinic. Contact reception to update it.</p>

        <!-- Details -->
        <div class="mt-6 rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
            <div class="grid sm:grid-cols-2 gap-4 text-sm">
                <div><div class="text-slate-400 text-xs uppercase tracking-wider">Name</div>{{ $patient->fullName() }}</div>
                <div><div class="text-slate-400 text-xs uppercase tracking-wider">Date of birth</div>{{ $patient->date_of_birth?->format('M j, Y') ?? '—' }}</div>
                <div><div class="text-slate-400 text-xs uppercase tracking-wider">Phone</div>{{ $patient->phone ?? '—' }}</div>
                <div><div class="text-slate-400 text-xs uppercase tracking-wider">Blood type</div>{{ $patient->blood_type ?? '—' }}</div>
                <div class="sm:col-span-2"><div class="text-slate-400 text-xs uppercase tracking-wider">Medical history</div>{{ $patient->medical_history ?? '—' }}</div>
            </div>
        </div>

        <!-- Allergies -->
        <div class="mt-6 rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
            <h2 class="font-display text-lg font-bold">Allergies</h2>
            <div class="mt-3 flex flex-wrap gap-2">
                @forelse ($patient->allergies as $allergy)
                    <span class="px-3 py-1 rounded-full text-xs font-medium {{ $allergy->severity->badgeClasses() }}">{{ $allergy->name }} · {{ $allergy->severity->label() }}</span>
                @empty
                    <span class="text-sm text-slate-400">No known allergies on file.</span>
                @endforelse
            </div>
        </div>

        <!-- Treatment history (procedures from completed & paid visits) -->
        <div class="mt-6 rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
            <h2 class="font-display text-lg font-bold">Treatment history</h2>
            <p class="text-xs text-slate-400 mt-0.5">Procedures that have been completed and paid for.</p>
            <div class="mt-3 space-y-3">
                @forelse ($history as $proc)
                    <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                        <div>
                            <div class="font-medium">{{ $proc->procedure_name }}</div>
                            <div class="text-xs text-slate-500 mt-0.5">{{ $proc->appointment?->scheduled_at?->format('M j, Y') }} · {{ $proc->performer?->name ?? 'Dentist' }}</div>
                            @if ($proc->notes)<div class="text-sm text-slate-500 mt-1">{{ $proc->notes }}</div>@endif
                        </div>
                        <span class="text-sm text-slate-600 whitespace-nowrap">₱{{ number_format($proc->price, 2) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">No completed treatments yet.</p>
                @endforelse
            </div>
            <div class="mt-4">{{ $history->links() }}</div>
        </div>

        <!-- Recommendations -->
        <div class="mt-6 rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
            <h2 class="font-display text-lg font-bold">Recommended procedures</h2>
            <div class="mt-3 space-y-3">
                @forelse ($patient->recommendations as $rec)
                    <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                        <div>
                            <div class="font-medium">{{ $rec->recommendation }}</div>
                            @if ($rec->notes)<div class="text-sm text-slate-500 mt-1">{{ $rec->notes }}</div>@endif
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $rec->status->badgeClasses() }}">{{ $rec->status->label() }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">No recommendations at this time.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
