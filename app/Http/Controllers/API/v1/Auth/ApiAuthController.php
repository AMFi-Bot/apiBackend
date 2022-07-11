<?php

namespace App\Http\Controllers\API\v1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

class ApiAuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);
        if ($validator->fails()) {
            return response(['message' => $validator->errors()->all()], 422);
        }
        $request['password'] = Hash::make($request['password']);
        $request['remember_token'] = Str::random(10);
        $user = User::create($request->toArray());
        $token = $user->createToken('Access Token')->accessToken;
        $response = ["data" => [
            'token_type' => 'Bearer',
            'token' => $token
        ]];
        return response($response, 200);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }
        $user = User::where('email', $request->email)->first();
        if ($user) {
            if (Hash::check($request->password, $user->password)) {
                $token = $user->createToken('Access Token')->accessToken;
                $response = ["data" => [
                    "access_token" => base64_encode($token),
                    "token_SHA256_checksum" => hash("sha256", $token),
                ]];
                return response($response, 200);
            } else {
                $response = ["message" => "Password mismatch"];
                return response($response, 422);
            }
        } else {
            $response = ["message" => 'User does not exist'];
            return response($response, 422);
        }
    }

    public function logout(Request $request)
    {
        $accessToken = $request->user()->token();
        $accessToken->revoke();

        return response()->json('Logged out successfully', 200);
    }

    public function verify_email(EmailVerificationRequest $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response(["message" => "User already verified email"], 200);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
            return response(["message" => "Email verified successfully"], 200);
        }

        return response(["message" => "Email verification failed. Please try again"], 400);
    }

    public function send_email_verification_notification(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response(["message" => "User already verified email"], 200);
        } else {
            $user->sendEmailVerificationNotification();
            return response(["message" => "Verification link sent"], 200);
        }
    }

    public function check_email_verification(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response(["message" => "true", "data" => "Email verified at " . $user->email_verified_at], 200);
        } else {
            return response(["message" => "false", "data" => "Email unverified"], 200);
        }
    }

    public function email_unverified_notice()
    {
        return response([
            "message" => "Unauthorized",
            "data" => "You have unverified email. Please verify it (to send notification do: POST /api/auth/email_verification_notification)"
        ], 401);
    }
}
