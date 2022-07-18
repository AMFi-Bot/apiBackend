<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\v1\Auth\ApiAuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('auth')->group(function () {
    Route::post('/login', [ApiAuthController::class, 'login']);
    Route::post('/register', [ApiAuthController::class, 'register']);

    Route::middleware("auth:sanctum")->group(function () {
        Route::post('/logout', [ApiAuthController::class, 'logout']);
        Route::get('/verify_email/{id}/{hash}', [ApiAuthController::class, 'verify_email'])
            ->middleware(['signed'])
            ->name('verification.verify');;
        Route::post('/email_verification_notification', [ApiAuthController::class, 'send_email_verification_notification'])
            ->name('verification.send');

        Route::get('/email_verification', [ApiAuthController::class, 'check_email_verification']);
    });

    Route::get('/email_unverified', [ApiAuthController::class, 'email_unverified_notice'])->name('verification.notice');
});

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return response(['user' => $request->user()]);
});

Route::get('/test', function () {
    return response("test", 200);
});
