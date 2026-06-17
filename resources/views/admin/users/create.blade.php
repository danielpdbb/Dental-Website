@extends('layouts.admin')

@section('title', 'New user')
@section('heading', 'Create user')

@section('content')
    <div class="max-w-3xl rounded-2xl bg-white border border-slate-200/60 p-6 md:p-8 shadow-soft">
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf
            @include('admin.users.form', ['isEdit' => false])
        </form>
    </div>
@endsection
