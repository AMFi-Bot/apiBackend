<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DiscordAuthenticateController extends Controller
{
    public function redirect()
    {
        /**
         * @var \SocialiteProviders\Discord\Provider $driver
         * 
         */
        $driver = Socialite::driver('discord');
        $driver->scopes(["identify", "email", "guilds"]);
        return $driver->redirect();
    }

    public function callback(Request $request)
    {
        $discordUser = Socialite::driver('discord')->user();

        $user = User::updateOrCreate([
            'discord_id' => $discordUser->id,
        ], [
            'name' => $discordUser->name,
            'email' => $discordUser->email,
            'discord_token' => $discordUser->token,
            'discord_refresh_token' => $discordUser->refreshToken,
            'avatar' => $discordUser->getAvatar(),
        ]);

        Auth::login($user);

        return response()->json(["user" => $user]);
    }
}
