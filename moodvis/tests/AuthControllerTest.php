<?php

namespace Tests;

use App\Models\User;
use Laravel\Lumen\Testing\DatabaseMigrations;

class AuthControllerTest extends TestCase
{
    use DatabaseMigrations;

    public function testRegister()
    {
        $response = $this->post('/register', [
            'username' => 'indraUser',
            'email' => 'Testuser@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123'
        ]);

        $response->seeStatusCode(201);
        $response->seeJsonContains([
            'status' => 'success',
            'message' => 'User registered successfully. Please log in.'
        ]);
    }

    public function testLogin()
    {

        User::create([
            'username' => 'indraUser',
            'email' => 'Testuser@example.com',
            'password' => app('hash')->make('rahasia123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'Testuser@example.com',
            'password' => 'rahasia123'
        ]);

        $response->seeStatusCode(200);
        $response->seeJsonContains(['status' => 'success', 'message' => 'Login berhasil']);
        $response->seeJsonStructure(['token', 'user']);
    }

    public function testLogout()
    {

        $user = User::create([
            'username' => 'indraUser',
            'email' => 'Testuser@example.com',
            'password' => app('hash')->make('rahasia123'),
        ]);

        $token = auth()->login($user);

        $response = $this->post('/logout', [], ['Authorization' => "Bearer $token"]);

        $response->seeStatusCode(200);
        $response->seeJsonContains(['status' => 'success', 'message' => 'Successfully logged out']);
    }

    public function testMe()
    {

        $user = User::create([
            'username' => 'indraser',
            'email' => 'Testuser@example.com',
            'password' => app('hash')->make('rahasia123'),
        ]);

        $token = auth()->login($user);

        $response = $this->get('/me', ['Authorization' => "Bearer $token"]);

        $response->seeStatusCode(200);
        $response->seeJsonContains([
            'status' => 'success',
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
            ],
        ]);
    }

    public function testRoot()
    {
        $response = $this->get('/');
        $response->assertResponseStatus(200);
    }
}
