# PROJECT PROGRESS — Sistem Absensi Sekolah

Terakhir diperbarui: 20 November 2026

## Status

**Fase pengembangan inti selesai dan terverifikasi.** Aplikasi Laravel + MySQL berjalan dengan data demo satu semester, autentikasi tiga peran, absensi manual/QR, konfirmasi siswa, koreksi audit, rekap, dan pemeliharaan terjadwal. Seluruh test otomatis lulus.

## Ringkasan Kebutuhan

- [x] Tabel dan kolom domain berbahasa Indonesia (snake_case).
- [x] Laravel Blade + Tailwind, MySQL Laragon.
- [x] Peran admin, guru, siswa dengan middleware dan policy.
- [x] Absensi manual guru dan QR siswa (token terenkripsi, regenerasi/pencabutan).
- [x] Konfirmasi pembelajaran oleh siswa, pengalihan otomatis, penonaktifan pengalihan.
- [x] Rekap harian, per mapel, dan per kelas.
- [x] Koreksi absensi admin dengan audit dan log aktivitas.
- [x] Scheduler pemeliharaan sesi/konfirmasi setiap menit.
- [x] Seeder demo realistis dan akun demo.
- [x] Validasi backend untuk seluruh alur kritis (bukan fitur frontend palsu).
- [x] Unggah foto profil siswa/guru dan rekap seluruh siswa untuk admin.
- [x] Test fitur nyata dan didokumentasikan.
- [x] README dan dokumen progres.

## Keputusan Implementasi

- Tahun ajaran demo: **2026/2027**; semester ganjil aktif **2026-09-01 s.d. 2026-12-31**; semester genap dibuat tidak aktif.
- 2 kelas: **X IPA 1**, **X IPS 1**; 3 guru: Budi, Siti, Ahmad; 22 siswa; 6 mapel; 38 jadwal mingguan tanpa benturan.
- Siswa demo: **Andi Pratama** (`siswa@sekolah.test`, NIS `2425001`).
- Sesi demo konfirmasi memakai sesi X IPA 1 terakhir yang sudah dimulai pada hari seeding, berakhir satu menit setelah seeding. Pada hari Jumat, sesi diambil alih Budi sebagai guru piket.
- Permintaan konfirmasi tidak boleh kedaluwarsa sebelum jam pelajaran selesai (`waktu_selesai` menjadi acuan minimum).
- Pengalihan otomatis dapat dimatikan melalui pengaturan `pengalihan_konfirmasi_otomatis`.
- Scheduler memakai `Schedule::call(...)->everyMinute()->withoutOverlapping()` di `routes/console.php`, plus perintah manual `absensi:pemeliharaan`.
- Autentikasi memakai `Auth::attempt` dengan kunci kredensial `password`, sementara kolom database tetap `kata_sandi` melalui `getAuthPasswordName()`.
- Antarmuka memakai sistem desain **Buku Induk** (kertas, tinta, garis rambut, aksen vermilion) dengan tipografi Crimson Pro, Atkinson Hyperlegible, dan JetBrains Mono yang dimuat lokal melalui `@fontsource/*`; token warna dan komponen berada di `resources/css/app.css`.

## Data Demo Terverifikasi

Dihasilkan oleh `php artisan migrate:fresh --seed`:

| Entitas | Jumlah |
| --- | --- |
| Pengguna | 26 |
| Guru | 3 |
| Siswa | 22 |
| Kelas | 2 |
| Mata pelajaran | 6 |
| Jadwal pelajaran | 38 |
| Penugasan guru | 12 |
| Jadwal guru piket | 5 |
| Petugas konfirmasi | 5 |
| Sesi absensi | 448 |
| Catatan absensi | 4.818 |
| Permintaan konfirmasi | 440 |
| Ringkasan harian | 1.276 |
| Kode QR | 22 |
| Audit absensi | 1 |
| Log aktivitas | 9 |
| Notifikasi | 4 |

Distribusi status sesi: terkonfirmasi 433, ditutup 7, belum dikonfirmasi 2, perlu pemeriksaan 2, dibatalkan 2, menunggu 1, menunggu konfirmasi 1.

Distribusi kehadiran: hadir 4.451, izin 150, sakit 102, alpha 115.

Distribusi ringkasan harian: hadir penuh 910, parsial 331, belum lengkap 34, campuran non-hadir 1.

Verifikasi tambahan:
- QR siswa demo dapat didekripsi dan diverifikasi.
- Permintaan konfirmasi aktif untuk Andi Pratama memiliki batas respons beberapa menit setelah seeding.
- Rekap mapel/harian/kelas menghasilkan total kategori yang konsisten.

## Pengujian

Perintah: `php artisan test`

Hasil terakhir: **12 test lulus, 197 assertion, 0 gagal**.

File test:
- `tests/Feature/ExampleTest.php` — halaman awal mengarahkan tamu ke login.
- `tests/Feature/SmokeTest.php` — login, logout, seluruh halaman utama admin/guru/siswa, akses lintas peran.
- `tests/Feature/AlurAbsensiTest.php` — 8 skenario:
  1. Guru menyimpan absensi dalam jendela; sesi menjadi menunggu konfirmasi; permintaan terkirim dengan batas setelah jam selesai.
  2. Absensi ditolak setelah jendela ditutup.
  3. QR menolak pemindaian ganda, QR tercabut, dan siswa kelas lain.
  4. Siswa mengonfirmasi pembelajaran berlangsung; sesi terkonfirmasi; log tercatat.
  5. Konfirmasi dialihkan ke petugas berikutnya; pengalihan otomatis dapat dinonaktifkan.
  6. Koreksi admin mencatat audit dan log, status sesi tetap menunggu konfirmasi.
  7. Rekap harian/mapel/kelas konsisten dan kategori harian benar.
  8. Pemeliharaan menutup sesi terlewat dan menyelesaikan konfirmasi kedaluwarsa.

Catatan: test memakai MySQL `absensi_sekolah_test`, waktu beku `2026-11-20 11:35`, dan seeder penuh per test sehingga suite berjalan sekitar 5–6 menit.

## Perbaikan Penting

- **Login:** Laravel 13 memfilter kredensial berdasarkan kunci `password`; sebelumnya `kata_sandi` ikut masuk ke query sehingga autentikasi gagal. Controller kini mengirim `password`, sementara model tetap memetakan kolom `kata_sandi`.
- **Kedaluwarsa konfirmasi:** permintaan tidak lagi kedaluwarsa sebelum jam pelajaran selesai.
- **Pengalihan otomatis:** kini menghormati pengaturan `pengalihan_konfirmasi_otomatis`.
- **Sesi demo hari ini:** memakai sesi terjadwal yang sudah dimulai, sehingga tidak bertumpang tindih dan mendukung pengambilalihan piket Jumat.
- **Test bawaan:** `ExampleTest` disesuaikan dengan perilaku redirect aplikasi.

## Keterbatasan / Catatan Operasional

- MySQL Laragon harus berjalan sebelum migrasi/test.
- Scheduler harus aktif agar konfirmasi kedaluwarsa dan sesi terlewat diproses otomatis.
- Permintaan konfirmasi demo hanya aktif sekitar 4 menit setelah seeding; jalankan ulang `migrate:fresh --seed` untuk demo baru.
- Seeder tidak menimpa database yang sudah berisi pengguna.
- Belum ada konfigurasi deployment produksi (queue, cache, web server, HTTPS) karena fokus pada aplikasi lokal Laragon.

## Langkah Berikutnya (opsional)

1. Tambah test untuk CRUD master data dan validasi benturan jadwal.
2. Tambah test ekspor laporan dan notifikasi in-app.
3. Optimasi seeder/test (mis. dataset ringkas khusus test) agar suite lebih cepat.
4. Siapkan panduan deployment produksi dan backup database.
5. Audit aksesibilitas dan responsivitas tampilan.
