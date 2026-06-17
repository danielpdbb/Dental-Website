@extends('layouts.admin')

@section('title', 'New patient')
@section('heading', 'New patient')

@section('content')
    <div class="max-w-3xl rounded-2xl bg-white border border-slate-200/60 p-6 md:p-8 shadow-soft">
        <form method="POST" action="{{ route('clinic.patients.store') }}">
            @csrf
            @include('clinic.patients.form', ['patient' => null])
            <div class="mt-7 flex items-center gap-3">
                <button type="submit" class="h-11 px-6 rounded-xl gradient-brand text-white font-semibold shadow-brand hover:opacity-90 transition">Create patient</button>
                <a href="{{ route('clinic.patients.index') }}" class="h-11 px-6 inline-flex items-center rounded-xl border border-slate-200 font-medium text-slate-600 hover:bg-slate-50 transition">Cancel</a>
            </div>
        </form>
    </div>
@endsection
