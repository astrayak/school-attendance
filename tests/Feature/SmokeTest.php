<?php

namespace Tests\Feature;

use App\Models\CatatanAbsensi;
use App\Models\Pengguna;
use App\Models\SesiAbsensi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SmokeTest extends TestCase
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

    public function test_login_role_dan_seluruh_halaman_utama_dapat_dirender(): void
    {
        $admin = Pengguna::where('email', 'admin@sekolah.test')->firstOrFail();
        $guru = Pengguna::where('email', 'guru@sekolah.test')->firstOrFail();
        $siswa = Pengguna::where('email', 'siswa@sekolah.test')->firstOrFail();

        $this->post(route('login.proses'), [
            'email' => $admin->email,
            'kata_sandi' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($admin);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();

        $this->actingAs($admin);

        foreach ([
            'admin.dashboard',
            'admin.siswa.index',
            'admin.guru.index',
            'admin.kelas.index',
            'admin.mata-pelajaran.index',
            'admin.tahun-ajaran.index',
            'admin.semester.index',
            'admin.penugasan.index',
            'admin.jadwal.index',
            'admin.piket.index',
            'admin.petugas-konfirmasi.index',
            'admin.sesi.index',
            'admin.rekap-siswa.index',
            'admin.laporan.index',
            'admin.log.index',
            'admin.pengaturan.index',
        ] as $namaRute) {
            $this->get(route($namaRute))
                ->assertOk();
        }

        $sesi = SesiAbsensi::where('status_sesi', SesiAbsensi::STATUS_MENUNGGU_KONFIRMASI)
            ->orderByDesc('id_sesi_absensi')
            ->firstOrFail();

        $this->get(route('admin.sesi.show', $sesi))->assertOk();

        $this->actingAs($guru);

        $this->get(route('guru.dashboard'))->assertOk();
        $this->get(route('guru.profil'))->assertOk();
        $this->get(route('guru.jadwal.index'))->assertOk();
        $this->get(route('guru.sesi.index'))->assertOk();
        $this->get(route('guru.sesi.show', $sesi))->assertOk();
        $this->get(route('guru.absensi.show', $sesi))->assertOk();
        $this->get(route('guru.scanner.index'))->assertOk();
        $this->get(route('guru.piket.index'))->assertOk();

        $this->actingAs($siswa);

        $this->get(route('siswa.dashboard'))->assertOk();
        $this->get(route('siswa.profil'))->assertOk();
        $this->get(route('siswa.qr'))->assertOk();
        $this->get(route('siswa.riwayat.index'))->assertOk();
        $this->get(route('siswa.rekap.mapel'))->assertOk();
        $this->get(route('siswa.rekap.harian'))->assertOk();
        $this->get(route('siswa.konfirmasi.index'))->assertOk();

        $catatan = CatatanAbsensi::where('id_siswa', $siswa->siswa->id_siswa)->firstOrFail();

        $this->get(route('siswa.riwayat.show', $catatan))->assertOk();
    }

    public function test_peran_tidak_dapat_membuka_halaman_peran_lain(): void
    {
        $guru = Pengguna::where('email', 'guru@sekolah.test')->firstOrFail();
        $siswa = Pengguna::where('email', 'siswa@sekolah.test')->firstOrFail();

        $this->actingAs($guru)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($guru)->get(route('siswa.dashboard'))->assertForbidden();
        $this->actingAs($siswa)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($siswa)->get(route('guru.dashboard'))->assertForbidden();
    }
}
