<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\InvitationController;
use App\Http\Controllers\Api\V1\PlanController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\WorkspaceController;
use App\Http\Controllers\Api\V1\WorkspaceMemberController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('plans', [PlanController::class, 'index'])->name('plans.index');

    Route::post('register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('login', [AuthController::class, 'login'])->name('auth.login');

    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('password.email');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

    Route::get('verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware('signed')
        ->name('verification.verify');

    Route::post('invitations/{token}/accept', [InvitationController::class, 'accept'])->name('invitations.accept');
    Route::post('invitations/{token}/decline', [InvitationController::class, 'decline'])->name('invitations.decline');

    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::post('resend-verification-email', [AuthController::class, 'resendVerificationEmail'])
            ->name('verification.send');

        Route::prefix('user')->group(function () {
            Route::get('/', [UserController::class, 'show'])->name('user.show');
            Route::put('/', [UserController::class, 'update'])->name('user.update');
            Route::put('password', [UserController::class, 'changePassword'])->name('user.password');
            Route::delete('/', [UserController::class, 'destroy'])->name('user.destroy');
        });

        Route::apiResource('workspaces', WorkspaceController::class);

        Route::get('workspaces/{workspace}/members', [WorkspaceMemberController::class, 'index'])->name('workspaces.members.index');
        Route::put('workspaces/{workspace}/members/{user}', [WorkspaceMemberController::class, 'update'])->name('workspaces.members.update');
        Route::delete('workspaces/{workspace}/members/{user}', [WorkspaceMemberController::class, 'destroy'])->name('workspaces.members.destroy');

        Route::get('workspaces/{workspace}/invitations', [InvitationController::class, 'index'])->name('workspaces.invitations.index');
        Route::post('workspaces/{workspace}/invitations', [InvitationController::class, 'store'])->name('workspaces.invitations.store');
        Route::delete('workspaces/{workspace}/invitations/{invitation}', [InvitationController::class, 'destroy'])->name('workspaces.invitations.destroy');

        Route::get('workspaces/{workspace}/subscription', [SubscriptionController::class, 'show'])->name('workspaces.subscription.show');
        Route::post('workspaces/{workspace}/subscription', [SubscriptionController::class, 'store'])->name('workspaces.subscription.store');
        Route::delete('workspaces/{workspace}/subscription', [SubscriptionController::class, 'destroy'])->name('workspaces.subscription.destroy');
    });
});
