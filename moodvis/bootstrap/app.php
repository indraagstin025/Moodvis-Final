<?php

require_once __DIR__ . '/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
*/

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

$app->withFacades();
$app->withEloquent();

// Ini sudah benar, biarkan seperti ini
$app->configure('database');
$app->configure('cors');

/*
|--------------------------------------------------------------------------
| Register Config Files
|--------------------------------------------------------------------------
*/

$app->configure('app');
$app->configure('auth');
$app->configure('jwt');
$app->configure('dompdf');

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
*/

// Register JWT Auth Service Provider FIRST
$app->register(Tymon\JWTAuth\Providers\LumenServiceProvider::class);

// Register other service providers
// $app->register(App\Providers\AppServiceProvider::class);
$app->register(App\Providers\AuthServiceProvider::class);
// $app->register(App\Providers\EventServiceProvider::class);
$app->register(\Barryvdh\DomPDF\ServiceProvider::class);

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
*/

// =======================================================================
// INI BAGIAN YANG DIPERBAIKI. KOSONGKAN DARI SEMUA MIDDLEWARE CORS.
// =======================================================================
$app->middleware([
    App\Http\Middleware\CorsMiddleware::class,
    //     // App\Http\Middleware\ExampleMiddleware::class // Boleh dikomentari atau dihapus
]);

$app->routeMiddleware([
    'auth' => App\Http\Middleware\Authenticate::class,
    'jwt.auth' => App\Http\Middleware\JwtMiddleware::class,
    'jwt.refresh' => App\Http\Middleware\RefreshTokenMiddleware::class,
    'admin' => App\Http\Middleware\IsAdmin::class,
    'role' => App\Http\Middleware\RoleMiddleware::class,
]);

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
*/

$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router) {
    require __DIR__ . '/../routes/web.php';
});

return $app;
