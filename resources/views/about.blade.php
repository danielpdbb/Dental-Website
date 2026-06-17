@extends('layouts.app')

@section('title', "About — Bonoan's Dental Clinic")
@section('description', 'Learn about our mission, our team and our commitment to gentle, modern dental care.')

@section('content')
    <div class="container mx-auto px-6 py-20 max-w-3xl">
        <div class="text-xs font-semibold tracking-widest text-brand-blue uppercase">Our story</div>
        <h1 class="font-display text-5xl font-bold mt-3">About Bonoan's Dental Clinic</h1>

        <p class="mt-6 text-lg text-slate-500 leading-relaxed">
            Founded with the belief that great dentistry should feel warm and human, Bonoan's Dental
            Clinic has been serving families in Dagupan City for over a decade. We blend
            state-of-the-art tools with a patient-first approach.
        </p>

        <h2 class="font-display text-2xl font-bold mt-12">Our mission</h2>
        <p class="mt-3 text-slate-500">
            To make excellent dental care accessible, transparent, and a little bit delightful.
        </p>

        <h2 class="font-display text-2xl font-bold mt-12">Our values</h2>
        <ul class="mt-3 space-y-2 text-slate-500 list-disc pl-6">
            <li>Gentle, judgement-free care</li>
            <li>Honest pricing and clear treatment plans</li>
            <li>Modern, sterile facilities</li>
            <li>Lifelong learning and continuous improvement</li>
        </ul>
    </div>
@endsection
