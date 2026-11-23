<?php

namespace Tests\Feature;

use App\Models\AuditAbsensi;
use App\Models\CatatanAbsensi;
use App\Models\Kelas;
use App\Models\PengaturanSistem;
use App\Models\Pengguna;
use App\Models\PermintaanKonfirmasi;
use App\Models\SesiAbsensi;
use App\Models\Siswa;
use App\Services\LayananAbsensi;
use App\Services\LayananJadwal;
use App\Services\LayananKonfirmasi;
use App\Services\LayananPengaturan;
use App\Services\LayananQrCode;
use App\Services\LayananRekapAbsensi;
use App\Services\LayananSesi;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AlurAbsensiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-11-20 11:35:00'));
        $this->seed();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function pengguna(string $email): Pengguna
    {
        return Pengguna::where('email', $email)->firstOrFail();
    }

    private function sesiHariIni(string $namaKelas, int $urutan): SesiAbsensi
    {
        $idKelas = Kelas::where('nama_kelas', $namaKelas)->value('id_kelas');

        return SesiAbsensi::query()
            ->with(['jadwal.kelas', 'jadwal.mataPelajaran'])
            ->whereDate('tanggal_pelajaran', now()->toDateString())
            ->whereHas('jadwal', fn ($q) => $q->where('id_kelas', $idKelas)->where('urutan_pelajaran', $urutan))
            ->firstOrFail();
    }

    public function test_guru_dapat_menyimpan_absensi_dalam_jendela_dan_konfirmasi_dikirim(): void
    {
        $this->travelTo(Carbon::parse('2026-11-20 11:50:00'));

        $guru = $this->pengguna('guru@sekolah.test');
        $sesi = $this->sesiHariIni('X IPS 1', 3);
        $siswa = app(LayananAbsensi::class)->siswaPadaSesi($sesi);

        $this->assertSame(SesiAbsensi::STATUS_MENUNGGU, $sesi->status_sesi);
        $this->assertCount(10, $siswa);

        $data = $siswa->mapWithKeys(fn (Siswa $murid) => [
            $murid->id_siswa => [
                'status' => $murid->nis === '2425014' ? CatatanAbsensi::IZIN : CatatanAbsensi::HADIR,
                'keterangan' => $murid->nis === '2425014' ? 'Izin mengikuti kegiatan lomba.' : null,
            ],
        ])->all();

        $hasil = app(LayananAbsensi::class)->simpan(
            $sesi,
            $data,
            SesiAbsensi::METODE_MANUAL,
            $guru,
            'Pengisian absensi oleh guru pengampu.',
        );

        $this->assertSame(SesiAbsensi::STATUS_MENUNGGU_KONFIRMASI, $hasil->status_sesi);
        $this->assertSame($guru->guru->id_guru, $hasil->id_guru_pelaksana);
        $this->assertNotNull($hasil->waktu_disimpan);
        $this->assertSame(SesiAbsensi::METODE_MANUAL, $hasil->metode_absensi);
        $this->assertSame(10, $hasil->catatanAbsensi()->count());
        $this->assertSame(1, $hasil->catatanAbsensi()->where('status_kehadiran', CatatanAbsensi::IZIN)->count());

        $permintaan = PermintaanKonfirmasi::query()
            ->where('id_sesi_absensi', $hasil->id_sesi_absensi)
            ->where('status_permintaan', PermintaanKonfirmasi::MENUNGGU)
            ->firstOrFail();

        $this->assertSame(1, $permintaan->urutan_penerima);
        $this->assertSame('Maya Anggraini', $permintaan->siswaPenerima->nama_lengkap);

        // Permintaan baru boleh kedaluwarsa setelah pembelajaran selesai.
        $this->assertTrue($permintaan->waktu_kedaluwarsa->gt($hasil->waktu_selesai));

        $this->assertDatabaseHas('log_aktivitas', [
            'id_pengguna' => $guru->id_pengguna,
            'jenis_aktivitas' => 'simpan_absensi',
            'jenis_data' => 'sesi_absensi',
            'id_data_terkait' => (string) $hasil->id_sesi_absensi,
        ]);
    }

    public function test_absensi_ditolak_setelah_jendela_ditutup(): void
    {
        $this->travelTo(Carbon::parse('2026-11-20 12:10:00'));

        $guru = $this->pengguna('guru@sekolah.test');
        $sesi = $this->sesiHariIni('X IPS 1', 3);
        $siswa = app(LayananAbsensi::class)->siswaPadaSesi($sesi);

        $this->expectException(ValidationException::class);

        app(LayananAbsensi::class)->simpan(
            $sesi,
            $siswa->mapWithKeys(fn (Siswa $murid) => [
                $murid->id_siswa => ['status' => CatatanAbsensi::HADIR, 'keterangan' => null],
            ])->all(),
            SesiAbsensi::METODE_MANUAL,
            $guru,
        );
    }

    public function test_qr_menolak_pemindaian_duplikat_qr_dicabut_dan_kelas_lain(): void
    {
        // Senin, 23 November 2026: slot pertama X IPA 1 (Matematika, Budi).
        $this->travelTo(Carbon::parse('2026-11-23 08:40:00'));

        app(LayananJadwal::class)->pastikanSesiTanggal(now()->copy()->startOfDay());

        $guru = $this->pengguna('guru@sekolah.test');
        $admin = $this->pengguna('admin@sekolah.test');
        $sesi = $this->sesiHariIni('X IPA 1', 1);
        $qr = app(LayananQrCode::class);

        $andi = Siswa::where('nis', '2425001')->firstOrFail();
        $bunga = Siswa::where('nis', '2425002')->firstOrFail();
        $naufal = Siswa::where('nis', '2425014')->firstOrFail();

        $this->assertSame(SesiAbsensi::STATUS_AKTIF, $sesi->status_sesi);

        $hasil = app(LayananAbsensi::class)->catatQr($sesi, $qr->token($andi->kodeQrAktif), $guru);
        $this->assertSame('hadir', $hasil['status']);
        $this->assertSame($andi->id_siswa, $hasil['siswa']->id_siswa);

        $ulang = app(LayananAbsensi::class)->catatQr($sesi, $qr->token($andi->kodeQrAktif), $guru);
        $this->assertSame('sudah_diabsen', $ulang['status']);

        $this->assertDatabaseHas('catatan_absensi', [
            'id_sesi_absensi' => $sesi->id_sesi_absensi,
            'id_siswa' => $andi->id_siswa,
            'status_kehadiran' => CatatanAbsensi::HADIR,
            'metode_absensi' => CatatanAbsensi::METODE_QR,
        ]);

        $lain = app(LayananAbsensi::class)->catatQr($sesi, $qr->token($naufal->kodeQrAktif), $guru);
        $this->assertSame('kelas_lain', $lain['status']);

        $this->assertSame('hadir', app(LayananAbsensi::class)->catatQr($sesi, $qr->token($bunga->kodeQrAktif), $guru)['status']);

        $qr->cabut($bunga->kodeQrAktif, $admin);

        $dicabut = app(LayananAbsensi::class)->catatQr($sesi, $qr->token($bunga->kodeQrAktif), $guru);
        $this->assertSame('dicabut', $dicabut['status']);

        $this->assertDatabaseCount('audit_absensi', 3);
        $this->assertSame(
            2,
            AuditAbsensi::query()->where('alasan_perubahan', 'Pemindaian QR Code')->count()
        );
    }

    public function test_siswa_mengonfirmasi_pembelajaran_berlangsung(): void
    {
        $this->travelTo(Carbon::parse('2026-11-20 11:36:00'));

        $siswa = $this->pengguna('siswa@sekolah.test');
        $permintaan = PermintaanKonfirmasi::where('status_permintaan', PermintaanKonfirmasi::MENUNGGU)->firstOrFail();
        $sesi = $permintaan->sesiAbsensi;

        $this->actingAs($siswa)
            ->post(route('siswa.konfirmasi.jawab', $permintaan), [
                'jawaban' => PermintaanKonfirmasi::BERLANGSUNG,
                'keterangan' => 'Pembelajaran berlangsung dengan guru piket.',
            ])
            ->assertRedirect(route('siswa.konfirmasi.index'));

        $permintaan->refresh();
        $this->assertSame(PermintaanKonfirmasi::DIKONFIRMASI, $permintaan->status_permintaan);
        $this->assertSame(PermintaanKonfirmasi::BERLANGSUNG, $permintaan->jawaban);
        $this->assertNotNull($permintaan->waktu_direspons);
        $this->assertSame(SesiAbsensi::STATUS_TERKONFIRMASI, $sesi->fresh()->status_sesi);

        $this->assertDatabaseHas('log_aktivitas', [
            'id_pengguna' => $siswa->id_pengguna,
            'jenis_aktivitas' => 'konfirmasi',
            'jenis_data' => 'permintaan_konfirmasi',
            'id_data_terkait' => (string) $permintaan->id_permintaan_konfirmasi,
        ]);
    }

    public function test_konfirmasi_dialihkan_ke_petugas_berikutnya_dan_dapat_dinonaktifkan(): void
    {
        $pertama = PermintaanKonfirmasi::where('status_permintaan', PermintaanKonfirmasi::MENUNGGU)->firstOrFail();
        $sesi = $pertama->sesiAbsensi;

        // Persempit masa tunggu petugas pertama agar pengalihan dapat diuji.
        $pertama->update(['waktu_kedaluwarsa' => Carbon::parse('2026-11-20 11:35:30')]);

        $this->travelTo(Carbon::parse('2026-11-20 11:36:00'));
        app(LayananKonfirmasi::class)->prosesKedaluwarsa();

        $this->assertSame(PermintaanKonfirmasi::KEDALUWARSA, $pertama->fresh()->status_permintaan);
        $this->assertSame(SesiAbsensi::STATUS_MENUNGGU_KONFIRMASI, $sesi->fresh()->status_sesi);

        $kedua = PermintaanKonfirmasi::query()
            ->where('id_sesi_absensi', $sesi->id_sesi_absensi)
            ->where('urutan_penerima', 2)
            ->firstOrFail();

        $this->assertSame(PermintaanKonfirmasi::MENUNGGU, $kedua->status_permintaan);
        $this->assertSame('Bunga Lestari', $kedua->siswaPenerima->nama_lengkap);

        // Nonaktifkan pengalihan otomatis: permintaan kedua harus kedaluwarsa
        // tanpa membuat permintaan ketiga dan sesi tetap menunggu konfirmasi.
        PengaturanSistem::query()
            ->where('kunci', 'pengalihan_konfirmasi_otomatis')
            ->update(['nilai' => '0']);

        app()->forgetInstance(LayananPengaturan::class);
        app()->forgetInstance(LayananKonfirmasi::class);

        $kedua->update(['waktu_kedaluwarsa' => Carbon::parse('2026-11-20 11:36:30')]);
        $this->travelTo(Carbon::parse('2026-11-20 11:37:00'));

        app(LayananKonfirmasi::class)->prosesKedaluwarsa();

        $this->assertSame(PermintaanKonfirmasi::KEDALUWARSA, $kedua->fresh()->status_permintaan);
        $this->assertSame(2, PermintaanKonfirmasi::where('id_sesi_absensi', $sesi->id_sesi_absensi)->count());
        $this->assertSame(SesiAbsensi::STATUS_MENUNGGU_KONFIRMASI, $sesi->fresh()->status_sesi);
    }

    public function test_koreksi_admin_mencatat_audit_dan_log_aktivitas(): void
    {
        $this->travelTo(Carbon::parse('2026-11-20 11:36:00'));

        $admin = $this->pengguna('admin@sekolah.test');
        $sesi = SesiAbsensi::where('status_sesi', SesiAbsensi::STATUS_MENUNGGU_KONFIRMASI)->firstOrFail();
        $sesi->load('catatanAbsensi');

        $andi = Siswa::where('nis', '2425001')->firstOrFail();
        $catatan = $sesi->catatanAbsensi->firstWhere('id_siswa', $andi->id_siswa);
        $this->assertSame(CatatanAbsensi::HADIR, $catatan->status_kehadiran);

        $data = $sesi->catatanAbsensi->mapWithKeys(fn (CatatanAbsensi $baris) => [
            $baris->id_siswa => [
                'status' => $baris->id_siswa === $andi->id_siswa
                    ? CatatanAbsensi::ALPHA
                    : $baris->status_kehadiran,
                'keterangan' => $baris->id_siswa === $andi->id_siswa
                    ? 'Dikoreksi setelah verifikasi.'
                    : $baris->keterangan,
            ],
        ])->all();

        $this->actingAs($admin)
            ->post(route('admin.sesi.koreksi', $sesi), [
                'absensi' => $data,
                'alasan' => 'Verifikasi bukti ketidakhadiran oleh admin.',
            ])
            ->assertRedirect(route('admin.sesi.show', $sesi));

        $this->assertSame(CatatanAbsensi::ALPHA, $catatan->fresh()->status_kehadiran);
        $this->assertSame(SesiAbsensi::STATUS_MENUNGGU_KONFIRMASI, $sesi->fresh()->status_sesi);

        $audit = AuditAbsensi::query()
            ->where('id_catatan_absensi', $catatan->id_catatan_absensi)
            ->latest('waktu_perubahan')
            ->firstOrFail();

        $this->assertSame(CatatanAbsensi::HADIR, $audit->status_sebelumnya);
        $this->assertSame(CatatanAbsensi::ALPHA, $audit->status_sesudahnya);
        $this->assertSame('Verifikasi bukti ketidakhadiran oleh admin.', $audit->alasan_perubahan);
        $this->assertSame($admin->id_pengguna, $audit->id_pengguna);

        $this->assertDatabaseHas('log_aktivitas', [
            'id_pengguna' => $admin->id_pengguna,
            'jenis_aktivitas' => 'koreksi_absensi',
            'jenis_data' => 'sesi_absensi',
            'id_data_terkait' => (string) $sesi->id_sesi_absensi,
        ]);
    }

    public function test_rekap_harian_mapel_dan_kelas_konsisten(): void
    {
        $rekap = app(LayananRekapAbsensi::class);
        $andi = Siswa::where('nis', '2425001')->firstOrFail();

        $harian = $rekap->rekapHarian($andi->id_siswa, Carbon::parse('2026-09-01'), Carbon::parse('2026-11-30'));

        $this->assertNotEmpty($harian);

        foreach ($harian as $baris) {
            $this->assertSame(
                (int) $baris->jumlah_mapel_dijadwalkan,
                (int) $baris->jumlah_hadir + (int) $baris->jumlah_izin + (int) $baris->jumlah_sakit
                    + (int) $baris->jumlah_alpha + (int) $baris->jumlah_belum_diisi,
                'Jumlah kategori harus sama dengan jumlah mapel terjadwal.'
            );
        }

        $penuh = $harian->first(fn ($baris) => $baris->tanggal->toDateString() === '2026-11-02');
        $this->assertNotNull($penuh);
        $this->assertSame(4, (int) $penuh->jumlah_mapel_dijadwalkan);
        $this->assertSame(4, (int) $penuh->jumlah_hadir);
        $this->assertSame('hadir_penuh', $penuh->status_harian);

        $alpha = $harian->first(fn ($baris) => $baris->tanggal->toDateString() === '2026-10-22');
        $this->assertNotNull($alpha);
        $this->assertSame(1, (int) $alpha->jumlah_alpha);
        $this->assertSame('parsial', $alpha->status_harian);

        $izin = $harian->first(fn ($baris) => $baris->tanggal->toDateString() === '2026-09-15');
        $this->assertNotNull($izin);
        $this->assertSame(1, (int) $izin->jumlah_izin);
        $this->assertSame('parsial', $izin->status_harian);

        $mapel = $rekap->rekapMapel($andi->id_siswa, Carbon::parse('2026-09-01'), Carbon::parse('2026-09-30'));
        $this->assertNotEmpty($mapel);

        foreach ($mapel as $baris) {
            $this->assertSame(
                $baris['total_pertemuan'],
                $baris['hadir'] + $baris['izin'] + $baris['sakit'] + $baris['alpha'] + $baris['belum_diisi']
            );
            $this->assertSame(
                round(($baris['hadir'] / $baris['total_pertemuan']) * 100, 1),
                $baris['persentase_hadir']
            );
        }

        $idKelas = Kelas::where('nama_kelas', 'X IPA 1')->value('id_kelas');
        $kelas = app(LayananRekapAbsensi::class)->ringkasanKelas(
            $idKelas,
            Carbon::parse('2026-11-01'),
            Carbon::parse('2026-11-19'),
        );

        $this->assertCount(12, $kelas);

        foreach ($kelas as $baris) {
            $this->assertSame(
                $baris['total_pertemuan'],
                $baris['hadir'] + $baris['izin'] + $baris['sakit'] + $baris['alpha'] + $baris['belum_diisi']
            );
        }

        $this->assertSame('izin_penuh', $rekap->kategoriHarian(3, 0, 3, 0, 0, 0));
        $this->assertSame('sakit_penuh', $rekap->kategoriHarian(3, 0, 0, 3, 0, 0));
        $this->assertSame('alpha_penuh', $rekap->kategoriHarian(3, 0, 0, 0, 3, 0));
        $this->assertSame('campuran_non_hadir', $rekap->kategoriHarian(3, 0, 1, 1, 1, 0));
        $this->assertSame('belum_lengkap', $rekap->kategoriHarian(3, 2, 0, 0, 0, 1));
    }

    public function test_pemeliharaan_menutup_sesi_terlewat_dan_menyelesaikan_konfirmasi_kedaluwarsa(): void
    {
        $this->travelTo(Carbon::parse('2026-11-20 12:10:00'));

        $sesiDemo = SesiAbsensi::where('status_sesi', SesiAbsensi::STATUS_MENUNGGU_KONFIRMASI)->firstOrFail();
        $sesiKosong = $this->sesiHariIni('X IPS 1', 3);

        app(LayananSesi::class)->pemeliharaan();

        $this->assertSame(SesiAbsensi::STATUS_DITUTUP, $sesiKosong->fresh()->status_sesi);
        $this->assertSame(SesiAbsensi::STATUS_BELUM_DIKONFIRMASI, $sesiDemo->fresh()->status_sesi);

        $this->assertSame(
            PermintaanKonfirmasi::TIDAK_DIKONFIRMASI,
            PermintaanKonfirmasi::query()
                ->where('id_sesi_absensi', $sesiDemo->id_sesi_absensi)
                ->orderBy('urutan_penerima')
                ->firstOrFail()
                ->status_permintaan
        );
    }
}
