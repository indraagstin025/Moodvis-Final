<?php

namespace Tests;

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class ExampleTest extends TestCase
{
    // ... (gunakan DatabaseMigrations dan DatabaseTransactions jika perlu)

    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_that_base_endpoint_returns_a_successful_response()
    {
        $this->get('/');

        // Ubah assertion ini
        $this->assertResponseOk(); // Memastikan status 200
        $this->seeJsonStructure([ // Memastikan struktur JSON
            'status',
            'message',
            'version'
        ]);
        $this->seeJson([ // Atau memastikan konten JSON spesifik
            'status' => 'success',
            'message' => 'Welcome to your Lumen API!'
        ]);
    }
}
