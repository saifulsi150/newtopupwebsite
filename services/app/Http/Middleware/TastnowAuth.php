<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TastnowAuth
{
    public function handle(Request $request, Closure $next)
    {
        $key = trim((string) ($request->header('X-Api-Key') ?? $request->header('x-api-key') ?? ''));

        $sharedSecret = trim((string) config('tastnow.shared_secret'));
        if ($sharedSecret === '' && function_exists('gs')) {
            $sharedSecret = trim((string) (gs()->website_api_key ?? ''));
        }

        if ($key === '' || $sharedSecret === '' || !hash_equals($sharedSecret, $key)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        return $next($request);
    }
}
