<?php

namespace App\Http\Controllers;

use App\Models\Classes; // Pastikan Anda sudah membuat model Classes
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ClassController extends Controller
{
    /**
     * Menampilkan daftar semua kelas.
     * Endpoint ini bisa digunakan untuk mengisi dropdown di frontend.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $classes = Classes::with('teacher:id,name')->orderBy('name', 'asc')->get();
        return response()->json([
            'status' => 'success',
            'classes' => $classes,
        ]);
    }

    /**
     * Menyimpan kelas baru yang dibuat oleh Admin.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
// Di dalam file app/Http/Controllers/ClassController.php

public function store(Request $request)
{
    // Langkah 1: Validasi input. Ini sudah benar.
    $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
        'name' => 'required|string|max:100|unique:classes,name',
        'teacher_id' => 'nullable|integer|exists:users,id'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'message' => 'Data tidak valid.',
            'errors' => $validator->errors()
        ], 422);
    }

    // Langkah 2: Bungkus proses pembuatan data dalam blok try-catch yang solid.
    try {
        $class = Classes::create($validator->validated()); // Gunakan data yang sudah divalidasi

        // Muat relasi guru agar bisa langsung ditampilkan di frontend
        $class->load('teacher:id,name');

        return response()->json([
            'status' => 'success',
            'message' => 'Kelas baru berhasil dibuat.',
            'class' => $class
        ], 201);

    } catch (\Exception $e) {
        // Langkah 3: Jika terjadi error APAPUN saat menyimpan, tangkap dan kembalikan respons JSON.
        // Log error ini agar Anda tahu apa yang sebenarnya terjadi.
        \Illuminate\Support\Facades\Log::error('Gagal membuat kelas: ' . $e->getMessage());

        // Kembalikan respons error dalam format JSON yang valid.
        return response()->json([
            'status' => 'error',
            'message' => 'Terjadi kesalahan internal pada server. Gagal menyimpan kelas.'
        ], 500);
    }
}

    /**
     * Menampilkan detail satu kelas spesifik.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $class = Classes::with('teacher:id,name')->find($id);

        if (!$class) {
            return response()->json(['status' => 'error', 'message' => 'Kelas tidak ditemukan.'], 404);
        }

        return response()->json([
            'status' => 'success',
            'class' => $class,
        ]);
    }

    /**
     * Mengupdate nama kelas yang sudah ada.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $class = Classes::find($id);

        if (!$class) {
            return response()->json(['status' => 'error', 'message' => 'Kelas tidak ditemukan.'], 404);
        }

        try {
            $this->validate($request, [
                // Pastikan nama baru unik, kecuali untuk dirinya sendiri
                'name' => 'required|string|max:100|unique:classes,name,' . $id,
                'teacher_id' => 'nullable|integer|exists:users,id'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'errors' => $e->errors()], 422);
        }

        $class->name = $request->input('name');
        $class->teacher_id = $request->input('teacher_id');
        $class->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Kelas berhasil diperbarui.',
            'class' => $class->load('teacher:id,name'),
        ]);
    }

    /**
     * Menghapus sebuah kelas.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $class = Classes::find($id);

        if (!$class) {
            return response()->json(['status' => 'error', 'message' => 'Kelas tidak ditemukan.'], 404);
        }

        // Sebelum menghapus, Anda bisa menambahkan logika untuk memeriksa
        // apakah ada siswa yang masih terdaftar di kelas ini.
        // Jika ada, mungkin Anda ingin mencegah penghapusan atau memindahkan siswa terlebih dahulu.

        $class->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Kelas berhasil dihapus.',
        ]);
    }
}