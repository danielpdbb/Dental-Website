@extends('layouts.app')

@section('title', "Services & Pricing — Bonoan's Dental Clinic")
@section('description', 'Explore our dental services: cleanings, fillings, root canals, whitening, implants and more — with transparent prices.')

@section('content')
    <div class="container mx-auto px-6 py-20">
        <div class="max-w-2xl">
            <div class="text-xs font-semibold tracking-widest text-brand-blue uppercase">Services</div>
            <h1 class="font-display text-5xl font-bold mt-3">Treatments &amp; pricing</h1>
            <p class="mt-4 text-slate-500">Transparent prices. No surprises.</p>
        </div>

        <div class="mt-12 grid md:grid-cols-2 gap-5">
            @forelse ($services as $service)
                <div class="rounded-2xl border border-slate-200/60 bg-white p-6 shadow-soft flex items-start justify-between gap-4 hover:shadow-brand transition">
                    <div class="flex-1 min-w-0">
                        <div class="font-display font-semibold text-lg">{{ $service->name }}</div>
                        <p class="text-sm text-slate-500 mt-1">{{ $service->description }}</p>
                        <div class="text-xs text-slate-400 mt-2">~ {{ $service->duration_minutes }} mins</div>
                    </div>
                    <div class="text-right shrink-0">
                        <div class="text-xs uppercase tracking-wider text-slate-400">Starting at</div>
                        <div class="font-display font-bold text-2xl text-gradient-brand">
                            ₱{{ number_format($service->price, 2) }}
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-slate-400">Our service list is being updated. Please check back soon.</p>
            @endforelse
        </div>
    </div>
@endsection
