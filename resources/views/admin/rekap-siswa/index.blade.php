@extends('layouts.app')

@section('judul', 'Rekap Siswa')
@section('deskripsi', 'Rekap kehadiran seluruh siswa beserta foto profil dan status pertemuan.')

@section('konten')
<div class="space-y-5">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Siswa" :nilai="$siswa->total()" ikon="users" warna="ink" keterangan="Sesuai filter aktif" />
        <x-stat-card label="Pertemuan tercatat" :nilai="$totalPertemuan" ikon="calendar-check" warna="slateblue" keterangan="Dalam periode terpilih" />
        <x-stat-card label="Rata-rata kehadiran" :nilai="number_format($rataPersen, 1, ',', '.').'%'" ikon="badge-check" warna="moss" keterangan="Dihitung dari seluruh pertemuan" />
        <x-stat-card label="Perlu perhatian" :nilai="$perluPerhatian" ikon="alert-triangle" warna="clay" keterangan="Kehadiran di bawah 75%" />
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.rekap-siswa.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_180px_170px_190px_auto]">
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-ink-muted">
                        <i data-lucide="search" class="h-4 w-4"></i>
                    </span>
                    <input type="search" name="q" value="{{ $pencarian }}" class="form-input pl-9" placeholder="Cari nama, NIS, NISN, atau email...">
                </div>
                <select name="kelas" class="form-select">
                    <option value="">Semua kelas</option>
                    @foreach ($kelas as $item)
                        <option value="{{ $item->id_kelas }}" @selected((string) $idKelas === (string) $item->id_kelas)>{{ $item->nama_kelas }}</option>
                    @endforeach
                </select>
                <select name="status" class="form-select">
                    @foreach (['semua' => 'Semua status', 'aktif' => 'Aktif', 'lulus' => 'Lulus', 'pindah' => 'Pindah', 'nonaktif' => 'Nonaktif'] as $nilai => $label)
                        <option value="{{ $nilai }}" @selected($status === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="periode" class="form-select">
                    @foreach (['semester' => 'Periode berjalan', 'bulan' => 'Bulan ini', '7hari' => '7 hari terakhir'] as $nilai => $label)
                        <option value="{{ $nilai }}" @selected($periode === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>
                <div class="flex gap-2">
                    <button type="submit" class="btn-secondary">Terapkan</button>
                    <a href="{{ route('admin.rekap-siswa.index') }}" class="btn-ghost">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="card-header">
            <div>
                <h3 class="section-title">Rekap seluruh siswa</h3>
                <p class="mt-0.5 text-[12px] text-ink-muted">
                    {{ $periodeLabel }} · {{ $dari->locale('id')->translatedFormat('d M Y') }} – {{ $sampai->locale('id')->translatedFormat('d M Y') }}
                </p>
            </div>
            <span class="font-mono text-[10px] uppercase tracking-[0.1em] text-ink-muted">{{ $siswa->total() }} siswa</span>
        </div>

        @if ($siswa->isEmpty())
            <x-empty judul="Tidak ada siswa" pesan="Ubah filter pencarian, kelas, atau status untuk melihat data siswa." ikon="users" />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-line">
                    <thead class="table-head">
                        <tr>
                            <th class="px-5 py-2.5">Siswa</th>
                            <th class="px-4 py-2.5">Kelas</th>
                            <th class="px-3 py-2.5 text-center">Hadir</th>
                            <th class="px-3 py-2.5 text-center">Izin</th>
                            <th class="px-3 py-2.5 text-center">Sakit</th>
                            <th class="px-3 py-2.5 text-center">Alpha</th>
                            <th class="px-3 py-2.5 text-center">Belum</th>
                            <th class="px-5 py-2.5">Kehadiran</th>
                            <th class="px-4 py-2.5">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($siswa as $murid)
                            @php
                                $kelasMurid = $murid->riwayatKelasAktif->first()?->kelas;
                                $persen = (float) $murid->rekap_persen;
                                $warnaBaris = $persen >= 85 ? 'bg-moss' : ($persen >= 75 ? 'bg-ochre' : 'bg-clay');
                                $warnaStatus = $persen >= 85 ? 'bg-moss-soft text-moss ring-moss/25' : ($persen >= 75 ? 'bg-ochre-soft text-ochre ring-ochre/25' : 'bg-clay-soft text-clay ring-clay/25');
                                $labelStatus = $murid->rekap_total === 0 ? 'Belum ada data' : ($persen >= 85 ? 'Baik' : ($persen >= 75 ? 'Cukup' : 'Waspada'));
                            @endphp
                            <tr class="transition-colors hover:bg-paper/70">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <x-avatar :pengguna="$murid->pengguna" :nama="$murid->nama_lengkap" ukuran="sm" />
                                        <div class="min-w-0">
                                            <a href="{{ route('admin.siswa.show', $murid) }}" class="block truncate text-[13px] font-bold text-ink hover:text-accent-deep">{{ $murid->nama_lengkap }}</a>
                                            <p class="text-[11px] text-ink-muted">NIS {{ $murid->nis }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="table-cell">{{ $kelasMurid?->nama_kelas ?? '—' }}</td>
                                <td class="px-3 py-3 text-center font-mono text-[11.5px] tabular-nums text-ink">{{ $murid->rekap_hadir }}</td>
                                <td class="px-3 py-3 text-center font-mono text-[11.5px] tabular-nums text-ink-muted">{{ $murid->rekap_izin }}</td>
                                <td class="px-3 py-3 text-center font-mono text-[11.5px] tabular-nums text-ink-muted">{{ $murid->rekap_sakit }}</td>
                                <td class="px-3 py-3 text-center font-mono text-[11.5px] tabular-nums text-clay">{{ $murid->rekap_alpha }}</td>
                                <td class="px-3 py-3 text-center font-mono text-[11.5px] tabular-nums text-ink-muted">{{ $murid->rekap_belum }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="progress-track w-20">
                                            <div class="progress-bar {{ $warnaBaris }}" style="width: {{ min(100, max(0, $persen)) }}%"></div>
                                        </div>
                                        <span class="font-mono text-[11px] tabular-nums text-ink">{{ number_format($persen, 1, ',', '.') }}%</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <x-badge :teks="$labelStatus" :warna="$murid->rekap_total === 0 ? 'bg-paper-deep text-ink-muted ring-line-strong' : $warnaStatus" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-line px-5 py-3">
                {{ $siswa->links() }}
            </div>
        @endif
    </div>
</div>
@endsection