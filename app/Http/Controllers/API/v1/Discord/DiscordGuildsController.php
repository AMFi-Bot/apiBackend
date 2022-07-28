<?php

namespace App\Http\Controllers\API\v1\Discord;

use App\Http\Controllers\Controller;
use App\Models\DiscordGuild;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;

use Laravel\Socialite\Facades\Socialite;
use GuzzleHttp;

class DiscordGuildsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $guildsResponse = DiscordGuildsController::getGuilds($request->user()->discord_token);


        if ($guildsResponse["response_status"] == 200) {
            return response(["guilds" => $guildsResponse["response_data"]]);
        } else {
            return response("Something went wrong. Upstream server returned bad status code", 401);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $code = $request->input("code");
        $client_id = env("DISCORD_CLIENT_ID");
        $client_secret = env("DISCORD_CLIENT_SECRET");
        $redirect_uri = env("DISCORD_BOT_SETUP_REDIRECT_URI");

        $response = DiscordRequestController::fetchRequest(
            "POST",
            "oauth2/token",
            [
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $redirect_uri
            ],
            [],
            "application/x-www-form-urlencoded",
        );

        if ($response["response_status"] == 200) {
            $guild = $response["response_data"]["guild"];

            if (($request->input("permissions") & 0x8) != 0x8) {
                return response("discord.guild.permission.non_admin", 401);
            }

            $channels = DiscordRequestController::smartRequest(
                "GET",
                "guilds/" . $guild["id"] . "/channels",
                null,
                [],
                "application/json",
                "Bot"
            )["response_data"];

            $guild = DiscordGuild::create([
                'id' => $guild["id"],
                'name' => $guild["name"],
                'icon' => $guild["icon"],
                'roles' => json_encode($guild["roles"]),
                'channels' => json_encode($channels),
            ]);

            DiscordRequestController::smartRequest(
                "POST",
                "channels/" . $guild['system_channel_id'] . "/messages",
                [
                    "content" => "The bot has joined! Hope your " .
                        "communication to became easier :)",
                ],
                [],
                "application/json",
                "Bot",
            );



            return response(["guild" => $guild]);
        } else {
            Log::debug($response);
            return response("discord.guild.authentication_failed", 401);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\DiscordGuild  $discordGuild
     * @return \Illuminate\Http\Response
     */
    public function show(DiscordGuild $discordGuild)
    {
        if ($discordGuild)
            return response()->json(["guild" => $discordGuild->all()[0]], 200);
        else {
            return response('discord.guild.not_found', 404);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\DiscordGuild  $discordGuild
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, DiscordGuild $discordGuild)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\DiscordGuild  $discordGuild
     * @return \Illuminate\Http\Response
     */
    public function destroy(DiscordGuild $discordGuild)
    {
        //
    }

    public static function getGuilds($discordToken)
    {
        $response = DiscordRequestController::fetchRequest(
            "GET",
            "users/@me/guilds",
            null,
            [
                "Authorization" => "Bearer $discordToken"
            ],
        );

        if ($response["response_status"] == 200) {
            $guilds = $response["response_data"];

            $guilds = array_filter($guilds, function ($g) {
                return ($g["permissions"] & 0x8) == 0x8;
            });

            foreach ($guilds as $key => $guild) {
                $discord_guild = DiscordGuild::where('id', $guild["id"])
                    ->first();

                $guild["bot_exists"] = isset($discord_guild) ? true : false;
                $guilds[$key] = $guild;
            }

            return [
                'response_status' => 200,
                'response_data' => $guilds,
                'response_headers' => $response["response_headers"]
            ];
        } else {
            return $response;
        }
    }
}
