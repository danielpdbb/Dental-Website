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
        /* Prettier native dropdowns: custom chevron + spacing */
        select:not([multiple]) {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 1.1rem;
            padding-right: 2.5rem;
            cursor: pointer;
        }
    </style>
    @stack('styles')
</head>

<body class="min-h-screen flex flex-col bg-white text-slate-800">

    @include('components.header')

    <main class="flex-1">
        @yield('content')
    </main>

    @include('components.footer')

    @include('partials.toast')
    @include('partials.confirm-modal')
    @include('partials.review-modal')
    @include('partials.select')
    @include('partials.chatbot')

    @stack('scripts')
</body>

</html>
