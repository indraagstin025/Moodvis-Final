<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Validation\ValidationException; // Untuk menangani error validasi

class UserProfileController extends Controller
{
    /**
     * Constructor for UserProfileController.
     * Apply 'jwt.auth' middleware to all methods in this controller.
     */
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    /**
     * Get the authenticated user's profile details.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show()
    {
        // Mendapatkan user yang sedang login
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found.'
            ], 404);
        }

        // Mengembalikan hanya data yang relevan untuk profil
        return response()->json([
            'status' => 'success',
            'user' => $user->only(['id', 'name', 'email', 'role', 'profile_picture']),
        ]);
    }

    /**
     * Update the authenticated user's profile information (name, email, profile_picture).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        // Validasi input
        try {
            $this->validate($request, [
                'name' => 'required|string|min:3|max:255',
                // Rule unique:users,email harus mengecualikan email user yang sedang login
                'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
                'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Opsional, harus gambar
                'class_id' => 'sometimes|nullable|integer|exists:classes,id'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        }

        // Perbarui data nama dan email
        $user->name = $request->input('name');
        $user->email = $request->input('email');

        // Handle upload foto profil
        if ($request->hasFile('profile_picture')) {
            // Hapus foto profil lama jika ada
            if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
                Storage::disk('public')->delete($user->profile_picture);
            }

            // Simpan foto profil baru
            $path = $request->file('profile_picture')->store('profile_pictures', 'public');
            $user->profile_picture = $path;
        } elseif ($request->input('clear_profile_picture') === true) {
            // Logika untuk menghapus foto profil jika ada permintaan dari frontend
            if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
                Storage::disk('public')->delete($user->profile_picture);
            }
            $user->profile_picture = null;
        }

        if ($request->has('class_id')) {
            $user->class_id = $request->input('class_id');
        }

        // Simpan perubahan ke database
        if ($user->save()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Profile updated successfully.',
                'user' => $user->only(['id', 'name', 'email', 'role', 'profile_picture']),
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Failed to update profile.'
        ], 500);
    }

    /**
     * Change the authenticated user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        // Validasi input password
        try {
            $this->validate($request, [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*#?&]/',
                'new_password_confirmation' => 'required|string', // Pastikan konfirmasi juga ada
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        }

        // Verifikasi password lama
        if (!Hash::check($request->input('current_password'), $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Password lama Anda salah.'
            ], 401);
        }

        // Perbarui password baru
        $user->password = Hash::make($request->input('new_password'));

        // Simpan perubahan
        if ($user->save()) {
            // Opsional: Invalidate semua token lama user setelah password diubah untuk keamanan
            JWTAuth::invalidate(JWTAuth::fromUser($user)); // Invalidate token yang sedang digunakan
            // Jika Anda memiliki mekanisme untuk invalidasi semua token (misalnya, menyimpan JTI di DB),
            // Anda bisa menggunakannya di sini untuk logged out semua sesi aktif.

            return response()->json([
                'status' => 'success',
                'message' => 'Password berhasil diubah. Anda perlu login kembali dengan password baru.'
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Gagal mengubah password.'
        ], 500);
    }
}