@extends('layouts.app')

@section('judul', 'Profil Saya')
@section('deskripsi', 'Data identitas, kelas, dan akun Anda.')

@section('aksi')
    <a href="{{ route('siswa.qr') }}" class="btn-primary btn-sm"><i data-lucide="qr-code" class="h-4 w-4"></i> QR Code</a>
@endsection

@section('konten')
<div class="grid gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        <div class="card overflow-hidden">
            <div class="border-l-2 border-l-accent bg-paper px-6 py-6">
                <div class="flex flex-wrap items-center gap-5">
                    <x-avatar :pengguna="$siswa->pengguna" :nama="$siswa->nama_lengkap" ukuran="lg" />
                    <div class="min-w-0">
                        <p class="kicker">Profil siswa</p>
                        <h2 class="mt-1 font-serif text-xl font-semibold text-ink">{{ $siswa->nama_lengkap }}</h2>
                        <p class="mt-1 text-[12.5px] text-ink-muted">
                            {{ $kelas?->nama_kelas ?? 'Belum memiliki kelas' }} · NIS {{ $siswa->nis }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="grid gap-x-6 gap-y-4 px-6 py-5 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">NISN</p>
                    <p class="mt-1 text-sm font-medium text-ink">{{ $siswa->nisn ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Jenis kelamin</p>
                    <p class="mt-1 text-sm font-medium text-ink">{{ $siswa->jenis_kelamin === 'L' ? 'Laki-laki' : ($siswa->jenis_kelamin === 'P' ? 'Perempuan' : '—') }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Tempat, tanggal lahir</p>
                    <p class="mt-1 text-sm font-medium text-ink">
                        {{ $siswa->tempat_lahir ?: '—' }}{{ $siswa->tanggal_lahir ? ', '.$siswa->tanggal_lahir->locale('id')->translatedFormat('d F Y') : '' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Agama</p>
                    <p class="mt-1 text-sm font-medium text-ink">{{ $siswa->agama ?: '—' }}</p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Alamat</p>
                    <p class="mt-1 text-sm text-ink-soft">{{ $siswa->alamat ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Nomor telepon</p>
                    <p class="mt-1 text-sm font-medium text-ink">{{ $siswa->nomor_telepon ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Status siswa</p>
                    <p class="mt-1"><x-badge :teks="ucfirst($siswa->status_siswa)" :warna="$siswa->status_siswa === 'aktif' ? 'bg-moss-soft text-moss ring-moss/20' : 'bg-paper-deep text-ink-soft ring-ink-muted/20'" /></p>
                </div>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="card-header">
                <div>
                    <p class="text-sm font-semibold text-ink">Riwayat kelas</p>
                    <p class="mt-0.5 text-xs text-ink-muted">Perpindahan kelas tetap tersimpan untuk menjaga keutuhan data absensi.</p>
                </div>
            </div>
            @if ($siswa->riwayatKelas->isEmpty())
                <x-empty judul="Belum ada kelas" pesan="Anda belum ditempatkan pada kelas mana pun." ikon="school" />
            @else
                <ul class="divide-y divide-line">
                    @foreach ($siswa->riwayatKelas->sortByDesc('tanggal_mulai') as $riwayat)
                        <li class="flex flex-wrap items-center gap-3 px-5 py-3">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-sm bg-paper-deep text-ink-soft">
                                <i data-lucide="school" class="h-4 w-4"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-ink">{{ $riwayat->kelas?->nama_kelas ?? '—' }}</p>
                                <p class="text-xs text-ink-muted">
                                    {{ $riwayat->tahunAjaran?->nama_tahun_ajaran ?? $riwayat->kelas?->tahunAjaran?->nama_tahun_ajaran ?? '—' }} ·
                                    {{ $riwayat->tanggal_mulai?->format('d/m/Y') }}–{{ $riwayat->tanggal_selesai?->format('d/m/Y') ?? 'sekarang' }}
                                </p>
                            </div>
                            <x-badge :teks="$riwayat->status_aktif ? 'Aktif' : 'Selesai'" :warna="$riwayat->status_aktif ? 'bg-moss-soft text-moss ring-moss/20' : 'bg-paper-deep text-ink-soft ring-ink-muted/20'" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="space-y-6">
        <div class="card">
            <div class="card-header">
                <p class="text-sm font-semibold text-ink">Foto profil</p>
            </div>
            <div class="card-body space-y-4">
                <div class="flex items-center gap-4">
                    <x-avatar :pengguna="$siswa->pengguna" :nama="$siswa->nama_lengkap" ukuran="xl" />
                    <div class="min-w-0">
                        <p class="text-[12.5px] font-bold text-ink">Foto saat ini</p>
                        <p class="mt-0.5 text-[11.5px] leading-relaxed text-ink-muted">JPG, PNG, atau WEBP · maksimal 2 MB</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('siswa.profil.foto') }}" enctype="multipart/form-data" class="space-y-3 border-t border-line pt-4">
                    @csrf
                    <div>
                        <label for="foto" class="form-label">Ganti foto</label>
                        <input id="foto" name="foto" type="file" accept="image/jpeg,image/png,image/webp"
                            class="form-input text-[12px] file:mr-3 file:border file:border-line file:bg-paper file:px-2.5 file:py-1 file:text-[11px] file:font-bold file:text-ink">
                        @error('foto')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="btn-primary btn-sm w-full">
                        <i data-lucide="upload" class="h-3.5 w-3.5"></i>
                        Simpan foto
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <p class="text-sm font-semibold text-ink">Akun</p>
            </div>
            <dl class="divide-y divide-line text-sm">
                <div class="px-5 py-3">
                    <dt class="text-xs font-medium uppercase tracking-wide text-ink-muted">Nama pengguna</dt>
                    <dd class="mt-1 font-medium text-ink">{{ $siswa->pengguna?->nama_pengguna ?? '—' }}</dd>
                </div>
                <div class="px-5 py-3">
                    <dt class="text-xs font-medium uppercase tracking-wide text-ink-muted">Alamat email</dt>
                    <dd class="mt-1 break-all font-medium text-ink">{{ $siswa->pengguna?->email ?? '—' }}</dd>
                </div>
                <div class="px-5 py-3">
                    <dt class="text-xs font-medium uppercase tracking-wide text-ink-muted">Terakhir masuk</dt>
                    <dd class="mt-1 font-medium text-ink">{{ $siswa->pengguna?->terakhir_masuk?->locale('id')->translatedFormat('d F Y, H:i') ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        <div class="card">
            <div class="card-header">
                <p class="text-sm font-semibold text-ink">Orang tua / wali</p>
            </div>
            <dl class="divide-y divide-line text-sm">
                <div class="px-5 py-3">
                    <dt class="text-xs font-medium uppercase tracking-wide text-ink-muted">Nama</dt>
                    <dd class="mt-1 font-medium text-ink">{{ $siswa->nama_orang_tua ?: '—' }}</dd>
                </div>
                <div class="px-5 py-3">
                    <dt class="text-xs font-medium uppercase tracking-wide text-ink-muted">Nomor telepon</dt>
                    <dd class="mt-1 font-medium text-ink">{{ $siswa->nomor_telepon_orang_tua ?: '—' }}</dd>
                </div>
            </dl>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <p class="text-sm font-semibold text-ink">QR Code absensi</p>
                    <p class="mt-0.5 text-xs text-ink-muted">QR hanya berisi token acak, tanpa data pribadi.</p>
                </div>
            </div>
            <div class="card-body">
                @if ($siswa->kodeQrAktif)
                    <div class="flex items-center gap-2 rounded-sm bg-moss-soft px-3 py-2 text-xs font-medium text-moss ring-1 ring-inset ring-moss/15">
                        <i data-lucide="shield-check" class="h-4 w-4"></i>
                        QR aktif sejak {{ $siswa->kodeQrAktif->tanggal_dibuat?->format('d/m/Y') }}
                    </div>
                @else
                    <div class="flex items-center gap-2 rounded-sm bg-paper px-3 py-2 text-xs font-medium text-ink-soft ring-1 ring-inset ring-line">
                        <i data-lucide="qr-code" class="h-4 w-4"></i>
                        QR akan dibuat saat halaman QR dibuka.
                    </div>
                @endif
                <a href="{{ route('siswa.qr') }}" class="btn-primary mt-3 w-full">
                    <i data-lucide="qr-code" class="h-4 w-4"></i> Buka QR Code
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
