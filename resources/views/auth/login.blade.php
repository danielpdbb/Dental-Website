<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $mode === 'signup' ? 'Create your account' : 'Log in' }} — Bonoan's Dental Clinic</title>
    <meta name="description" content="Log in to the Bonoan's Dental Clinic patient portal or create a new patient account." />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        display: ['Georgia', 'serif'],
                    },
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
        .gradient-brand {
            background: linear-gradient(135deg, #3B82F6 0%, #10B981 100%);
        }

        .text-gradient-brand {
            background: linear-gradient(135deg, #3B82F6 0%, #10B981 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col bg-white text-slate-800">

    @include('components.header')

    <main class="flex-1 flex items-center justify-center px-6 py-16">
        <div class="w-full max-w-md">
            <div class="rounded-3xl bg-white p-8 md:p-10 shadow-brand border border-slate-200/60">

                <!-- Heading -->
                <div class="text-center">
                    <h1 class="font-display text-3xl font-bold">
                        {{ $mode === 'signup' ? 'Create your account' : 'Welcome back' }}
                    </h1>
                    <p class="mt-2 text-sm text-slate-500">
                        {{ $mode === 'signup'
                            ? 'Sign up to book appointments and view your records.'
                            : 'Log in to your patient portal.' }}
                    </p>
                </div>

                <!-- TODO (backend): change action to "/auth" + method POST, add @csrf,
                     create a POST route and a controller to handle login/signup.
                     For now this is the front-end only, so the button does not submit. -->
                <form action="#" class="mt-8 space-y-4">

                    @if ($mode === 'signup')
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Full name</label>
                            <input type="text" name="name" placeholder="Juan Dela Cruz"
                                class="w-full h-11 px-4 rounded-xl border border-slate-200 focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 outline-none transition" />
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                        <input type="email" name="email" placeholder="you@example.com"
                            class="w-full h-11 px-4 rounded-xl border border-slate-200 focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 outline-none transition" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                        <input type="password" name="password" placeholder="••••••••"
                            class="w-full h-11 px-4 rounded-xl border border-slate-200 focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 outline-none transition" />
                    </div>

                    <button type="button"
                        class="w-full h-12 rounded-xl gradient-brand text-white font-semibold shadow-brand hover:opacity-90 transition">
                        {{ $mode === 'signup' ? 'Create account' : 'Log in' }}
                    </button>
                </form>

                <!-- Toggle between login / signup -->
                <p class="mt-6 text-center text-sm text-slate-500">
                    @if ($mode === 'signup')
                        Already have an account?
                        <a href="/auth" class="font-semibold text-gradient-brand">Log in</a>
                    @else
                        Don't have an account?
                        <a href="/auth?mode=signup" class="font-semibold text-gradient-brand">Sign up</a>
                    @endif
                </p>
            </div>
        </div>
    </main>

    @include('components.footer')

</body>

</html>
