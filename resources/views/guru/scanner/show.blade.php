@extends('layouts.app')

@section('judul', 'Pindai QR Siswa')
@section('deskripsi')
    {{ $sesi->jadwal?->mataPelajaran?->nama_mata_pelajaran }} · {{ $sesi->jadwal?->kelas?->nama_kelas }} · {{ $sesi->tanggal_pelajaran?->format('d/m/Y') }}
@endsection

@section('aksi')
    <a href="{{ route('guru.absensi.show', $sesi) }}" class="btn-secondary btn-sm"><i data-lucide="clipboard-list" class="h-4 w-4"></i> Isi Manual</a>
    <a href="{{ route('guru.scanner.index') }}" class="btn-ghost btn-sm">Kembali</a>
@endsection

@section('konten')
@php
    $awal = $catatan->map(fn ($baris) => [
        'nama_lengkap' => $baris->siswa?->nama_lengkap ?? 'Siswa',
        'nis' => $baris->siswa?->nis ?? '—',
        'waktu' => $baris->waktu_kehadiran?->format('H:i:s'),
        'status' => $baris->status_kehadiran === \App\Models\CatatanAbsensi::HADIR ? 'hadir' : 'lainnya',
        'pesan' => \App\Support\Status::label('KEHADIRAN', $baris->status_kehadiran),
    ])->values();

    $konfigurasiPemindai = [
        'url' => route('guru.scanner.pindai', $sesi),
        'csrf' => csrf_token(),
        'pesan' => 'Kamera belum diaktifkan. Tekan "Mulai Kamera" atau masukkan token secara manual.',
        'ringkasan' => [
            'total' => $siswa->count(),
            'terisi' => $catatan->count(),
            'hadir' => $hadir,
        ],
        'awal' => $awal,
    ];
@endphp

<div class="grid gap-6 xl:grid-cols-3"
     x-data="pemindaiQr(window.konfigurasiPemindai)">
    <div class="space-y-6 xl:col-span-2">
        <div class="card overflow-hidden">
            <div class="border-l-2 border-l-accent bg-paper px-6 py-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="kicker">Scanner kehadiran</p>
                        <h2 class="mt-1.5 font-serif text-xl font-semibold text-ink">{{ $sesi->jadwal?->mataPelajaran?->nama_mata_pelajaran ?? 'Sesi' }}</h2>
                        <p class="mt-1 text-[12.5px] text-ink-muted">
                            {{ $sesi->jadwal?->kelas?->nama_kelas }} ·
                            {{ $sesi->waktu_mulai?->format('H:i') }}–{{ $sesi->waktu_selesai?->format('H:i') }} ·
                            {{ $sesi->guruPelaksana?->nama_lengkap ?? $sesi->guruPenanggungJawab?->nama_lengkap }}
                        </p>
                    </div>
                    <div class="border border-line bg-surface px-3 py-2 text-right">
                        <p class="kicker">Jendela absensi</p>
                        <p class="mt-1 font-mono text-[12px] font-medium tabular-nums text-ink">{{ $sesi->waktu_absensi_dibuka?->format('H:i') }}–{{ $sesi->waktu_absensi_ditutup?->format('H:i') }}</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 border-b border-line px-6 py-4 sm:grid-cols-3">
                <div class="rounded-sm bg-paper px-4 py-3 ring-1 ring-inset ring-line/70">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-ink-muted">Total siswa</p>
                    <p class="mt-1 text-2xl font-semibold text-ink" x-text="ringkasan.total">{{ $siswa->count() }}</p>
                </div>
                <div class="rounded-sm bg-moss-soft px-4 py-3 ring-1 ring-inset ring-moss-soft/70">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-moss">Hadir</p>
                    <p class="mt-1 text-2xl font-semibold text-moss" x-text="ringkasan.hadir">{{ $hadir }}</p>
                </div>
                <div class="rounded-sm bg-ochre-soft px-4 py-3 ring-1 ring-inset ring-ochre-soft/70">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-ochre">Belum tercatat</p>
                    <p class="mt-1 text-2xl font-semibold text-ochre" x-text="Math.max(ringkasan.total - ringkasan.terisi, 0)">{{ max($siswa->count() - $catatan->count(), 0) }}</p>
                </div>
            </div>

            <div class="card-body space-y-4">
                <div class="relative overflow-hidden rounded-sm bg-ink ring-1 ring-inset ring-ink-soft">
                    <div class="aspect-video w-full">
                        <video x-ref="video" class="h-full w-full object-cover" playsinline muted></video>
                    </div>
                    <div class="pointer-events-none absolute inset-0 grid place-items-center" x-show="!memindai">
                        <div class="bg-ink/85 px-6 py-5 text-center text-line ring-1 ring-inset ring-paper/10">
                            <i data-lucide="scan-line" class="mx-auto h-10 w-10 text-moss"></i>
                            <p class="mt-2 text-sm font-medium">Kamera belum aktif</p>
                            <p class="mt-0.5 text-xs text-ink-muted">Tekan tombol mulai di bawah untuk memindai.</p>
                        </div>
                    </div>
                    <div class="absolute left-3 top-3 inline-flex items-center gap-2 bg-ink/75 px-2.5 py-1 font-mono text-[10px] uppercase tracking-[0.08em] text-paper ring-1 ring-inset ring-paper/15" x-show="memindai" x-cloak>
                        <span class="h-1.5 w-1.5 animate-none bg-moss"></span> Kamera aktif
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="button" class="btn-primary" @click="mulai()" x-show="!memindai">
                        <i data-lucide="camera" class="h-4 w-4"></i> Mulai Kamera
                    </button>
                    <button type="button" class="btn-secondary" @click="hentikan()" x-show="memindai" x-cloak>
                        <i data-lucide="camera-off" class="h-4 w-4"></i> Hentikan Kamera
                    </button>
                    <span class="inline-flex items-center gap-2 rounded-sm bg-paper-deep px-3 py-2 text-xs font-medium text-ink-soft" x-show="memproses" x-cloak>
                        <i data-lucide="loader-2" class="h-3.5 w-3.5 animate-spin"></i> Memproses…
                    </span>
                </div>

                <div class="rounded-sm border px-4 py-3 text-sm"
                     :class="{
                         'border-moss-soft bg-moss-soft text-moss': jenis === 'sukses',
                         'border-clay-soft bg-clay-soft text-clay': jenis === 'error',
                         'border-slateblue-soft bg-slateblue-soft text-slateblue': jenis === 'info',
                     }"
                     role="status" aria-live="polite">
                    <p x-text="pesan">{{ $konfigurasiPemindai['pesan'] }}</p>
                </div>

                <form class="rounded-sm border border-dashed border-line-strong bg-paper/70 p-4" @submit.prevent="kirim($refs.tokenManual.value); $refs.tokenManual.value = ''">
                    <label for="token_manual" class="form-label">Input token manual</label>
                    <p class="mb-2 text-xs text-ink-muted">Gunakan bila kamera tidak tersedia. Token dapat diminta siswa dari halaman QR Code miliknya.</p>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <input type="text" id="token_manual" x-ref="tokenManual" class="form-input flex-1 font-mono text-xs" maxlength="160" minlength="10" placeholder="Tempel token QR di sini" autocomplete="off">
                        <button type="submit" class="btn-secondary" :disabled="memproses">
                            <i data-lucide="send" class="h-4 w-4"></i> Catat Kehadiran
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="card-header">
                <div>
                    <p class="text-sm font-semibold text-ink">Riwayat pemindaian</p>
                    <p class="mt-0.5 text-xs text-ink-muted">15 pemindaian terakhir pada sesi ini.</p>
                </div>
            </div>
            <template x-if="hasil.length === 0">
                <x-empty judul="Belum ada pemindaian" pesan="Hasil pemindaian QR akan tampil di sini secara langsung." ikon="scan-line" />
            </template>
            <ul class="divide-y divide-line" x-show="hasil.length > 0">
                <template x-for="(item, indeks) in hasil" :key="indeks">
                    <li class="flex items-center gap-3 px-5 py-3">
                        <span class="grid h-9 w-9 shrink-0 place-items-center border border-line"
                              :class="item.status === 'hadir' ? 'bg-moss-soft text-moss' : 'bg-paper-deep text-ink-muted'">
                            <i data-lucide="user-check" class="h-4 w-4"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-ink" x-text="item.nama_lengkap"></p>
                            <p class="text-xs text-ink-muted">NIS <span x-text="item.nis"></span> · <span x-text="item.pesan"></span></p>
                        </div>
                        <span class="shrink-0 text-xs font-medium text-ink-muted" x-text="item.waktu ?? '—'"></span>
                    </li>
                </template>
            </ul>
        </div>
    </div>

    <div class="space-y-6">
        <div class="card">
            <div class="card-header">
                <div>
                    <p class="text-sm font-semibold text-ink">Finalisasi sesi</p>
                    <p class="mt-0.5 text-xs text-ink-muted">Semua siswa harus tercatat sebelum finalisasi.</p>
                </div>
            </div>
            <div class="card-body space-y-3">
                <div class="flex items-center justify-between rounded-sm bg-paper px-4 py-3 text-sm">
                    <span class="text-ink-soft">Tercatat</span>
                    <span class="font-semibold text-ink"><span x-text="ringkasan.terisi">{{ $catatan->count() }}</span> / <span x-text="ringkasan.total">{{ $siswa->count() }}</span></span>
                </div>
                <p class="text-xs text-ink-muted">
                    Finalisasi menyimpan absensi QR dan mengirim permintaan konfirmasi pembelajaran ke petugas siswa. Pastikan seluruh siswa telah dipindai.
                </p>
                <form method="POST" action="{{ route('guru.scanner.finalisasi', $sesi) }}">
                    @csrf
                    <button type="submit"
                            class="btn-primary w-full"
                            :disabled="ringkasan.total === 0 || ringkasan.terisi < ringkasan.total"
                            onclick="return confirm('Finalisasi absensi QR sesi ini?')">
                        <i data-lucide="check-check" class="h-4 w-4"></i> Finalisasi Absensi
                    </button>
                    <p class="mt-2 text-center text-[11px] text-ink-muted" x-show="ringkasan.terisi < ringkasan.total">
                        Lengkapi <span x-text="Math.max(ringkasan.total - ringkasan.terisi, 0)"></span> siswa lagi atau gunakan pengisian manual.
                    </p>
                </form>
                <a href="{{ route('guru.absensi.show', $sesi) }}" class="btn-ghost w-full">Lengkapi Secara Manual</a>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="card-header">
                <div>
                    <p class="text-sm font-semibold text-ink">Siswa belum tercatat</p>
                    <p class="mt-0.5 text-xs text-ink-muted">Daftar awal saat halaman dimuat.</p>
                </div>
                <span class="border border-line bg-ochre-soft px-2.5 py-1 font-mono text-[10px] uppercase tracking-[0.08em] text-ochre">{{ $belum->count() }} siswa</span>
            </div>
            @if ($belum->isEmpty())
                <x-empty judul="Semua sudah tercatat" pesan="Seluruh siswa pada kelas ini sudah memiliki catatan absensi." ikon="party-popper" />
            @else
                <ul class="max-h-80 divide-y divide-line overflow-y-auto">
                    @foreach ($belum as $murid)
                        <li class="flex items-center gap-3 px-5 py-2.5">
                            <span class="grid h-8 w-8 shrink-0 place-items-center border border-line bg-paper-deep font-mono text-[10px] font-medium text-ink-soft">
                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($murid->nama_lengkap, 0, 1)) }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm text-ink-soft">{{ $murid->nama_lengkap }}</p>
                                <p class="text-[11px] text-ink-muted">NIS {{ $murid->nis }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
@endsection

@push('skrip')
<script>
    window.konfigurasiPemindai = {{ \Illuminate\Support\Js::from($konfigurasiPemindai) }};
</script>
@endpush
