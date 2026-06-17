@extends('layouts.app')

@section('title', "Contact — Bonoan's Dental Clinic")
@section('description', 'Get in touch with Bonoan\'s Dental Clinic. Phone, email, address and clinic hours.')

@section('content')
    <div class="container mx-auto px-6 py-20 max-w-4xl">
        <div class="text-xs font-semibold tracking-widest text-brand-blue uppercase">Contact</div>
        <h1 class="mt-3 font-display text-5xl font-bold">Get in touch</h1>
        <p class="mt-4 text-slate-500 max-w-xl">
            Questions, feedback or want to schedule by phone? We'd love to hear from you.
        </p>

        <div class="mt-12 grid sm:grid-cols-2 gap-5">
            <!-- Visit -->
            <div class="rounded-2xl border border-slate-200/60 bg-white p-6 shadow-soft flex items-start gap-4">
                <div class="h-11 w-11 rounded-xl gradient-brand flex items-center justify-center text-white shrink-0">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 2C8.686 2 6 4.686 6 8c0 5.25 6 13 6 13s6-7.75 6-13c0-3.314-2.686-6-6-6z" />
                        <circle cx="12" cy="8" r="2" />
                    </svg>
                </div>
                <div>
                    <div class="font-display font-semibold">Visit</div>
                    <div class="text-sm text-slate-500 mt-1">Bonoan, Dagupan City, Pangasinan</div>
                </div>
            </div>

            <!-- Call -->
            <div class="rounded-2xl border border-slate-200/60 bg-white p-6 shadow-soft flex items-start gap-4">
                <div class="h-11 w-11 rounded-xl gradient-brand flex items-center justify-center text-white shrink-0">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81a19.79 19.79 0 01-3.07-8.67A2 2 0 012 1h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 8.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z" />
                    </svg>
                </div>
                <div>
                    <div class="font-display font-semibold">Call</div>
                    <div class="text-sm text-slate-500 mt-1">(075) 000-0000</div>
                </div>
            </div>

            <!-- Email -->
            <div class="rounded-2xl border border-slate-200/60 bg-white p-6 shadow-soft flex items-start gap-4">
                <div class="h-11 w-11 rounded-xl gradient-brand flex items-center justify-center text-white shrink-0">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="4" width="20" height="16" rx="2" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2 7l10 7 10-7" />
                    </svg>
                </div>
                <div>
                    <div class="font-display font-semibold">Email</div>
                    <div class="text-sm text-slate-500 mt-1">hello@bonoandental.ph</div>
                </div>
            </div>

            <!-- Hours -->
            <div class="rounded-2xl border border-slate-200/60 bg-white p-6 shadow-soft flex items-start gap-4">
                <div class="h-11 w-11 rounded-xl gradient-brand flex items-center justify-center text-white shrink-0">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2" />
                    </svg>
                </div>
                <div>
                    <div class="font-display font-semibold">Hours</div>
                    <div class="text-sm text-slate-500 mt-1">Mon–Sat · 9:00 AM – 6:00 PM</div>
                </div>
            </div>
        </div>
    </div>
@endsection
