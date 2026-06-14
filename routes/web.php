<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/services', function () {
    return view('services');
});

Route::get('/about', function () {
    return view('about');
});

Route::get('/contact', function () {
    return view('contact');
});

Route::get('/auth', function () {
    // ?mode=signup shows the sign-up form; otherwise the login form
    return view('auth.login', ['mode' => request('mode', 'login')]);
});
