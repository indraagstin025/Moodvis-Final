<?php

namespace Tests;

use Laravel\Lumen\Testing\TestCase as BaseTestCase;
use Laravel\Lumen\Testing\Concerns\MakesHttpRequests; // Pastikan ini ada

/**
 * @mixin MakesHttpRequests // Pastikan docblock ini ada
 */
abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        return require __DIR__.'/../bootstrap/app.php';
    }
}