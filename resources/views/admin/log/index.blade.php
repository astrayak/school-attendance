@extends('layouts.app')

@section('judul', 'Log Aktivitas')
@section('deskripsi', 'Jejak audit seluruh perubahan data dan tindakan pengguna.')

@section('konten')
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.log.index') }}" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
            <div class="relative xl:col-span-2">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-ink-muted">
                    <i data-lucide="search" class="h-4 w-4"></i>
                </span>
                <input type="search" name="q" value="{{ $pencarian }}" class="form-input pl-9" placeholder="Cari deskripsi atau nama pengguna...">
            </div>
            <select name="pengguna" class="form-select">
                <option value="">Semua pengguna</option>
                @foreach ($pengguna as $item)
                    <option value="{{ $item->id_pengguna }}" @selected((string) $idPengguna === (string) $item->id_pengguna)>{{ $item->nama_pengguna }}</option>
                @endforeach
            </select>
            <select name="modul" class="form-select">
                <option value="">Semua modul</option>
                @foreach ($modulList as $item)
                    <option value="{{ $item }}" @selected($modul === $item)>{{ $item }}</option>
                @endforeach
            </select>
            <select name="jenis" class="form-select">
                <option value="">Semua jenis</option>
                @foreach ($jenisList as $item)
                    <option value="{{ $item }}" @selected($jenis === $item)>{{ $item }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button type="submit" class="btn-secondary">Filter</button>
                <a href="{{ route('admin.log.index') }}" class="btn-ghost">Reset</a>
            </div>
            <div class="sm:col-span-2 xl:col-span-3">
                <label class="form-label text-xs">Dari tanggal</label>
                <input type="date" name="dari" value="{{ $dari->toDateString() }}" class="form-input">
            </div>
            <div class="sm:col-span-2 xl:col-span-3">
                <label class="form-label text-xs">Sampai tanggal</label>
                <input type="date" name="sampai" value="{{ $sampai->toDateString() }}" class="form-input">
            </div>
        </form>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="card-header">
        <div>
            <p class="text-sm font-semibold text-ink">Riwayat aktivitas</p>
            <p class="mt-0.5 text-xs text-ink-muted">{{ $log->total() }} aktivitas ditemukan · {{ $dari->format('d/m/Y') }} – {{ $sampai->format('d/m/Y') }}</p>
        </div>
    </div>

    @if ($log->isEmpty())
        <x-empty judul="Tidak ada aktivitas" pesan="Ubah filter atau perluas rentang tanggal." ikon="history" />
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-line">
                <thead class="table-head">
                    <tr>
                        <th class="px-5 py-3">Waktu</th>
                        <th class="px-4 py-3">Pengguna</th>
                        <th class="px-4 py-3">Modul</th>
                        <th class="px-4 py-3">Aktivitas</th>
                        <th class="px-4 py-3">IP</th>
                        <th class="px-5 py-3">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($log as $item)
                        <tr class="align-top hover:bg-paper/70">
                            <td class="whitespace-nowrap px-5 py-3 text-sm text-ink-soft">
                                {{ $item->waktu_aktivitas?->format('d/m/Y') }}
                                <span class="block text-xs text-ink-muted">{{ $item->waktu_aktivitas?->format('H:i:s') }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-sm font-medium text-ink">{{ $item->pengguna?->nama_pengguna ?? 'Sistem' }}</p>
                                <p class="text-xs text-ink-muted">{{ \App\Support\Status::label('PERAN', $item->pengguna?->peran) }}</p>
                            </td>
                            <td class="table-cell">
                                <span class="inline-flex rounded-sm bg-paper-deep px-2 py-1 text-xs font-medium text-ink-soft">{{ $item->nama_modul }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-sm text-ink-soft">{{ $item->deskripsi_aktivitas }}</p>
                                <p class="text-xs text-ink-muted">{{ $item->jenis_aktivitas }}{{ $item->jenis_data ? ' · '.$item->jenis_data : '' }}{{ $item->id_data_terkait ? ' #'.$item->id_data_terkait : '' }}</p>
                            </td>
                            <td class="table-cell text-xs">{{ $item->alamat_ip ?: '—' }}</td>
                            <td class="px-5 py-3">
                                @if ($item->data_sebelumnya || $item->data_sesudahnya)
                                    <details>
                                        <summary class="cursor-pointer text-xs font-medium text-moss">Lihat data</summary>
                                        <div class="mt-2 grid gap-2 lg:grid-cols-2">
                                            <div>
                                                <p class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-ink-muted">Sebelum</p>
                                                <pre class="max-h-48 overflow-auto rounded-sm bg-ink p-2 text-[10px] leading-relaxed text-line">{{ json_encode($item->data_sebelumnya, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '—' }}</pre>
                                            </div>
                                            <div>
                                                <p class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-ink-muted">Sesudah</p>
                                                <pre class="max-h-48 overflow-auto rounded-sm bg-ink p-2 text-[10px] leading-relaxed text-line">{{ json_encode($item->data_sesudahnya, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '—' }}</pre>
                                            </div>
                                        </div>
                                    </details>
                                @else
                                    <span class="text-xs text-ink-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="border-t border-line px-5 py-3">{{ $log->links() }}</div>
    @endif
</div>
@endsection
