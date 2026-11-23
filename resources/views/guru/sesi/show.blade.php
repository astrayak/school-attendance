@extends('layouts.app')

@section('judul', 'Detail Sesi')
@section('deskripsi')
    {{ $sesi->jadwal?->mataPelajaran?->nama_mata_pelajaran }} · {{ $sesi->jadwal?->kelas?->nama_kelas }} · {{ $sesi->tanggal_pelajaran?->format('d/m/Y') }}
@endsection

@section('aksi')
    @if ($bolehMengisi)
        <a href="{{ route('guru.scanner.show', $sesi) }}" class="btn-secondary btn-sm"><i data-lucide="scan-line" class="h-4 w-4"></i> Scanner</a>
        <a href="{{ route('guru.absensi.show', $sesi) }}" class="btn-primary btn-sm"><i data-lucide="clipboard-check" class="h-4 w-4"></i> Isi Absensi</a>
    @endif
    <a href="{{ route('guru.sesi.index', ['tanggal' => $sesi->tanggal_pelajaran?->toDateString()]) }}" class="btn-ghost btn-sm">Kembali</a>
@endsection

@section('konten')
<div class="grid gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        <div class="card overflow-hidden">
            <div class="border-l-2 border-l-accent bg-paper px-6 py-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="kicker">Sesi pembelajaran</p>
                        <h2 class="mt-1.5 font-serif text-xl font-semibold text-ink">{{ $sesi->jadwal?->mataPelajaran?->nama_mata_pelajaran ?? '—' }}</h2>
                        <p class="mt-1 text-[12.5px] text-ink-muted">
                            {{ $sesi->jadwal?->kelas?->nama_kelas }} · {{ $sesi->tanggal_pelajaran?->locale('id')->translatedFormat('l, d F Y') }}
                            · {{ $sesi->waktu_mulai?->format('H:i') }}–{{ $sesi->waktu_selesai?->format('H:i') }}
                        </p>
                    </div>
                    <x-status :status="$sesi->status_sesi" kelompok="SESI" />
                </div>
                <div class="mt-5 grid gap-3 sm:grid-cols-3">
                    <div class="border border-line bg-surface px-3 py-2">
                        <p class="kicker">Guru pelaksana</p>
                        <p class="mt-1 text-[12.5px] font-bold text-ink">{{ $sesi->guruPelaksana?->nama_lengkap ?? 'Belum ditentukan' }}</p>
                    </div>
                    <div class="border border-line bg-surface px-3 py-2">
                        <p class="kicker">Jendela absensi</p>
                        <p class="mt-1 font-mono text-[11.5px] font-medium tabular-nums text-ink">{{ $sesi->waktu_absensi_dibuka?->format('H:i') }} – {{ $sesi->waktu_absensi_ditutup?->format('H:i') }}</p>
                    </div>
                    <div class="border border-line bg-surface px-3 py-2">
                        <p class="kicker">Batas konfirmasi</p>
                        <p class="mt-1 font-mono text-[11.5px] font-medium tabular-nums text-ink">{{ $sesi->waktu_batas_konfirmasi?->format('H:i') ?? '—' }}</p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-5">
                @if (! $bolehMengisi)
                    <div class="rounded-sm border border-line bg-paper px-4 py-3 text-sm text-ink-soft">
                        @if ($sesi->waktu_disimpan)
                            Absensi sudah disimpan. Perubahan hanya dapat dilakukan hingga jendela absensi ditutup.
                        @else
                            Sesi belum dapat diisi saat ini. Pastikan jendela absensi terbuka dan Anda berhak atas sesi ini.
                        @endif
                    </div>
                @endif
                @if ($bolehMengisi)
                    <div class="rounded-sm border border-moss-soft bg-moss-soft px-4 py-3 text-sm text-moss">
                        Jendela absensi sedang terbuka. Anda dapat mengisi manual atau menggunakan scanner QR.
                    </div>
                @endif
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="card-header">
                <div>
                    <p class="text-sm font-semibold text-ink">Kehadiran siswa</p>
                    <p class="mt-0.5 text-xs text-ink-muted">{{ $catatan->count() }} dari {{ $siswa->count() }} siswa sudah dicatat.</p>
                </div>
            </div>
            @if ($siswa->isEmpty())
                <x-empty judul="Tidak ada siswa" ikon="users" />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line">
                        <thead class="table-head">
                            <tr>
                                <th class="px-5 py-3">Siswa</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Metode</th>
                                <th class="px-4 py-3">Waktu</th>
                                <th class="px-5 py-3">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($siswa as $murid)
                                @php($baris = $catatan->get($murid->id_siswa))
                                <tr>
                                    <td class="px-5 py-3">
                                        <p class="text-sm font-medium text-ink">{{ $murid->nama_lengkap }}</p>
                                        <p class="text-xs text-ink-muted">NIS {{ $murid->nis }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($baris)
                                            <x-status :status="$baris->status_kehadiran" kelompok="KEHADIRAN" />
                                        @else
                                            <x-badge teks="Belum diisi" warna="bg-paper-deep text-ink-soft ring-ink-muted/20" />
                                        @endif
                                    </td>
                                    <td class="table-cell">{{ $baris ? \App\Support\Status::label('METODE', $baris->metode_absensi) : '—' }}</td>
                                    <td class="table-cell">{{ $baris?->waktu_kehadiran?->format('H:i:s') ?? '—' }}</td>
                                    <td class="px-5 py-3 text-xs text-ink-muted">{{ $baris?->keterangan ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="space-y-6">
        <div class="card overflow-hidden">
            <div class="card-header">
                <div>
                    <p class="text-sm font-semibold text-ink">Konfirmasi pembelajaran</p>
                    <p class="mt-0.5 text-xs text-ink-muted">Dikirim ke petugas siswa setelah absensi disimpan.</p>
                </div>
            </div>
            @if ($sesi->permintaanKonfirmasi->isEmpty())
                <x-empty judul="Belum ada permintaan" ikon="badge-check" />
            @else
                <ul class="divide-y divide-line">
                    @foreach ($sesi->permintaanKonfirmasi->sortBy('urutan_penerima') as $permintaan)
                        <li class="px-5 py-3">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-medium text-ink">{{ $permintaan->siswaPenerima?->nama_lengkap ?? '—' }}</p>
                                <x-status :status="$permintaan->status_permintaan" kelompok="KONFIRMASI" />
                            </div>
                            <p class="mt-0.5 text-xs text-ink-muted">
                                Prioritas {{ $permintaan->urutan_penerima }} · batas {{ $permintaan->waktu_kedaluwarsa?->format('H:i:s') }}
                            </p>
                            @if ($permintaan->jawaban)
                                <p class="mt-1 text-xs font-medium text-ink-soft">{{ \App\Support\Status::label('JAWABAN', $permintaan->jawaban) }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="card overflow-hidden">
            <div class="card-header">
                <p class="text-sm font-semibold text-ink">Audit perubahan</p>
            </div>
            @if ($audit->isEmpty())
                <x-empty judul="Belum ada perubahan" pesan="Perubahan status setelah absensi disimpan akan muncul di sini." ikon="history" />
            @else
                <ul class="divide-y divide-line">
                    @foreach ($audit as $daftarAudit)
                        @foreach ($daftarAudit as $perubahan)
                            <li class="px-5 py-3">
                                <p class="text-xs text-ink-soft">
                                    {{ \App\Support\Status::label('KEHADIRAN', $perubahan->status_sebelumnya) }} →
                                    <span class="font-semibold text-ink">{{ \App\Support\Status::label('KEHADIRAN', $perubahan->status_sesudahnya) }}</span>
                                </p>
                                <p class="mt-0.5 text-[11px] text-ink-muted">
                                    {{ $perubahan->waktu_perubahan?->format('d/m/Y H:i') }} · {{ $perubahan->pengguna?->nama_pengguna }}
                                </p>
                                @if ($perubahan->alasan_perubahan)
                                    <p class="mt-0.5 text-[11px] text-ink-muted">{{ $perubahan->alasan_perubahan }}</p>
                                @endif
                            </li>
                        @endforeach
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
@endsection
