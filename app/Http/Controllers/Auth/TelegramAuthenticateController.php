<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TelegramAuthenticateController extends Controller
{
    public function callback(Request $request)
    {

        $telegramUser = Socialite::driver('telegram')->user();



        $user = User::updateOrCreate([
            'telegram_id' => $telegramUser->id,
        ], [
            'name' => $telegramUser->name,
            'email' => $telegramUser->email,
            'avatar' => $telegramUser->getAvatar(),
        ]);


        Auth::login($user);

        return response()->json(["user" => $user]);
    }
}
