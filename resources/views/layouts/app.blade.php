<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', "Bonoan's Dental Clinic")</title>
    <meta name="description" content="@yield('description', 'Modern, gentle dental care in Bonoan, Dagupan City. Book online, view your records and pay securely.')" />

    {{-- NOTE: Tailwind is loaded via CDN for now. Before going to production, compile
         it with Vite (see README) — the CDN build is not meant for production. --}}
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
        .text-gradient-brand {
            background: linear-gradient(135deg, #3B82F6 0%, #10B981 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
    @stack('styles')
</head>

<body class="min-h-screen flex flex-col bg-white text-slate-800">

    @include('components.header')

    @include('partials.flash')

    <main class="flex-1">
        @yield('content')
    </main>

    @include('components.footer')

    @stack('scripts')
</body>

</html>
