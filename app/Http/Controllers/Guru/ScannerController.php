<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guru\Concerns\ProfilGuru;
use App\Models\CatatanAbsensi;
use App\Models\SesiAbsensi;
use App\Services\LayananAbsensi;
use App\Services\LayananSesi;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScannerController extends Controller
{
    use ProfilGuru;

    public function __construct(
        private readonly LayananAbsensi $absensi,
        private readonly LayananSesi $layananSesi,
    ) {
    }

    public function index(Request $request): View
    {
        $guru = $this->profilGuru($request);

        $sesi = $this->layananSesi->sesiHariIni()
            ->filter(fn (SesiAbsensi $item) => $item->id_guru_penanggung_jawab === $guru->id_guru
                || $item->id_guru_pelaksana === $guru->id_guru
                || $this->absensi->bolehMengisi($item, $request->user()))
            ->values();

        $terbuka = $sesi->filter(fn (SesiAbsensi $item) => $this->absensi->jendelaTerbuka($item))->values();

        return view('guru.scanner.index', compact('sesi', 'terbuka'));
    }

    public function show(Request $request, SesiAbsensi $sesi): View|RedirectResponse
    {
        $this->authorize('view', $sesi);

        if (! $this->absensi->bolehMengisi($sesi, $request->user())) {
            return redirect()
                ->route('guru.sesi.show', $sesi)
                ->with('error', 'Scanner hanya dapat digunakan saat jendela absensi terbuka dan Anda berhak mengisi sesi ini.');
        }

        $sesi->load([
            'jadwal.kelas',
            'jadwal.mataPelajaran',
            'guruPenanggungJawab',
            'guruPelaksana',
        ]);

        $siswa = $this->absensi->siswaPadaSesi($sesi);

        $catatan = CatatanAbsensi::query()
            ->with('siswa')
            ->where('id_sesi_absensi', $sesi->id_sesi_absensi)
            ->orderByDesc('waktu_kehadiran')
            ->get();

        $sudah = $catatan->keyBy('id_siswa');
        $belum = $siswa->reject(fn ($murid) => $sudah->has($murid->id_siswa))->values();

        $hadir = $catatan->where('status_kehadiran', CatatanAbsensi::HADIR)->count();

        return view('guru.scanner.show', compact('sesi', 'siswa', 'catatan', 'sudah', 'belum', 'hadir'));
    }

    public function pindai(Request $request, SesiAbsensi $sesi): JsonResponse
    {
        $this->authorize('view', $sesi);

        $data = $request->validate([
            'token' => ['required', 'string', 'min:10', 'max:160'],
        ], [], ['token' => 'isi QR Code']);

        $hasil = $this->absensi->catatQr($sesi, $data['token'], $request->user());

        $siswa = $this->absensi->siswaPadaSesi($sesi);
        $catatan = CatatanAbsensi::query()
            ->where('id_sesi_absensi', $sesi->id_sesi_absensi)
            ->get();

        return response()->json([
            'status' => $hasil['status'],
            'pesan' => $hasil['pesan'],
            'siswa' => $hasil['siswa'] ? [
                'nama_lengkap' => $hasil['siswa']->nama_lengkap,
                'nis' => $hasil['siswa']->nis,
            ] : null,
            'waktu' => $hasil['waktu']?->copy()->timezone(config('app.timezone'))->format('H:i:s'),
            'ringkasan' => [
                'total' => $siswa->count(),
                'terisi' => $catatan->count(),
                'hadir' => $catatan->where('status_kehadiran', CatatanAbsensi::HADIR)->count(),
            ],
        ]);
    }

    public function finalisasi(Request $request, SesiAbsensi $sesi): RedirectResponse
    {
        $this->authorize('view', $sesi);

        $this->absensi->finalisasiQr($sesi, $request->user());

        return redirect()
            ->route('guru.sesi.show', $sesi)
            ->with('sukses', 'Absensi QR berhasil difinalisasi dan dikirim untuk konfirmasi siswa.');
    }
}
