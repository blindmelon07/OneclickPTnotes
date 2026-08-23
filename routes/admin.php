<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'verified', 'role:admin'])
    ->group(function () {
        Route::redirect('/', 'admin/users');

        Route::livewire('users', 'pages::admin.users')->name('users');
        Route::livewire('roles', 'pages::admin.roles')->name('roles');
        Route::livewire('permissions', 'pages::admin.permissions')->name('permissions');
        Route::livewire('audit-log', 'pages::admin.audit-log')->name('audit-log');
    });
