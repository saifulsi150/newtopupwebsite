<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DisableRememberLogin
{
    /**
     * Block automatic login from remember-me cookies.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard();

        if (Auth::check() && method_exists($guard, 'viaRemember') && $guard->viaRemember()) {
            $user = Auth::user();

            if ($user) {
                $user->setRememberToken(null);
                $user->save();
            }

            Auth::logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
        }

        return $next($request);
    }
}
