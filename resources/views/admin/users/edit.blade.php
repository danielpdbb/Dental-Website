@extends('layouts.admin')

@section('title', 'Edit user')
@section('heading', 'Edit user')

@section('content')
    <div class="max-w-3xl rounded-2xl bg-white border border-slate-200/60 p-6 md:p-8 shadow-soft">
        <form method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf
            @method('PUT')
            @include('admin.users.form', ['isEdit' => true])
        </form>
    </div>
@endsection
