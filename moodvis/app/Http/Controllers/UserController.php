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
            'role' => 'required|string|in:murid,pengajar',
            'class_id' => 'required_if:role,murid|nullable|exists:classes,id'
        ]);


        $user = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'role' => $request->input('role'),
            // Simpan class_id jika role adalah murid
            'class_id' => $request->input('role') === 'murid' ? $request->input('class_id') : null,
        ]);


        return response()->json([
            'status' => 'success',
            'message' => 'Pengguna baru berhasil dibuat oleh admin.',
            'user' => $user->only(['id', 'name', 'email', 'role']),
        ], 201);
    }

    public function index()
    {


        $users = User::orderBy('created_at', 'desc')->get([
            'id',
            'name',
            'email',
            'role',
            'created_at'
        ]);

        return response()->json([
            'status' => 'success',
            'users' => $users
        ]);
    }


    /**
     * Mengambil satu pengguna spesifik berdasarkan ID.
     */
    public function getUser($id)
    {

        $user = User::find($id);


        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengguna tidak ditemukan.'
            ], 404);
        }


        return response()->json([
            'status' => 'success',
            'user' => $user->only(['id', 'name', 'email', 'role', 'created_at'])
        ]);
    }

    /**
     * Mengambil semua murid untuk pengajar yang sedang login.
     * Fungsi ini mengambil semua pengguna dengan peran 'murid'.
     * Untuk relasi spesifik pengajar-murid, diperlukan modifikasi skema database.
     */
    public function getMyStudents(Request $request)
    {

        $teacher = Auth::user();


        if (!$teacher || $teacher->role !== 'pengajar') {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak. Hanya pengajar yang dapat mengakses fitur ini.'
            ], 403);
        }


        $homeroomClass = $teacher->homeroomClass;

        // 2. Jika guru tidak menjadi wali kelas manapun, kembalikan array kosong.
        if (!$homeroomClass) {
            return response()->json(['status' => 'success', 'students' => []]);
        }

        // 3. Ambil semua siswa dari kelas tersebut.
        $students = $homeroomClass->students()
            ->with('class')
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'email', 'photo', 'class_id']);


        return response()->json(['status' => 'success', 'students' => $students]);
    }

    /**
     * Endpoint untuk testing.
     */
    public function test()
    {
        return response()->json(['status' => 'success', 'message' => 'UserController berhasil diakses!']);
    }
}