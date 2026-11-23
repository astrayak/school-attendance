<?php

namespace App\Http\Controllers\Sistem;

use App\Http\Controllers\Controller;
use App\Models\RiwayatPembaruan;
use App\Services\LayananLogAktivitas;
use App\Services\LayananPembaruan;
use App\Support\Periode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PembaruanController extends Controller
{
    public function __construct(
        private readonly LayananPembaruan $pembaruan,
        private readonly LayananLogAktivitas $log,
    ) {
    }

    public function index(Request $request): View
    {
        $pengguna = $request->user();

        return view('sistem.pembaruan.index', [
            'pengguna' => $pengguna,
            'pemeriksaan' => $this->pembaruan->pemeriksaan(),
            'opsi' => $this->pembaruan->opsi(),
            'riwayat' => RiwayatPembaruan::query()
                ->with('pengguna')
                ->orderByDesc('dibuat_pada')
                ->limit(8)
                ->get(),
            'bisaMenjalankan' => $pengguna->isAdmin(),
        ]);
    }

    public function jalankan(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'Hanya administrator yang dapat menjalankan pembaruan sistem.');

        $data = $request->validate([
            'tindakan' => ['required', 'array', 'min:1'],
            'tindakan.*' => ['string', Rule::in(array_keys($this->pembaruan->opsi()))],
        ]);

        $hasil = $this->pembaruan->jalankan($data['tindakan'], $request->user());
        $gagal = collect($hasil)->where('status', '!=', 'berhasil')->count();

        $this->log->catat(
            $request->user(),
            'pembaruan_sistem',
            'Sistem',
            'Menjalankan pembaruan sistem: '.collect($hasil)->pluck('label')->implode(', '),
            'riwayat_pembaruan',
            null,
        );

        if ($gagal === 0) {
            return redirect()
                ->route('sistem.pembaruan.index')
                ->with('sukses', 'Pembaruan berhasil dijalankan.');
        }

        return redirect()
            ->route('sistem.pembaruan.index')
            ->with('error', "Pembaruan selesai dengan {$gagal} tindakan gagal. Periksa riwayat troubleshooting.");
    }

    public function riwayat(Request $request): View
    {
        $pengguna = $request->user();

        $status = $request->query('status');
        $status = in_array($status, ['berhasil', 'gagal', 'sebagian'], true) ? $status : null;
        $jenis = $request->query('jenis');
        $jenis = array_key_exists((string) $jenis, $this->pembaruan->opsi()) ? $jenis : null;

        $dari = Periode::tanggal($request->query('dari'), now()->subDays(30));
        $sampai = Periode::tanggal($request->query('sampai'));

        if ($dari->gt($sampai)) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        $riwayat = RiwayatPembaruan::query()
            ->with('pengguna')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($jenis, fn ($q) => $q->where('jenis_tindakan', $jenis))
            ->whereBetween('dibuat_pada', [
                $dari->toDateString().' 00:00:00',
                $sampai->toDateString().' 23:59:59',
            ])
            ->orderByDesc('dibuat_pada')
            ->paginate(20)
            ->withQueryString();

        return view('sistem.troubleshooting.index', [
            'pengguna' => $pengguna,
            'riwayat' => $riwayat,
            'opsi' => $this->pembaruan->opsi(),
            'status' => $status,
            'jenis' => $jenis,
            'dari' => $dari,
            'sampai' => $sampai,
            'logError' => $pengguna->isAdmin() ? $this->pembaruan->logErrorTerbaru(20) : [],
            'bisaMenjalankan' => $pengguna->isAdmin(),
        ]);
    }
}