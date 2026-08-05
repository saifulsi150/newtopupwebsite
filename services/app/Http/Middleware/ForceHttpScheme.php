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
        if (app()->isLocal() && in_array($request->getHost(), ['127.0.0.1', 'localhost'], true)) {
            URL::forceScheme('http');
            URL::forceRootUrl('http://' . $request->getHttpHost());
        }

        return $next($request);
    }
}
