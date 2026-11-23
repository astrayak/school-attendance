@extends('layouts.app')

@section('judul', 'Guru Piket')
@section('deskripsi', 'Pantau sesi yang belum diisi dan ambil alih bila guru pengampu berhalangan.')

@section('aksi')
    <a href="{{ route('guru.scanner.index') }}" class="btn-secondary btn-sm"><i data-lucide="scan-line" class="h-4 w-4"></i> Scanner</a>
@endsection

@section('konten')
@if ($piketAktif)
    <div class="mb-6 flex flex-col gap-3 rounded-sm border border-slateblue-soft bg-slateblue-soft px-4 py-3 text-sm text-slateblue sm:flex-row sm:items-center">
        <i data-lucide="shield-check" class="h-5 w-5 shrink-0"></i>
        <div class="flex-1">
            <p class="font-semibold">Anda bertugas piket hari ini.</p>
            <p class="mt-0.5 text-slateblue">
                {{ $piketAktif->semester?->nama_semester ?? 'Semester aktif' }} ·
                berlaku {{ $piketAktif->tanggal_mulai_berlaku?->format('d/m/Y') }}–{{ $piketAktif->tanggal_berakhir?->format('d/m/Y') ?? 'seterusnya' }}
            </p>
        </div>
        <span class="inline-flex items-center gap-2 border border-line bg-surface px-2.5 py-1 font-mono text-[10px] uppercase tracking-[0.08em] text-slateblue">
            <span class="h-1.5 w-1.5 bg-slateblue"></span> Piket aktif
        </span>
    </div>
@else
    <div class="mb-6 flex items-start gap-3 rounded-sm border border-line bg-surface px-4 py-3 text-sm text-ink-soft">
        <i data-lucide="calendar-x" class="mt-0.5 h-5 w-5 shrink-0 text-ink-muted"></i>
        <p>Anda tidak terjadwal sebagai guru piket hari ini. Halaman ini tetap dapat dibuka untuk melihat status sesi, namun pengambilalihan hanya tersedia bagi guru piket yang bertugas.</p>
    </div>
@endif

<div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat-card label="Sesi Hari Ini" :nilai="$statistik['total_hari_ini']" ikon="calendar-days" warna="slate" />
    <x-stat-card label="Belum Diisi" :nilai="$statistik['belum_diisi']" ikon="clock" warna="amber" />
    <x-stat-card label="Perlu Tindakan" :nilai="$statistik['perlu_tindakan']" ikon="alert-triangle" warna="rose" keterangan="Sesi tanpa pelaksana" />
    <x-stat-card label="Diambil Alih" :nilai="$statistik['diambil_alih']" ikon="hand-helping" warna="violet" keterangan="Oleh Anda hari ini" />
</div>

<div class="card mb-6 overflow-hidden">
    <div class="card-header">
        <div>
            <p class="text-sm font-semibold text-ink">Sesi belum ditangani</p>
            <p class="mt-0.5 text-xs text-ink-muted">Sesi tanpa guru pelaksana atau yang masih menjadi tanggung jawab Anda.</p>
        </div>
        <span class="border border-line bg-ochre-soft px-2.5 py-1 font-mono text-[10px] uppercase tracking-[0.08em] text-ochre">{{ $sesiBelumDitangani->count() }} sesi</span>
    </div>

    @if (! $piketAktif)
        <x-empty judul="Hanya untuk guru piket" pesan="Anda perlu dijadwalkan sebagai guru piket pada hari ini untuk mengambil alih sesi." ikon="shield-off" />
    @elseif ($sesiBelumDitangani->isEmpty())
        <x-empty judul="Tidak ada sesi tertunda" pesan="Semua sesi hari ini sudah ditangani atau belum memasuki jendela absensi." ikon="check-circle-2" />
    @else
        <div class="divide-y divide-line">
            @foreach ($sesiBelumDitangani as $item)
                @php($terbuka = $item->sedangDibuka())
                <div class="px-5 py-4" x-data="{ buka: false }">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-sm bg-paper-deep text-ink-soft">
                                <i data-lucide="book-open" class="h-4 w-4"></i>
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-ink">{{ $item->jadwal?->mataPelajaran?->nama_mata_pelajaran ?? 'Sesi' }}</p>
                                <p class="mt-0.5 text-xs text-ink-muted">
                                    {{ $item->jadwal?->kelas?->nama_kelas }} ·
                                    {{ $item->waktu_mulai?->format('H:i') }}–{{ $item->waktu_selesai?->format('H:i') }} ·
                                    pengampu {{ $item->guruPenanggungJawab?->nama_lengkap ?? '—' }}
                                </p>
                                <p class="mt-1 text-[11px] text-ink-muted">
                                    Jendela {{ $item->waktu_absensi_dibuka?->format('H:i') }}–{{ $item->waktu_absensi_ditutup?->format('H:i') }}
                                    @if ($item->id_guru_pelaksana)
                                        · pelaksana saat ini {{ $item->guruPelaksana?->nama_lengkap ?? '—' }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-status :status="$item->status_sesi" kelompok="SESI" />
                            @if ($terbuka)
                                <button type="button" class="btn-primary btn-sm" @click="buka = !buka" x-text="buka ? 'Tutup' : 'Ambil Alih'">Ambil Alih</button>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-sm bg-paper-deep px-2.5 py-1 text-xs font-medium text-ink-muted">
                                    <i data-lucide="lock" class="h-3.5 w-3.5"></i> Di luar jendela
                                </span>
                            @endif
                        </div>
                    </div>

                    @if ($terbuka)
                        <form x-cloak x-show="buka" x-transition method="POST" action="{{ route('guru.piket.ambil-alih', $item) }}" class="mt-4 grid gap-3 rounded-sm border border-line bg-paper/70 p-4 sm:grid-cols-[1fr_200px_auto]">
                            @csrf
                            <div>
                                <label for="alasan-{{ $item->id_sesi_absensi }}" class="form-label">Alasan pengambilalihan <span class="text-clay">*</span></label>
                                <input type="text" id="alasan-{{ $item->id_sesi_absensi }}" name="alasan" class="form-input" required minlength="5" maxlength="255" placeholder="Contoh: guru pengampu berhalangan hadir">
                            </div>
                            <div>
                                <label for="tujuan-{{ $item->id_sesi_absensi }}" class="form-label">Lanjut ke</label>
                                <select id="tujuan-{{ $item->id_sesi_absensi }}" name="tujuan" class="form-select">
                                    <option value="absensi">Pengisian manual</option>
                                    <option value="scanner">Scanner QR</option>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="btn-primary w-full" onclick="return confirm('Ambil alih sesi ini? Guru pengampu akan menerima notifikasi.')">
                                    <i data-lucide="hand-helping" class="h-4 w-4"></i> Konfirmasi
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>

<div class="card overflow-hidden">
    <div class="card-header">
        <div>
            <p class="text-sm font-semibold text-ink">Sesi yang Anda ambil alih</p>
            <p class="mt-0.5 text-xs text-ink-muted">Sesi hari ini dengan pelaksana Anda setelah pengambilalihan.</p>
        </div>
        <a href="{{ route('guru.sesi.index') }}" class="btn-ghost btn-sm">Semua Sesi</a>
    </div>

    @if ($sesiDiambil->isEmpty())
        <x-empty judul="Belum ada pengambilalihan" pesan="Sesi yang Anda ambil alih hari ini akan tercantum di sini." ikon="hand-helping" />
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-line">
                <thead class="table-head">
                    <tr>
                        <th class="px-5 py-3">Waktu</th>
                        <th class="px-4 py-3">Kelas & mapel</th>
                        <th class="px-4 py-3">Diambil alih</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line bg-surface">
                    @foreach ($sesiDiambil as $item)
                        <tr class="hover:bg-paper/70">
                            <td class="px-5 py-3 text-sm text-ink-soft">
                                <p class="font-medium text-ink">{{ $item->waktu_mulai?->format('H:i') }}–{{ $item->waktu_selesai?->format('H:i') }}</p>
                                <p class="text-xs text-ink-muted">{{ $item->tanggal_pelajaran?->format('d/m/Y') }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-sm font-semibold text-ink">{{ $item->jadwal?->mataPelajaran?->nama_mata_pelajaran ?? '—' }}</p>
                                <p class="text-xs text-ink-muted">{{ $item->jadwal?->kelas?->nama_kelas }}</p>
                            </td>
                            <td class="table-cell">
                                <p>{{ $item->diambil_alih_pada?->format('H:i') ?? '—' }}</p>
                                <p class="max-w-xs truncate text-xs text-ink-muted" title="{{ $item->alasan_pengambilalihan }}">{{ $item->alasan_pengambilalihan ?: '—' }}</p>
                            </td>
                            <td class="px-4 py-3"><x-status :status="$item->status_sesi" kelompok="SESI" /></td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('guru.sesi.show', $item) }}" class="btn-secondary btn-sm">Detail</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
