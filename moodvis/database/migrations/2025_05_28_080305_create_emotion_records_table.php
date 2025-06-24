<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('emotion_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('detection_timestamp');
            $table->string('dominant_emotion');
            $table->decimal('happiness_score', 5, 4)->default(0); // Perhatikan typo: 'happines_score'
            $table->decimal('sadness_score', 5, 4)->default(0);
            $table->decimal('anger_score', 5, 4)->default(0);
            $table->decimal('fear_score', 5, 4)->default(0);
            $table->decimal('disgust_score', 5, 4)->default(0);
            $table->decimal('surprise_score', 5, 4)->default(0);
            $table->decimal('neutral_score', 5, 4)->default(0);
            $table->string('gender', 10)->nullable();
            $table->decimal('gender_probability', 5, 4)->nullable();
            $table->index('detection_timestamp');
            $table->index(['user_id', 'detection_timestamp']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emotion_records');
    }
};
