<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\DiscordAuthenticateController;
use App\Http\Controllers\Auth\TelegramAuthenticateController;

use Illuminate\Support\Facades\Route;

Route::middleware('apiResponseValidator')->group(function () {
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('guest')
        ->name('register');

    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('guest')
        ->name('login');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->middleware('auth')
        ->name('logout');

    Route::prefix("/ext_services")->group(function () {
        Route::prefix("/discord")->group(function () {
            Route::get("/redirect", [DiscordAuthenticateController::class, 'redirect']);
            Route::get("/callback", [DiscordAuthenticateController::class, 'callback']);
        });
        Route::prefix("/telegram")->group(function () {
            Route::get("/callback", [TelegramAuthenticateController::class, 'callback']);
        });
    });
});


// Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
//                 ->middleware('guest')
//                 ->name('password.email');

// Route::post('/reset-password', [NewPasswordController::class, 'store'])
//                 ->middleware('guest')
//                 ->name('password.update');

// Route::get('/verify-email/{id}/{hash}', [VerifyEmailController::class, '__invoke'])
//                 ->middleware(['auth', 'signed', 'throttle:6,1'])
//                 ->name('verification.verify');

// Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
//                 ->middleware(['auth', 'throttle:6,1'])
//                 ->name('verification.send');
