<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Analisis Emosi Siswa</title>
    <style>
        /* Menggunakan font sans-serif yang modern dan mudah dibaca */
        @page { margin: 40px; }
        body {
            font-family: 'Helvetica Neue', 'Arial', sans-serif;
            color: #333;
            line-height: 1.6;
            background-color: #ffffff;
            font-size: 10pt;
        }
        .report-container {
            width: 100%;
        }
        /* Header utama laporan dengan warna HIJAU TUA */
        .header {
            background-color: #15803d; /* Warna Hijau Tua Profesional */
            color: #ffffff;
            padding: 20px 30px;
            margin-bottom: 30px;
            border-radius: 8px;
            text-align: left;
        }
        .header h1 {
            margin: 0;
            font-size: 22pt;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .header p {
            margin: 5px 0 0;
            font-size: 11pt;
            opacity: 0.9;
        }
        /* Kontainer untuk informasi utama dengan aksen HIJAU */
        .info-grid {
            margin-bottom: 30px;
        }
        .info-card {
            background-color: #f0fdf4; /* Latar belakang hijau sangat muda */
            padding: 15px;
            border-radius: 8px;
            border-left: 5px solid #22c55e; /* Aksen Hijau Cerah */
        }
        .info-card .label {
            display: block;
            font-size: 9pt;
            color: #555;
            margin-bottom: 2px;
            text-transform: uppercase;
        }
        .info-card .value {
            display: block;
            font-size: 12pt;
            font-weight: bold;
            color: #111;
        }
        /* Judul untuk setiap seksi dengan warna HIJAU TUA */
        .section-title {
            font-size: 16pt;
            font-weight: bold;
            color: #15803d; /* Warna Hijau Tua Profesional */
            margin-top: 30px;
            margin-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 8px;
        }
        .content-paragraph {
            margin-bottom: 20px;
            text-align: justify;
        }
        .chart-container {
            text-align: center;
            margin-top: 20px;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            page-break-inside: avoid; /* Menjaga chart tidak terpotong */
        }
        .chart-container img {
            max-width: 95%;
            height: auto;
        }
        /* Penekanan dengan warna HIJAU CERAH */
        .important-note {
            font-weight: bold;
            color: #16a34a; /* Warna Hijau sebagai penekanan */
            text-transform: uppercase;
        }
       .footer {
            position: fixed; /* Membuat posisi footer tetap */
            bottom: 0px;     /* Menempatkannya di bagian paling bawah halaman */
            left: 0px;       /* Membentang dari kiri */
            right: 0px;      /* Sampai kanan */
            height: 50px;    /* Memberi tinggi pada area footer */

            /* Styling tambahan agar rapi */
            font-size: 8pt;
            color: #999;
            text-align: center;
            border-top: 1px solid #e0e0e0;
            padding-top: 10px;
            background-color: #ffffff; /* Beri background untuk menutupi teks di belakangnya */
        }

        /* Helper untuk layout tabel/grid */
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; padding: 0; }
        .w-1-3 { width: 33.33%; }
        .px-1 { padding-left: 4px; padding-right: 4px; }

        /* === KELAS CSS BARU UNTUK MEMAKSA PINDAH HALAMAN === */
        .new-page {
            page-break-before: always;
        }
        /* =================================================== */
    </style>
</head>
<body>
    <div class="report-container">
        <!-- HEADER LAPORAN -->
        <div class="header">
            <h1>Laporan Analisis Emosi</h1>
            <p>Sistem Aplikasi MoodVis</p>
        </div>

        <!-- INFORMASI UTAMA DALAM BENTUK KARTU -->
        <table class="info-grid">
            <tr>
                <td class="w-1-3 px-1">
                    <div class="info-card">
                        <span class="label">Nama Siswa</span>
                        <span class="value">{{ $studentName ?? 'Belum Teridentifikasi' }}</span>
                    </div>
                </td>
                <td class="w-1-3 px-1">
                    <div class="info-card">
                        <span class="label">Periode Analisis</span>
                        <span class="value">{{ $reportPeriod ?? 'Tidak Tersedia' }}</span>
                    </div>
                </td>
                <td class="w-1-3 px-1">
                    <div class="info-card">
                        <span class="label">Tanggal Laporan</span>
                        <span class="value">{{ \Carbon\Carbon::parse($printDate)->isoFormat('D MMMM YYYY') }}</span>
                    </div>
                </td>
            </tr>
        </table>

        <!-- RINGKASAN UTAMA -->
        <div class="section-title">Ringkasan Utama</div>
        <div class="content-paragraph">
            Berdasarkan kompilasi data dari catatan harian emosi, teridentifikasi bahwa <span class="important-note">{{ ucfirst($summaryEmotion) ?? 'belum ada data' }}</span> merupakan emosi yang paling dominan terekam dari {{ $studentName ?? 'siswa' }} selama periode pengamatan. Temuan ini menyediakan wawasan awal mengenai kecenderungan emosional yang diperlihatkan.
        </div>

        <!-- VISUALISASI GRAFIK -->
        <div class="section-title">Visualisasi Tren Emosi</div>
        <div class="content-paragraph">
            Grafik berikut menyajikan gambaran visual mengenai fluktuasi frekuensi emosi ananda selama periode pelaporan untuk pemahaman yang lebih mendalam.
        </div>
        <div class="chart-container">
            @if(isset($chartImageBase64) && $chartImageBase64)
                <img src="{{ $chartImageBase64 }}" alt="Grafik Tren Emosi Siswa">
            @else
                <p style="color: #777; padding: 50px 0;">Tidak ada data yang memadai untuk menghasilkan grafik tren emosi pada periode ini.</p>
            @endif
        </div>

        <!-- === WRAPPER BARU UNTUK MEMINDAHKAN KONTEN KE HALAMAN BARU === -->
        <div class="new-page">
            <!-- CATATAN PENUTUP -->
            <div class="section-title">Catatan & Rekomendasi</div>
            <div class="content-paragraph">
                Laporan ini bertujuan untuk menjadi panduan awal dalam memahami kondisi emosional siswa. Diskusi lebih lanjut antara guru, orang tua, dan siswa sangat dianjurkan untuk mendukung perkembangan emosional yang positif. Jika ada tren emosi negatif yang konsisten, disarankan untuk berkonsultasi dengan konselor sekolah.
            </div>

            <!-- FOOTER -->
            <div class="footer">
                Laporan ini dihasilkan secara otomatis oleh Sistem Aplikasi MoodVis.
                <br>
                Untuk interpretasi yang lebih mendalam, sangat disarankan untuk berkonsultasi langsung dengan konselor sekolah.
            </div>
        </div>
        <!-- =============================================================== -->
    </div>
</body>
</html>
