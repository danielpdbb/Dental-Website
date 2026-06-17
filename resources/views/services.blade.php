@extends('layouts.app')

@section('title', "Services & Pricing — Bonoan's Dental Clinic")
@section('description', 'Explore our dental services: cleanings, fillings, root canals, whitening, implants and more — with transparent prices.')

@section('content')
    @php
        // Placeholder catalogue. Replaced by DB-backed data once the Management
        // "service management" feature is built.
        $services = [
            ['name' => 'Dental Cleaning', 'description' => 'Professional scaling and polishing to remove plaque and tartar.', 'duration_minutes' => 45, 'base_price' => 800],
            ['name' => 'Tooth Extraction', 'description' => 'Simple or surgical removal of a damaged or impacted tooth.', 'duration_minutes' => 30, 'base_price' => 1200],
            ['name' => 'Composite Filling', 'description' => 'Tooth-coloured resin restoration for cavities.', 'duration_minutes' => 45, 'base_price' => 1500],
            ['name' => 'Root Canal Treatment', 'description' => 'Complete cleaning, shaping and sealing of infected root canals.', 'duration_minutes' => 90, 'base_price' => 5000],
            ['name' => 'Dental Crown', 'description' => 'Porcelain or zirconia cap that restores a broken or weakened tooth.', 'duration_minutes' => 60, 'base_price' => 7500],
            ['name' => 'Teeth Whitening', 'description' => 'In-office bleaching for a noticeably brighter smile.', 'duration_minutes' => 60, 'base_price' => 4500],
            ['name' => 'Dental Implant', 'description' => 'Titanium post with crown — a permanent replacement for a missing tooth.', 'duration_minutes' => 120, 'base_price' => 35000],
            ['name' => 'Orthodontic Braces', 'description' => 'Metal or ceramic braces to align teeth and correct your bite.', 'duration_minutes' => 60, 'base_price' => 30000],
        ];
    @endphp

    <div class="container mx-auto px-6 py-20">
        <div class="max-w-2xl">
            <div class="text-xs font-semibold tracking-widest text-brand-blue uppercase">Services</div>
            <h1 class="font-display text-5xl font-bold mt-3">Treatments &amp; pricing</h1>
            <p class="mt-4 text-slate-500">Transparent prices. No surprises.</p>
        </div>

        <div class="mt-12 grid md:grid-cols-2 gap-5">
            @foreach ($services as $service)
                <div class="rounded-2xl border border-slate-200/60 bg-white p-6 shadow-soft flex items-start justify-between gap-4 hover:shadow-brand transition">
                    <div class="flex-1 min-w-0">
                        <div class="font-display font-semibold text-lg">{{ $service['name'] }}</div>
                        <p class="text-sm text-slate-500 mt-1">{{ $service['description'] }}</p>
                        <div class="text-xs text-slate-400 mt-2">~ {{ $service['duration_minutes'] }} mins</div>
                    </div>
                    <div class="text-right shrink-0">
                        <div class="text-xs uppercase tracking-wider text-slate-400">Starting at</div>
                        <div class="font-display font-bold text-2xl text-gradient-brand">
                            ₱{{ number_format($service['base_price']) }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
