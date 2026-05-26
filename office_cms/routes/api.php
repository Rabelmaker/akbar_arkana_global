<?php

use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->group(function () {
    Route::post('/login', [MobileAuthController::class, 'login']);
    Route::post('/register', [MobileAuthController::class, 'register']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [MobileAuthController::class, 'me']);
        Route::post('/logout', [MobileAuthController::class, 'logout']);
    });
});

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    $user = $request->user()->loadMissing('roles.permissions');

    return [
        ...$user->toArray(),
        'roles' => $user->getRoleNames()->values(),
        'permissions' => $user->getAllPermissions()->pluck('name')->values(),
    ];
});

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])
        ->middleware('permission:users.view');
    Route::get('/users/{user}', [UserController::class, 'show'])
        ->middleware('permission:users.view');
    Route::post('/users', [UserController::class, 'store'])
        ->middleware('permission:users.create');
    Route::put('/users/{user}', [UserController::class, 'update'])
        ->middleware('permission:users.edit');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])
        ->middleware('permission:users.delete');

    Route::get('/roles/users', [RoleController::class, 'users'])
        ->middleware('permission:users.view');
    Route::get('/roles', [RoleController::class, 'index'])
        ->middleware('permission:roles.view');
    Route::get('/roles/{role}', [RoleController::class, 'show'])
        ->middleware('permission:roles.view');
    Route::post('/roles', [RoleController::class, 'store'])
        ->middleware('permission:roles.create');
    Route::put('/roles/{role}', [RoleController::class, 'update'])
        ->middleware('permission:roles.edit');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
        ->middleware('permission:roles.delete');

    Route::get('/permissions', [PermissionController::class, 'index'])
        ->middleware('permission:roles.view');
});
