<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Siswa\Concerns\ProfilSiswa;
use App\Models\PermintaanKonfirmasi;
use App\Services\LayananKonfirmasi;
use App\Support\Status;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KonfirmasiController extends Controller
{
    use ProfilSiswa;

    public function __construct(private readonly LayananKonfirmasi $konfirmasi)
    {
    }

    public function index(Request $request): View
    {
        $siswa = $this->profilSiswa($request);

        $daftar = $this->konfirmasi->daftarUntukSiswa($siswa);
        $jumlahMenunggu = $daftar->where('status_permintaan', PermintaanKonfirmasi::MENUNGGU)->count();

        return view('siswa.konfirmasi.index', compact('siswa', 'daftar', 'jumlahMenunggu'));
    }

    public function jawab(Request $request, PermintaanKonfirmasi $permintaan): RedirectResponse
    {
        $this->authorize('jawab', $permintaan);

        $data = $request->validate([
            'jawaban' => ['required', Rule::in([
                PermintaanKonfirmasi::BERLANGSUNG,
                PermintaanKonfirmasi::TIDAK_BERLANGSUNG,
            ])],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [], [
            'jawaban' => 'jawaban konfirmasi',
            'keterangan' => 'keterangan',
        ]);

        $this->konfirmasi->jawab(
            $permintaan,
            $data['jawaban'],
            $request->user(),
            $data['keterangan'] ?? null,
        );

        $pesan = $data['jawaban'] === PermintaanKonfirmasi::BERLANGSUNG
            ? 'Terima kasih. Pembelajaran telah dikonfirmasi berlangsung.'
            : 'Laporan Anda telah dikirim. Admin akan memeriksa sesi pembelajaran tersebut.';

        return redirect()
            ->route('siswa.konfirmasi.index')
            ->with('sukses', $pesan);
    }
}
