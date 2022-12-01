<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (isset($request->header()['token'][0])) {
            $token = $request->header()['token'][0];
            $user = User::where('api_token', 'LIKE', trim($token))->first();
            if ($user) {
                Auth::login($user);
                return $next($request);
            } else {
                return response(['message' => 'Не авторизован'], 401);
            }
        } else {
            return response(['message' => 'Не авторизован'], 403);
        }
    }
}
