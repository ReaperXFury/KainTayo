<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::livewire('/register', 'auth.register')->name('register');
    Route::livewire('/login', 'auth.login')->name('login');
});

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('/dashboard', 'admin.dashboard')->name('dashboard');
    Route::livewire('/orders', 'admin.orders')->name('orders');
    Route::livewire('/menu', 'admin.menu')->name('menu');
    Route::livewire('/customers', 'admin.customers')->name('customers');
    Route::livewire('/reports', 'admin.reports')->name('reports');
});

Route::middleware(['auth', 'role:customer'])->prefix('customer')->name('customer.')->group(function () {
    Route::livewire('/home', 'customer.home')->name('home');
});