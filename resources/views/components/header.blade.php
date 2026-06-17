<!-- Header -->
<header class="sticky top-0 z-50 border-b border-slate-200 bg-white/80 backdrop-blur">
    <div class="container mx-auto px-6 flex items-center justify-between h-16">

        <!-- Left: Logo + Brand -->
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl shadow-soft gradient-brand flex items-center justify-center overflow-hidden">
                <img src="/images/logo.jpg" alt="Logo" class="w-full h-full object-cover" />
            </div>

            <a href="{{ route('home') }}" class="font-bold text-xl text-brand-navy leading-tight" style="font-family: Georgia, serif;">
                <span class="block">Bonoan's Dental Clinic</span>
                <span class="block text-xs uppercase font-extralight tracking-widest text-black"
                    style="font-family: system-ui, sans-serif; text-shadow: 0 0 8px rgba(0,0,0,0.25);">
                    Your smile. Our pride.
                </span>
            </a>
        </div>

        <!-- Center Nav -->
        <nav class="hidden md:flex items-center gap-6 text-sm font-medium text-slate-600">
            <a href="{{ route('home') }}" class="hover:text-brand-blue transition">Home</a>
            <a href="{{ route('services') }}" class="hover:text-brand-blue transition">Services</a>
            <a href="{{ route('about') }}" class="hover:text-brand-blue transition">About</a>
            <a href="{{ route('contact') }}" class="hover:text-brand-blue transition">Contact</a>
        </nav>

        <!-- Right Actions -->
        <div class="flex items-center gap-2">
            @auth
                <a href="{{ auth()->user()->canManageUsers() ? route('admin.dashboard') : route('dashboard') }}"
                    class="text-sm font-medium text-slate-600 hover:text-brand-blue transition px-3 py-1.5">
                    Dashboard
                </a>
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 hover:opacity-80 transition" title="My profile">
                    @include('partials.avatar', ['user' => auth()->user(), 'size' => 'h-8 w-8 text-xs'])
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="gradient-brand text-white text-sm font-semibold px-4 py-2 rounded-lg shadow-brand hover:opacity-90 transition">
                        Log out
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600 hover:text-brand-blue transition px-3 py-1.5">
                    Log in
                </a>
                <a href="{{ route('register') }}"
                    class="gradient-brand text-white text-sm font-semibold px-4 py-2 rounded-lg shadow-brand hover:opacity-90 transition">
                    Book now
                </a>
            @endauth
        </div>

    </div>
</header>
