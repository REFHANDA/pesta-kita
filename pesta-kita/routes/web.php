<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/login', function () {
    return view('login');
})->name('login');



Route::get('/register', function () {
    return view('register-klien');
})->name('register');

Route::get('/register/vendor', function () {
    return view('register-vendor');
})->name('register.vendor');

Route::get('/register/venue', function () {
    return view('register-vendor');
})->name('register.venue');

