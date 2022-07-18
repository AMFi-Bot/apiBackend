<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;

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

    public function callback()
    {
        return Socialite::driver('discord')->user();
    }
}
