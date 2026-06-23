@extends('layouts.admin')

@section('title', 'Billing')
@section('heading', 'Billing queue')

@section('content')
    <p class="text-sm text-slate-500 mb-5">Sessions endorsed by dentists, waiting for a billing statement.</p>

    <div class="rounded-2xl bg-white border border-slate-200/60 shadow-soft overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-5 py-3 font-medium">Endorsed</th>
                    <th class="px-5 py-3 font-medium">Patient</th>
                    <th class="px-5 py-3 font-medium">Procedures</th>
                    <th class="px-5 py-3 font-medium">Dentist</th>
                    <th class="px-5 py-3 font-medium text-right">Total</th>
                    <th class="px-5 py-3 font-medium text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($appointments as $appt)
                    @php $performed = $appt->procedures->where('status', \App\Enums\ProcedureStatus::Performed); @endphp
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-5 py-3">{{ $appt->endorsed_at?->format('M j, g:i A') ?? '—' }}</td>
                        <td class="px-5 py-3">{{ $appt->patient?->fullName() ?? '—' }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ \Illuminate\Support\Str::limit($performed->pluck('procedure_name')->join(', '), 50) ?: '—' }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ $appt->dentist?->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-right font-medium">₱{{ number_format($performed->sum('price'), 2) }}</td>
                        <td class="px-5 py-3 text-right">
                            <form method="POST" action="{{ route('clinic.appointments.billing.store', $appt) }}" data-confirm="Create the billing statement for this session?">
                                @csrf
                                <button class="h-9 px-4 rounded-lg gradient-brand text-white text-xs font-semibold hover:opacity-90 transition">Create statement</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-slate-400">Nothing waiting to be billed.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $appointments->links() }}</div>
@endsection
