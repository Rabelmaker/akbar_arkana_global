<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (request()->inertia()) {
        return Inertia::location(request()->fullUrl());
    }

    return response()->view('public')->header('Vary', 'X-Inertia');
})->name('home')
    ->withoutMiddleware(HandleInertiaRequests::class);

Route::view('/contact', 'public')->name('contact')->withoutMiddleware(HandleInertiaRequests::class);

Route::post('/api/contact', \App\Http\Controllers\ContactController::class)->middleware('throttle:5,1');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';

Route::fallback(function () {
    $path = request()->path();

    abort_if(
        request()->expectsJson()
        || preg_match('~^(?:api|assets|build|storage)(?:/|$)|[.][^/]+$~', $path),
        404,
    );

    return response()->view('public', status: 404);
})->withoutMiddleware(HandleInertiaRequests::class);
