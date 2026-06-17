<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Access denied — Bonoan's Dental Clinic</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { display: ['Georgia', 'serif'] }, colors: { 'brand-blue': '#3B82F6', 'brand-green': '#10B981', 'brand-navy': '#1E3A5F' }, boxShadow: { 'brand': '0 8px 32px -4px rgba(59,130,246,0.18)' } } } }
    </script>
    <style>.gradient-brand { background: linear-gradient(135deg, #3B82F6 0%, #10B981 100%); }</style>
</head>

<body class="min-h-screen bg-slate-50 flex items-center justify-center px-6 text-slate-800">
    <div class="text-center max-w-md">
        <div class="mx-auto h-16 w-16 rounded-2xl gradient-brand flex items-center justify-center text-white">
            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m0-9a4 4 0 014 4v1H8v-1a4 4 0 014-4zM5 11V9a7 7 0 0114 0v2" />
            </svg>
        </div>
        <h1 class="mt-6 font-display text-3xl font-bold">Access denied</h1>
        <p class="mt-3 text-slate-500">
            {{ $exception?->getMessage() ?: 'You do not have permission to access this area.' }}
        </p>
        <div class="mt-8 flex items-center justify-center gap-3">
            @auth
                <a href="{{ url()->previous() }}" class="h-11 px-6 inline-flex items-center rounded-xl border border-slate-200 font-medium text-slate-600 hover:bg-slate-100 transition">Go back</a>
                <a href="{{ route('home') }}" class="h-11 px-6 inline-flex items-center rounded-xl gradient-brand text-white font-semibold shadow-brand hover:opacity-90 transition">Home</a>
            @else
                <a href="{{ route('login') }}" class="h-11 px-6 inline-flex items-center rounded-xl gradient-brand text-white font-semibold shadow-brand hover:opacity-90 transition">Log in</a>
            @endauth
        </div>
    </div>
</body>

</html>
