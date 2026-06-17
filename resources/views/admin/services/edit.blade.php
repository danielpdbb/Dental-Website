@extends('layouts.admin')

@section('title', 'Edit service')
@section('heading', 'Edit service')

@section('content')
    <div class="max-w-2xl rounded-2xl bg-white border border-slate-200/60 p-6 md:p-8 shadow-soft">
        <form method="POST" action="{{ route('admin.services.update', $service) }}">
            @csrf
            @method('PUT')
            @include('admin.services.form', ['service' => $service])
            <div class="mt-7 flex items-center gap-3">
                <button class="h-11 px-6 rounded-xl gradient-brand text-white font-semibold shadow-brand hover:opacity-90 transition">Save changes</button>
                <a href="{{ route('admin.services.index') }}" class="h-11 px-6 inline-flex items-center rounded-xl border border-slate-200 font-medium text-slate-600 hover:bg-slate-50 transition">Cancel</a>
            </div>
        </form>
    </div>
@endsection
