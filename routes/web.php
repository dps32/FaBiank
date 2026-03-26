<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/dashboard');
    }

    return redirect('/login');
})->name('home');


// Login
Route::get('/login', function () {
    return view('login');
})->name('login');


// Register
Route::get('/register', function () {
    return view('register');
})->name('register');
