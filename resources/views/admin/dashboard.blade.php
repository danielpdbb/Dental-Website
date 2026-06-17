@extends('layouts.admin')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')
    <div class="grid sm:grid-cols-3 gap-5">
        <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
            <div class="text-xs uppercase tracking-wider text-slate-400">Total users</div>
            <div class="mt-2 font-display text-3xl font-bold">{{ $totalUsers }}</div>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
            <div class="text-xs uppercase tracking-wider text-slate-400">Verified</div>
            <div class="mt-2 font-display text-3xl font-bold text-emerald-600">{{ $verifiedUsers }}</div>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
            <div class="text-xs uppercase tracking-wider text-slate-400">Suspended</div>
            <div class="mt-2 font-display text-3xl font-bold text-red-500">{{ $inactiveUsers }}</div>
        </div>
    </div>

    <div class="mt-6 rounded-2xl bg-white border border-slate-200/60 p-6 shadow-soft">
        <div class="flex items-center justify-between">
            <h2 class="font-display text-lg font-bold">Users by role</h2>
            <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-brand-blue hover:underline">Manage users →</a>
        </div>
        <div class="mt-4 grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach ($roleCounts as $label => $count)
                <div class="rounded-xl border border-slate-200/60 p-4">
                    <div class="text-sm text-slate-500">{{ $label }}</div>
                    <div class="mt-1 font-display text-2xl font-bold">{{ $count }}</div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
