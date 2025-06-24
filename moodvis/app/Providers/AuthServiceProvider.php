<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Tymon\JWTAuth\Facades\JWTAuth; // Penting: import JWTAuth

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        // Here you may define how you wish users to be authenticated for your Lumen
        // application. The callback which receives the incoming request instance
        // should return either a User instance or null. You're free to obtain
        // the User instance via an API token or any other method necessary.

        Auth::viaRequest('api', function ($request) {
            // Gunakan JWTAuth untuk mengautentikasi pengguna
            // JWTAuth::parseToken()->authenticate() akan mengambil token dari request
            // dan mencoba mendapatkan user.
            try {
                return JWTAuth::parseToken()->authenticate();
            } catch (\Exception $e) {
                // Jika ada error (token tidak valid, expired, dll.), kembalikan null
                // Middleware JWTAuth::class akan menangani error ini
                return null;
            }
        });

        // Atau, Anda juga bisa mengkonfigurasi default guard di config/auth.php
        // Tapi untuk Lumen, viaRequest seringkali lebih langsung.

    }
}
