<?php

return [
    'defaults' => [
        'guard' => 'api', // <--- Pastikan ini 'api' atau 'jwt'
        'passwords' => 'users',
    ],

    'guards' => [
        'api' => [
            'driver' => 'jwt', // <--- PENTING: Driver harus 'jwt'
            'provider' => 'users',
        ],
        // Anda bisa menambahkan 'web' guard juga jika diperlukan untuk sesi
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
    ],
];
