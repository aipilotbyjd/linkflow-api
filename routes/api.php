<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\InvitationController;
use App\Http\Controllers\Api\V1\PlanController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\WorkspaceController;
use App\Http\Controllers\Api\V1\WorkspaceMemberController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->as('v1.')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public Routes
    |--------------------------------------------------------------------------
    */

    Route::get('plans', [PlanController::class, 'index'])->name('plans.index');

    /*
    |--------------------------------------------------------------------------
    | Auth Routes (Guest)
    |--------------------------------------------------------------------------
    */

    Route::prefix('auth')->as('auth.')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->name('register');
        Route::post('login', [AuthController::class, 'login'])->name('login');
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
        Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');
    });

    Route::get('verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware('signed')
        ->name('verification.verify');

    /*
    |--------------------------------------------------------------------------
    | Invitation Routes (Public)
    |--------------------------------------------------------------------------
    */

    Route::prefix('invitations/{token}')->as('invitations.')->group(function () {
        Route::post('accept', [InvitationController::class, 'accept'])->name('accept');
        Route::post('decline', [InvitationController::class, 'decline'])->name('decline');
    });

    /*
    |--------------------------------------------------------------------------
    | Authenticated Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:api')->group(function () {

        // Auth (Authenticated)
        Route::prefix('auth')->as('auth.')->group(function () {
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::post('resend-verification-email', [AuthController::class, 'resendVerificationEmail'])->name('resend-verification');
        });

        // User Profile
        Route::prefix('user')->as('user.')->group(function () {
            Route::get('/', [UserController::class, 'show'])->name('show');
            Route::put('/', [UserController::class, 'update'])->name('update');
            Route::put('password', [UserController::class, 'changePassword'])->name('password');
            Route::post('avatar', [UserController::class, 'uploadAvatar'])->name('avatar.upload');
            Route::delete('avatar', [UserController::class, 'deleteAvatar'])->name('avatar.delete');
            Route::delete('/', [UserController::class, 'destroy'])->name('destroy');
        });

        // Workspaces
        Route::apiResource('workspaces', WorkspaceController::class);

        // Workspace Nested Routes
        Route::prefix('workspaces/{workspace}')->as('workspaces.')->group(function () {

            // Members
            Route::prefix('members')->as('members.')->group(function () {
                Route::get('/', [WorkspaceMemberController::class, 'index'])->name('index');
                Route::put('{user}', [WorkspaceMemberController::class, 'update'])->name('update');
                Route::delete('{user}', [WorkspaceMemberController::class, 'destroy'])->name('destroy');
            });

            // Leave Workspace
            Route::post('leave', [WorkspaceMemberController::class, 'leave'])->name('leave');

            // Invitations
            Route::prefix('invitations')->as('invitations.')->group(function () {
                Route::get('/', [InvitationController::class, 'index'])->name('index');
                Route::post('/', [InvitationController::class, 'store'])->name('store');
                Route::delete('{invitation}', [InvitationController::class, 'destroy'])->name('destroy');
            });

            // Subscription
            Route::prefix('subscription')->as('subscription.')->group(function () {
                Route::get('/', [SubscriptionController::class, 'show'])->name('show');
                Route::post('/', [SubscriptionController::class, 'store'])->name('store');
                Route::delete('/', [SubscriptionController::class, 'destroy'])->name('destroy');
            });
        });
    });
});
