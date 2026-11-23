@extends('layouts.app')

@section('judul', 'Riwayat Absensi')
@section('deskripsi')
    Catatan kehadiran Anda pada periode terpilih. Secara default, semester aktif ditampilkan.
@endsection

@section('aksi')
    <a href="{{ route('siswa.rekap.harian') }}" class="btn-secondary btn-sm"><i data-lucide="calendar-check" class="h-4 w-4"></i> Rekap Harian</a>
@endsection

@section('konten')
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" action="{{ route('siswa.riwayat.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[210px_160px_160px_1fr_auto]">
            <div>
                <label for="semester" class="form-label">Periode</label>
                <select id="semester" name="semester" class="form-select">
                    <option value="0" @selected(! $semester)>Semua periode (tanggal)</option>
                    @foreach ($semesterList as $item)
                        <option value="{{ $item->id_semester }}" @selected((string) $idSemester === (string) $item->id_semester)>
                            {{ ucfirst($item->nama_semester) }} · {{ $item->tahunAjaran?->nama_tahun_ajaran }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="dari" class="form-label">Dari tanggal</label>
                <input type="date" id="dari" name="dari" value="{{ $dari->toDateString() }}" class="form-input">
            </div>
            <div>
                <label for="sampai" class="form-label">Sampai tanggal</label>
                <input type="date" id="sampai" name="sampai" value="{{ $sampai->toDateString() }}" class="form-input">
            </div>
            <div>
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-select">
                    <option value="">Semua status</option>
                    @foreach (\App\Support\Status::KEHADIRAN as $nilai => $label)
                        <option value="{{ $nilai }}" @selected($status === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn-secondary">Terapkan</button>
                <a href="{{ route('siswa.riwayat.index') }}" class="btn-ghost">Reset</a>
            </div>
        </form>
        <p class="mt-3 text-xs text-ink-muted">
            @if ($semester)
                Periode aktif: <strong class="text-ink-soft">{{ ucfirst($semester->nama_semester) }} {{ $semester->tahunAjaran?->nama_tahun_ajaran }}</strong>
                ({{ $semester->tanggal_mulai?->format('d/m/Y') }} – {{ $semester->tanggal_selesai?->format('d/m/Y') }}).
                Tanggal diabaikan saat memilih periode tertentu.
            @else
                Rentang tanggal bebas. Pilih semester untuk membatasi catatan pada satu periode saja.
            @endif
        </p>
    </div>
</div>

<div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
    <x-stat-card label="Hadir" :nilai="$ringkasan['hadir']" ikon="check-circle-2" warna="emerald" />
    <x-stat-card label="Izin" :nilai="$ringkasan['izin']" ikon="file-text" warna="amber" />
    <x-stat-card label="Sakit" :nilai="$ringkasan['sakit']" ikon="thermometer" warna="sky" />
    <x-stat-card label="Alpha" :nilai="$ringkasan['alpha']" ikon="x-circle" warna="rose" />
    <x-stat-card label="Total Catatan" :nilai="$ringkasan['total']" ikon="list-checks" warna="slate" />
</div>

<div class="card overflow-hidden">
    <div class="card-header">
        <div>
            <p class="text-sm font-semibold text-ink">{{ $catatan->total() }} catatan ditemukan</p>
            <p class="mt-0.5 text-xs text-ink-muted">{{ $dari->locale('id')->translatedFormat('d F Y') }} – {{ $sampai->locale('id')->translatedFormat('d F Y') }}</p>
        </div>
        <a href="{{ route('siswa.rekap.mapel') }}" class="btn-ghost btn-sm">Rekap Mapel</a>
    </div>

    @if ($catatan->isEmpty())
        <x-empty judul="Tidak ada catatan" pesan="Tidak ada absensi pada rentang tanggal dan status ini." ikon="history" />
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-line">
                <thead class="table-head">
                    <tr>
                        <th class="px-5 py-3">Tanggal</th>
                        <th class="px-4 py-3">Mata pelajaran</th>
                        <th class="px-4 py-3">Kelas</th>
                        <th class="px-4 py-3">Guru</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Metode</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line bg-surface">
                    @foreach ($catatan as $baris)
                        <tr class="hover:bg-paper/70">
                            <td class="px-5 py-3">
                                <p class="text-sm font-medium text-ink">{{ $baris->sesiAbsensi?->tanggal_pelajaran?->format('d/m/Y') ?? '—' }}</p>
                                <p class="text-xs text-ink-muted">{{ $baris->sesiAbsensi?->waktu_mulai?->format('H:i') }}–{{ $baris->sesiAbsensi?->waktu_selesai?->format('H:i') }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-sm font-medium text-ink">{{ $baris->sesiAbsensi?->jadwal?->mataPelajaran?->nama_mata_pelajaran ?? '—' }}</p>
                                @if ($baris->keterangan)
                                    <p class="mt-0.5 max-w-xs truncate text-xs text-ink-muted" title="{{ $baris->keterangan }}">{{ $baris->keterangan }}</p>
                                @endif
                            </td>
                            <td class="table-cell">{{ $baris->sesiAbsensi?->jadwal?->kelas?->nama_kelas ?? '—' }}</td>
                            <td class="table-cell">{{ $baris->sesiAbsensi?->guruPelaksana?->nama_lengkap ?? $baris->sesiAbsensi?->guruPenanggungJawab?->nama_lengkap ?? '—' }}</td>
                            <td class="px-4 py-3"><x-status :status="$baris->status_kehadiran" kelompok="KEHADIRAN" /></td>
                            <td class="table-cell">{{ \App\Support\Status::label('METODE', $baris->metode_absensi) }}</td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('siswa.riwayat.show', $baris) }}" class="btn-secondary btn-sm">Detail</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="border-t border-line px-5 py-3">{{ $catatan->links() }}</div>
    @endif
</div>
@endsection
