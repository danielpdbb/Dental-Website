@extends('layouts.admin')

@section('title', 'Analytics')
@section('heading', 'Analytics & reports')

@section('content')
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
            <div class="text-xs uppercase tracking-wider text-slate-400">Appointments</div>
            <div class="mt-2 font-display text-3xl font-bold">{{ $totalAppointments }}</div>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
            <div class="text-xs uppercase tracking-wider text-slate-400">Revenue (paid)</div>
            <div class="mt-2 font-display text-3xl font-bold text-emerald-600">₱{{ number_format($totalRevenue, 2) }}</div>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
            <div class="text-xs uppercase tracking-wider text-slate-400">Outstanding</div>
            <div class="mt-2 font-display text-3xl font-bold text-red-500">₱{{ number_format($outstanding, 2) }}</div>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
            <div class="text-xs uppercase tracking-wider text-slate-400">Cancellation rate</div>
            <div class="mt-2 font-display text-3xl font-bold text-slate-700">{{ $cancellationRate }}%</div>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
            <div class="text-xs uppercase tracking-wider text-slate-400">No-show rate</div>
            <div class="mt-2 font-display text-3xl font-bold text-red-500">{{ $noShowRate }}%</div>
        </div>
    </div>

    <div class="mt-6 grid lg:grid-cols-2 gap-6">
        <!-- Status breakdown -->
        <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
            <h3 class="font-display text-lg font-bold mb-4">Appointments by status</h3>
            <div class="space-y-2">
                @foreach ($statusCounts as $label => $count)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-600">{{ $label }}</span>
                        <span class="font-medium">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Monthly trend -->
        <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
            <h3 class="font-display text-lg font-bold mb-4">Last 6 months</h3>
            <table class="w-full text-sm">
                <thead class="text-slate-400 text-left text-xs uppercase tracking-wider">
                    <tr><th class="py-1">Month</th><th class="py-1 text-right">Appts</th><th class="py-1 text-right">Revenue</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($months as $m)
                        <tr>
                            <td class="py-2">{{ $m['label'] }}</td>
                            <td class="py-2 text-right">{{ $m['appointments'] }}</td>
                            <td class="py-2 text-right">₱{{ number_format($m['revenue'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
