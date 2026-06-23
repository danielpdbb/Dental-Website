<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Login — Bonoan's Dental Clinic</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { display: ['Georgia', 'serif'] }, colors: { 'brand-blue': '#3B82F6', 'brand-green': '#10B981', 'brand-navy': '#1E3A5F' }, boxShadow: { 'brand': '0 8px 32px -4px rgba(59,130,246,0.18)' } } } }
    </script>
    <style>.gradient-brand { background: linear-gradient(135deg, #3B82F6 0%, #10B981 100%); }</style>
</head>

<body class="min-h-screen bg-brand-navy flex items-center justify-center px-6">
    <div class="w-full max-w-md">
        <div class="text-center mb-6">
            <div class="mx-auto w-12 h-12 rounded-xl gradient-brand flex items-center justify-center overflow-hidden">
                <img src="/images/logo.jpg" alt="Logo" class="w-full h-full object-cover" />
            </div>
            <h1 class="mt-4 text-white font-display text-2xl font-bold">Admin Panel</h1>
            <p class="text-white/60 text-sm mt-1">Authorized staff only</p>
        </div>

        <div class="rounded-3xl bg-white p-8 shadow-brand">
            @if ($errors->any())
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 text-red-600 px-4 py-3 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="login" class="block text-sm font-medium text-slate-700 mb-1">Email or username</label>
                    <input id="login" type="text" name="login" value="{{ old('login') }}" required autofocus
                        class="w-full h-11 px-4 rounded-xl border border-slate-200 focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 outline-none transition" />
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password"
                        class="w-full h-11 px-4 rounded-xl border border-slate-200 focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 outline-none transition" />
                </div>
                <button type="submit" class="w-full h-12 rounded-xl gradient-brand text-white font-semibold shadow-brand hover:opacity-90 transition">
                    Sign in
                </button>
            </form>

            <p class="mt-5 text-center text-sm">
                <a href="{{ route('password.request') }}" class="font-medium text-slate-500 hover:text-brand-blue transition">Forgot password?</a>
            </p>
        </div>

        <p class="text-center mt-6">
            <a href="{{ route('home') }}" class="text-white/50 text-sm hover:text-white transition">← Back to website</a>
        </p>
    </div>
</body>

</html>
