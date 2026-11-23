<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use App\Models\Siswa;
use App\Services\LayananRekapAbsensi;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FotoProfilDanRekapSiswaTest extends TestCase
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

    public function test_siswa_dapat_mengganti_foto_profil_dan_file_tidak_valid_ditolak(): void
    {
        Storage::fake('public');

        $siswa = Pengguna::where('email', 'siswa@sekolah.test')->firstOrFail();

        $this->actingAs($siswa)
            ->post(route('siswa.profil.foto'), [
                'foto' => UploadedFile::fake()->image('foto-siswa.jpg', 240, 240),
            ])
            ->assertRedirect();

        $siswa->refresh();
        $this->assertNotNull($siswa->foto_profil);
        Storage::disk('public')->assertExists($siswa->foto_profil);

        $this->get(route('siswa.profil'))
            ->assertOk()
            ->assertSee($siswa->foto_profil_url, false);

        $lama = $siswa->foto_profil;

        $this->actingAs($siswa)
            ->post(route('siswa.profil.foto'), [
                'foto' => UploadedFile::fake()->create('bukan-foto.txt', 10, 'text/plain'),
            ])
            ->assertSessionHasErrors('foto');

        $this->assertSame($lama, $siswa->fresh()->foto_profil);
    }

    public function test_guru_dapat_mengganti_foto_profil(): void
    {
        Storage::fake('public');

        $guru = Pengguna::where('email', 'guru@sekolah.test')->firstOrFail();

        $this->actingAs($guru)
            ->post(route('guru.profil.foto'), [
                'foto' => UploadedFile::fake()->image('foto-guru.png', 240, 240),
            ])
            ->assertRedirect();

        $guru->refresh();
        $this->assertNotNull($guru->foto_profil);
        Storage::disk('public')->assertExists($guru->foto_profil);

        $this->get(route('guru.profil'))
            ->assertOk()
            ->assertSee($guru->foto_profil_url, false);
    }

    public function test_admin_dapat_melihat_rekap_seluruh_siswa(): void
    {
        $admin = Pengguna::where('email', 'admin@sekolah.test')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.rekap-siswa.index'))
            ->assertOk()
            ->assertSee('Rekap seluruh siswa')
            ->assertSee('Andi Pratama');

        $this->actingAs($admin)
            ->get(route('admin.rekap-siswa.index', ['periode' => 'bulan']))
            ->assertOk()
            ->assertSee('Bulan ini');
    }

    public function test_rekap_banyak_siswa_menghitung_hasil_dari_sesi_dan_catatan(): void
    {
        $siswa = Siswa::where('nis', '2425001')->firstOrFail();
        $layanan = app(LayananRekapAbsensi::class);

        $rekap = $layanan->rekapBanyakSiswa(
            [$siswa->id_siswa],
            Carbon::parse('2026-09-01')->startOfDay(),
            now()->startOfDay(),
        );

        $this->assertTrue($rekap->has($siswa->id_siswa));

        $baris = $rekap->get($siswa->id_siswa);
        $this->assertGreaterThan(0, $baris['total_pertemuan']);
        $this->assertLessThanOrEqual($baris['total_pertemuan'], $baris['hadir']);
    }
}