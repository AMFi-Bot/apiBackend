<?php

namespace App\Http\Middleware\API\v1\Discord;

use Closure;
use Illuminate\Http\Request;
use App\Http\Controllers\API\v1\Discord\DiscordRequestController;

class DiscordAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * 
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        return $next($request);

        $response = DiscordRequestController::smartRequest(
            'GET',
            'users/@me',
            null,
            [],
            'application/json',
            'Bearer',
            $request,
        );

        if (
            $response['response_status'] == 200 &&
            $response['response_data']["id"] == $request->user()->discord_id
        ) {
            return $next($request);
        } else {
            return response("Unauthorized", 401);
        }
    }
}
