@extends('layouts.app')

@section('judul', 'Laporan Absensi')
@section('deskripsi', 'Rekap kehadiran per kelas, mata pelajaran, dan periode.')

@section('aksi')
    @if ($kelasTerpilih)
        <a href="{{ route('admin.laporan.ekspor', request()->only(['kelas', 'mapel', 'dari', 'sampai'])) }}" class="btn-primary btn-sm">
            <i data-lucide="download" class="h-4 w-4"></i> Ekspor CSV
        </a>
    @endif
@endsection

@section('konten')
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.laporan.index') }}" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-[1fr_1fr_160px_160px_auto]">
            <div>
                <label class="form-label">Kelas</label>
                <select name="kelas" class="form-select">
                    @foreach ($daftarKelas as $item)
                        <option value="{{ $item->id_kelas }}" @selected($kelasTerpilih?->id_kelas === $item->id_kelas)>
                            {{ $item->nama_kelas }} · {{ $item->tahunAjaran?->nama_tahun_ajaran }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Mata pelajaran</label>
                <select name="mapel" class="form-select">
                    <option value="">Semua mata pelajaran</option>
                    @foreach ($mataPelajaran as $item)
                        <option value="{{ $item->id_mata_pelajaran }}" @selected((string) $idMapel === (string) $item->id_mata_pelajaran)>{{ $item->nama_mata_pelajaran }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Dari</label>
                <input type="date" name="dari" value="{{ $dari->toDateString() }}" class="form-input">
            </div>
            <div>
                <label class="form-label">Sampai</label>
                <input type="date" name="sampai" value="{{ $sampai->toDateString() }}" class="form-input">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn-primary">Terapkan</button>
                <a href="{{ route('admin.laporan.index') }}" class="btn-ghost">Reset</a>
            </div>
        </form>
        @if ($semesterAktif)
            <p class="mt-3 text-xs text-ink-muted">
                Periode berjalan: {{ ucfirst($semesterAktif->nama_semester) }} {{ $semesterAktif->tahunAjaran?->nama_tahun_ajaran }}
                ({{ $semesterAktif->tanggal_mulai?->format('d/m/Y') }} – {{ $semesterAktif->tanggal_selesai?->format('d/m/Y') }}).
            </p>
        @endif
    </div>
</div>

@if (! $kelasTerpilih)
    <div class="card">
        <x-empty judul="Belum ada kelas" pesan="Tambahkan kelas dan siswa untuk melihat laporan." ikon="bar-chart-3" />
    </div>
@else
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-stat-card label="Siswa" :nilai="$total['siswa']" ikon="users" warna="slate" />
        <x-stat-card label="Hadir" :nilai="$total['hadir']" ikon="check-circle-2" warna="emerald" />
        <x-stat-card label="Izin / Sakit" :nilai="$total['izin'] + $total['sakit']" ikon="file-text" warna="amber" :keterangan="'Izin '.$total['izin'].' · Sakit '.$total['sakit']" />
        <x-stat-card label="Alpha" :nilai="$total['alpha']" ikon="x-circle" warna="rose" />
        <x-stat-card label="Rata-rata Hadir" :nilai="$persentaseKelas.'%'" ikon="percent" warna="violet" :keterangan="$jumlahSesi.' sesi berakhir'" />
    </div>

    <div class="card overflow-hidden">
        <div class="card-header">
            <div>
                <p class="text-sm font-semibold text-ink">{{ $kelasTerpilih->nama_kelas }}{{ $mapelTerpilih ? ' · '.$mapelTerpilih->nama_mata_pelajaran : '' }}</p>
                <p class="mt-0.5 text-xs text-ink-muted">
                    Periode {{ $dari->format('d/m/Y') }} – {{ $sampai->format('d/m/Y') }} ·
                    {{ $jumlahSesi }} sesi telah berakhir · {{ $total['belum_diisi'] }} catatan belum diisi
                </p>
            </div>
        </div>

        @if ($laporan->isEmpty())
            <x-empty judul="Belum ada siswa" pesan="Kelas ini belum memiliki siswa aktif." ikon="users" />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-line">
                    <thead class="table-head">
                        <tr>
                            <th class="px-5 py-3">Siswa</th>
                            <th class="px-3 py-3 text-center">Pertemuan</th>
                            <th class="px-3 py-3 text-center">Hadir</th>
                            <th class="px-3 py-3 text-center">Izin</th>
                            <th class="px-3 py-3 text-center">Sakit</th>
                            <th class="px-3 py-3 text-center">Alpha</th>
                            <th class="px-3 py-3 text-center">Belum</th>
                            <th class="px-5 py-3">Persentase</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($laporan as $baris)
                            <tr class="hover:bg-paper/70">
                                <td class="px-5 py-3">
                                    <p class="text-sm font-medium text-ink">{{ $baris['nama_lengkap'] }}</p>
                                    <p class="text-xs text-ink-muted">NIS {{ $baris['nis'] }}</p>
                                </td>
                                <td class="table-cell text-center">{{ $baris['total_pertemuan'] }}</td>
                                <td class="table-cell text-center font-medium text-moss">{{ $baris['hadir'] }}</td>
                                <td class="table-cell text-center text-ochre">{{ $baris['izin'] }}</td>
                                <td class="table-cell text-center text-slateblue">{{ $baris['sakit'] }}</td>
                                <td class="table-cell text-center text-clay">{{ $baris['alpha'] }}</td>
                                <td class="table-cell text-center text-ink-muted">{{ $baris['belum_diisi'] }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="h-1.5 w-28 overflow-hidden bg-paper-deep">
                                            <div class="h-full {{ $baris['persentase_hadir'] >= 75 ? 'bg-moss' : ($baris['persentase_hadir'] >= 50 ? 'bg-ochre' : 'bg-clay') }}" style="width: {{ min(100, max(0, $baris['persentase_hadir'])) }}%"></div>
                                        </div>
                                        <span class="text-sm font-semibold text-ink">{{ $baris['persentase_hadir'] }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif
@endsection
