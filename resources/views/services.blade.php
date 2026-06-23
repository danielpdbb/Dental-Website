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
                <div class="rounded-2xl border border-slate-200/60 bg-white p-6 shadow-soft flex flex-col hover:shadow-brand transition">
                    <div class="flex items-start justify-between gap-4">
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
                    <a href="{{ route('portal.appointments.create', ['service_ids' => [$service->id]]) }}"
                       class="mt-4 inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-xl gradient-brand text-white text-sm font-semibold shadow-brand hover:opacity-90 transition">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M16 2v4M8 2v4M3 10h18"/></svg>
                        Book this service
                    </a>
                </div>
            @empty
                <p class="text-slate-400">Our service list is being updated. Please check back soon.</p>
            @endforelse
        </div>
    </div>
@endsection
