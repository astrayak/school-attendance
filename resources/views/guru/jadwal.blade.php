@extends('layouts.app')

@section('judul', 'Jadwal Saya')
@section('deskripsi')
    {{ $versi?->nama_versi ?? 'Belum ada versi aktif' }} · {{ ucfirst($semester?->nama_semester ?? '-') }} {{ $semester?->tahunAjaran?->nama_tahun_ajaran }}
@endsection

@section('konten')
@if (! $semester)
    <div class="card mb-6">
        <x-empty judul="Belum ada semester aktif" pesan="Hubungi admin untuk mengaktifkan semester. Jadwal guru hanya tampil pada periode yang aktif." ikon="calendar-clock" />
    </div>
@endif

<form method="GET" action="{{ route('guru.jadwal.index') }}" class="card mb-6">
    <div class="card-body flex flex-wrap items-end gap-3">
        <div class="min-w-[240px] flex-1">
            <label for="semester" class="form-label">Periode</label>
            <select id="semester" name="semester" class="form-select">
                @foreach ($semesterList as $item)
                    <option value="{{ $item->id_semester }}" @selected((string) $idSemester === (string) $item->id_semester)>
                        {{ ucfirst($item->nama_semester) }} · {{ $item->tahunAjaran?->nama_tahun_ajaran }}
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-secondary">Tampilkan</button>
        <a href="{{ route('guru.jadwal.index') }}" class="btn-ghost">Periode aktif</a>
    </div>
</form>

<div class="grid gap-6 xl:grid-cols-3">
    <div class="space-y-5 xl:col-span-2">
        @if ($jadwal->isEmpty())
            <div class="card">
                <x-empty judul="Belum ada jadwal mengajar" :pesan="'Jadwal untuk '.$labelPeriode.' belum tersedia. Pilih periode lain atau hubungi admin.'" ikon="calendar-days" />
            </div>
        @else
            @foreach ($urutanHari as $hari)
                @continue (! $jadwal->has($hari))
                <div class="card overflow-hidden">
                    <div class="card-header">
                        <div class="flex items-center gap-2">
                            <span class="grid h-8 w-8 place-items-center rounded-sm bg-moss-soft text-moss">
                                <i data-lucide="calendar" class="h-4 w-4"></i>
                            </span>
                            <p class="text-sm font-semibold text-ink">{{ \App\Support\Status::HARI[$hari] }}</p>
                        </div>
                        <x-badge :teks="$jadwal[$hari]->count().' jadwal'" warna="bg-paper-deep text-ink-soft ring-ink-muted/20" />
                    </div>
                    <div class="divide-y divide-line">
                        @foreach ($jadwal[$hari] as $item)
                            <div class="flex flex-wrap items-center gap-4 px-5 py-4">
                                <div class="w-20 shrink-0">
                                    <p class="text-sm font-semibold text-ink">{{ substr($item->jam_mulai, 0, 5) }}</p>
                                    <p class="text-[11px] text-ink-muted">s.d. {{ substr($item->jam_selesai, 0, 5) }}</p>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-ink">{{ $item->mataPelajaran?->nama_mata_pelajaran }}</p>
                                    <p class="text-xs text-ink-muted">
                                        {{ $item->kelas?->nama_kelas }} · Urutan {{ $item->urutan_pelajaran }}
                                        @if ($item->ruang_kelas) · Ruang {{ $item->ruang_kelas }} @endif
                                    </p>
                                </div>
                                <x-badge :teks="$item->mataPelajaran?->kode_mata_pelajaran" warna="bg-paper-deep text-ink-soft ring-ink-muted/20" />
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <div class="space-y-6">
        <div class="card overflow-hidden">
            <div class="card-header">
                <div>
                    <p class="text-sm font-semibold text-ink">Piket semester ini</p>
                    <p class="mt-0.5 text-xs text-ink-muted">Hari penugasan piket Anda.</p>
                </div>
            </div>
            @if ($piket->isEmpty())
                <x-empty judul="Tidak ada jadwal piket" ikon="shield-check" />
            @else
                <ul class="divide-y divide-line">
                    @foreach ($piket as $item)
                        <li class="flex items-center gap-3 px-5 py-3">
                            <span class="grid h-8 w-8 place-items-center rounded-sm bg-slateblue-soft text-slateblue">
                                <i data-lucide="shield-check" class="h-4 w-4"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium capitalize text-ink">{{ $item->hari }}</p>
                                <p class="text-xs text-ink-muted">
                                    {{ $item->tanggal_mulai_berlaku?->format('d/m/Y') }} –
                                    {{ $item->tanggal_berakhir?->format('d/m/Y') ?? 'sekarang' }}
                                </p>
                            </div>
                            <a href="{{ route('guru.piket.index') }}" class="btn-ghost btn-sm">Buka</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="card">
            <div class="card-header">
                <p class="text-sm font-semibold text-ink">Informasi</p>
            </div>
            <div class="card-body space-y-2 text-xs text-ink-soft">
                <p class="flex gap-2"><i data-lucide="calendar-clock" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-ink-muted"></i> Jadwal hanya ditampilkan untuk semester aktif dan penugasan guru yang masih berlaku.</p>
                <p class="flex gap-2"><i data-lucide="info" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-ink-muted"></i> Jendela absensi terbuka 15 menit sebelum pelajaran berakhir.</p>
                <p class="flex gap-2"><i data-lucide="scan-line" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-ink-muted"></i> Scanner QR hanya aktif pada jendela waktu yang sama.</p>
                <p class="flex gap-2"><i data-lucide="shield-check" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-ink-muted"></i> Bila Anda piket, sesi yang terlewat dapat diambil alih dari menu Piket.</p>
            </div>
        </div>
    </div>
</div>
@endsection
