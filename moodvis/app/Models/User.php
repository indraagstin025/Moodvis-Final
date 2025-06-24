<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Facades\URL;


class User extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'photo',
        'role',
        'teacher_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

      protected $appends = ['photo_url'];

    /**
     * Bagian ini TIDAK DIUBAH sesuai permintaan Anda.
     */
// app/Models/User.php

public function getPhotoUrlAttribute()
{
    if ($this->photo) {
        return url('profile/' . $this->photo);
    }

    return null;
}


    /**
     * Mendapatkan data pengajar yang membimbing murid ini.
     * (Relasi untuk murid)
     */
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }




    /**
     * Mendapatkan semua murid yang dibimbing oleh pengajar ini.
     * (Relasi untuk pengajar)
     */
    public function students()
    {
        return $this->hasMany(User::class, 'teacher_id');
    }

    /**
 * Relasi untuk MURID ke kelasnya.
 * Menjawab pertanyaan: "Murid ini ada di kelas mana?"
 */
public function class()
{
    // Menggunakan class_id sebagai foreign key
    return $this->belongsTo(Classes::class, 'class_id');
}

/**
 * Relasi untuk GURU sebagai wali kelas.
 * Menjawab pertanyaan: "Kelas mana yang diampu oleh guru ini?"
 */
public function homeroomClass()
{
    // Menggunakan teacher_id sebagai foreign key di tabel classes
    return $this->hasOne(Classes::class, 'teacher_id');
}


    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key-value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {

        return [
            'role' => $this->role,
        ];
    }



    /**
     * Cek apakah user adalah admin.
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Cek apakah user adalah pengajar.
     * @return bool
     */
    public function isPengajar(): bool
    {
        return $this->role === 'pengajar';
    }

    /**
     * Cek apakah user adalah murid.
     * @return bool
     */
    public function isMurid(): bool
    {
        return $this->role === 'murid';
    }
}