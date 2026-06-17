<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'Admin') — Bonoan's Dental Clinic</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { display: ['Georgia', 'serif'] },
                    colors: {
                        'brand-blue': '#3B82F6',
                        'brand-green': '#10B981',
                        'brand-navy': '#1E3A5F',
                    },
                    boxShadow: {
                        'brand': '0 8px 32px -4px rgba(59,130,246,0.18)',
                        'soft': '0 2px 16px -2px rgba(0,0,0,0.08)',
                    },
                }
            }
        }
    </script>
    <style>
        .gradient-brand { background: linear-gradient(135deg, #3B82F6 0%, #10B981 100%); }
    </style>
    @stack('styles')
</head>

<body class="min-h-screen bg-slate-50 text-slate-800">
    <div class="flex min-h-screen">

        <!-- Sidebar -->
        <aside class="hidden md:flex w-64 flex-col bg-brand-navy text-white">
            <div class="h-16 flex items-center gap-3 px-6 border-b border-white/10">
                <div class="w-9 h-9 rounded-lg gradient-brand flex items-center justify-center overflow-hidden">
                    <img src="/images/logo.jpg" alt="Logo" class="w-full h-full object-cover" />
                </div>
                <span class="font-display font-bold leading-tight">Admin Panel</span>
            </div>

            <nav class="flex-1 px-3 py-6 space-y-1 text-sm">
                <a href="{{ route('admin.dashboard') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg transition {{ request()->routeIs('admin.dashboard') ? 'bg-white/15 font-semibold' : 'text-white/80 hover:bg-white/10' }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                    Dashboard
                </a>
                <a href="{{ route('admin.users.index') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg transition {{ request()->routeIs('admin.users.*') ? 'bg-white/15 font-semibold' : 'text-white/80 hover:bg-white/10' }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 100-8 4 4 0 000 8z"/></svg>
                    Users
                </a>
            </nav>

            <div class="p-3 border-t border-white/10">
                <a href="{{ route('home') }}" class="block px-3 py-2 text-xs text-white/60 hover:text-white transition">← Back to website</a>
            </div>
        </aside>

        <!-- Main column -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top bar -->
            <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6">
                <h1 class="font-display text-lg font-bold">@yield('heading', 'Dashboard')</h1>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-slate-500 hidden sm:block">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-slate-600 hover:text-red-500 transition">Log out</button>
                    </form>
                </div>
            </header>

            <main class="flex-1 p-6">
                @if (session('status'))
                    <div class="mb-5 rounded-xl border border-brand-green/30 bg-brand-green/10 text-emerald-800 px-4 py-3 text-sm">
                        {{ session('status') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>

</html>
