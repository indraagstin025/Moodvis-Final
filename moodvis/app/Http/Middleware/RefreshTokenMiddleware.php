<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class RefreshTokenMiddleware
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
        try {
            // Attempt to refresh the token
            $newToken = JWTAuth::refresh();

            // Set the new token in the response header
            $response = $next($request);
            $response->headers->set('Authorization', 'Bearer ' . $newToken);

            return $response;
        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'Token has expired and cannot be refreshed'], 401);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Token refresh failed'], 401);
        }
    }
}
