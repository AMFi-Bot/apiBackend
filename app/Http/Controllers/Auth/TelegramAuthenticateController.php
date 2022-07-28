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

        $telegramID = $telegramUser->id;
        $email = $telegramUser->email;

        if ($request->user()) {
            $user = $request->user();

            if (isset($request->user()->telegram_id)) {
                Auth::guard('web')->logout();

                $request->session()->invalidate();

                $request->session()->regenerateToken();

                return response()->noContent();
            }

            $adsUser = User::where(['telegram_id' => $telegramID])->first();

            if (
                $adsUser &&
                isset($adsUser->telegram_id) &&
                $adsUser->telegram_id == $telegramID
            ) {
                $adsUser->telegram_id = null;
                $adsUser->save();
            }

            $user->telegram_id = $telegramID;
            $user->email = isset($user->email) && $user->email ? $user->email : $email;
            $user->avatar = isset($user->avatar) && $user->avatar ? $user->avatar : $telegramUser->getAvatar();

            $user->save();
        } elseif (User::where(['telegram_id' => $telegramID])->first()) {
            $user = User::where(['telegram_id' => $telegramID])->first();

            $user->email = isset($user->email) && $user->email ? $user->email : $email;
            $user->avatar = isset($user->avatar) && $user->avatar ? $user->avatar : $telegramUser->getAvatar();

            $user->save();
        } elseif (User::where(['email' => $email])->first()) {
            $user = User::where(['email' => $email])->first();

            $user->telegram_id = $telegramID;
            $user->avatar = isset($user->avatar) && $user->avatar ? $user->avatar : $telegramUser->getAvatar();

            $user->save();
        } else {
            $user = User::create([
                'telegram_id' => $telegramID,
                'name' => $telegramUser->name,
                'email' => $telegramUser->email,
                'avatar' => $telegramUser->getAvatar(),
            ]);
        }

        Auth::login($user);

        return response()->json(["user" => $user]);
    }
}
