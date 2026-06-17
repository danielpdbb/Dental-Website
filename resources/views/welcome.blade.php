@extends('layouts.app')

@section('title', "Bonoan's Dental Clinic — Your smile. Our passion. Our pride.")
@section('description', 'Book your dental appointment online. Cleanings, fillings, whitening, implants and more in Bonoan, Dagupan City.')

@push('styles')
    <style>
        .blur-brand-tl { position: absolute; top: -8rem; left: -8rem; width: 24rem; height: 24rem; border-radius: 9999px; background: rgba(59, 130, 246, 0.20); filter: blur(64px); z-index: -1; }
        .blur-brand-tr { position: absolute; top: 5rem; right: -8rem; width: 24rem; height: 24rem; border-radius: 9999px; background: rgba(16, 185, 129, 0.20); filter: blur(64px); z-index: -1; }
    </style>
@endpush

@section('content')
    <!-- Hero -->
    <section class="relative overflow-hidden">
        <div class="blur-brand-tl"></div>
        <div class="blur-brand-tr"></div>
        <div class="container mx-auto px-6 pt-20 pb-24 grid lg:grid-cols-2 gap-12 items-center">
            <div>
                <!-- Eyebrow -->
                <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white/60 backdrop-blur px-3 py-1 text-xs font-medium text-slate-500 mb-6">
                    <svg class="h-3.5 w-3.5 text-brand-green" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 3l14 9-14 9V3z" />
                    </svg>
                    Modern dental care · Bonoan, Dagupan
                </div>
                <h1 class="font-display text-5xl md:text-6xl font-bold leading-tight">
                    Your smile.<br />
                    <span class="text-gradient-brand">Our passion. Our pride.</span>
                </h1>
                <p class="mt-6 text-lg text-slate-500 max-w-xl">
                    Friendly, gentle, expert dental care for the whole family. Book online in seconds,
                    view your records anytime, and pay your bill securely.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('register') }}"
                        class="inline-flex items-center gap-2 gradient-brand text-white font-semibold h-12 px-7 rounded-xl shadow-brand hover:opacity-90 transition">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 2v4M8 2v4M3 10h18M9 16l2 2 4-4" />
                        </svg>
                        Book an appointment
                    </a>
                    <a href="#services"
                        class="inline-flex items-center h-12 px-7 rounded-xl border border-slate-200 font-semibold text-slate-700 hover:bg-slate-50 transition">
                        View services
                    </a>
                </div>
                <div class="mt-10 flex items-center gap-8 text-sm text-slate-500">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-brand-green" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4" />
                        </svg>
                        Sterile environment
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-brand-blue" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12h2l2-7 4 14 3-9 2 5h5" />
                        </svg>
                        Patient-first care
                    </div>
                </div>
            </div>

            <!-- Card -->
            <div class="relative">
                <div class="absolute inset-0 -z-10 gradient-brand rounded-[2.5rem] blur-2xl opacity-30"></div>
                <div class="rounded-[2.5rem] bg-white p-10 shadow-brand border border-slate-200/60 relative overflow-hidden">
                    <div class="w-44 h-44 mx-auto rounded-2xl shadow-soft gradient-brand flex items-center justify-center overflow-hidden">
                        <img src="/images/logo.jpg" alt="Logo" class="w-full h-full object-cover" />
                    </div>
                    <div class="mt-8 grid grid-cols-3 gap-4 text-center">
                        <div>
                            <div class="font-display text-2xl font-bold text-gradient-brand">15+</div>
                            <div class="text-xs uppercase tracking-wider text-slate-400 mt-1">Years</div>
                        </div>
                        <div>
                            <div class="font-display text-2xl font-bold text-gradient-brand">5k+</div>
                            <div class="text-xs uppercase tracking-wider text-slate-400 mt-1">Patients</div>
                        </div>
                        <div>
                            <div class="font-display text-2xl font-bold text-gradient-brand">4.9★</div>
                            <div class="text-xs uppercase tracking-wider text-slate-400 mt-1">Rating</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services -->
    <section id="services" class="container mx-auto px-6 py-20">
        <div class="max-w-2xl">
            <div class="text-xs font-semibold tracking-widest text-brand-blue uppercase">What we do</div>
            <h2 class="font-display text-4xl font-bold mt-2">Comprehensive dental services</h2>
            <p class="mt-3 text-slate-500">From routine checkups to advanced procedures, all under one roof.</p>
        </div>
        <div class="mt-12 grid md:grid-cols-3 gap-6">
            <div class="rounded-2xl border border-slate-200/60 bg-white p-7 shadow-soft hover:shadow-brand transition group">
                <div class="h-12 w-12 rounded-xl gradient-brand flex items-center justify-center text-white mb-5 group-hover:scale-110 transition">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 14s1.5 2 4 2 4-2 4-2" />
                        <line x1="9" y1="9" x2="9.01" y2="9" stroke-linecap="round" stroke-width="3" />
                        <line x1="15" y1="9" x2="15.01" y2="9" stroke-linecap="round" stroke-width="3" />
                    </svg>
                </div>
                <div class="font-display font-semibold text-lg">Preventive Care</div>
                <p class="mt-2 text-sm text-slate-500">Cleanings, fluoride and check-ups to keep your smile bright.</p>
            </div>
            <div class="rounded-2xl border border-slate-200/60 bg-white p-7 shadow-soft hover:shadow-brand transition group">
                <div class="h-12 w-12 rounded-xl gradient-brand flex items-center justify-center text-white mb-5 group-hover:scale-110 transition">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 3H5a2 2 0 00-2 2v4a6 6 0 006 6 6 6 0 006-6V5a2 2 0 00-2-2h-4z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 13v3a4 4 0 008 0v-1" />
                        <circle cx="21" cy="15" r="1" />
                    </svg>
                </div>
                <div class="font-display font-semibold text-lg">Restorative</div>
                <p class="mt-2 text-sm text-slate-500">Fillings, root canals, crowns and bridges.</p>
            </div>
            <div class="rounded-2xl border border-slate-200/60 bg-white p-7 shadow-soft hover:shadow-brand transition group">
                <div class="h-12 w-12 rounded-xl gradient-brand flex items-center justify-center text-white mb-5 group-hover:scale-110 transition">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 3l1.5 4.5L11 9l-4.5 1.5L5 15l-1.5-4.5L-1 9l4.5-1.5L5 3zM19 13l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3z" />
                    </svg>
                </div>
                <div class="font-display font-semibold text-lg">Cosmetic</div>
                <p class="mt-2 text-sm text-slate-500">Whitening, veneers and smile makeovers.</p>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="container mx-auto px-6 py-16">
        <div class="rounded-3xl gradient-brand p-12 text-center text-white shadow-brand">
            <h3 class="font-display text-3xl md:text-4xl font-bold">Ready for your next visit?</h3>
            <p class="mt-3 text-white/90">Sign up in seconds and book your appointment online.</p>
            <a href="{{ route('register') }}"
                class="inline-block mt-6 h-12 px-8 rounded-xl bg-white text-brand-navy font-semibold leading-[3rem] hover:bg-white/90 transition shadow">
                Create your patient account
            </a>
        </div>
    </section>
@endsection
