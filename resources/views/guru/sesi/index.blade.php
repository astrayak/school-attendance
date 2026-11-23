@extends('layouts.app')

@section('judul', 'Sesi Absensi')
@section('deskripsi', 'Sesi mengajar pada tanggal terpilih.')

@section('aksi')
    <a href="{{ route('guru.scanner.index') }}" class="btn-secondary btn-sm">
        <i data-lucide="scan-line" class="h-4 w-4"></i> Scanner
    </a>
@endsection

@section('konten')
@if ($piketAktif)
    <div class="mb-5 flex items-start gap-3 rounded-sm border border-slateblue-soft bg-slateblue-soft px-4 py-3 text-sm text-slateblue">
        <i data-lucide="shield-check" class="mt-0.5 h-5 w-5 shrink-0"></i>
        <p>Anda bertugas piket hari ini. seluruh sesi ditampilkan agar dapat dipantau; ambil alih melalui menu Piket bila guru pengampu tidak mengisi.</p>
    </div>
@endif

<div class="card mb-6">
    <div class="card-body">
        <form method="GET" action="{{ route('guru.sesi.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[170px_1fr_180px_auto]">
            <input type="date" name="tanggal" value="{{ $tanggal->toDateString() }}" class="form-input">
            <select name="kelas" class="form-select">
                <option value="">Semua kelas</option>
                @foreach ($kelas as $item)
                    <option value="{{ $item->id_kelas }}" @selected((string) $idKelas === (string) $item->id_kelas)>{{ $item->nama_kelas }}</option>
                @endforeach
            </select>
            <select name="status" class="form-select">
                <option value="">Semua status</option>
                @foreach (\App\Support\Status::SESI as $nilai => $label)
                    <option value="{{ $nilai }}" @selected($status === $nilai)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button type="submit" class="btn-secondary">Filter</button>
                <a href="{{ route('guru.sesi.index') }}" class="btn-ghost">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="card-header">
        <div>
            <p class="text-sm font-semibold text-ink">{{ $tanggal->locale('id')->translatedFormat('l, d F Y') }}</p>
            <p class="mt-0.5 text-xs text-ink-muted">{{ $sesi->total() }} sesi ditemukan</p>
        </div>
        <a href="{{ route('guru.jadwal.index') }}" class="btn-ghost btn-sm">Jadwal Saya</a>
    </div>

    @if ($sesi->isEmpty())
        <x-empty judul="Tidak ada sesi" pesan="Tidak ada sesi pada tanggal dan filter ini. Sesi dibuat otomatis dari jadwal aktif." ikon="calendar-check" />
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-line">
                <thead class="table-head">
                    <tr>
                        <th class="px-5 py-3">Waktu</th>
                        <th class="px-4 py-3">Kelas & mapel</th>
                        <th class="px-4 py-3">Guru</th>
                        <th class="px-4 py-3 text-center">Terisi</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line bg-surface">
                    @foreach ($sesi as $item)
                        <tr class="hover:bg-paper/70">
                            <td class="px-5 py-3 text-sm text-ink-soft">
                                {{ $item->waktu_mulai?->format('H:i') }} – {{ $item->waktu_selesai?->format('H:i') }}
                                <span class="block text-xs text-ink-muted">Absensi {{ $item->waktu_absensi_dibuka?->format('H:i') }}–{{ $item->waktu_absensi_ditutup?->format('H:i') }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-sm font-semibold text-ink">{{ $item->jadwal?->mataPelajaran?->nama_mata_pelajaran ?? '—' }}</p>
                                <p class="text-xs text-ink-muted">{{ $item->jadwal?->kelas?->nama_kelas }}</p>
                            </td>
                            <td class="table-cell">
                                <p>{{ $item->guruPelaksana?->nama_lengkap ?? $item->guruPenanggungJawab?->nama_lengkap ?? '—' }}</p>
                                <p class="text-xs text-ink-muted">{{ \App\Support\Status::label('JENIS_PELAKSANA', $item->jenis_pelaksana) }}</p>
                            </td>
                            <td class="table-cell text-center">{{ $item->catatan_absensi_count }}</td>
                            <td class="px-4 py-3"><x-status :status="$item->status_sesi" kelompok="SESI" /></td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('guru.sesi.show', $item) }}" class="btn-secondary btn-sm">Detail</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="border-t border-line px-5 py-3">{{ $sesi->links() }}</div>
    @endif
</div>
@endsection
