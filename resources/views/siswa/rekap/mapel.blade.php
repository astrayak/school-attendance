@extends('layouts.app')

@section('judul', 'Rekap per Mata Pelajaran')
@section('deskripsi')
    Persentase kehadiran Anda untuk setiap mata pelajaran, default semester aktif.
@endsection

@section('aksi')
    <a href="{{ route('siswa.rekap.harian') }}" class="btn-secondary btn-sm"><i data-lucide="calendar-check" class="h-4 w-4"></i> Rekap Harian</a>
@endsection

@section('konten')
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" action="{{ route('siswa.rekap.mapel') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[210px_160px_160px_1fr_auto]">
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
                <label for="mapel" class="form-label">Mata pelajaran</label>
                <select id="mapel" name="mapel" class="form-select">
                    <option value="">Semua mata pelajaran</option>
                    @foreach ($mataPelajaran as $item)
                        <option value="{{ $item->id_mata_pelajaran }}" @selected((string) request('mapel') === (string) $item->id_mata_pelajaran)>{{ $item->nama_mata_pelajaran }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn-secondary">Terapkan</button>
                <a href="{{ route('siswa.rekap.mapel') }}" class="btn-ghost">Reset</a>
            </div>
        </form>
        <p class="mt-3 text-xs text-ink-muted">
            @if ($semester)
                Periode aktif: <strong class="text-ink-soft">{{ ucfirst($semester->nama_semester) }} {{ $semester->tahunAjaran?->nama_tahun_ajaran }}</strong>
                ({{ $semester->tanggal_mulai?->format('d/m/Y') }} – {{ $semester->tanggal_selesai?->format('d/m/Y') }}).
                Tanggal diabaikan saat memilih periode tertentu.
            @else
                Rentang tanggal bebas. Pilih semester untuk membatasi rekap pada satu periode saja.
            @endif
        </p>
    </div>
</div>

<div class="mb-5 border border-line border-l-2 border-l-accent bg-surface px-6 py-6">
    <div class="flex flex-wrap items-end justify-between gap-6">
        <div>
            <p class="kicker">Rekap mapel</p>
            <h2 class="mt-1.5 font-serif text-xl font-semibold text-ink">{{ $siswa->nama_lengkap }}</h2>
            <p class="mt-1 text-[12.5px] text-ink-muted">
                {{ $kelas?->nama_kelas ?? 'Tanpa kelas' }} ·
                {{ $dari->locale('id')->translatedFormat('d M Y') }} – {{ $sampai->locale('id')->translatedFormat('d M Y') }}
                @if ($mapelTerpilih)
                    · {{ $mapelTerpilih->nama_mata_pelajaran }}
                @endif
            </p>
        </div>
        <div class="flex items-center gap-5">
            <div class="text-right">
                <p class="font-serif text-[28px] font-semibold leading-none tabular-nums text-ink">{{ number_format($persentase, 1, ',', '.') }}%</p>
                <p class="kicker mt-1.5">Kehadiran</p>
            </div>
            <div class="h-12 w-px bg-line"></div>
            <div class="text-right">
                <p class="font-serif text-[28px] font-semibold leading-none tabular-nums text-ink">{{ $total['pertemuan'] }}</p>
                <p class="kicker mt-1.5">Pertemuan</p>
            </div>
        </div>
    </div>
</div>

<div class="mb-6 grid gap-4 sm:grid-cols-3 xl:grid-cols-6">
    <x-stat-card label="Hadir" :nilai="$total['hadir']" ikon="check-circle-2" warna="emerald" />
    <x-stat-card label="Izin" :nilai="$total['izin']" ikon="file-text" warna="amber" />
    <x-stat-card label="Sakit" :nilai="$total['sakit']" ikon="thermometer" warna="sky" />
    <x-stat-card label="Alpha" :nilai="$total['alpha']" ikon="x-circle" warna="rose" />
    <x-stat-card label="Belum Diisi" :nilai="$total['belum']" ikon="clock" warna="slate" />
    <x-stat-card label="Pertemuan" :nilai="$total['pertemuan']" ikon="calendar-days" warna="violet" />
</div>

<div class="card overflow-hidden">
    <div class="card-header">
        <div>
            <p class="text-sm font-semibold text-ink">Rincian per mata pelajaran</p>
            <p class="mt-0.5 text-xs text-ink-muted">Sesi yang belum diisi tidak dihitung sebagai alpha, tetapi tetap tercatat pada kolom belum diisi.</p>
        </div>
    </div>

    @if ($rekap->isEmpty())
        <x-empty judul="Belum ada data" pesan="Belum ada sesi pembelajaran yang selesai pada rentang tanggal ini." ikon="bar-chart-3" />
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-line">
                <thead class="table-head">
                    <tr>
                        <th class="px-5 py-3">Mata pelajaran</th>
                        <th class="px-3 py-3 text-center">Pertemuan</th>
                        <th class="px-3 py-3 text-center">Hadir</th>
                        <th class="px-3 py-3 text-center">Izin</th>
                        <th class="px-3 py-3 text-center">Sakit</th>
                        <th class="px-3 py-3 text-center">Alpha</th>
                        <th class="px-3 py-3 text-center">Belum</th>
                        <th class="px-5 py-3">Persentase</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line bg-surface">
                    @foreach ($rekap as $baris)
                        <tr class="hover:bg-paper/70">
                            <td class="px-5 py-4">
                                <p class="text-sm font-semibold text-ink">{{ $baris['nama_mata_pelajaran'] }}</p>
                            </td>
                            <td class="px-3 py-4 text-center text-sm font-medium text-ink-soft">{{ $baris['total_pertemuan'] }}</td>
                            <td class="px-3 py-4 text-center text-sm font-semibold text-moss">{{ $baris['hadir'] }}</td>
                            <td class="px-3 py-4 text-center text-sm text-ochre">{{ $baris['izin'] }}</td>
                            <td class="px-3 py-4 text-center text-sm text-slateblue">{{ $baris['sakit'] }}</td>
                            <td class="px-3 py-4 text-center text-sm text-clay">{{ $baris['alpha'] }}</td>
                            <td class="px-3 py-4 text-center text-sm text-ink-muted">{{ $baris['belum_diisi'] }}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="h-1.5 w-24 overflow-hidden bg-paper-deep">
                                        <div class="h-full {{ $baris['persentase_hadir'] >= 75 ? 'bg-moss' : ($baris['persentase_hadir'] >= 50 ? 'bg-ochre' : 'bg-clay') }}" style="width: {{ min(100, max(0, $baris['persentase_hadir'])) }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-ink-soft">{{ number_format($baris['persentase_hadir'], 1, ',', '.') }}%</span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-paper">
                    <tr class="text-sm font-semibold text-ink">
                        <td class="px-5 py-3">Total</td>
                        <td class="px-3 py-3 text-center">{{ $total['pertemuan'] }}</td>
                        <td class="px-3 py-3 text-center text-moss">{{ $total['hadir'] }}</td>
                        <td class="px-3 py-3 text-center text-ochre">{{ $total['izin'] }}</td>
                        <td class="px-3 py-3 text-center text-slateblue">{{ $total['sakit'] }}</td>
                        <td class="px-3 py-3 text-center text-clay">{{ $total['alpha'] }}</td>
                        <td class="px-3 py-3 text-center text-ink-muted">{{ $total['belum'] }}</td>
                        <td class="px-5 py-3">{{ number_format($persentase, 1, ',', '.') }}%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
</div>
@endsection
