<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
*/

if (!function_exists('public_path')) {
    function public_path($path = '')
    {
        return app()->basePath('public') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

// Rute dasar (opsional)
$router->get('/', function () use ($router) {
    return 'MoodVis API v' . $router->app->version();
});



// Grup untuk semua rute API dengan prefix /api
$router->group(['prefix' => 'api'], function () use ($router) {
    $router->post('/register', ['uses' => 'AuthController@register']);
    $router->post('/login', ['uses' => 'AuthController@login']);


    // == Rute Publik (Tidak Perlu Login) ==


    // == Rute Terproteksi (Wajib Login) ==
    $router->group(['middleware' => 'auth'], function () use ($router) {

        // Auth & Profile
    $router->post('/logout', ['uses' => 'AuthController@logout']);
        $router->post('/refresh', ['uses' => 'AuthController@refresh']);
        $router->get('/me', ['uses' => 'AuthController@me']);
        $router->put('/profile', ['uses' => 'ProfileController@update']);
        $router->post('/profile/update', ['uses' => 'ProfileController@update']);


        // --- FITUR DASHBOARD PENGAJAR ---
        $router->get('/teacher/students', ['uses' => 'UserController@getMyStudents']);
        $router->post('/user/join-class', ['uses' => 'UserController@joinClass']);


        // PERBAIKAN FINAL: Ubah {id} menjadi {userId} agar cocok dengan Controller
        $router->get('/students/{userId}/emotion-history', ['uses' => 'EmotionHistoryController@getEmotionFrequencyTrend']);
        $router->get('/classes', ['uses' => 'ClassController@index']);
        // ---------------------------------------------

        // Emotion Records & History (untuk murid yang login)
        $router->get('/emotion/history/summary', ['uses' => 'EmotionHistoryController@getEmotionSummary']);
        $router->get('/emotion/history/trend', ['uses' => 'EmotionHistoryController@getEmotionFrequencyTrend']);
        $router->get('/emotion-records', ['uses' => 'EmotionRecordController@index']);
        $router->post('/emotion-records', ['uses' => 'EmotionRecordController@store']);
        $router->get('/emotion-records/{id}', ['uses' => 'EmotionRecordController@show']);
        $router->delete('/emotion-records/{id}', ['uses' => 'EmotionRecordController@destroy']);

        // Laporan (Reports)
        $router->post('/reports/generate', ['uses' => 'ReportController@generateReport']);
        $router->get('/reports', ['uses' => 'ReportController@listMyReports']);
        $router->get('/reports/{id}/download', ['uses' => 'ReportController@downloadReport']);
        $router->delete('/reports/{id}', ['uses' => 'ReportController@deleteReport']);

        // Rute untuk Admin (menggunakan middleware role dan prefix)
        $router->group(['middleware' => 'role:admin', 'prefix' => 'admin'], function () use ($router) {
            $router->get('/users', ['uses' => 'UserController@index']);
            $router->post('/users', ['uses' => 'UserController@createUser']);
            $router->put('/users/{id}', ['uses' => 'UserController@updateUserByAdmin']);
            $router->get('/classes', ['uses' => 'ClassController@index']);
            $router->post('/classes', ['uses' => 'ClassController@store']);
            $router->get('/classes/{id}', ['uses' => 'ClassController@show']);
            $router->put('/classes/{id}', ['uses' => 'ClassController@update']);
            $router->delete('/classes/{id}', ['uses' => 'ClassController@destroy']);
        });
    });
});

$router->get('/profile/{filename}', function ($filename) {
    $path = public_path('profile/' . $filename);

    if (!file_exists($path)) {
        return response()->json([
            'error' => 'File tidak ditemukan',
            'filename' => $filename,
            'checked_path' => $path,
            'files_in_dir' => scandir(public_path('profile')),
        ], 404);
    }

    return response()->file($path, [
        'Content-Type' => mime_content_type($path),
        'Access-Control-Allow-Origin' => '*',
    ]);
});