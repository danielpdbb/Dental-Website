@extends('layouts.admin')

@section('title', 'Users')
@section('heading', 'User management')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap gap-2">
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search name, username, email"
                class="h-10 px-4 rounded-lg border border-slate-200 text-sm focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 outline-none w-64" />
            <select name="role" class="h-10 px-3 rounded-lg border border-slate-200 text-sm focus:border-brand-blue outline-none">
                <option value="">All roles</option>
                @foreach ($roles as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['role'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="h-10 px-4 rounded-lg bg-slate-800 text-white text-sm font-medium hover:bg-slate-700 transition">Filter</button>
        </form>

        <a href="{{ route('admin.users.create') }}"
            class="h-10 px-4 inline-flex items-center gap-2 rounded-lg gradient-brand text-white text-sm font-semibold shadow-brand hover:opacity-90 transition">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New user
        </a>
    </div>

    <div class="rounded-2xl bg-white border border-slate-200/60 shadow-soft overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-5 py-3 font-medium">Name</th>
                    <th class="px-5 py-3 font-medium">Username</th>
                    <th class="px-5 py-3 font-medium">Email</th>
                    <th class="px-5 py-3 font-medium">Role</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($users as $row)
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                @include('partials.avatar', ['user' => $row, 'size' => 'h-9 w-9 text-xs'])
                                <span class="font-medium text-slate-800">{{ $row->name }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-slate-500">{{ $row->username }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ $row->email }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $row->role->badgeClasses() }}">{{ $row->role->label() }}</span>
                        </td>
                        <td class="px-5 py-3">
                            @php $status = $row->status(); @endphp
                            <span class="inline-flex items-center gap-1.5 text-xs font-medium
                                @class([
                                    'text-emerald-600' => $status === 'active',
                                    'text-amber-600' => $status === 'unverified',
                                    'text-red-500' => $status === 'suspended',
                                ])">
                                <span class="h-1.5 w-1.5 rounded-full @class([
                                    'bg-emerald-500' => $status === 'active',
                                    'bg-amber-500' => $status === 'unverified',
                                    'bg-red-500' => $status === 'suspended',
                                ])"></span>
                                {{ ucfirst($status) }}
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('admin.users.edit', $row) }}" class="text-brand-blue hover:underline">Edit</a>
                                @can('delete', $row)
                                    <form method="POST" action="{{ route('admin.users.destroy', $row) }}"
                                        data-confirm="Remove {{ $row->name }}? Their account will be deactivated.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:underline">Delete</button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-slate-400">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>
@endsection
