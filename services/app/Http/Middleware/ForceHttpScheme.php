<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class ForceHttpScheme
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->isLocal()) {
            URL::forceScheme('http');
            URL::forceRootUrl('http://' . $request->getHttpHost());
        }

        return $next($request);
    }
}
