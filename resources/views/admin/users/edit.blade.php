@extends('layouts.admin')

@section('title', 'Edit user')
@section('heading', 'Edit user')

@section('content')
    <div class="max-w-3xl space-y-6">
        {{-- Full patient profile (read-only) so the clinic can review the details. --}}
        @if ($user->role->value === 'patient')
            <div class="rounded-2xl bg-white border border-slate-200/60 p-6 md:p-8 shadow-soft">
                <div class="flex items-center gap-3 mb-4">
                    @include('partials.avatar', ['user' => $user, 'size' => 'h-12 w-12 text-sm'])
                    <div>
                        <div class="font-display text-lg font-bold">{{ $user->name }}</div>
                        <div class="text-xs text-slate-400">Patient profile (read-only)</div>
                    </div>
                </div>

                @if ($patient)
                    <div class="grid sm:grid-cols-3 gap-4 text-sm">
                        <div><div class="text-slate-400 text-xs uppercase tracking-wider">Mobile</div>{{ $patient->phone ?? '—' }}</div>
                        <div><div class="text-slate-400 text-xs uppercase tracking-wider">Gender</div>{{ $patient->gender ?? '—' }}</div>
                        <div><div class="text-slate-400 text-xs uppercase tracking-wider">Date of birth</div>{{ $patient->date_of_birth?->format('M j, Y') ?? '—' }}</div>
                        <div><div class="text-slate-400 text-xs uppercase tracking-wider">Blood type</div>{{ $patient->blood_type ?? '—' }}</div>
                        <div><div class="text-slate-400 text-xs uppercase tracking-wider">Outstanding</div>
                            <span class="{{ $patient->outstandingBalance() > 0 ? 'text-red-500 font-medium' : 'text-emerald-600' }}">₱{{ number_format($patient->outstandingBalance(), 2) }}</span>
                        </div>
                        <div><div class="text-slate-400 text-xs uppercase tracking-wider">Allergies</div>{{ $patient->allergies->pluck('name')->join(', ') ?: 'None recorded' }}</div>
                        <div class="sm:col-span-3"><div class="text-slate-400 text-xs uppercase tracking-wider">Address</div>{{ $patient->address ?? '—' }}</div>
                        <div class="sm:col-span-3"><div class="text-slate-400 text-xs uppercase tracking-wider">Medical history</div>{{ $patient->medical_history ?? '—' }}</div>
                    </div>
                    <a href="{{ route('clinic.patients.show', $patient) }}" class="inline-block mt-4 text-sm font-medium text-brand-blue hover:underline">Open full clinical record →</a>
                @else
                    <p class="text-sm text-slate-400">This patient hasn't completed their profile yet.</p>
                @endif
            </div>
        @endif

        {{-- Editable account form --}}
        <div class="rounded-2xl bg-white border border-slate-200/60 p-6 md:p-8 shadow-soft">
            <form method="POST" action="{{ route('admin.users.update', $user) }}" data-review="Save changes to this user?">
                @csrf
                @method('PUT')
                @include('admin.users.form', ['isEdit' => true])
            </form>
        </div>
    </div>
@endsection
