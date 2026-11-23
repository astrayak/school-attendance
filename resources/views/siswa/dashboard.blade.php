@extends('layouts.app')

@section('judul', 'Dashboard Siswa')
@section('deskripsi', 'Ringkasan kehadiran dan jadwal Anda hari ini.')

@section('aksi')
    <a href="{{ route('siswa.qr') }}" class="btn-primary btn-sm"><i data-lucide="qr-code" class="h-4 w-4"></i> QR Code Saya</a>
@endsection

@section('konten')
<div class="space-y-5">
    <section class="border border-line bg-surface">
        <div class="border-l-2 border-l-accent px-6 py-6">
            <div class="flex flex-wrap items-start justify-between gap-8">
                <div class="flex min-w-0 items-start gap-4">
                    <x-avatar :pengguna="$siswa->pengguna" :nama="$siswa->nama_lengkap" ukuran="lg" />
                    <div class="min-w-0">
                        <p class="kicker">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</p>
                        <h2 class="mt-2 font-serif text-[28px] font-semibold leading-tight tracking-[-0.02em] text-ink">Halo, {{ $siswa->nama_lengkap }}</h2>
                        <p class="mt-1.5 font-mono text-[10.5px] uppercase tracking-[0.1em] text-ink-muted">
                            {{ $kelas?->nama_kelas ?? 'Belum memiliki kelas' }} · NIS {{ $siswa->nis }}
                        </p>
                        <div class="mt-4 inline-flex items-center gap-2 border border-line bg-paper px-2.5 py-1">
                            <i data-lucide="badge-check" class="h-3.5 w-3.5 text-moss"></i>
                            <span class="font-mono text-[10px] uppercase tracking-[0.08em] text-ink-soft">
                                Kehadiran semester ini {{ number_format($statistik['persentase'], 1, ',', '.') }}%
                            </span>
                        </div>
                    </div>
                </div>

                <dl class="grid w-full grid-cols-2 gap-px border border-line bg-line sm:w-auto sm:grid-cols-4">
                    @foreach ([
                        ['Hadir', $statistik['hadir']],
                        ['Izin', $statistik['izin']],
                        ['Sakit', $statistik['sakit']],
                        ['Alpha', $statistik['alpha']],
                    ] as $sel)
                        <div class="min-w-[86px] bg-surface px-4 py-3 text-center">
                            <dt class="kicker">{{ $sel[0] }}</dt>
                            <dd class="mt-1 font-serif text-[21px] font-semibold leading-none tabular-nums text-ink">{{ $sel[1] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>
    </section>

    @if ($jumlahMenunggu > 0)
        <div class="flex flex-col gap-3 border border-line border-l-2 border-l-ochre bg-ochre-soft/60 px-4 py-3 sm:flex-row sm:items-center">
            <i data-lucide="bell-ring" class="h-4 w-4 shrink-0 text-ochre"></i>
            <p class="flex-1 text-[12.5px] leading-relaxed text-ink-soft">
                Anda memiliki <strong class="font-bold text-ink">{{ $jumlahMenunggu }}</strong> permintaan konfirmasi pembelajaran yang menunggu jawaban. Konfirmasi sebelum batas waktu berakhir.
            </p>
            <a href="{{ route('siswa.konfirmasi.index') }}" class="btn-primary btn-sm shrink-0">Konfirmasi sekarang</a>
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Hadir" :nilai="$statistik['hadir']" ikon="check-circle-2" warna="moss" keterangan="$labelPeriode" />
        <x-stat-card label="Izin" :nilai="$statistik['izin']" ikon="file-text" warna="ochre" keterangan="$labelPeriode" />
        <x-stat-card label="Sakit" :nilai="$statistik['sakit']" ikon="thermometer" warna="slateblue" keterangan="$labelPeriode" />
        <x-stat-card label="Alpha" :nilai="$statistik['alpha']" ikon="x-circle" warna="clay" keterangan="$labelPeriode" />
    </div>

    <div class="grid gap-5 xl:grid-cols-3">
        <div class="space-y-5 xl:col-span-2">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="section-title">Jadwal hari ini</h3>
                        <p class="mt-0.5 text-[12px] text-ink-muted">{{ $sesiHariIni->count() }} sesi pembelajaran untuk kelas Anda.</p>
                    </div>
                    @if ($rekapHariIni)
                        <x-status :status="$rekapHariIni->status_harian" kelompok="STATUS_HARIAN" />
                    @endif
                </div>

                @if ($sesiHariIni->isEmpty())
                    <x-empty judul="Tidak ada jadwal" pesan="Tidak ada sesi pembelajaran untuk kelas Anda hari ini." ikon="calendar-x" />
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($sesiHariIni as $sesi)
                            @php($baris = $catatanHariIni->get($sesi->id_sesi_absensi))
                            <li class="flex flex-wrap items-center gap-4 px-5 py-3.5">
                                <div class="w-14 shrink-0 border-r border-line pr-3 text-center">
                                    <p class="font-mono text-[11px] leading-tight text-ink">{{ $sesi->waktu_mulai?->format('H:i') }}</p>
                                    <p class="font-mono text-[10px] leading-tight text-ink-muted">{{ $sesi->waktu_selesai?->format('H:i') }}</p>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-[13px] font-bold text-ink">{{ $sesi->jadwal?->mataPelajaran?->nama_mata_pelajaran ?? '—' }}</p>
                                    <p class="mt-0.5 truncate text-[11.5px] text-ink-muted">
                                        {{ $sesi->guruPelaksana?->nama_lengkap ?? $sesi->guruPenanggungJawab?->nama_lengkap ?? 'Guru belum ditentukan' }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if ($baris)
                                        <x-status :status="$baris->status_kehadiran" kelompok="KEHADIRAN" />
                                    @elseif ($sesi->waktu_absensi_ditutup && now()->gt($sesi->waktu_absensi_ditutup) && ! $sesi->waktu_disimpan)
                                        <x-badge teks="Belum diisi" warna="bg-paper-deep text-ink-muted ring-line-strong" />
                                    @elseif ($sesi->waktu_absensi_dibuka && now()->between($sesi->waktu_absensi_dibuka, $sesi->waktu_absensi_ditutup))
                                        <span class="inline-flex items-center gap-2 font-mono text-[10px] uppercase tracking-[0.08em] text-moss">
                                            <span class="h-1.5 w-1.5 bg-moss"></span> Absensi dibuka
                                        </span>
                                    @else
                                        <span class="font-mono text-[10px] uppercase tracking-[0.08em] text-ink-muted">Belum dimulai</span>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="section-title">Riwayat terbaru</h3>
                        <p class="mt-0.5 text-[12px] text-ink-muted">5 catatan kehadiran terakhir Anda.</p>
                    </div>
                    <a href="{{ route('siswa.riwayat.index') }}" class="btn-ghost btn-sm">Lihat semua</a>
                </div>

                @if ($riwayatTerbaru->isEmpty())
                    <x-empty judul="Belum ada riwayat" pesan="Catatan kehadiran akan muncul setelah guru menyimpan absensi." ikon="history" />
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-line">
                            <thead class="table-head">
                                <tr>
                                    <th class="px-5 py-2.5">Tanggal</th>
                                    <th class="px-4 py-2.5">Mata pelajaran</th>
                                    <th class="px-4 py-2.5">Status</th>
                                    <th class="px-5 py-2.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach ($riwayatTerbaru as $catatan)
                                    <tr class="transition-colors hover:bg-paper/70">
                                        <td class="table-cell font-mono text-[11px]">{{ $catatan->sesiAbsensi?->tanggal_pelajaran?->format('d/m/Y') ?? '—' }}</td>
                                        <td class="px-4 py-3">
                                            <p class="text-[12.5px] font-bold text-ink">{{ $catatan->sesiAbsensi?->jadwal?->mataPelajaran?->nama_mata_pelajaran ?? '—' }}</p>
                                            <p class="text-[11px] text-ink-muted">{{ $catatan->sesiAbsensi?->jadwal?->kelas?->nama_kelas }}</p>
                                        </td>
                                        <td class="px-4 py-3"><x-status :status="$catatan->status_kehadiran" kelompok="KEHADIRAN" /></td>
                                        <td class="px-5 py-3 text-right">
                                            <a href="{{ route('siswa.riwayat.show', $catatan) }}" class="btn-secondary btn-sm">Detail</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="section-title">Rekap semester ini</h3>
                </div>
                <div class="card-body space-y-4">
                    <div>
                        <div class="flex items-baseline justify-between gap-3">
                            <span class="text-[12px] text-ink-soft">Persentase kehadiran</span>
                            <span class="font-mono text-[13px] font-medium tabular-nums text-ink">{{ number_format($statistik['persentase'], 1, ',', '.') }}%</span>
                        </div>
                        <div class="progress-track mt-2">
                            <div class="progress-bar bg-moss" style="width: {{ min(100, max(0, $statistik['persentase'])) }}%"></div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-px border border-line bg-line">
                        <div class="bg-surface px-3 py-2.5 text-center">
                            <p class="font-serif text-[20px] font-semibold leading-none tabular-nums text-ink">{{ $statistik['total'] }}</p>
                            <p class="kicker mt-1.5">Total pertemuan</p>
                        </div>
                        <div class="bg-surface px-3 py-2.5 text-center">
                            <p class="font-serif text-[20px] font-semibold leading-none tabular-nums text-ink">{{ $rekapSemester->count() }}</p>
                            <p class="kicker mt-1.5">Mata pelajaran</p>
                        </div>
                    </div>
                    <a href="{{ route('siswa.rekap.mapel') }}" class="btn-secondary w-full">Rekap per mapel</a>
                </div>
            </div>

            @if ($permintaanMenunggu->isNotEmpty())
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="section-title">Perlu konfirmasi</h3>
                            <p class="mt-0.5 text-[12px] text-ink-muted">Jawab sebelum batas waktu.</p>
                        </div>
                        <span class="font-mono text-[11px] font-medium text-ochre">{{ $jumlahMenunggu }}</span>
                    </div>
                    <ul class="divide-y divide-line">
                        @foreach ($permintaanMenunggu as $permintaan)
                            <li class="px-5 py-3">
                                <p class="text-[12.5px] font-bold text-ink">{{ $permintaan->sesiAbsensi?->jadwal?->mataPelajaran?->nama_mata_pelajaran ?? 'Sesi pembelajaran' }}</p>
                                <p class="mt-0.5 font-mono text-[10px] uppercase tracking-[0.06em] text-ink-muted">
                                    {{ $permintaan->sesiAbsensi?->tanggal_pelajaran?->format('d/m/Y') }} · batas {{ $permintaan->waktu_kedaluwarsa?->format('H:i') }}
                                </p>
                            </li>
                        @endforeach
                    </ul>
                    <div class="border-t border-line px-5 py-3">
                        <a href="{{ route('siswa.konfirmasi.index') }}" class="btn-primary btn-sm w-full">Buka konfirmasi</a>
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h3 class="section-title">Akses cepat</h3>
                </div>
                <div class="divide-y divide-line">
                    @foreach ([
                        ['siswa.qr', 'qr-code', 'Tampilkan QR Code'],
                        ['siswa.rekap.harian', 'calendar-check', 'Rekap harian'],
                        ['siswa.profil', 'user-round', 'Profil saya'],
                    ] as $tautan)
                        <a href="{{ route($tautan[0]) }}" class="group flex items-center gap-3 px-5 py-3 text-[12.5px] font-bold text-ink-soft transition-colors hover:bg-paper hover:text-ink">
                            <i data-lucide="{{ $tautan[1] }}" class="h-4 w-4 shrink-0 text-ink-muted group-hover:text-accent"></i>
                            <span class="flex-1">{{ $tautan[2] }}</span>
                            <i data-lucide="arrow-right" class="h-3.5 w-3.5 text-ink-muted transition-colors group-hover:text-accent"></i>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
