<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terhubung engan model.
     * @var string
     *
     */

     protected $table = 'reports';

     /**
      * Attribut yang dapat diisi secara massal.
      * Ini adalah kolom-kolom dari tabel 'reports' yang telah dibuat di migrasi.
      * @var array<int, string>
      */

      protected $fillable = [
        'student_id',
        'teacher_id',
        'start_date',
        'end_date',
        'filename',
        'file_path',
      ];

      /**
       * Attribut yang harus di cast ke tipe data asli
       * @var array<string, string>
       */
      protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
      ];

      /**
     * Mendapatkan data siswa (user) yang memiliki laporan ini.
     * Relasi ini menghubungkan 'student_id' di tabel 'reports'
     * ke 'id' di tabel 'users'.
     */
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Mendapatkan data pengajar (user) yang membuat laporan ini.
     * Relasi ini menghubungkan 'teacher_id' di tabel 'reports'
     * ke 'id' di tabel 'users'.
     */
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

}

?>
