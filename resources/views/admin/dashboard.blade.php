@extends('layouts.app')

@section('judul', 'Dashboard Admin')
@section('deskripsi', now()->locale('id')->translatedFormat('l, d F Y') . ' · ringkasan operasional sekolah hari ini.')

@section('aksi')
    <a href="{{ route('admin.sesi.index') }}" class="btn-primary btn-sm">
        <i data-lucide="list-checks" class="h-4 w-4"></i>
        Sesi Hari Ini
    </a>
@endsection

@section('konten')
<section class="border border-line bg-surface">
    <div class="grid lg:grid-cols-[1.5fr_1fr]">
        <div class="border-b border-line px-6 py-7 lg:border-b-0 lg:border-r">
            <div class="flex items-center gap-2.5">
                <span class="h-px w-7 bg-accent"></span>
                <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-ink-muted">Pusat kendali</p>
            </div>
            <h2 class="mt-3 max-w-xl font-serif text-[30px] font-semibold leading-[1.15] tracking-[-0.02em] text-ink sm:text-[34px]">
                Selamat datang, {{ auth()->user()->nama_pengguna }}.
            </h2>
            <p class="mt-3 max-w-xl text-[13px] leading-relaxed text-ink-muted">
                Pantau sesi absensi, konfirmasi pembelajaran, dan kepatuhan jadwal. Setiap tindakan tercatat pada log aktivitas.
            </p>
            <div class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-3">
                <a href="{{ route('admin.siswa.create') }}" class="btn-primary btn-sm">
                    <i data-lucide="user-plus" class="h-3.5 w-3.5"></i> Tambah siswa
                </a>
                <a href="{{ route('admin.jadwal.index') }}" class="link-quiet text-[12px]">Kelola jadwal</a>
                <a href="{{ route('admin.laporan.index') }}" class="link-quiet text-[12px]">Lihat laporan</a>
            </div>
        </div>

        <dl class="grid grid-cols-2">
            @foreach ([
                ['Sesi hari ini', $ringkasan['total_sesi']],
                ['Belum diisi', $ringkasan['belum_diisi']],
                ['Terkonfirmasi', $ringkasan['terkonfirmasi']],
                ['Perlu periksa', $ringkasan['perlu_pemeriksaan']],
            ] as $i => $sel)
                <div class="px-5 py-4 {{ $i < 2 ? 'border-b border-line' : '' }} {{ $i % 2 === 0 ? 'border-r border-line' : '' }}">
                    <dt class="kicker">{{ $sel[0] }}</dt>
                    <dd class="mt-1.5 font-serif text-[28px] font-semibold leading-none tabular-nums tracking-tight text-ink">{{ $sel[1] }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
</section>

<div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat-card label="Siswa Aktif" :nilai="$statistik['siswa_aktif']" ikon="users" warna="moss" keterangan="Terdaftar dan berstatus aktif" />
    <x-stat-card label="Guru Aktif" :nilai="$statistik['guru_aktif']" ikon="graduation-cap" warna="slateblue" keterangan="Guru yang dapat mengajar" />
    <x-stat-card label="Kelas Aktif" :nilai="$statistik['kelas_aktif']" ikon="school" warna="ochre" keterangan="Kelas pada tahun ajaran berjalan" />
    <x-stat-card label="Mata Pelajaran" :nilai="$statistik['mapel_aktif']" ikon="book-open" warna="accent" keterangan="Mapel aktif dijadwalkan" />
</div>

<div class="mt-5 grid gap-5 xl:grid-cols-3">
    <div class="card xl:col-span-2">
        <div class="card-header">
            <div>
                <h3 class="section-title">Sesi yang membutuhkan perhatian</h3>
                <p class="mt-0.5 text-[12px] text-ink-muted">Sesi belum diisi atau masih menunggu konfirmasi hari ini.</p>
            </div>
            <a href="{{ route('admin.sesi.index') }}" class="btn-ghost btn-sm">
                Semua sesi <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
            </a>
        </div>

        @if ($perhatian->isEmpty())
            <x-empty judul="Semua sesi tertangani" pesan="Tidak ada sesi yang belum diisi atau menunggu konfirmasi saat ini." ikon="check-circle-2" />
        @else
            <div class="divide-y divide-line">
                @foreach ($perhatian as $sesi)
                    <a href="{{ route('admin.sesi.show', $sesi) }}" class="group flex flex-wrap items-center gap-4 px-5 py-3.5 transition-colors hover:bg-paper">
                        <div class="w-14 shrink-0 border-r border-line pr-3 text-center">
                            <p class="font-mono text-[11px] leading-tight text-ink">{{ $sesi->waktu_mulai->format('H:i') }}</p>
                            <p class="font-mono text-[10px] leading-tight text-ink-muted">{{ $sesi->waktu_selesai->format('H:i') }}</p>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[13px] font-bold text-ink">{{ $sesi->jadwal?->mataPelajaran?->nama_mata_pelajaran }}</p>
                            <p class="mt-0.5 truncate text-[11.5px] text-ink-muted">
                                {{ $sesi->jadwal?->kelas?->nama_kelas }} · {{ $sesi->guruPenanggungJawab?->nama_lengkap }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($sesi->waktu_disimpan === null)
                                <x-badge teks="Belum diisi" warna="bg-clay-soft text-clay ring-clay/25" />
                            @endif
                            <x-status :status="$sesi->status_sesi" kelompok="SESI" />
                            <i data-lucide="chevron-right" class="h-4 w-4 text-ink-muted transition-colors group-hover:text-ink"></i>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="section-title">Kehadiran 7 hari</h3>
                <p class="mt-0.5 text-[12px] text-ink-muted">Persentase hadir dari total catatan harian.</p>
            </div>
        </div>
        <div class="card-body space-y-4">
            @foreach ($grafik as $hari)
                @php
                    $totalHari = $hari['hadir'] + $hari['izin'] + $hari['sakit'] + $hari['alpha'] + $hari['belum'];
                    $persen = $totalHari > 0 ? round(($hari['hadir'] / $totalHari) * 100) : 0;
                @endphp
                <div>
                    <div class="mb-1.5 flex items-baseline justify-between gap-3">
                        <span class="text-[12px] font-bold text-ink-soft">{{ $hari['label'] }} <span class="font-mono text-[10px] font-normal text-ink-muted">{{ \Carbon\Carbon::parse($hari['tanggal'])->format('d/m') }}</span></span>
                        <span class="font-mono text-[10px] tabular-nums text-ink-muted">{{ $hari['hadir'] }}/{{ $totalHari }} · {{ $persen }}%</span>
                    </div>
                    <div class="progress-track">
                        <div class="progress-bar bg-moss" style="width: {{ $persen }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="mt-5 grid gap-5 xl:grid-cols-3">
    <div class="card xl:col-span-2">
        <div class="card-header">
            <div>
                <h3 class="section-title">Aktivitas terbaru</h3>
                <p class="mt-0.5 text-[12px] text-ink-muted">Jejak tindakan pengguna pada sistem.</p>
            </div>
            <a href="{{ route('admin.log.index') }}" class="btn-ghost btn-sm">Lihat log</a>
        </div>
        @if ($logTerbaru->isEmpty())
            <x-empty judul="Belum ada aktivitas" ikon="history" />
        @else
            <ul class="divide-y divide-line">
                @foreach ($logTerbaru as $log)
                    <li class="flex gap-3 px-5 py-3">
                        <span class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center border border-line bg-paper text-ink-muted">
                            <i data-lucide="activity" class="h-3.5 w-3.5"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-[13px] text-ink-soft">{{ $log->deskripsi_aktivitas }}</p>
                            <p class="mt-0.5 font-mono text-[10px] uppercase tracking-[0.04em] text-ink-muted">
                                {{ $log->pengguna?->nama_pengguna }} · {{ $log->nama_modul }} · {{ $log->waktu_aktivitas?->locale('id')->diffForHumans() }}
                            </p>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="section-title">Konfirmasi hari ini</h3>
        </div>
        <div class="card-body">
            <dl class="divide-y divide-line border-y border-line">
                @foreach ([
                    ['Menunggu konfirmasi', $ringkasan['menunggu_konfirmasi'], 'text-ochre'],
                    ['Terkonfirmasi', $ringkasan['terkonfirmasi'], 'text-moss'],
                    ['Perlu pemeriksaan', $ringkasan['perlu_pemeriksaan'], 'text-slateblue'],
                    ['Belum dikonfirmasi', $ringkasan['belum_dikonfirmasi'], 'text-ink-muted'],
                ] as $baris)
                    <div class="flex items-center justify-between gap-3 py-2.5">
                        <dt class="text-[12.5px] text-ink-soft">{{ $baris[0] }}</dt>
                        <dd class="font-serif text-[20px] font-semibold leading-none tabular-nums {{ $baris[2] }}">{{ $baris[1] }}</dd>
                    </div>
                @endforeach
            </dl>

            <a href="{{ route('admin.petugas-konfirmasi.index') }}" class="btn-secondary mt-5 w-full">
                <i data-lucide="user-check" class="h-4 w-4"></i>
                Atur petugas konfirmasi
            </a>
        </div>
    </div>
</div>
@endsection
