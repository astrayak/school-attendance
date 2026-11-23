<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PengaturanRequest;
use App\Models\PengaturanSistem;
use App\Services\LayananLogAktivitas;
use App\Services\LayananPengaturan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class PengaturanController extends Controller
{
    public function __construct(
        private readonly LayananPengaturan $pengaturan,
        private readonly LayananLogAktivitas $log,
    ) {
    }

    public function index(): View
    {
        $nilai = $this->pengaturan->semua();

        $riwayat = PengaturanSistem::query()
            ->with('pembuat')
            ->orderByDesc('tanggal_berlaku')
            ->orderByDesc('id_pengaturan')
            ->paginate(20);

        $zonaWaktu = collect(timezone_identifiers_list())
            ->filter(fn (string $zona) => str_starts_with($zona, 'Asia/') || $zona === 'UTC')
            ->values();

        return view('admin.pengaturan.index', compact('nilai', 'riwayat', 'zonaWaktu'));
    }

    public function store(PengaturanRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $sebelum = $this->pengaturan->semua();

        $this->pengaturan->simpan($data, $request->user());

        Cache::forget('zona_waktu_aplikasi');

        $this->log->catat(
            $request->user(),
            'ubah_pengaturan',
            'Pengaturan Sistem',
            'Memperbarui pengaturan jendela absensi dan konfirmasi.',
            'pengaturan_sistem',
            null,
            $sebelum,
            $data
        );

        return redirect()
            ->route('admin.pengaturan.index')
            ->with('sukses', 'Pengaturan berhasil disimpan dan berlaku untuk sesi baru pada tanggal hari ini.');
    }
}
