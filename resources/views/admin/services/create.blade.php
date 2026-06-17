@extends('layouts.admin')

@section('title', 'New service')
@section('heading', 'New service')

@section('content')
    <div class="max-w-2xl rounded-2xl bg-white border border-slate-200/60 p-6 md:p-8 shadow-soft">
        <form method="POST" action="{{ route('admin.services.store') }}" data-review="Create this service?">
            @csrf
            @include('admin.services.form', ['service' => null])
            <div class="mt-7 flex items-center gap-3">
                <button class="h-11 px-6 rounded-xl gradient-brand text-white font-semibold shadow-brand hover:opacity-90 transition">Create service</button>
                <a href="{{ route('admin.services.index') }}" class="h-11 px-6 inline-flex items-center rounded-xl border border-slate-200 font-medium text-slate-600 hover:bg-slate-50 transition">Cancel</a>
            </div>
        </form>
    </div>
@endsection
