<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotifikasiController extends Controller
{
    public function index(Request $request): View
    {
        $notifikasi = $request->user()
            ->notifikasi()
            ->orderByDesc('dibuat_pada')
            ->paginate(20);

        return view('notifikasi.index', compact('notifikasi'));
    }

    public function baca(Request $request, Notifikasi $notifikasi): RedirectResponse
    {
        abort_unless($notifikasi->id_pengguna === $request->user()->id_pengguna, 403);

        if (! $notifikasi->sudah_dibaca) {
            $notifikasi->update([
                'sudah_dibaca' => true,
                'dibaca_pada' => now(),
            ]);
        }

        if ($notifikasi->url) {
            return redirect()->to($notifikasi->url);
        }

        return redirect()->route('notifikasi.index');
    }

    public function bacaSemua(Request $request): RedirectResponse
    {
        $request->user()->notifikasi()
            ->where('sudah_dibaca', false)
            ->update([
                'sudah_dibaca' => true,
                'dibaca_pada' => now(),
            ]);

        return redirect()->route('notifikasi.index')->with('sukses', 'Semua notifikasi telah ditandai dibaca.');
    }
}
