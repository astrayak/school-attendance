@extends('layouts.app')

@section('judul', 'Sesi Absensi')
@section('deskripsi', 'Pantau sesi yang dihasilkan dari jadwal aktif setiap hari.')

@section('aksi')
    <a href="{{ route('admin.jadwal.index') }}" class="btn-secondary btn-sm">
        <i data-lucide="calendar-days" class="h-4 w-4"></i> Jadwal
    </a>
@endsection

@section('konten')
<div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat-card label="Total Sesi" :nilai="$statistik['total']" ikon="list-checks" warna="slate" />
    <x-stat-card label="Belum Diisi" :nilai="$statistik['belum']" ikon="clock" warna="amber" keterangan="Termasuk sesi yang masih berjalan" />
    <x-stat-card label="Terkonfirmasi" :nilai="$statistik['terkonfirmasi']" ikon="badge-check" warna="emerald" />
    <x-stat-card label="Perlu Perhatian" :nilai="$statistik['perlu_pemeriksaan'] + $statistik['belum_dikonfirmasi']" ikon="alert-triangle" warna="rose" keterangan="Perlu pemeriksaan / belum dikonfirmasi" />
</div>

<div class="card mb-6">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.sesi.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[160px_1fr_1fr_170px_auto]">
            <input type="date" name="tanggal" value="{{ $tanggal->toDateString() }}" class="form-input">
            <select name="kelas" class="form-select">
                <option value="">Semua kelas</option>
                @foreach ($kelas as $item)
                    <option value="{{ $item->id_kelas }}" @selected((string) $idKelas === (string) $item->id_kelas)>{{ $item->nama_kelas }}</option>
                @endforeach
            </select>
            <select name="guru" class="form-select">
                <option value="">Semua guru</option>
                @foreach ($guru as $item)
                    <option value="{{ $item->id_guru }}" @selected((string) $idGuru === (string) $item->id_guru)>{{ $item->nama_lengkap }}</option>
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
                <a href="{{ route('admin.sesi.index') }}" class="btn-ghost">Reset</a>
            </div>
            <div class="relative sm:col-span-2 lg:col-span-5">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-ink-muted">
                    <i data-lucide="search" class="h-4 w-4"></i>
                </span>
                <input type="search" name="q" value="{{ $pencarian }}" class="form-input pl-9" placeholder="Cari mata pelajaran, kelas, atau guru...">
            </div>
        </form>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="card-header">
        <div>
            <p class="text-sm font-semibold text-ink">Sesi tanggal {{ $tanggal->locale('id')->translatedFormat('d F Y') }}</p>
            <p class="mt-0.5 text-xs text-ink-muted">{{ $sesi->total() }} sesi ditemukan</p>
        </div>
    </div>

    @if ($sesi->isEmpty())
        <x-empty judul="Tidak ada sesi" pesan="Sesi dibuat otomatis dari jadwal aktif. Pastikan jadwal sudah diaktifkan dan tanggal sesuai hari jadwal." ikon="list-checks" />
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-line">
                <thead class="table-head">
                    <tr>
                        <th class="px-5 py-3">Waktu</th>
                        <th class="px-4 py-3">Kelas & mapel</th>
                        <th class="px-4 py-3">Guru</th>
                        <th class="px-4 py-3">Pelaksana</th>
                        <th class="px-4 py-3 text-center">Terisi</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line bg-surface">
                    @foreach ($sesi as $item)
                        <tr class="hover:bg-paper/70">
                            <td class="px-5 py-3 text-sm text-ink-soft">
                                <p class="font-medium text-ink">{{ $item->waktu_mulai?->format('H:i') }} – {{ $item->waktu_selesai?->format('H:i') }}</p>
                                <p class="text-xs text-ink-muted">Buka {{ $item->waktu_absensi_dibuka?->format('H:i') }} · Tutup {{ $item->waktu_absensi_ditutup?->format('H:i') }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-sm font-semibold text-ink">{{ $item->jadwal?->mataPelajaran?->nama_mata_pelajaran ?? '—' }}</p>
                                <p class="text-xs text-ink-muted">{{ $item->jadwal?->kelas?->nama_kelas }}</p>
                            </td>
                            <td class="table-cell">
                                <p>{{ $item->guruPenanggungJawab?->nama_lengkap ?? '—' }}</p>
                                <p class="text-xs text-ink-muted">{{ \App\Support\Status::label('JENIS_PELAKSANA', $item->jenis_pelaksana) }}</p>
                            </td>
                            <td class="table-cell">{{ $item->guruPelaksana?->nama_lengkap ?? '—' }}</td>
                            <td class="table-cell text-center">
                                <span class="inline-flex items-center gap-1 rounded-sm bg-paper-deep px-2 py-1 text-xs font-medium text-ink-soft">
                                    <i data-lucide="users" class="h-3 w-3"></i> {{ $item->catatan_absensi_count }}
                                </span>
                            </td>
                            <td class="px-4 py-3"><x-status :status="$item->status_sesi" kelompok="SESI" /></td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('admin.sesi.show', $item) }}" class="btn-secondary btn-sm">
                                    <i data-lucide="eye" class="h-3.5 w-3.5"></i> Detail
                                </a>
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
