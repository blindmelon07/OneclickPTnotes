<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check()
    ? redirect()->route(auth()->user()->landingRoute())
    : redirect()->route('login'))->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')
        ->middleware('unless_hidden:'.User::HIDE_DASHBOARD)
        ->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
require __DIR__.'/patients.php';
require __DIR__.'/invoices.php';
