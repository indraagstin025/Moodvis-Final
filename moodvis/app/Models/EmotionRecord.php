<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmotionRecord extends Model
{


    /**
     * Nama tabel yang terhubung dengan model.
     *
     * @var string
     */
    protected $table = 'emotion_records';

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'detection_timestamp',
        'dominant_emotion',
        'happiness_score',
        'sadness_score',
        'anger_score',
        'fear_score',
        'disgust_score',
        'surprise_score',
        'neutral_score',
        'gender',
        'gender_probability',
    ];

    /**
     * Atribut yang harus di-cast ke tipe data asli.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'detection_timestamp' => 'datetime',
        'happines_score' => 'float',
        'sadness_score' => 'float',
        'anger_score' => 'float',
        'fear_score' => 'float',
        'disgust_score' => 'float',
        'surprise_score' => 'float',
        'neutral_score' => 'float',
        'gender_probability' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Mendapatkan pengguna (user) yang memiliki catatan emosi ini.
     */
    public function user()
    {

        return $this->belongsTo(User::class, 'user_id');
    }
}
