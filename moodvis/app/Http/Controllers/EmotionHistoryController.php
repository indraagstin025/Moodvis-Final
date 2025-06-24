<?php

namespace App\Http\Controllers;

use App\Models\EmotionRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class EmotionHistoryController extends Controller
{

    public function getEmotionSummary(Request $request, $userId = null)
    {



        $actualUserId = $userId ?? Auth::user()->id;

        $user = User::find($actualUserId);
        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }


        $validated = $this->validate($request, [
            'period_type' => 'sometimes|string|in:weekly,monthly',
            'date' => 'sometimes|date_format:Y-m-d',
            'year' => 'sometimes|integer',
            'month' => 'sometimes|integer|min:1|max:12',
            'start_date' => 'sometimes|date_format:Y-m-d',
            'end_date' => 'sometimes|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        $periodType = $validated['period_type'] ?? 'custom';
        $startDate = null;
        $endDate = null;

        try {
            if (isset($validated['start_date']) && isset($validated['end_date'])) {
                $startDate = Carbon::parse($validated['start_date'])->startOfDay();
                $endDate = Carbon::parse($validated['end_date'])->endOfDay();
                $periodType = 'custom';
            } elseif ($periodType === 'weekly') {
                $targetDate = isset($validated['date']) ? Carbon::parse($validated['date']) : Carbon::now();
                $startDate = $targetDate->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
                $endDate = $targetDate->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
            } elseif ($periodType === 'monthly') {
                $targetYear = $validated['year'] ?? Carbon::now()->year;
                $targetMonth = $validated['month'] ?? Carbon::now()->month;
                $baseDateForMonth = Carbon::create($targetYear, $targetMonth, 1);
                $startDate = $baseDateForMonth->copy()->startOfMonth()->startOfDay();
                $endDate = $baseDateForMonth->copy()->endOfMonth()->endOfDay();
            } else {

                $startDate = Carbon::now()->subDays(6)->startOfDay();
                $endDate = Carbon::now()->endOfDay();
                $periodType = 'default_7_days';
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Format tanggal atau periode tidak valid.'], 400);
        }

        $emotionScoreColumns = ['happiness_score' => 'happy', 'sadness_score' => 'sad', 'anger_score' => 'angry', 'fear_score' => 'fearful', 'disgust_score' => 'disgusted', 'surprise_score' => 'surprised', 'neutral_score' => 'neutral'];
        $rawSelectStrings = [];
        foreach ($emotionScoreColumns as $dbColumn => $alias) {
            $rawSelectStrings[] = "AVG(`{$dbColumn}`) as avg_{$alias}";
        }

        try {

            $averageScoresResult = EmotionRecord::where('user_id', $actualUserId)
                ->whereBetween('detection_timestamp', [$startDate, $endDate])
                ->selectRaw(implode(', ', $rawSelectStrings))->first();

            $averageScores = [];
            $summaryEmotion = 'tidak ada data';
            $maxAvgScore = -1.0;
            if ($averageScoresResult && $averageScoresResult->avg_happy !== null) {
                foreach ($emotionScoreColumns as $dbColumn => $alias) {
                    $currentAvgScore = round(floatval($averageScoresResult->{"avg_{$alias}"}), 4);
                    $averageScores[$alias] = $currentAvgScore;
                    if ($currentAvgScore > $maxAvgScore) {
                        $maxAvgScore = $currentAvgScore;
                        $summaryEmotion = $alias;
                    }
                }
            } elseif ($averageScoresResult) {
                $summaryEmotion = 'tidak ada data skor';
            }

            return response()->json([
                'period_type' => $periodType,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'summary_emotion' => $summaryEmotion,
                'average_scores' => $averageScores,
            ]);
        } catch (\Exception $e) {
            Log::error("Error calculating emotion summary: " . $e->getMessage());
            return response()->json(['message' => 'Gagal menghitung ringkasan emosi.'], 500);
        }
    }



    /**
     * Mengambil data frekuensi emosi harian untuk membuat grafik tren.
     * Method ini sudah diperbaiki untuk menerima $userId secara eksplisit.
     */
    /**
     * Mengambil data frekuensi emosi harian untuk membuat grafik tren.
     * Method ini sudah diperbaiki untuk menerima $userId secara opsional.
     */
/**
     * Mengambil data frekuensi emosi harian untuk membuat grafik tren.
     * Versi final yang fleksibel dan bisa menangani 2 skenario.
     */
public function getEmotionFrequencyTrend(Request $request, $userId = null)
{
    $requestingUser = Auth::user();
    $targetUserId = $userId ?? $requestingUser->id;

    $targetUser = User::find($targetUserId);
    if (!$targetUser) {
        return response()->json(['message' => 'Pengguna target tidak ditemukan.'], 404);
    }

    if ($requestingUser->role === 'pengajar') {
if (!$targetUser->class || $targetUser->class->teacher_id != $requestingUser->id) {
        return response()->json(['message' => 'Akses ditolak. Anda bukan guru pembimbing dari siswa ini.'], 403);
    }
    }
    else if ($requestingUser->role === 'murid') {
        if ($requestingUser->id != $targetUserId) {
            return response()->json(['message' => 'Akses ditolak. Anda hanya bisa melihat data Anda sendiri.'], 403);
        }
    }

    try {
        $validated = $this->validate($request, [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);
    } catch (ValidationException $e) {
        return response()->json(['message' => 'Data yang diberikan tidak valid.', 'errors' => $e->errors()], 422);
    }

    $startDate = Carbon::parse($validated['start_date'])->startOfDay();
    $endDate = Carbon::parse($validated['end_date'])->endOfDay();

    try {
        // -- AKTIFKAN QUERY LOG --
        DB::connection()->enableQueryLog();

        $frequencyDataRaw = EmotionRecord::where('user_id', $targetUserId)
            ->whereBetween('detection_timestamp', [$startDate, $endDate])
            ->select(DB::raw('DATE(detection_timestamp) as detection_day'), 'dominant_emotion', DB::raw('COUNT(*) as frequency'))
            ->groupBy('detection_day', 'dominant_emotion')->orderBy('detection_day', 'asc')->get();

        // -- DAPATKAN DAN CATAT LOG QUERY --
        $queries = DB::getQueryLog();
        Log::info('SQL QUERY EXECUTED:', $queries);
        DB::connection()->disableQueryLog(); // Matikan lagi agar tidak membebani server

        // ... (Sisa kode Anda dari sini ke bawah tidak perlu diubah) ...
        $allEmotionsFromDb = EmotionRecord::where('user_id', $targetUserId)
            ->whereBetween('detection_timestamp', [$startDate, $endDate])
            ->distinct()->pluck('dominant_emotion')->toArray();

        if (empty($frequencyDataRaw->toArray())) { // Cek jika hasil query kosong
            return response()->json([
                'message' => 'Tidak ada data deteksi emosi ditemukan untuk periode ini.',
                'chartJsFormat' => ['labels' => [], 'datasets' => []]
            ]);
        }
        $tempPivot = [];
        $allEmotionKeys = array_unique(array_map(fn($e) => strtolower(str_replace(' ', '_', $e)), $allEmotionsFromDb));
        $currentDayForPivot = $startDate->copy();
        while ($currentDayForPivot->lte($endDate)) {
            $dateString = $currentDayForPivot->toDateString();
            $tempPivot[$dateString] = ['date' => $dateString];
            foreach ($allEmotionKeys as $emotionKey) { $tempPivot[$dateString][$emotionKey] = 0; }
            $currentDayForPivot->addDay();
        }
        foreach ($frequencyDataRaw as $record) {
            $date = $record->detection_day;
            $emotionKey = strtolower(str_replace(' ', '_', $record->dominant_emotion));
            if (isset($tempPivot[$date])) { $tempPivot[$date][$emotionKey] = $record->frequency; }
        }
        $formattedData = array_values($tempPivot);
        $chartJsLabels = array_column($formattedData, 'date');
        $emotionSeries = [];
        foreach ($allEmotionKeys as $emotionKey) {
             $emotionSeries[$emotionKey] = [
                'label' => ucfirst(str_replace('_', ' ', $emotionKey)),
                'data' => array_column($formattedData, $emotionKey),
            ];
        }

        return response()->json([
            'message' => 'Tren frekuensi emosi berhasil diambil.',
            'pivotData' => $formattedData,
            'chartJsFormat' => [ 'labels' => $chartJsLabels, 'datasets' => array_values($emotionSeries) ]
        ]);

    } catch (\Exception $e) {
        Log::error("Error fetching emotion frequency trend for user {$targetUserId}: " . $e->getMessage());
        return response()->json(['message' => 'Gagal mengambil tren frekuensi emosi.'], 500);
    }
    }
}