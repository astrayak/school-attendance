@extends('layouts.app')

@section('judul', 'Riwayat Troubleshooting')
@section('deskripsi', 'Riwayat tindakan pembaruan dan error terbaru untuk memudahkan penelusuran masalah.')

@section('aksi')
    <a href="{{ route('sistem.pembaruan.index') }}" class="btn-secondary btn-sm">
        <i data-lucide="refresh-cw" class="h-4 w-4"></i> Pembaruan Sistem
    </a>
@endsection

@section('konten')
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" action="{{ route('sistem.troubleshooting.index') }}" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <select name="status" class="form-select">
                <option value="">Semua status</option>
                @foreach (['berhasil' => 'Berhasil', 'gagal' => 'Gagal', 'sebagian' => 'Sebagian'] as $nilai => $label)
                    <option value="{{ $nilai }}" @selected($status === $nilai)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="jenis" class="form-select">
                <option value="">Semua tindakan</option>
                @foreach ($opsi as $kunci => $item)
                    <option value="{{ $kunci }}" @selected($jenis === $kunci)>{{ $item['label'] }}</option>
                @endforeach
            </select>
            <div>
                <label class="form-label text-xs">Dari tanggal</label>
                <input type="date" name="dari" value="{{ $dari->toDateString() }}" class="form-input">
            </div>
            <div>
                <label class="form-label text-xs">Sampai tanggal</label>
                <input type="date" name="sampai" value="{{ $sampai->toDateString() }}" class="form-input">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn-secondary">Filter</button>
                <a href="{{ route('sistem.troubleshooting.index') }}" class="btn-ghost">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card mb-6 overflow-hidden">
    <div class="card-header">
        <div>
            <p class="text-sm font-semibold text-ink">Riwayat pembaruan</p>
            <p class="mt-0.5 text-xs text-ink-muted">{{ $riwayat->total() }} tindakan ditemukan · {{ $dari->format('d/m/Y') }} – {{ $sampai->format('d/m/Y') }}</p>
        </div>
    </div>

    @if ($riwayat->isEmpty())
        <x-empty judul="Tidak ada riwayat" pesan="Ubah filter atau jalankan pembaruan terlebih dahulu." ikon="history" />
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-line">
                <thead class="table-head">
                    <tr>
                        <th class="px-5 py-3">Waktu</th>
                        <th class="px-4 py-3">Tindakan</th>
                        <th class="px-4 py-3">Pengguna</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Durasi</th>
                        <th class="px-5 py-3">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line bg-surface">
                    @foreach ($riwayat as $item)
                        <tr class="align-top hover:bg-paper/70">
                            <td class="whitespace-nowrap px-5 py-3 text-sm text-ink-soft">
                                {{ $item->dibuat_pada?->format('d/m/Y') }}
                                <span class="block text-xs text-ink-muted">{{ $item->dibuat_pada?->format('H:i:s') }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-sm font-medium text-ink">{{ $item->label_tindakan }}</p>
                                <p class="text-xs text-ink-muted">{{ $item->jenis_tindakan }}</p>
                            </td>
                            <td class="px-4 py-3 text-sm text-ink-soft">{{ $item->pengguna?->nama_pengguna ?? 'Sistem' }}</td>
                            <td class="px-4 py-3">
                                @php($warnaStatus = $item->status === 'berhasil' ? 'bg-moss-soft text-moss ring-moss/20' : 'bg-clay-soft text-clay ring-clay/20')
                                <span class="inline-flex rounded-sm px-2 py-1 text-xs font-semibold ring-1 ring-inset {{ $warnaStatus }}">{{ ucfirst($item->status) }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-ink-soft">{{ $item->durasi_ms !== null ? $item->durasi_ms.' ms' : '—' }}</td>
                            <td class="px-5 py-3">
                                <p class="text-sm text-ink-soft">{{ $item->keterangan ?: '—' }}</p>
                                @if ($item->keluaran)
                                    <details class="mt-1">
                                        <summary class="cursor-pointer text-xs font-medium text-moss">Lihat output</summary>
                                        <pre class="mt-2 max-h-56 overflow-auto rounded-sm bg-ink p-2 text-[10px] leading-relaxed text-line">{{ $item->keluaran }}</pre>
                                    </details>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="border-t border-line px-5 py-3">{{ $riwayat->links() }}</div>
    @endif
</div>

<div class="card overflow-hidden">
    <div class="card-header">
        <div>
            <p class="text-sm font-semibold text-ink">Log error terbaru</p>
            <p class="mt-0.5 text-xs text-ink-muted">Ringkasan error terakhir dari storage/logs/laravel.log.</p>
        </div>
        @if ($pengguna->isAdmin() && $logError)
            <span class="inline-flex rounded-sm bg-clay-soft px-2.5 py-1 font-mono text-[10px] uppercase tracking-[0.06em] text-clay ring-1 ring-inset ring-clay/20">
                {{ count($logError) }} error terakhir
            </span>
        @endif
    </div>

    @if (! $pengguna->isAdmin())
        <div class="px-5 py-4 text-sm text-ink-muted">
            Log error hanya ditampilkan untuk administrator. Riwayat pembaruan di atas tetap dapat dilihat.
        </div>
    @elseif (empty($logError))
        <x-empty judul="Tidak ada error" pesan="Tidak ditemukan entri error pada log terakhir." ikon="shield-check" />
    @else
        <div class="divide-y divide-line">
            @foreach ($logError as $error)
                <details class="group px-5 py-3">
                    <summary class="cursor-pointer">
                        <span class="font-mono text-xs text-ink-muted">{{ $error['waktu'] }}</span>
                        <span class="mt-1 block break-words text-sm text-clay">{{ \Illuminate\Support\Str::limit($error['pesan'], 220) }}</span>
                    </summary>
                    @if ($error['detail'])
                        <pre class="mt-2 max-h-72 overflow-auto rounded-sm bg-ink p-3 text-[10px] leading-relaxed text-line">{{ implode("\n", array_slice($error['detail'], 0, 8)) }}</pre>
                    @endif
                </details>
            @endforeach
        </div>
    @endif
</div>
@endsection