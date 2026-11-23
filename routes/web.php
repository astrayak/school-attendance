<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\GuruController as AdminGuruController;
use App\Http\Controllers\Admin\GuruPiketController;
use App\Http\Controllers\Admin\JadwalController;
use App\Http\Controllers\Admin\KelasController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\LogAktivitasController;
use App\Http\Controllers\Admin\MataPelajaranController;
use App\Http\Controllers\Admin\PengaturanController;
use App\Http\Controllers\Admin\PenugasanGuruController;
use App\Http\Controllers\Admin\PetugasKonfirmasiController;
use App\Http\Controllers\Admin\RekapSiswaController;
use App\Http\Controllers\Admin\SemesterController;
use App\Http\Controllers\Admin\SesiAbsensiController;
use App\Http\Controllers\Admin\SiswaController;
use App\Http\Controllers\Admin\TahunAjaranController;
use App\Http\Controllers\Guru\AbsensiController;
use App\Http\Controllers\Guru\DashboardController as GuruDashboardController;
use App\Http\Controllers\Guru\JadwalController as GuruJadwalController;
use App\Http\Controllers\Guru\PiketController;
use App\Http\Controllers\Guru\ProfilController as GuruProfilController;
use App\Http\Controllers\Guru\ScannerController;
use App\Http\Controllers\Guru\SesiController as GuruSesiController;
use App\Http\Controllers\Siswa\DashboardController as SiswaDashboardController;
use App\Http\Controllers\Siswa\KonfirmasiController;
use App\Http\Controllers\Siswa\ProfilController;
use App\Http\Controllers\Siswa\QrController;
use App\Http\Controllers\Siswa\RekapController;
use App\Http\Controllers\Siswa\RiwayatController;
use App\Http\Controllers\Sistem\PembaruanController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'tampilkanLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.proses')->middleware('throttle:10,1');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::get('/dashboard', DashboardController::class)->middleware('auth')->name('dashboard');

Route::middleware(['auth', 'peran:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');

    Route::resource('siswa', SiswaController::class)->except(['destroy']);
    Route::post('siswa/{siswa}/qr/regenerasi', [SiswaController::class, 'regenerasiQr'])->name('siswa.qr.regenerasi');
    Route::post('siswa/{siswa}/qr/cabut', [SiswaController::class, 'cabutQr'])->name('siswa.qr.cabut');

    Route::resource('guru', AdminGuruController::class)->except(['destroy']);
    Route::resource('kelas', KelasController::class)->except(['destroy', 'show'])->parameters(['kelas' => 'kelas']);
    Route::resource('mata-pelajaran', MataPelajaranController::class)->except(['destroy', 'show']);
    Route::resource('tahun-ajaran', TahunAjaranController::class)->except(['destroy', 'show']);
    Route::resource('semester', SemesterController::class)->except(['destroy', 'show']);

    Route::get('penugasan-guru', [PenugasanGuruController::class, 'index'])->name('penugasan.index');
    Route::get('penugasan-guru/tambah', [PenugasanGuruController::class, 'create'])->name('penugasan.create');
    Route::post('penugasan-guru', [PenugasanGuruController::class, 'store'])->name('penugasan.store');
    Route::post('penugasan-guru/{penugasan}/status', [PenugasanGuruController::class, 'ubahStatus'])->name('penugasan.status');

    Route::get('jadwal', [JadwalController::class, 'index'])->name('jadwal.index');
    Route::post('jadwal/versi', [JadwalController::class, 'storeVersi'])->name('jadwal.versi.store');
    Route::post('jadwal/versi/{versi}/aktifkan', [JadwalController::class, 'aktifkanVersi'])->name('jadwal.versi.aktifkan');
    Route::post('jadwal/versi/{versi}/revisi', [JadwalController::class, 'revisi'])->name('jadwal.versi.revisi');
    Route::get('jadwal/tambah', [JadwalController::class, 'create'])->name('jadwal.create');
    Route::post('jadwal', [JadwalController::class, 'storeJadwal'])->name('jadwal.store');
    Route::get('jadwal/{jadwal}/edit', [JadwalController::class, 'edit'])->name('jadwal.edit');
    Route::put('jadwal/{jadwal}', [JadwalController::class, 'updateJadwal'])->name('jadwal.update');
    Route::post('jadwal/{jadwal}/status', [JadwalController::class, 'ubahStatusJadwal'])->name('jadwal.status');

    Route::get('guru-piket', [GuruPiketController::class, 'index'])->name('piket.index');
    Route::get('guru-piket/tambah', [GuruPiketController::class, 'create'])->name('piket.create');
    Route::post('guru-piket', [GuruPiketController::class, 'store'])->name('piket.store');
    Route::post('guru-piket/{piket}/status', [GuruPiketController::class, 'ubahStatus'])->name('piket.status');

    Route::get('petugas-konfirmasi', [PetugasKonfirmasiController::class, 'index'])->name('petugas-konfirmasi.index');
    Route::get('petugas-konfirmasi/tambah', [PetugasKonfirmasiController::class, 'create'])->name('petugas-konfirmasi.create');
    Route::post('petugas-konfirmasi', [PetugasKonfirmasiController::class, 'store'])->name('petugas-konfirmasi.store');
    Route::post('petugas-konfirmasi/{petugas}/status', [PetugasKonfirmasiController::class, 'ubahStatus'])->name('petugas-konfirmasi.status');

    Route::get('sesi-absensi', [SesiAbsensiController::class, 'index'])->name('sesi.index');
    Route::get('sesi-absensi/{sesi}', [SesiAbsensiController::class, 'show'])->name('sesi.show');
    Route::post('sesi-absensi/{sesi}/koreksi', [SesiAbsensiController::class, 'koreksi'])->name('sesi.koreksi');
    Route::post('sesi-absensi/{sesi}/batalkan', [SesiAbsensiController::class, 'batalkan'])->name('sesi.batalkan');

    Route::get('laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::get('laporan/ekspor', [LaporanController::class, 'ekspor'])->name('laporan.ekspor');
    Route::get('rekap-siswa', [RekapSiswaController::class, 'index'])->name('rekap-siswa.index');
    Route::get('log-aktivitas', [LogAktivitasController::class, 'index'])->name('log.index');
    Route::get('pengaturan', [PengaturanController::class, 'index'])->name('pengaturan.index');
    Route::post('pengaturan', [PengaturanController::class, 'store'])->name('pengaturan.store');
});

Route::middleware(['auth', 'peran:guru'])->prefix('guru')->name('guru.')->group(function () {
    Route::get('/dashboard', GuruDashboardController::class)->name('dashboard');
    Route::get('profil', GuruProfilController::class)->name('profil');
    Route::post('profil/foto', [GuruProfilController::class, 'perbaruiFoto'])->name('profil.foto');
    Route::get('jadwal', GuruJadwalController::class)->name('jadwal.index');
    Route::get('sesi-absensi', [GuruSesiController::class, 'index'])->name('sesi.index');
    Route::get('sesi-absensi/{sesi}', [GuruSesiController::class, 'show'])->name('sesi.show');
    Route::get('absensi/{sesi}', [AbsensiController::class, 'show'])->name('absensi.show');
    Route::post('absensi/{sesi}', [AbsensiController::class, 'simpan'])->name('absensi.simpan');
    Route::get('scanner', [ScannerController::class, 'index'])->name('scanner.index');
    Route::get('scanner/{sesi}', [ScannerController::class, 'show'])->name('scanner.show');
    Route::post('scanner/{sesi}/pindai', [ScannerController::class, 'pindai'])->name('scanner.pindai');
    Route::post('scanner/{sesi}/finalisasi', [ScannerController::class, 'finalisasi'])->name('scanner.finalisasi');
    Route::get('guru-piket', [PiketController::class, 'index'])->name('piket.index');
    Route::post('guru-piket/{sesi}/ambil-alih', [PiketController::class, 'ambilAlih'])->name('piket.ambil-alih');
});

Route::middleware(['auth', 'peran:siswa'])->prefix('siswa')->name('siswa.')->group(function () {
    Route::get('/dashboard', SiswaDashboardController::class)->name('dashboard');
    Route::get('profil', ProfilController::class)->name('profil');
    Route::post('profil/foto', [ProfilController::class, 'perbaruiFoto'])->name('profil.foto');
    Route::get('qr-code', QrController::class)->name('qr');
    Route::get('riwayat-absensi', [RiwayatController::class, 'index'])->name('riwayat.index');
    Route::get('riwayat-absensi/{catatan}', [RiwayatController::class, 'show'])->name('riwayat.show');
    Route::get('rekap-mapel', [RekapController::class, 'mapel'])->name('rekap.mapel');
    Route::get('rekap-harian', [RekapController::class, 'harian'])->name('rekap.harian');
    Route::get('konfirmasi', [KonfirmasiController::class, 'index'])->name('konfirmasi.index');
    Route::post('konfirmasi/{permintaan}', [KonfirmasiController::class, 'jawab'])->name('konfirmasi.jawab');
});

Route::middleware('auth')->group(function () {
    Route::get('/notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi.index');
    Route::post('/notifikasi/{notifikasi}/baca', [NotifikasiController::class, 'baca'])->name('notifikasi.baca');
    Route::post('/notifikasi/baca-semua', [NotifikasiController::class, 'bacaSemua'])->name('notifikasi.baca-semua');
});

Route::middleware('auth')->prefix('sistem')->name('sistem.')->group(function () {
    Route::get('pembaruan', [PembaruanController::class, 'index'])->name('pembaruan.index');
    Route::post('pembaruan', [PembaruanController::class, 'jalankan'])->name('pembaruan.jalankan');
    Route::get('troubleshooting', [PembaruanController::class, 'riwayat'])->name('troubleshooting.index');
});
