<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Nonaktifkan pengecekan foreign key sebelum truncate
        Schema::disableForeignKeyConstraints();
        User::truncate();
        Schema::enableForeignKeyConstraints();

        // 1. Buat 1 Akun Admin
        User::create([
            'name' => 'Admin Utama',
            'email' => 'admin@moodvis.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // 2. Buat 1 Akun Guru Utama (yang akan memiliki semua siswa)
        $mainTeacher = User::create([
            'name' => 'Guru Utama',
            'email' => 'guru.utama@moodvis.com',
            'password' => Hash::make('password'),
            'role' => 'pengajar',
        ]);

        // 2a. Buat 1 kelas dan hubungkan ke Guru Utama
        $kelasUtama = \App\Models\Classes::create([
            'name' => 'XI IPA 1',
            'teacher_id' => $mainTeacher->id,
        ]);

        // 3. Buat 10 Akun Murid dan alokasikan SEMUANYA ke Guru Utama dan kelas utama
        for ($i = 1; $i <= 1; $i++) {
            User::create([
                'name' => 'Murid ' . $i,
                'email' => 'murid' . $i . '@moodvis.com',
                'password' => Hash::make('password'),
                'role' => 'murid',
                'teacher_id' => $mainTeacher->id, // Semua murid terhubung ke $mainTeacher
                'class_id' => $kelasUtama->id,     // Semua murid masuk ke kelas utama
            ]);
        }

        // Output untuk memberitahu Anda kredensial login
        $this->command->info('Database seeding completed!');
        $this->command->info('==================================');
        $this->command->info('Akun yang bisa digunakan untuk login:');
        $this->command->line('Admin: <info>admin@moodvis.com</info> | Password: <info>password</info>');
        $this->command->line('Guru: <info>guru.utama@moodvis.com</info> | Password: <info>password</info>');
        $this->command->line('Murid: <info>murid1@moodvis.com</info> s/d <info>murid10@moodvis.com</info> | Password: <info>password</info>');
        $this->command->info('==================================');
    }
}
