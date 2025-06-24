<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role  // Ini adalah parameter setelah titik dua, misal: 'admin'
     * @return mixed
     */
    public function handle($request, Closure $next, $role)
    {
        // Cek apakah pengguna sudah login
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Ambil data pengguna yang sedang login
        $user = Auth::user();

        // Cek apakah role pengguna TIDAK SAMA DENGAN role yang diizinkan
        if ($user->role !== $role) {
            // Jika tidak sama, tolak akses
            return response()->json(['message' => 'Forbidden. You do not have the required role.'], 403);
        }

        // Jika role-nya cocok, izinkan permintaan untuk melanjutkan
        return $next($request);
    }
}
