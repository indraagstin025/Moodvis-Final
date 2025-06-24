<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Mendaftarkan pengguna baru.
     * Validasi dilakukan di luar try-catch untuk respons error 422 yang detail.
     */

    public function register(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|max:100',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {

            $teacher = User::where('role', 'pengajar')
                ->withCount('students')
                ->orderBy('students_count', 'asc')
                ->first();

            if (!$teacher) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No teachers available in the system.',
                ], 500);
            }

            $user = User::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => Hash::make($request->input('password')),
                'role' => 'murid',
                'teacher_id' => $teacher->id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'User registered successfully',
                'user' => $user->only(['id', 'name', 'email', 'role', 'teacher_id']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred during registration.',
            ], 500);
        }
    }

    /**
     * Melakukan login pengguna dan mengembalikan token JWT.
     */
    public function login(Request $request)
    {

        $this->validate($request, [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['email', 'password']);

        try {
            $user = User::where('email', $credentials['email'])->first();


            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Email atau password yang Anda masukkan salah.'
                ], 401);
            }


            if (! $token = JWTAuth::fromUser($user)) {
                return response()->json(['status' => 'error', 'message' => 'Gagal membuat token autentikasi.'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada server saat proses login.'], 500);
        }


        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil',

            'user' => $user->only(['id', 'name', 'email', 'photo_url', 'role']),
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60
        ]);
    }

    /**
     * Mendapatkan data pengguna yang sedang terautentikasi.
     */
// app/Http/Controllers/AuthController.php


public function me()
{
    $user = auth()->user();
    if (!$user) {
        return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
    }
    $user = \App\Models\User::with(['class', 'homeroomClass'])->find($user->id);
    return response()->json(['user' => $user]);
}


    /**
     * Melakukan logout dengan membatalkan token saat ini.
     */
public function logout()
{
    try {
        $token = JWTAuth::getToken();

        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token not provided'
            ], 400);
        }

        JWTAuth::invalidate($token);

        return response()->json([
            'status' => 'success',
            'message' => 'Successfully logged out'
        ], 200);
    } catch (\Exception $e) {
        Log::error('Logout error: ' . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => 'Logout failed: ' . $e->getMessage(),
        ], 500);
    }
}


    /**
     * Memperbarui token yang sudah ada.
     */
    public function refresh()
    {
        try {
            $newToken = JWTAuth::parseToken()->refresh();

            return response()->json([
                'status' => 'success',
                'token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60
            ]);
        } catch (JWTException $e) {

            return response()->json([
                'status' => 'error',
                'message' => 'Token refresh failed.',
                'details' => $e->getMessage()
            ], 401);
        }
    }
}
