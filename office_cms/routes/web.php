<?php

use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::view('/admin/dashboard', 'app')
    ->middleware(['auth', 'verified', 'permission:dashboard.view']);

Route::view('/admin/roles', 'app')
    ->middleware(['auth', 'verified', 'permission:roles.view']);

Route::view('/admin/users', 'app')
    ->middleware(['auth', 'verified', 'permission:users.view']);

Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');
