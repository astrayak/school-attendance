@extends('layouts.app')

@section('judul', 'Dashboard Guru')
@section('deskripsi')
    Selamat datang, {{ $guru->nama_lengkap }} · {{ now()->locale('id')->translatedFormat('l, d F Y') }}
@endsection

@section('aksi')
    <a href="{{ route('guru.sesi.index') }}" class="btn-primary btn-sm">
        <i data-lucide="list-checks" class="h-4 w-4"></i> Sesi Hari Ini
    </a>
@endsection

@section('konten')
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat-card label="Sesi Saya" :nilai="$statistik['sesi_saya']" ikon="calendar-days" warna="ink" keterangan="Jadwal hari ini" />
    <x-stat-card label="Belum Diisi" :nilai="$statistik['belum_diisi']" ikon="clock" warna="ochre" keterangan="Menunggu pencatatan" />
    <x-stat-card label="Menunggu Konfirmasi" :nilai="$statistik['menunggu_konfirmasi']" ikon="badge-check" warna="slateblue" keterangan="Menunggu jawaban siswa" />
    <x-stat-card label="Perlu Pemeriksaan" :nilai="$statistik['perlu_pemeriksaan']" ikon="alert-triangle" warna="clay" keterangan="Perlu ditindaklanjuti" />
</div>

@if ($piketAktif)
    <div class="mt-5 border border-line border-l-2 border-l-slateblue bg-surface px-5 py-4">
        <div class="flex flex-wrap items-center gap-4">
            <div class="w-10 shrink-0 border-r border-line pr-3 text-center">
                <i data-lucide="shield-check" class="mx-auto h-5 w-5 text-slateblue"></i>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-[13px] font-bold text-ink">Anda bertugas sebagai guru piket hari ini</p>
                <p class="mt-0.5 text-[12px] text-ink-muted">{{ $statistik['piket'] }} sesi belum ditangani dapat Anda ambil alih bila diperlukan.</p>
            </div>
            <a href="{{ route('guru.piket.index') }}" class="btn-secondary btn-sm">Buka piket</a>
        </div>
    </div>
@endif

<div class="mt-5 grid gap-5 xl:grid-cols-3">
    <div class="space-y-5 xl:col-span-2">
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="section-title">Sesi absensi hari ini</h3>
                    <p class="mt-0.5 text-[12px] text-ink-muted">Sesi yang Anda ampu atau Anda ambil alih.</p>
                </div>
                <a href="{{ route('guru.sesi.index') }}" class="btn-ghost btn-sm">Lihat semua</a>
            </div>
            @if ($sesiSaya->isEmpty())
                <x-empty judul="Tidak ada sesi hari ini" pesan="Sesi akan muncul sesuai jadwal aktif. Gunakan menu Jadwal Saya untuk melihat jadwal mingguan." ikon="calendar-check" />
            @else
                <div class="divide-y divide-line">
                    @foreach ($sesiSaya as $sesi)
                        <div class="flex flex-wrap items-center gap-4 px-5 py-3.5">
                            <div class="w-14 shrink-0 border-r border-line pr-3 text-center">
                                <p class="font-mono text-[11px] leading-tight text-ink">{{ $sesi->waktu_mulai?->format('H:i') }}</p>
                                <p class="font-mono text-[10px] leading-tight text-ink-muted">{{ $sesi->waktu_selesai?->format('H:i') }}</p>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[13px] font-bold text-ink">{{ $sesi->jadwal?->mataPelajaran?->nama_mata_pelajaran ?? '—' }}</p>
                                <p class="mt-0.5 truncate text-[11.5px] text-ink-muted">{{ $sesi->jadwal?->kelas?->nama_kelas }} · {{ $sesi->guruPelaksana?->nama_lengkap ?? $sesi->guruPenanggungJawab?->nama_lengkap }}</p>
                            </div>
                            <x-status :status="$sesi->status_sesi" kelompok="SESI" />
                            <a href="{{ route('guru.sesi.show', $sesi) }}" class="btn-secondary btn-sm">Detail</a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="section-title">Jadwal mengajar mingguan</h3>
                    <p class="mt-0.5 text-[12px] text-ink-muted">{{ $versi?->nama_versi ?? 'Belum ada versi jadwal aktif' }} · {{ $labelPeriode }}</p>
                </div>
            </div>
            @if ($jadwalMingguIni->isEmpty())
                <x-empty judul="Belum ada jadwal" :pesan="'Jadwal untuk '.$labelPeriode.' belum tersedia. Buka Jadwal Saya untuk memilih periode lain.'" ikon="calendar-days" />
            @else
                <div class="divide-y divide-line">
                    @foreach ($urutanHari as $hari)
                        @continue (! $jadwalMingguIni->has($hari))
                        <div class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <span class="h-1 w-1 bg-accent"></span>
                                <p class="font-mono text-[10px] uppercase tracking-[0.16em] text-ink-muted">{{ \App\Support\Status::HARI[$hari] }}</p>
                            </div>
                            <div class="mt-2.5 grid border-l border-t border-line sm:grid-cols-2">
                                @foreach ($jadwalMingguIni[$hari] as $item)
                                    <div class="border-b border-r border-line bg-surface px-3 py-2.5">
                                        <p class="text-[12.5px] font-bold text-ink">{{ $item->mataPelajaran?->nama_mata_pelajaran }}</p>
                                        <p class="mt-0.5 font-mono text-[10px] text-ink-muted">
                                            {{ substr($item->jam_mulai, 0, 5) }}–{{ substr($item->jam_selesai, 0, 5) }}
                                            · {{ $item->kelas?->nama_kelas }}
                                            @if ($item->ruang_kelas) · {{ $item->ruang_kelas }} @endif
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="space-y-5">
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="section-title">Notifikasi terbaru</h3>
                    <p class="mt-0.5 text-[12px] text-ink-muted">Belum dibaca</p>
                </div>
                <a href="{{ route('notifikasi.index') }}" class="btn-ghost btn-sm">Semua</a>
            </div>
            @if ($notifikasi->isEmpty())
                <x-empty judul="Tidak ada notifikasi baru" ikon="bell" />
            @else
                <ul class="divide-y divide-line">
                    @foreach ($notifikasi as $item)
                        <li class="border-l-2 border-l-transparent px-5 py-3">
                            <p class="text-[12.5px] font-bold text-ink">{{ $item->judul }}</p>
                            <p class="mt-0.5 line-clamp-2 text-[12px] leading-relaxed text-ink-muted">{{ $item->pesan }}</p>
                            <p class="mt-1.5 font-mono text-[9.5px] uppercase tracking-[0.08em] text-ink-muted">{{ $item->dibuat_pada?->diffForHumans() }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="section-title">Jadwal piket</h3>
                    <p class="mt-0.5 text-[12px] text-ink-muted">Penugasan aktif Anda.</p>
                </div>
            </div>
            @if ($jadwalPiket->isEmpty())
                <x-empty judul="Tidak ada jadwal piket" ikon="shield-check" />
            @else
                <ul class="divide-y divide-line">
                    @foreach ($jadwalPiket as $item)
                        <li class="flex items-center gap-3 px-5 py-3">
                            <i data-lucide="shield-check" class="h-4 w-4 shrink-0 text-slateblue"></i>
                            <div class="min-w-0 flex-1">
                                <p class="text-[12.5px] font-bold capitalize text-ink">{{ $item->hari }}</p>
                                <p class="mt-0.5 font-mono text-[10px] text-ink-muted">
                                    {{ $item->tanggal_mulai_berlaku?->format('d/m/Y') }} – {{ $item->tanggal_berakhir?->format('d/m/Y') ?? 'sekarang' }}
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
@endsection
