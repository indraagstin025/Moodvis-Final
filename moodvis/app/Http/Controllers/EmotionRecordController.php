<?php

namespace App\Http\Controllers;

use App\Models\EmotionRecord;
// use App\Models\User; // Tidak digunakan secara langsung di sini, bisa di-comment jika tidak ada penggunaan lain
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class EmotionRecordController extends Controller
{
    /**
     * Menampilkan daftar riwayat deteksi emosi untuk pengguna yang terautentikasi.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $perPage = $request->query('per_page', 15);
        $sortBy = $request->query('sort_by', 'detection_timestamp');
        $sortOrder = $request->query('sort_order', 'desc');

        // Kolom yang diizinkan untuk pengurutan
        $allowedSortColumns = [
            'detection_timestamp',
            'dominant_emotion',
            'created_at'
            // Anda bisa menambahkan kolom lain jika diperlukan, misalnya 'happiness_score'
        ];

        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'detection_timestamp'; // Default jika kolom sort tidak valid
        }
        if (!in_array(strtolower($sortOrder), ['asc', 'desc'])) {
            $sortOrder = 'desc'; // Default jika urutan sort tidak valid
        }

        try {
            $emotionRecords = EmotionRecord::where('user_id', $user->id)
                ->orderBy($sortBy, $sortOrder)
                ->paginate($perPage);

            return response()->json($emotionRecords);
        } catch (\Exception $e) {
            Log::error('Error fetching emotion records for user ' . $user->id . ': ' . $e->getMessage());
            return response()->json(['message' => 'Gagal mengambil data riwayat emosi.'], 500);
        }
    }

    /**
     * Menyimpan catatan hasil deteksi emosi yang baru.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Validasi input dari frontend
        // Pastikan nama field (misal 'happiness_score') sesuai dengan yang dikirim dari frontend
        // dan juga sesuai dengan yang ada di $fillable model EmotionRecord.
        $validatedData = $this->validate($request, [
            'timestamp' => 'required|date_format:Y-m-d\TH:i:s.v\Z', // Format ISO 8601 dari JS Date.toISOString()
            'dominant_emotion' => 'required|string|max:50', // Batasi panjang string
            'happiness_score' => 'required|numeric|between:0,1', // Nama field sudah benar
            'sadness_score' => 'required|numeric|between:0,1',
            'anger_score' => 'required|numeric|between:0,1',
            'fear_score' => 'required|numeric|between:0,1',
            'disgust_score' => 'required|numeric|between:0,1',
            'surprise_score' => 'required|numeric|between:0,1',
            'neutral_score' => 'required|numeric|between:0,1',
            // 'age' => 'sometimes|nullable|integer|min:0|max:150', // Dihapus
            'gender' => ['sometimes', 'nullable', 'string', Rule::in(['male', 'female', 'other', 'neutral'])], // 'neutral' ditambahkan jika face-api bisa mengembalikan ini
            'gender_probability' => 'sometimes|nullable|numeric|between:0,1',
        ]);

        try {
            $emotionRecord = EmotionRecord::create([
                'user_id' => $user->id,
                'detection_timestamp' => $validatedData['timestamp'], // Data dari frontend 'timestamp' disimpan ke 'detection_timestamp'
                'dominant_emotion' => $validatedData['dominant_emotion'],
                'happiness_score' => $validatedData['happiness_score'], // Nama field sudah benar
                'sadness_score' => $validatedData['sadness_score'],
                'anger_score' => $validatedData['anger_score'],
                'fear_score' => $validatedData['fear_score'],
                'disgust_score' => $validatedData['disgust_score'],
                'surprise_score' => $validatedData['surprise_score'],
                'neutral_score' => $validatedData['neutral_score'],
                // 'age' => $validatedData['age'] ?? null, // Dihapus
                'gender' => $validatedData['gender'] ?? null,
                'gender_probability' => $validatedData['gender_probability'] ?? null,
            ]);

            return response()->json(['message' => 'Catatan emosi berhasil disimpan!', 'data' => $emotionRecord], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation error while saving emotion record for user ' . $user->id . ': ' . $e->getMessage(), $e->errors());
            return response()->json(['message' => 'Data yang diberikan tidak valid.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error saving emotion record for user ' . $user->id . ': ' . $e->getMessage());
            $errorDetails = config('app.debug') ? ['error_details' => $e->getMessage()] : []; // Sederhanakan trace untuk produksi
            return response()->json(array_merge(['message' => 'Gagal menyimpan catatan emosi.'], $errorDetails), 500);
        }
    }

    /**
     * Menampilkan satu catatan emosi secara spesifik.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $user = Auth::user();
        try {
            // Pastikan hanya user yang bersangkutan yang bisa melihat recordnya
            $emotionRecord = EmotionRecord::where('user_id', $user->id)->findOrFail($id);
            return response()->json($emotionRecord);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Catatan emosi tidak ditemukan.'], 404); // Typo 'meesage' diperbaiki
        } catch (\Exception $e) {
            Log::error("Error fetching emotion record {$id} for user " . $user->id . ": " . $e->getMessage());
            return response()->json(['message' => 'Gagal mengambil data catatan emosi.'], 500);
        }
    }

    /**
     * Menghapus catatan emosi spesifik.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $user = Auth::user();
        try {
            // Pastikan hanya user yang bersangkutan yang bisa menghapus recordnya
            $emotionRecord = EmotionRecord::where('user_id', $user->id)->findOrFail($id);
            $emotionRecord->delete();
            return response()->json(['message' => 'Catatan emosi berhasil dihapus.']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Catatan emosi tidak ditemukan.'], 404); // Status code 404 lebih sesuai
        } catch (\Exception $e) {
            Log::error("Error deleting emotion record {$id} for user " . $user->id . ": " . $e->getMessage());
            return response()->json(['message' => 'Gagal menghapus catatan emosi.'], 500);
        }
    }
}