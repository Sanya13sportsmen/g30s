<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordMail;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function register(Request $request) {
        $validator = Validator::make($request->all(),[
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|confirmed|min:6',
        ]);

        if ($validator->fails()) {
            return response([
                'message' => $validator->errors()->first()
            ], 400);
        }

        $user = new User();
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('Get30seconds')->accessToken
        ]);
    }

    public function login(Request $request) {
        $validator = Validator::make($request->all(),[
            'email' => 'required|string|email',
            'password' => 'required|string|min:6'
        ]);

        if ($validator->fails()) {
            return response([
                'message' => $validator->errors()->first()
            ], 400);
        }

        $user = User::where('email', '=', $request->email)->first();

        if (!$user) {
            return response()->json([
               'message' => 'User does not exist.'
            ], 404);
        }

        if(Hash::check($request->password, $user->password)) {
            return response()->json([
                'user' => $user,
                'token' => $user->createToken('Get30seconds')->accessToken
            ]);
        } else {
            return response()->json([
                'message' => 'Incorrect password.'
            ], 400);
        }
    }

    public function loginWithSocialAccount(Request $request) {
        $validator = Validator::make($request->all(),[
            'provider' => 'required|string|in:facebook,google',
            'token' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response([
                'message' => $validator->errors()->first()
            ], 400);
        }

        try {
            $providerUser = Socialite::driver($request->provider)->userFromToken($request->token);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Token is invalid.'
            ], 400);
        }

        $user = User::where([
            ['email', '=', $providerUser->user["email"]],
            ['provider', '=', $request->provider],
            ['provider_user_id', '=', $providerUser->user["id"]]
        ])->first();

        if (!$user) {
            $validator = Validator::make($providerUser->user, [
                'email' => 'unique:users',
            ]);

            if ($validator->fails()) {
                return response([
                    'message' => $validator->errors()->first()
                ], 400);
            }

            $user = new User();
            $user->email = $providerUser->user["email"];
            $user->provider = $request->provider;
            $user->provider_user_id = $providerUser->user["id"];
            $user->save();
        }

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('Get30seconds')->accessToken
        ]);
    }

    public function logout(Request $request) {
        $request->user()->token()->revoke();
        return response()->json([
            'message' => 'Successfully logout.'
        ]);
    }

    public function forgotPassword(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
        ]);

        if ($validator->fails()) {
            return response([
                'message' => $validator->errors()->first()
            ], 400);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user)
            return response()->json([
                'message' => 'User does not exist.'
            ], 404);
        $user->password_reset_code = random_int(100000, 999999);
        $user->code_expires_at = Carbon::now()->addDays(1);
        $user->save();

        try {
            Mail::to($user)
                ->send(new ResetPasswordMail($user));
            return response()->json([
                'message' => 'The email has been sent.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Email sending error.'
            ], 400);
        }
    }

    public function checkResetPasswordCode(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'code' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response([
                'message' => $validator->errors()->first()
            ], 400);
        }

        $user = User::where([
            ['email', '=', $request->email],
            ['password_reset_code', '=', $request->code]
        ])->first();

        if (!$user)
            return response()->json([
                'message' => 'User does not exist.'
            ], 404);
        if (Carbon::now() > $user->code_expires_at) {
            $user->password_reset_code = null;
            $user->code_expires_at = null;
            $user->save();
            return response()->json([
                'message' => 'Code is expired.'
            ], 400);
        }
        return response()->json([
            'message' => 'Code is valid.'
        ]);
    }

    public function resetPassword(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string|confirmed|min:6',
            'code' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response([
                'message' => $validator->errors()->first()
            ], 400);
        }
        $user = User::where([
            ['password_reset_code', $request->code],
            ['email', $request->email]
        ])->first();
        if (!$user) {
            return response()->json([
                'message' => 'User does not exist.'
            ], 404);
        }
        $user->password = Hash::make($request->password);
        $user->password_reset_code = null;
        $user->code_expires_at = null;
        $user->save();

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('Get30seconds')->accessToken
        ]);
    }
}
