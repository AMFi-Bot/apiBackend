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

        $discordID = $discordUser->id;
        $email = $discordUser->email;

        if ($request->user()) {
            $user = $request->user();

            if (isset($request->user()->discord_id)) {
                Auth::guard('web')->logout();

                $request->session()->invalidate();

                $request->session()->regenerateToken();

                return response()->noContent();
            }

            $adsUser = User::where(['discord_id' => $discordID])->first();

            if (
                $adsUser &&
                isset($adsUser->discord_id) &&
                $adsUser->discord_id == $discordID
            ) {
                $adsUser->discord_id = null;
                $adsUser->save();
            }

            $user->discord_id = $discordID;
            $user->discord_token = $discordUser->token;
            $user->discord_refresh_token = $discordUser->refreshToken;
            $user->email = isset($user->email) && $user->email ? $user->email : $email;
            $user->avatar = isset($user->avatar) && $user->avatar ? $user->avatar : $discordUser->getAvatar();

            $user->save();
        } elseif (User::where(['discord_id' => $discordID])->first()) {
            $user = User::where(['discord_id' => $discordID])->first();

            $user->discord_token = $discordUser->token;
            $user->discord_refresh_token = $discordUser->refreshToken;
            $user->email = isset($user->email) && $user->email ? $user->email : $email;
            $user->avatar = isset($user->avatar) && $user->avatar ? $user->avatar : $discordUser->getAvatar();

            $user->save();
        } elseif (User::where(['email' => $email])->first()) {
            $user = User::where(['email' => $email])->first();

            $user->discord_token = $discordUser->token;
            $user->discord_refresh_token = $discordUser->refreshToken;
            $user->discord_id = $discordID;
            $user->avatar = isset($user->avatar) && $user->avatar ? $user->avatar : $discordUser->getAvatar();

            $user->save();
        } else {
            $user = User::create([
                'discord_id' => $discordID,
                'name' => $discordUser->name,
                'email' => $discordUser->email,
                'discord_token' => $discordUser->token,
                'discord_refresh_token' => $discordUser->refreshToken,
                'avatar' => $discordUser->getAvatar(),
            ]);
        }

        Auth::login($user);

        return response()->json(["user" => $user]);
    }
}
