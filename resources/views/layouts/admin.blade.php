<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'Clinic') — Bonoan's Dental Clinic</title>

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
    @php
        $role = auth()->user()->role->value;
        $isMgmt = $role === 'management';
        $canDesk = in_array($role, ['management', 'receptionist']); // appointments, referrals, scheduling

        // [label, route name, active-pattern, visible?]
        $links = array_filter([
            ['Dashboard', 'admin.dashboard', 'admin.dashboard', $isMgmt],
            ['Appointments', 'clinic.appointments.index', 'clinic.appointments.*', $canDesk],
            ['Patients', 'clinic.patients.index', 'clinic.patients.*', true],
            ['Scheduling', 'clinic.scheduling', 'clinic.scheduling', $canDesk],
            ['Referrals', 'clinic.referrals.index', 'clinic.referrals.*', $canDesk],
            ['Services', 'admin.services.index', 'admin.services.*', $isMgmt],
            ['Analytics', 'admin.analytics', 'admin.analytics', $isMgmt],
            ['Users', 'admin.users.index', 'admin.users.*', $isMgmt],
        ], fn ($l) => $l[3]);
    @endphp

    <div class="flex min-h-screen">

        <!-- Sidebar -->
        <aside class="hidden md:flex w-64 flex-col bg-brand-navy text-white">
            <div class="h-16 flex items-center gap-3 px-6 border-b border-white/10">
                <div class="w-9 h-9 rounded-lg gradient-brand flex items-center justify-center overflow-hidden">
                    <img src="/images/logo.jpg" alt="Logo" class="w-full h-full object-cover" />
                </div>
                <span class="font-display font-bold leading-tight">Clinic Panel</span>
            </div>

            <nav class="flex-1 px-3 py-6 space-y-1 text-sm">
                @foreach ($links as [$label, $routeName, $pattern])
                    <a href="{{ route($routeName) }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg transition {{ request()->routeIs($pattern) ? 'bg-white/15 font-semibold' : 'text-white/80 hover:bg-white/10' }}">
                        <span class="h-1.5 w-1.5 rounded-full bg-current opacity-60"></span>
                        {{ $label }}
                    </a>
                @endforeach
            </nav>

            <div class="p-3 border-t border-white/10">
                <a href="{{ route('home') }}" class="block px-3 py-2 text-xs text-white/60 hover:text-white transition">← Back to website</a>
            </div>
        </aside>

        <!-- Main column -->
        <div class="flex-1 flex flex-col min-w-0">
            <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6">
                <h1 class="font-display text-lg font-bold">@yield('heading', 'Dashboard')</h1>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-slate-500 hidden sm:block">{{ auth()->user()->name }}
                        <span class="text-slate-300">·</span> {{ auth()->user()->role->label() }}</span>
                    <form method="POST" action="{{ route('logout') }}">
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

                @if ($errors->any())
                    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 text-red-600 px-4 py-3 text-sm">
                        <ul class="list-disc pl-5 space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>

</html>
