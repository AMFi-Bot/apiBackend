<?php

namespace App\Http\Middleware\API\v1\Discord;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\API\v1\Discord\DiscordGuildsController;
use App\Models\DiscordGuild;

class DiscordGuildAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * 
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, string $type = "hard")
    {

        $guild_id = $request->route()->parameter('id');

        if (!DiscordGuild::where('id', $guild_id)->first())
            return response(["message" => "discord.guild.not_exist"], 401);


        if (
            isset($request->user()->discord_guilds) &&
            $request->user()->discord_guilds &&
            $type == "light"
        ) {
            foreach (json_decode($request->user()->discord_guilds) as $guild) {
                if ($guild->id == $guild_id) {
                    return $next($request);
                }
            }
        }

        $response = DiscordGuildsController::getGuilds(
            $request->user()->discord_token
        );

        if ($response["response_status"] == 200) {
            $guilds = $response["response_data"];

            $request->user()->discord_guilds = $guilds;
            $request->user()->save();

            foreach ($guilds as $guild) {
                if ($guild["id"] == $guild_id) {
                    return $next($request);
                }
            }

            return response("discord.guild.permissions_failed", 401);
        } else {
            return response("discord.guild.cannot_get", 500);
        }
    }
}
