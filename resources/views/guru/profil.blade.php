@extends('layouts.app')

@section('judul', 'Profil Saya')
@section('deskripsi', 'Data identitas, akun, dan foto profil Anda.')

@section('aksi')
    <a href="{{ route('guru.jadwal.index') }}" class="btn-secondary btn-sm">
        <i data-lucide="calendar-days" class="h-3.5 w-3.5"></i>
        Jadwal Saya
    </a>
@endsection

@section('konten')
<div class="grid gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        <div class="card overflow-hidden">
            <div class="border-l-2 border-l-accent bg-paper px-6 py-6">
                <div class="flex flex-wrap items-center gap-5">
                    <x-avatar :pengguna="$guru->pengguna" :nama="$guru->nama_lengkap" ukuran="xl" />
                    <div class="min-w-0">
                        <p class="kicker">Profil guru</p>
                        <h2 class="mt-1 font-serif text-xl font-semibold tracking-tight text-ink">{{ $guru->nama_lengkap }}</h2>
                        <p class="mt-1 text-[12.5px] text-ink-muted">
                            {{ $guru->pengguna?->email ?? '—' }}
                            · {{ $guru->status_guru === 'aktif' ? 'Guru aktif' : 'Guru nonaktif' }}
                        </p>
                    </div>
                    <x-badge
                        :teks="ucfirst($guru->status_guru)"
                        :warna="$guru->status_guru === 'aktif' ? 'bg-moss-soft text-moss ring-moss/25' : 'bg-paper-deep text-ink-muted ring-line-strong'"
                    />
                </div>
            </div>

            <dl class="grid gap-x-6 gap-y-4 px-6 py-5 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-ink-muted">NIP</dt>
                    <dd class="mt-1 text-sm font-medium text-ink">{{ $guru->nip ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-ink-muted">NUPTK</dt>
                    <dd class="mt-1 text-sm font-medium text-ink">{{ $guru->nuptk ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-ink-muted">Jenis kelamin</dt>
                    <dd class="mt-1 text-sm font-medium text-ink">{{ $guru->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-ink-muted">Tempat, tanggal lahir</dt>
                    <dd class="mt-1 text-sm font-medium text-ink">
                        {{ $guru->tempat_lahir ?: '—' }}{{ $guru->tanggal_lahir ? ', '.$guru->tanggal_lahir->locale('id')->translatedFormat('d F Y') : '' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-ink-muted">Nomor telepon</dt>
                    <dd class="mt-1 text-sm font-medium text-ink">{{ $guru->nomor_telepon ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-ink-muted">Status akun</dt>
                    <dd class="mt-1">
                        <x-badge
                            :teks="$guru->pengguna?->status_aktif ? 'Aktif' : 'Nonaktif'"
                            :warna="$guru->pengguna?->status_aktif ? 'bg-moss-soft text-moss ring-moss/25' : 'bg-paper-deep text-ink-muted ring-line-strong'"
                        />
                    </dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-medium uppercase tracking-wide text-ink-muted">Alamat</dt>
                    <dd class="mt-1 text-sm text-ink-soft">{{ $guru->alamat ?: '—' }}</dd>
                </div>
            </dl>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="section-title">Kelas wali</h3>
                    <p class="mt-0.5 text-[12px] text-ink-muted">Kelas yang menjadi tanggung jawab wali Anda.</p>
                </div>
            </div>
            @if ($guru->kelasWali->isEmpty())
                <x-empty judul="Belum ada kelas wali" pesan="Anda belum ditugaskan sebagai wali kelas." ikon="school" />
            @else
                <ul class="divide-y divide-line">
                    @foreach ($guru->kelasWali as $kelasWali)
                        <li class="flex items-center gap-3 px-5 py-3">
                            <i data-lucide="school" class="h-4 w-4 shrink-0 text-ink-muted"></i>
                            <div class="min-w-0 flex-1">
                                <p class="text-[12.5px] font-bold text-ink">{{ $kelasWali->nama_kelas }}</p>
                                <p class="mt-0.5 text-[11.5px] text-ink-muted">{{ $kelasWali->tahunAjaran?->nama_tahun_ajaran ?? 'Tahun ajaran tidak tersedia' }}</p>
                            </div>
                            <x-badge :teks="$kelasWali->status_kelas === 'aktif' ? 'Aktif' : 'Nonaktif'"
                                :warna="$kelasWali->status_kelas === 'aktif' ? 'bg-moss-soft text-moss ring-moss/25' : 'bg-paper-deep text-ink-muted ring-line-strong'" />
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
                    <x-avatar :pengguna="$guru->pengguna" :nama="$guru->nama_lengkap" ukuran="lg" />
                    <div class="min-w-0">
                        <p class="text-[12.5px] font-bold text-ink">Foto saat ini</p>
                        <p class="mt-0.5 text-[11.5px] leading-relaxed text-ink-muted">JPG, PNG, atau WEBP · maksimal 2 MB</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('guru.profil.foto') }}" enctype="multipart/form-data" class="space-y-3 border-t border-line pt-4">
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
                <p class="text-sm font-semibold text-ink">Ringkasan mengajar</p>
            </div>
            <dl class="divide-y divide-line text-sm">
                <div class="flex items-center justify-between gap-3 px-5 py-3">
                    <dt class="text-ink-muted">Penugasan</dt>
                    <dd class="font-serif text-[18px] font-semibold tabular-nums text-ink">{{ $jumlahPenugasan }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3 px-5 py-3">
                    <dt class="text-ink-muted">Jadwal mengajar</dt>
                    <dd class="font-serif text-[18px] font-semibold tabular-nums text-ink">{{ $jumlahJadwal }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3 px-5 py-3">
                    <dt class="text-ink-muted">Jadwal piket aktif</dt>
                    <dd class="font-serif text-[18px] font-semibold tabular-nums text-ink">{{ $guru->jadwalPiket->count() }}</dd>
                </div>
            </dl>
            <div class="border-t border-line px-5 py-3">
                <a href="{{ route('guru.jadwal.index') }}" class="btn-secondary btn-sm w-full">Buka jadwal mengajar</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <p class="text-sm font-semibold text-ink">Akun</p>
            </div>
            <dl class="divide-y divide-line text-sm">
                <div class="px-5 py-3">
                    <dt class="text-xs font-medium uppercase tracking-wide text-ink-muted">Nama pengguna</dt>
                    <dd class="mt-1 font-medium text-ink">{{ $guru->pengguna?->nama_pengguna ?? '—' }}</dd>
                </div>
                <div class="px-5 py-3">
                    <dt class="text-xs font-medium uppercase tracking-wide text-ink-muted">Alamat email</dt>
                    <dd class="mt-1 break-all font-medium text-ink">{{ $guru->pengguna?->email ?? '—' }}</dd>
                </div>
                <div class="px-5 py-3">
                    <dt class="text-xs font-medium uppercase tracking-wide text-ink-muted">Terakhir masuk</dt>
                    <dd class="mt-1 font-medium text-ink">{{ $guru->pengguna?->terakhir_login?->locale('id')->translatedFormat('d F Y, H:i') ?? '—' }}</dd>
                </div>
            </dl>
        </div>
    </div>
</div>
@endsection