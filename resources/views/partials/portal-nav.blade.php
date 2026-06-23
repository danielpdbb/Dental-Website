@php
    $tabs = [
        ['My record', 'portal.record', 'portal.record'],
        ['Appointments', 'portal.appointments.index', 'portal.appointments.index'],
        ['Book', 'portal.appointments.create', 'portal.appointments.create'],
        ['Referrals', 'portal.referrals.index', 'portal.referrals.*'],
        ['Rewards', 'portal.rewards.index', 'portal.rewards.*'],
    ];
@endphp
<div class="flex flex-wrap gap-2 mb-8 border-b border-slate-200 pb-3">
    @foreach ($tabs as [$label, $routeName, $pattern])
        <a href="{{ route($routeName) }}"
            class="px-4 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs($pattern) ? 'gradient-brand text-white shadow-brand' : 'text-slate-600 hover:bg-slate-100' }}">
            {{ $label }}
        </a>
    @endforeach
</div>
