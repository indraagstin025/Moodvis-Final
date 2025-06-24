<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\EmotionRecord;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class EmotionRecordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. Kosongkan tabel emotion_records terlebih dahulu
        Schema::disableForeignKeyConstraints();
        EmotionRecord::truncate();
        Schema::enableForeignKeyConstraints();

        // 2. Ambil semua ID pengguna dengan peran 'murid'
        $studentIds = User::where('role', 'murid')->pluck('id');

        if ($studentIds->isEmpty()) {
            $this->command->info('Tidak ada murid ditemukan, seeder data emosi dilewati.');
            return;
        }

        // PERBAIKAN: Daftar emosi disamakan dengan nama kolom di database
        $emotions = ['happiness', 'sadness', 'anger', 'fear', 'disgust', 'surprise', 'neutral'];

        // 3. Loop setiap murid dan buat data emosi untuk mereka
        foreach ($studentIds as $studentId) {
            // Buat antara 10 sampai 25 record untuk setiap murid
            $numberOfRecords = rand(10, 25);

            for ($i = 0; $i < $numberOfRecords; $i++) {
                $dominantEmotion = $emotions[array_rand($emotions)];

                // Buat skor acak, dan pastikan skor dominan lebih tinggi
                $scores = [];
                foreach ($emotions as $emotion) {
                    $scores[$emotion . '_score'] = mt_rand(0, 70) / 100;
                }
                $scores[$dominantEmotion . '_score'] = mt_rand(80, 100) / 100;

                // Buat timestamp acak dalam 30 hari terakhir dari hari ini.
                $randomDaysAgo = rand(0, 29);
                $randomTimestamp = Carbon::now()->subDays($randomDaysAgo);

                EmotionRecord::create(array_merge([
                    'user_id' => $studentId,
                    'detection_timestamp' => $randomTimestamp,
                    'dominant_emotion' => $dominantEmotion,
                    'gender' => ['male', 'female'][array_rand(['male', 'female'])],
                    'gender_probability' => mt_rand(70, 100) / 100,
                ], $scores));
            }
        }
        $this->command->info('Seeder data emosi untuk semua murid berhasil dijalankan!');
    }
}