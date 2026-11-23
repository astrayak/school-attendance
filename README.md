# Sistem Absensi Sekolah

Aplikasi absensi sekolah berbasis Laravel 13, Blade, Tailwind CSS, dan MySQL (Laragon). Sistem memiliki tiga peran: **admin**, **guru**, dan **siswa**, dengan data demo satu semester penuh.

## Fitur Utama

### Admin
- Data master: siswa, guru, kelas, mata pelajaran, tahun ajaran, dan semester.
- Penugasan guru per kelas/mata pelajaran/semester.
- Jadwal pelajaran dengan versi jadwal, validasi benturan kelas dan guru, serta revisi/aktivasi versi.
- Jadwal guru piket dan petugas konfirmasi siswa.
- Monitoring sesi absensi, koreksi absensi dengan audit, dan pembatalan sesi.
- Rekap seluruh siswa: filter kelas/status/periode, foto profil, dan ringkasan kehadiran.
- Laporan rekap kelas/mapel dan ekspor laporan.
- Log aktivitas dan pengaturan sistem (jendela absensi, batas konfirmasi, pengalihan otomatis, zona waktu).

### Guru
- Dashboard, jadwal mengajar, dan daftar sesi.
- Profil guru dengan unggah foto profil (JPG/PNG/WEBP, maksimal 2 MB).
- Pengisian absensi manual dengan validasi jendela waktu dan hak akses sesi.
- Pemindaian QR siswa (kamera/input token) serta finalisasi absensi QR.
- Pengambilalihan sesi sebagai guru piket.

### Siswa
- Dashboard, profil, dan QR Code identitas (token terenkripsi, dapat dicabut/diregenerasi admin).
- Unggah foto profil (JPG/PNG/WEBP, maksimal 2 MB) yang tampil di rekap admin.
- Riwayat absensi dan rekap per mata pelajaran/harian.
- Konfirmasi pembelajaran setelah jam pelajaran selesai, termasuk pelaporan pembelajaran tidak berlangsung.

### Lintas Peran
- Notifikasi in-app.
- Middleware peran dan kebijakan (policy) untuk sesi, jadwal, siswa, dan permintaan konfirmasi.
- Audit perubahan status absensi dan log aktivitas pengguna.
- Penjadwalan pemeliharaan sesi/konfirmasi setiap menit.

## Antarmuka

Tampilan memakai arah desain **Buku Induk**: kertas hangat, tinta, garis rambut, sudut tegas, dan satu aksen vermilion. Tidak ada gradient dekoratif, blob blur, atau kartu membulat berlebihan.

- Tipografi: **Crimson Pro** (judul), **Atkinson Hyperlegible** (teks), **JetBrains Mono** (label, tanggal, angka).
- Palet warna: `paper`, `surface`, `ink`, `line`, `accent`, `moss`, `ochre`, `slateblue`, `clay` — didefinisikan di `resources/css/app.css`.
- Font dimuat lokal lewat paket `@fontsource/*`, tanpa ketergantungan CDN.
- Komponen dasar: `.card`, `.btn-*`, `.form-*`, `.badge`, `.kicker`, `.progress-track`, `.stamp` dengan radius 2px.
- Ikon memakai Lucide dengan ketebalan garis 1.6 agar selaras dengan tipografi editorial.

## Kebutuhan

- PHP 8.3+
- Composer 2
- Node.js 20+ dan npm
- MySQL 8 (Laragon)
- Ekstensi PHP: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `zip`

## Instalasi

```bash
cd C:\laragon\www\school-attendance
composer install
copy .env.example .env
php artisan key:generate
npm install
npm run build
```

Sesuaikan koneksi database pada `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=absensi_sekolah
DB_USERNAME=root
DB_PASSWORD=
```

Buat database `absensi_sekolah`, lalu jalankan migrasi dan seeder demo:

```bash
php artisan migrate:fresh --seed
php artisan storage:link
```

Seeder akan melewati proses bila tabel `pengguna` sudah berisi data. Gunakan `migrate:fresh --seed` untuk membangun ulang data demo.

## Akun Demo

| Peran | Email | Kata sandi |
| --- | --- | --- |
| Admin | `admin@sekolah.test` | `password` |
| Guru | `guru@sekolah.test` | `password` |
| Siswa | `siswa@sekolah.test` | `password` |

- Guru demo: **Budi Santoso** (wali kelas X IPA 1, guru Matematika/Informatika, guru piket Jumat).
- Siswa demo: **Andi Pratama** (NIS `2425001`, kelas X IPA 1).
- Akun guru lain: `siti@sekolah.test`, `ahmad@sekolah.test` (kata sandi sama).

### Data Demo

- Tahun ajaran 2026/2027, semester ganjil aktif `2026-09-01` sampai `2026-12-31`.
- 2 kelas (X IPA 1, X IPS 1), 3 guru, 22 siswa, 6 mata pelajaran, 38 jadwal.
- Riwayat sesi dan absensi hari kerja sejak awal semester sampai kemarin.
- Variasi status sesi: terkonfirmasi, dibatalkan, ditutup tanpa absensi, perlu pemeriksaan, belum dikonfirmasi.
- Beberapa sesi memakai QR, gabungan QR/manual, dan pengambilalihan guru piket.
- Satu koreksi absensi admin beserta audit dan log.
- Satu permintaan konfirmasi aktif untuk Andi Pratama, dibuat saat seeder dijalankan dengan batas respons sekitar **4 menit setelah seeding**. Setelah kedaluwarsa, sesi akan diproses scheduler menjadi belum dikonfirmasi.

## Menjalankan Aplikasi

Dengan Laragon, arahkan virtual host ke folder `public`. Untuk pengembangan cepat:

```bash
php artisan serve
```

Buka `http://127.0.0.1:8000` atau `http://school-attendance.test` sesuai konfigurasi.

## Scheduler

Pemeliharaan sesi dan konfirmasi berjalan setiap menit melalui `routes/console.php`:

```bash
php artisan schedule:list
php artisan schedule:work
```

Untuk server produksi, daftarkan cron setiap menit:

```cron
* * * * * cd /path/to/school-attendance && php artisan schedule:run >> /dev/null 2>&1
```

Di Windows, buat Task Scheduler yang menjalankan `php artisan schedule:run` setiap menit. Perintah manual tersedia:

```bash
php artisan absensi:pemeliharaan
```

Perintah ini menyinkronkan status sesi hari ini/kemarin, menutup sesi terlewat, memproses permintaan konfirmasi kedaluwarsa, mengalihkan ke petugas berikutnya, dan menandai sesi belum dikonfirmasi.

## Pengujian

Test memakai database MySQL `absensi_sekolah_test` (lihat `phpunit.xml`) dan waktu beku `2026-11-20 11:35` agar hasil deterministik.

```bash
php artisan test
```

Hasil verifikasi terakhir:

- **12 test lulus, 197 assertion**.
- Cakupan: login dan seluruh halaman utama per peran, pembatasan akses antarperan, pengisian absensi dalam/luar jendela, pengiriman permintaan konfirmasi, QR ganda/tercabut/kelas lain, konfirmasi positif, pengalihan konfirmasi dan penonaktifannya, koreksi admin dengan audit/log, konsistensi rekap harian/mapel/kelas, serta pemeliharaan sesi terjadwal.

Catatan: suite berjalan lambat (sekitar 5–6 menit) karena setiap test menjalankan seeder demo penuh.

## Struktur Penting

| Bagian | Lokasi |
| --- | --- |
| Controller admin/guru/siswa | `app/Http/Controllers` |
| Layanan domain | `app/Services` (`LayananAbsensi`, `LayananKonfirmasi`, `LayananJadwal`, `LayananQrCode`, `LayananRekapAbsensi`, `LayananSesi`, dll.) |
| Policy | `app/Policies` |
| Middleware peran | `app/Http/Middleware` |
| Migrasi | `database/migrations` |
| Seeder demo | `database/seeders/DatabaseSeeder.php` |
| Scheduler | `routes/console.php` |
| Test | `tests/Feature` |

## Catatan Keamanan dan Konsistensi Data

- Kata sandi memakai hash Bcrypt; autentikasi menggunakan kolom `kata_sandi` melalui `getAuthPasswordName()`, dengan kredensial Laravel `password`.
- Token QR disimpan sebagai hash SHA-256 dan salinan terenkripsi; QR lama otomatis dicabut saat regenerasi.
- Semua perubahan status absensi dicatat pada `audit_absensi`; aktivitas pengguna dicatat pada `log_aktivitas`.
- Absensi divalidasi di backend: jendela waktu, hak guru/piket, kelengkapan seluruh siswa, dan status sesi.
- Nama tabel/kolom domain menggunakan bahasa Indonesia; tabel infrastruktur Laravel tetap memakai nama bawaan framework.
