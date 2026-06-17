@extends('layouts.admin')

@section('title', 'Services')
@section('heading', 'Service & pricing management')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5">
        <form method="GET" action="{{ route('admin.services.index') }}" class="flex flex-wrap gap-2">
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search service"
                class="h-10 px-4 rounded-lg border border-slate-200 text-sm focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 outline-none w-56" />
            <select name="status" class="h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue">
                <option value="">All</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                <option value="hidden" @selected(($filters['status'] ?? '') === 'hidden')>Hidden</option>
            </select>
            <button type="submit" class="h-10 px-4 rounded-lg bg-slate-800 text-white text-sm font-medium hover:bg-slate-700 transition">Filter</button>
        </form>

        <a href="{{ route('admin.services.create') }}"
            class="h-10 px-4 inline-flex items-center gap-2 rounded-lg gradient-brand text-white text-sm font-semibold shadow-brand hover:opacity-90 transition">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New service
        </a>
    </div>

    <div class="rounded-2xl bg-white border border-slate-200/60 shadow-soft overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-5 py-3 font-medium">Service</th>
                    <th class="px-5 py-3 font-medium">Duration</th>
                    <th class="px-5 py-3 font-medium">Price</th>
                    <th class="px-5 py-3 font-medium">Active</th>
                    <th class="px-5 py-3 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($services as $service)
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-5 py-3 font-medium text-slate-800">{{ $service->name }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ $service->duration_minutes }} min</td>
                        <td class="px-5 py-3 text-slate-500">₱{{ number_format($service->price, 2) }}</td>
                        <td class="px-5 py-3">
                            @if ($service->is_active)
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-brand-green/10 text-emerald-700">Active</span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-500">Hidden</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('admin.services.edit', $service) }}" class="text-brand-blue hover:underline">Edit</a>
                                <form method="POST" action="{{ route('admin.services.destroy', $service) }}" data-confirm="Remove the service “{{ $service->name }}”?">
                                    @csrf @method('DELETE')
                                    <button class="text-red-500 hover:underline">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">No services yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $services->links() }}</div>
@endsection
