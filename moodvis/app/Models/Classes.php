<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classes extends Model
{
    /**
     * Properti $fillable menentukan kolom mana saja yang boleh diisi
     * secara massal. Dalam hal ini, hanya kolom 'name'.
     */
   protected $fillable = ['name', 'teacher_id'];

    /**
     * Mendefinisikan relasi kebalikannya: satu Kelas bisa memiliki banyak User (siswa).
     * Ini adalah relasi "hasMany".
     */
    public function users()
    {
        return $this->hasMany(User::class, 'class_id');
    }

    // Di dalam file app/Models/Classes.php

/**
 * Relasi dari Kelas ke GURU (Wali Kelas).
 */
public function teacher()
{
    return $this->belongsTo(User::class, 'teacher_id');
}

/**
 * Relasi dari Kelas ke semua MURID-nya.
 */
public function students()
{
    return $this->hasMany(User::class, 'class_id');
}
}
