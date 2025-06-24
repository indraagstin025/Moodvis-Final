<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $origin = $request->header('Origin');
        $allowedOrigins = array_map('trim', [
            'https://m00thzqr-5173.asse.devtunnels.ms',

        ]);

        // Untuk preflight request
        if ($request->isMethod('OPTIONS')) {
            $response = response('', 204);
            if (in_array($origin, $allowedOrigins)) {
                $response->header('Access-Control-Allow-Origin', $origin );
            }
            $response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, X-Requested-With');
            $response->header('Access-Control-Max-Age', 3600);
            $response->header('Access-Control-Allow-Credentials', 'true');
            return $response;
        }

        $response = $next($request);
        if (in_array($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin ?? '*');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, X-Requested-With');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }
        return $response;
    }
}