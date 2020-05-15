<?php

namespace App\Http\Middleware;

use Closure;
use Laravel\Socialite\Facades\Socialite;

class CheckSocialToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $token = $request->header('Authorization');
        $token = explode(' ', $token);
        if (!isset($token[1])) {
            return response()->json([
                'message' => 'Token is invalid.'
            ], 401);
        }

        try {
            Socialite::driver($request->provider)->userFromToken((string)$token);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Token is invalid.'
            ], 401);
        }
        return $next($request);
    }
}
