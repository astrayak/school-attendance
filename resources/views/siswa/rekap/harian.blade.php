@extends('layouts.app')

@section('judul', 'Rekap Harian')
@section('deskripsi')
    Ringkasan kehadiran Anda per hari, default semester aktif.
@endsection

@section('aksi')
    <a href="{{ route('siswa.rekap.mapel') }}" class="btn-secondary btn-sm"><i data-lucide="bar-chart-3" class="h-4 w-4"></i> Rekap Mapel</a>
@endsection

@section('konten')
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" action="{{ route('siswa.rekap.harian') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[210px_160px_160px_auto]">
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
            <div class="flex items-end gap-2">
                <button type="submit" class="btn-secondary">Terapkan</button>
                <a href="{{ route('siswa.rekap.harian') }}" class="btn-ghost">Reset</a>
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

<div class="mb-6 grid gap-4 sm:grid-cols-3 xl:grid-cols-6">
    <x-stat-card label="Hari Aktif" :nilai="$total['hari']" ikon="calendar-days" warna="violet" />
    <x-stat-card label="Mapel" :nilai="$total['mapel']" ikon="book-open" warna="slate" />
    <x-stat-card label="Hadir" :nilai="$total['hadir']" ikon="check-circle-2" warna="emerald" />
    <x-stat-card label="Izin" :nilai="$total['izin']" ikon="file-text" warna="amber" />
    <x-stat-card label="Sakit" :nilai="$total['sakit']" ikon="thermometer" warna="sky" />
    <x-stat-card label="Alpha" :nilai="$total['alpha']" ikon="x-circle" warna="rose" />
</div>

<div class="card overflow-hidden">
    <div class="card-header">
        <div>
            <p class="text-sm font-semibold text-ink">Kehadiran harian</p>
            <p class="mt-0.5 text-xs text-ink-muted">{{ $dari->locale('id')->translatedFormat('d F Y') }} – {{ $sampai->locale('id')->translatedFormat('d F Y') }}</p>
        </div>
        @if ($total['belum'] > 0)
            <span class="inline-flex items-center gap-2 border border-line bg-paper-deep px-2.5 py-1 font-mono text-[10px] uppercase tracking-[0.06em] text-ink-soft">
                <i data-lucide="clock" class="h-3.5 w-3.5"></i> {{ $total['belum'] }} absensi belum diisi guru
            </span>
        @endif
    </div>

    @if ($rekap->isEmpty())
        <x-empty judul="Belum ada data harian" pesan="Belum ada sesi pembelajaran pada rentang tanggal ini." ikon="calendar-x" />
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-line">
                <thead class="table-head">
                    <tr>
                        <th class="px-5 py-3">Tanggal</th>
                        <th class="px-4 py-3">Kelas</th>
                        <th class="px-3 py-3 text-center">Mapel</th>
                        <th class="px-3 py-3 text-center">Hadir</th>
                        <th class="px-3 py-3 text-center">Izin</th>
                        <th class="px-3 py-3 text-center">Sakit</th>
                        <th class="px-3 py-3 text-center">Alpha</th>
                        <th class="px-3 py-3 text-center">Belum</th>
                        <th class="px-5 py-3">Status Hari</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line bg-surface">
                    @foreach ($rekap as $baris)
                        <tr class="hover:bg-paper/70">
                            <td class="px-5 py-3">
                                <p class="text-sm font-medium text-ink">{{ $baris->tanggal?->locale('id')->translatedFormat('l') }}</p>
                                <p class="text-xs text-ink-muted">{{ $baris->tanggal?->format('d/m/Y') }}</p>
                            </td>
                            <td class="table-cell">{{ $baris->kelas?->nama_kelas ?? '—' }}</td>
                            <td class="px-3 py-3 text-center text-sm font-medium text-ink-soft">{{ $baris->jumlah_mapel_dijadwalkan }}</td>
                            <td class="px-3 py-3 text-center text-sm font-semibold text-moss">{{ $baris->jumlah_hadir }}</td>
                            <td class="px-3 py-3 text-center text-sm text-ochre">{{ $baris->jumlah_izin }}</td>
                            <td class="px-3 py-3 text-center text-sm text-slateblue">{{ $baris->jumlah_sakit }}</td>
                            <td class="px-3 py-3 text-center text-sm text-clay">{{ $baris->jumlah_alpha }}</td>
                            <td class="px-3 py-3 text-center text-sm text-ink-muted">{{ $baris->jumlah_belum_diisi }}</td>
                            <td class="px-5 py-3"><x-status :status="$baris->status_harian" kelompok="STATUS_HARIAN" /></td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-paper text-sm font-semibold text-ink">
                    <tr>
                        <td class="px-5 py-3" colspan="2">Total</td>
                        <td class="px-3 py-3 text-center">{{ $total['mapel'] }}</td>
                        <td class="px-3 py-3 text-center text-moss">{{ $total['hadir'] }}</td>
                        <td class="px-3 py-3 text-center text-ochre">{{ $total['izin'] }}</td>
                        <td class="px-3 py-3 text-center text-slateblue">{{ $total['sakit'] }}</td>
                        <td class="px-3 py-3 text-center text-clay">{{ $total['alpha'] }}</td>
                        <td class="px-3 py-3 text-center text-ink-muted">{{ $total['belum'] }}</td>
                        <td class="px-5 py-3">{{ $total['hari'] }} hari</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
</div>

<div class="mt-4 rounded-sm border border-line bg-surface px-4 py-3 text-xs text-ink-muted">
    <p><strong class="text-ink-soft">Catatan:</strong> status harian dihitung dari seluruh sesi pada hari tersebut. Sesi yang belum diisi guru tidak dianggap alpha dan membuat status harian menjadi belum lengkap.</p>
</div>
@endsection
