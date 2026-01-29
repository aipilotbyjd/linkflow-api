<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('login', [AuthController::class, 'login'])->name('auth.login');

    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::prefix('user')->group(function () {
            Route::get('/', [UserController::class, 'show'])->name('user.show');
            Route::put('/', [UserController::class, 'update'])->name('user.update');
            Route::put('password', [UserController::class, 'changePassword'])->name('user.password');
            Route::delete('/', [UserController::class, 'destroy'])->name('user.destroy');
        });
    });
});
