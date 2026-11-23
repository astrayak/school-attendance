<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Models\Pengguna;
use App\Support\Periode;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogAktivitasController extends Controller
{
    public function index(Request $request): View
    {
        $pencarian = trim((string) $request->query('q'));
        $idPengguna = $request->integer('pengguna') ?: null;
        $modul = $request->query('modul');
        $jenis = $request->query('jenis');

        $dari = Periode::tanggal($request->query('dari'), now()->subDays(30));
        $sampai = Periode::tanggal($request->query('sampai'));

        if ($dari->gt($sampai)) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        $log = LogAktivitas::query()
            ->with('pengguna')
            ->when($pencarian !== '', function ($q) use ($pencarian) {
                $q->where(function ($qq) use ($pencarian) {
                    $qq->where('deskripsi_aktivitas', 'like', "%{$pencarian}%")
                        ->orWhereHas('pengguna', fn ($p) => $p->where('nama_pengguna', 'like', "%{$pencarian}%"));
                });
            })
            ->when($idPengguna, fn ($q) => $q->where('id_pengguna', $idPengguna))
            ->when($modul, fn ($q) => $q->where('nama_modul', $modul))
            ->when($jenis, fn ($q) => $q->where('jenis_aktivitas', $jenis))
            ->whereBetween('waktu_aktivitas', [$dari->toDateString().' 00:00:00', $sampai->toDateString().' 23:59:59'])
            ->orderByDesc('waktu_aktivitas')
            ->paginate(30)
            ->withQueryString();

        $pengguna = Pengguna::query()->whereHas('logAktivitas')->orderBy('nama_pengguna')->get();
        $modulList = LogAktivitas::query()->distinct()->orderBy('nama_modul')->pluck('nama_modul');
        $jenisList = LogAktivitas::query()->distinct()->orderBy('jenis_aktivitas')->pluck('jenis_aktivitas');

        return view('admin.log.index', compact(
            'log',
            'pengguna',
            'modulList',
            'jenisList',
            'pencarian',
            'idPengguna',
            'modul',
            'jenis',
            'dari',
            'sampai',
        ));
    }
}
