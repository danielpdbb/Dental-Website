@extends('layouts.app')

@section('title', "About — Bonoan's Dental Clinic")
@section('description', 'Learn about our mission, our team and our commitment to gentle, modern dental care.')

@section('content')
    {{-- Hero --}}
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-brand-blue/5 to-transparent"></div>
        <div class="relative container mx-auto px-6 pt-20 pb-12 max-w-4xl text-center">
            <div class="text-xs font-semibold tracking-widest text-brand-blue uppercase">Our story</div>
            <h1 class="font-display text-4xl sm:text-5xl font-bold mt-3 leading-tight">
                Gentle, modern dental care<br class="hidden sm:block"> with a <span class="text-gradient-brand">human touch</span>
            </h1>
            <p class="mt-6 text-lg text-slate-500 leading-relaxed max-w-2xl mx-auto">
                Founded on the belief that great dentistry should feel warm and human, Bonoan's Dental Clinic
                has served families in Dagupan City for over a decade — blending state-of-the-art tools with
                a patient-first approach.
            </p>
            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route('register') }}" class="h-11 px-6 inline-flex items-center rounded-xl gradient-brand text-white text-sm font-semibold shadow-brand hover:opacity-90 transition">Book an appointment</a>
                <a href="{{ route('services') }}" class="h-11 px-6 inline-flex items-center rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">See our services</a>
            </div>
        </div>
    </section>

    {{-- Stats band --}}
    <section class="container mx-auto px-6 max-w-4xl">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 rounded-2xl bg-white border border-slate-200/60 shadow-soft p-6">
            @foreach ([['10+', 'Years of care'], ['5,000+', 'Happy patients'], ['Mon–Sat', 'Open 6 days'], ['100%', 'Patient-first']] as [$stat, $label])
                <div class="text-center">
                    <div class="font-display text-3xl font-bold text-gradient-brand">{{ $stat }}</div>
                    <div class="text-xs text-slate-400 mt-1">{{ $label }}</div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Mission + values --}}
    <section class="container mx-auto px-6 py-16 max-w-5xl">
        <div class="grid lg:grid-cols-2 gap-10 items-start">
            <div>
                <h2 class="font-display text-2xl font-bold">Our mission</h2>
                <p class="mt-3 text-slate-500 leading-relaxed">
                    To make excellent dental care accessible, transparent, and a little bit delightful — so that
                    every visit leaves you healthier and smiling. We invest in modern technology and continuous
                    learning, but we never lose sight of the person in the chair.
                </p>
                <p class="mt-3 text-slate-500 leading-relaxed">
                    From your first check-up to long-term treatment plans, we keep things clear: honest pricing,
                    careful explanations, and a calm, judgement-free space.
                </p>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                @php
                    $values = [
                        ['Gentle care', 'Judgement-free, comfortable visits for all ages.', 'M11.5 19a7.5 7.5 0 1 0 0-15 7.5 7.5 0 0 0 0 15z'],
                        ['Honest pricing', 'Clear treatment plans and transparent costs.', 'M12 8c-1.7 0-3 .9-3 2s1.3 2 3 2 3 .9 3 2-1.3 2-3 2m0-8V6m0 12v-2'],
                        ['Modern & sterile', 'State-of-the-art, fully sanitised facilities.', 'M5 13l4 4L19 7'],
                        ['Always learning', 'We keep improving our skills and tools.', 'M12 14l9-5-9-5-9 5 9 5zm0 0v6'],
                    ];
                @endphp
                @foreach ($values as [$title, $desc, $path])
                    <div class="rounded-2xl bg-white border border-slate-200/60 p-5 shadow-soft hover:shadow-brand transition">
                        <div class="h-10 w-10 rounded-xl gradient-brand flex items-center justify-center text-white">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}"/></svg>
                        </div>
                        <div class="font-display font-bold mt-3">{{ $title }}</div>
                        <p class="text-sm text-slate-500 mt-1">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="container mx-auto px-6 pb-20 max-w-4xl">
        <div class="rounded-3xl gradient-brand text-white p-10 text-center shadow-brand">
            <h2 class="font-display text-2xl sm:text-3xl font-bold">Ready for a healthier smile?</h2>
            <p class="mt-2 text-white/80">Book online in minutes — choose your service, dentist and time.</p>
            <a href="{{ route('register') }}" class="mt-6 h-11 px-6 inline-flex items-center rounded-xl bg-white text-brand-blue text-sm font-semibold hover:bg-white/90 transition">Get started</a>
        </div>
    </section>
@endsection
