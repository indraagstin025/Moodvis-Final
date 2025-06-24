<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Log;


use App\Http\Controllers\EmotionHistoryController;

class ReportController extends Controller
{
    /**
     * Endpoint untuk Pengajar membuat laporan PDF untuk seorang siswa.
     */
    public function generateReport(Request $request)
    {
        Carbon::setLocale('id');
        Log::info('DEBUG: Carbon locale saat ini di generateReport (setelah manual): ' . Carbon::getLocale());
        $teacher = Auth::user();
        if ($teacher->role !== 'pengajar') {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }


        $validated = $this->validate($request, [
            'student_id' => 'required|integer|exists:users,id',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date'   => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'chart_image_base64' => 'nullable|string',
        ]);

        $student = User::find($validated['student_id']);
if (!$student || $student->role !== 'murid' || !$student->class || $student->class->teacher_id != $teacher->id) {
    return response()->json(['message' => 'Siswa tidak valid atau bukan bagian dari kelas Anda.'], 404);
}

        $historyController = new EmotionHistoryController();
        $studentId = $student->id;


        $requestForSummary = new Request(['start_date' => $validated['start_date'], 'end_date' => $validated['end_date']]);
        $summaryResponse = $historyController->getEmotionSummary($requestForSummary, $studentId);
        $summaryData = json_decode($summaryResponse->getContent(), true);



        $dataForPdf = [
            'studentName'        => $student->name,
            'reportPeriod'       => Carbon::parse($validated['start_date'])->isoFormat('D MMMM') . ' - ' . Carbon::parse($validated['end_date'])->isoFormat('D MMMM'),
            'printDate'          => Carbon::now()->isoFormat('D MMMM YYYY'),
            'summaryEmotion'     => $summaryData['summary_emotion'] ?? 'Tidak ada data',
            'chartImageBase64'   => $validated['chart_image_base64'] ?? null,
        ];

        try {
            $pdf = Pdf::loadView('reports.student_report', $dataForPdf);
            $filename = 'report_' . $student->id . '_' . time() . '.pdf';
            $filePath = 'reports/' . $filename;
            Storage::disk('local')->put($filePath, $pdf->output());

            $report = Report::create([
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'filename' => $filename,
                'file_path' => $filePath,
            ]);

            return response()->json(['message' => 'Laporan PDF untuk ' . $student->name . ' berhasil dibuat.', 'data' => $report], 201);
        } catch (\Exception $e) {
            Log::error('Gagal membuat laporan PDF: ' . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan internal saat membuat file laporan.'], 500);
        }
    }

    /**
     * Endpoint untuk Murid melihat daftar laporannya.
     */
    public function listMyReports()
    {
        $student = Auth::user();
        if ($student->role !== 'murid') {
            return response()->json(['message' => 'Hanya murid yang dapat melihat daftar laporan.'], 403);
        }


        $reports = Report::where('student_id', $student->id)
            ->with('teacher:id,name')
            ->select('id', 'teacher_id', 'start_date', 'end_date', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $reports]);
    }

    /**
     * Endpoint untuk mengunduh file laporan PDF.
     */
    public function downloadReport($id)
    {
        $user = Auth::user();
        $report = Report::find($id);

        if (!$report) {
            return response()->json(['message' => 'Laporan tidak ditemukan.'], 404);
        }

        if (($user->role === 'murid' && $user->id != $report->student_id) || ($user->role === 'pengajar' && $user->id != $report->teacher_id)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        if (Storage::disk('local')->exists($report->file_path)) {
            return response()->download(storage_path('app/' . $report->file_path), $report->filename);
        }

        return response()->json(['message' => 'File laporan tidak ditemukan di server.'], 404);
    }

        /**
     * Endpoint untuk menghapus laporan PDF.
     * Hanya pengajar yang membuat laporan atau murid yang memiliki laporan tersebut yang boleh menghapus.
     */
    public function deleteReport($id)
    {
        $user = Auth::user(); // Dapatkan pengguna yang sedang login
        $report = Report::find($id); // Cari laporan berdasarkan ID

        // Jika laporan tidak ditemukan
        if (!$report) {
            return response()->json(['message' => 'Laporan tidak ditemukan.'], 404);
        }

        // Cek hak akses untuk menghapus laporan
        // Seorang pengajar hanya boleh menghapus laporan yang dia buat
        // Seorang murid hanya boleh menghapus laporan yang dia miliki
        if (!($user->role === 'pengajar' && $user->id === $report->teacher_id) &&
            !($user->role === 'murid' && $user->id === $report->student_id)) {
            // Jika pengguna bukan pengajar yang membuat laporan ATAU bukan murid pemilik laporan
            return response()->json(['message' => 'Anda tidak memiliki izin untuk menghapus laporan ini.'], 403);
        }

        try {
            // 1. Hapus file PDF dari penyimpanan (storage) jika ada
            if (Storage::disk('local')->exists($report->file_path)) {
                Storage::disk('local')->delete($report->file_path);
                Log::info('File laporan dihapus: ' . $report->file_path);
            } else {
                Log::warning('File laporan tidak ditemukan di storage saat mencoba menghapus: ' . $report->file_path);
            }

            // 2. Hapus entri laporan dari database
            $report->delete();

            return response()->json(['message' => 'Laporan berhasil dihapus.'], 200);

        } catch (\Exception $e) {
            // Tangani kesalahan jika terjadi saat penghapusan
            Log::error('Gagal menghapus laporan: ' . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan internal saat menghapus laporan.'], 500);
        }
    }
}
