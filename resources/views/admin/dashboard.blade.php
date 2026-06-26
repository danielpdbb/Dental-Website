@extends('layouts.admin')

@section('title', 'Dashboard')
@section('heading', 'Management overview')

@section('content')
    @php $maxTrend = max(1, $trend->max('value')); @endphp

    {{-- Headline KPIs --}}
    <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-5">
        <div class="rounded-2xl bg-gradient-to-br from-brand-navy to-slate-800 text-white p-6 shadow-soft">
            <div class="text-xs uppercase tracking-wider text-white/60">Revenue this month</div>
            <div class="mt-2 font-display text-3xl font-bold">₱{{ number_format($revenueMonth, 2) }}</div>
            <div class="text-xs text-white/60 mt-1">collected payments</div>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
            <div class="text-xs uppercase tracking-wider text-slate-400">Appointments today</div>
            <div class="mt-2 font-display text-3xl font-bold">{{ $apptsToday }}</div>
            <div class="text-xs text-slate-400 mt-1">{{ $apptsTodayDone }} completed so far</div>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
            <div class="text-xs uppercase tracking-wider text-slate-400">Outstanding</div>
            <div class="mt-2 font-display text-3xl font-bold {{ $outstanding > 0 ? 'text-red-500' : 'text-emerald-600' }}">₱{{ number_format($outstanding, 2) }}</div>
            <div class="text-xs text-slate-400 mt-1">on {{ $billedUnpaid }} unpaid bill(s)</div>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
            <div class="text-xs uppercase tracking-wider text-slate-400">Registered patients</div>
            <div class="mt-2 font-display text-3xl font-bold">{{ number_format($totalPatients) }}</div>
            <div class="text-xs text-slate-400 mt-1">{{ $noShowMonth }} no-show this month</div>
        </div>
    </div>

    <div class="mt-6 grid lg:grid-cols-3 gap-6">
        {{-- Revenue trend --}}
        <div class="lg:col-span-2 rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-lg font-bold">Revenue — last 6 months</h2>
                <a href="{{ route('admin.analytics') }}" class="text-sm font-medium text-brand-blue hover:underline">Full analytics →</a>
            </div>
            <div class="mt-6 flex items-end gap-3 h-44">
                @foreach ($trend as $t)
                    <div class="flex-1 flex flex-col items-center justify-end h-full gap-2">
                        <div class="text-[11px] text-slate-400">₱{{ number_format($t['value'] / 1000, 0) }}k</div>
                        <div class="w-full rounded-t-lg gradient-brand" style="height: {{ $t['value'] > 0 ? max(4, round($t['value'] / $maxTrend * 100)) : 1 }}%"></div>
                        <div class="text-xs text-slate-500 font-medium">{{ $t['label'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Needs attention --}}
        <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
            <h2 class="font-display text-lg font-bold">Needs attention</h2>
            <div class="mt-4 space-y-3">
                <a href="{{ route('clinic.billing.index') }}" class="flex items-center justify-between rounded-xl border border-slate-200/60 px-4 py-3 hover:bg-slate-50 transition">
                    <div>
                        <div class="text-sm font-medium text-slate-700">Awaiting billing</div>
                        <div class="text-xs text-slate-400">endorsed by dentists</div>
                    </div>
                    <span class="font-display text-2xl font-bold {{ $forBilling > 0 ? 'text-amber-600' : 'text-slate-300' }}">{{ $forBilling }}</span>
                </a>
                <a href="{{ route('clinic.appointments.index', ['tab' => 'billed']) }}" class="flex items-center justify-between rounded-xl border border-slate-200/60 px-4 py-3 hover:bg-slate-50 transition">
                    <div>
                        <div class="text-sm font-medium text-slate-700">Unpaid bills</div>
                        <div class="text-xs text-slate-400">awaiting payment</div>
                    </div>
                    <span class="font-display text-2xl font-bold {{ $billedUnpaid > 0 ? 'text-red-500' : 'text-slate-300' }}">{{ $billedUnpaid }}</span>
                </a>
                <a href="{{ route('clinic.appointments.index') }}" class="flex items-center justify-between rounded-xl border border-slate-200/60 px-4 py-3 hover:bg-slate-50 transition">
                    <div>
                        <div class="text-sm font-medium text-slate-700">Today's schedule</div>
                        <div class="text-xs text-slate-400">{{ $apptsTodayDone }} of {{ $apptsToday }} done</div>
                    </div>
                    <span class="font-display text-2xl font-bold text-brand-blue">{{ $apptsToday }}</span>
                </a>
            </div>
        </div>
    </div>

    <div class="mt-6 grid lg:grid-cols-3 gap-6">
        {{-- Recent appointments --}}
        <div class="lg:col-span-2 rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-display text-lg font-bold">Recent appointments</h2>
                <a href="{{ route('clinic.appointments.index') }}" class="text-sm font-medium text-brand-blue hover:underline">View all →</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($recent as $a)
                    <div class="flex items-center justify-between gap-3 py-2.5 text-sm">
                        <div class="min-w-0">
                            <div class="font-medium text-slate-800 truncate">{{ $a->patient?->fullName() ?? '—' }}</div>
                            <div class="text-xs text-slate-400">{{ $a->scheduled_at->format('M j, g:i A') }} · {{ $a->service?->name ?? '—' }} · {{ $a->dentist?->name ?? '—' }}</div>
                        </div>
                        <span class="shrink-0 px-2.5 py-0.5 rounded-full text-xs font-medium {{ $a->status->badgeClasses() }}">{{ $a->status->label() }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-400 py-3">No appointments yet.</p>
                @endforelse
            </div>
        </div>

        {{-- People --}}
        <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-lg font-bold">Team &amp; users</h2>
                <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-brand-blue hover:underline">Manage →</a>
            </div>
            <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                <div class="rounded-xl bg-slate-50 p-3"><div class="font-display text-xl font-bold">{{ $totalUsers }}</div><div class="text-[11px] text-slate-400">Total</div></div>
                <div class="rounded-xl bg-slate-50 p-3"><div class="font-display text-xl font-bold text-emerald-600">{{ $verifiedUsers }}</div><div class="text-[11px] text-slate-400">Verified</div></div>
                <div class="rounded-xl bg-slate-50 p-3"><div class="font-display text-xl font-bold text-red-500">{{ $inactiveUsers }}</div><div class="text-[11px] text-slate-400">Suspended</div></div>
            </div>
            <div class="mt-4 space-y-2">
                @foreach ($roleCounts as $label => $count)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-500">{{ $label }}</span>
                        <span class="font-semibold text-slate-700">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
