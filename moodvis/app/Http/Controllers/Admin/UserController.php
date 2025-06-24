<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Membuat pengguna baru (guru/murid) oleh admin.
     * Validasi dan pembuatan pengguna dilakukan di sini.
     */
    public function createUser(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|max:100',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:murid,pengajar'
        ]);

        // 2. Buat pengguna baru jika validasi berhasil
        $user = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'role' => $request->input('role'),
        ]);

        // 3. Kirim respons sukses dalam format JSON
        return response()->json([
            'status' => 'success',
            'message' => 'Pengguna baru berhasil dibuat oleh admin.',
            'user' => $user->only(['id', 'name', 'email', 'role']),
        ], 201);
    }

     public function index()
    {
        // Ambil semua pengguna, urutkan dari yang terbaru,
        // dan pilih hanya kolom yang relevan untuk ditampilkan
        $users = User::orderBy('created_at', 'desc')->get([
            'id', 'name', 'email', 'role', 'created_at'
        ]);

        return response()->json([
            'status' => 'success',
            'users' => $users
        ]);
    }

        public function test()
    {
        return response()->json(['status' => 'success', 'message' => 'UserController berhasil diakses!']);
    }

    // Nanti Anda bisa menambahkan fungsi lain di sini, misalnya:
    // public function deleteUser($id) { ... }
    // public function updateUserByAdmin($id, Request $request) { ... }

     public function getMyStudents(Request $request)
    {
        $teacher = Auth::user();

        // Pengecekan keamanan, pastikan yang meminta adalah pengajar
        if ($teacher->role !== 'pengajar') {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        // Menggunakan relasi 'students' yang sudah kita buat di Model User
        // Kita hanya mengambil kolom id dan name karena hanya itu yang dibutuhkan dropdown
        $students = $teacher->students()->get(['id', 'name']);

        // Mengembalikan data siswa dalam format JSON
        return response()->json($students);
    }
}