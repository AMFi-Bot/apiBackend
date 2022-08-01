<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\v1\Auth\ApiAuthController;

use App\Http\Controllers\API\v1\Discord\DiscordGuildsController;
use App\Http\Controllers\API\v1\Discord\DiscordGuildModulesController;

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

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return response(['user' => $request->user()]);
    });

    Route::prefix("discord")->middleware(["discordAuth"])->group(function () {
        Route::apiResource("guilds", DiscordGuildsController::class);
        Route::middleware(["discordGuildAuth"])->group(function () {
            Route::apiResource("guilds.modules", DiscordGuildModulesController::class);
        });
        // Route::prefix("guilds/{id}/modules")->group(function () {
        // });
    });
});


Route::get('/test', function () {
    return response("test", 200);
});
