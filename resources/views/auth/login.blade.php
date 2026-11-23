@extends('layouts.guest')

@section('judul', 'Masuk')

@section('konten')
<div class="grid min-h-screen lg:grid-cols-[1.08fr_0.92fr]">

    {{-- Masthead: the register cover --}}
    <section class="relative hidden border-r border-line bg-paper-deep lg:flex lg:flex-col">
        <div class="paper-lines absolute inset-0 opacity-60" aria-hidden="true"></div>

        <div class="relative flex flex-1 flex-col justify-between px-12 py-10 xl:px-16">
            <header class="flex items-start justify-between gap-8">
                <div class="flex items-center gap-3.5">
                    <span class="grid h-11 w-11 shrink-0 place-items-center border border-ink font-serif text-[17px] font-semibold leading-none text-ink">AS</span>
                    <div>
                        <p class="font-serif text-[19px] font-semibold leading-tight text-ink">Absensi Sekolah</p>
                        <p class="mt-1 font-mono text-[9px] uppercase tracking-[0.2em] text-ink-muted">Buku Induk Digital</p>
                    </div>
                </div>

                <div class="stamp relative hidden shrink-0 text-accent xl:inline-grid" aria-hidden="true">
                    Buku Induk<br>Kehadiran<br>{{ now()->year }}
                </div>
            </header>

            <div class="max-w-[34rem] py-12">
                <div class="flex items-center gap-2.5">
                    <span class="h-px w-8 bg-accent"></span>
                    <p class="font-mono text-[10px] uppercase tracking-[0.22em] text-accent">Tata kelola kehadiran</p>
                </div>

                <h1 class="mt-5 font-serif text-[42px] font-semibold leading-[1.08] tracking-[-0.02em] text-ink xl:text-[48px]">
                    Setiap kehadiran tercatat pada barisnya.
                </h1>

                <p class="mt-5 max-w-md text-[15px] leading-relaxed text-ink-soft">
                    Jadwal, absensi manual maupun QR Code, konfirmasi pembelajaran, dan rekap kehadiran dalam satu catatan sekolah yang transparan.
                </p>

                <dl class="mt-10 divide-y divide-line border-y border-line">
                    @foreach ([
                        ['01', 'Jadwal & Sesi', 'Sesi dibuat otomatis mengikuti jadwal aktif.'],
                        ['02', 'QR Code Aman', 'Token di-hash, tanpa data pribadi di dalam kode.'],
                        ['03', 'Guru Piket', 'Pengambilalihan sesi tercatat beserta alasannya.'],
                        ['04', 'Konfirmasi Siswa', 'Validasi pembelajaran berjalan berlapis.'],
                    ] as $fitur)
                        <div class="flex gap-5 py-3.5">
                            <span class="w-7 shrink-0 pt-0.5 font-mono text-[10px] tabular-nums text-ink-muted">{{ $fitur[0] }}</span>
                            <div class="min-w-0">
                                <dt class="text-[13px] font-bold text-ink">{{ $fitur[1] }}</dt>
                                <dd class="mt-0.5 text-[12.5px] leading-relaxed text-ink-muted">{{ $fitur[2] }}</dd>
                            </div>
                        </div>
                    @endforeach
                </dl>
            </div>

            <footer class="flex flex-wrap items-center justify-between gap-2 border-t border-line pt-4 font-mono text-[9px] uppercase tracking-[0.14em] text-ink-muted">
                <span>Zona waktu {{ config('app.timezone') }}</span>
                <span>Data tersimpan dengan jejak audit</span>
            </footer>
        </div>
    </section>

    {{-- Access form --}}
    <section class="flex items-center justify-center px-5 py-10 sm:px-8 lg:px-12">
        <div class="w-full max-w-[400px]">

            <div class="mb-10 flex items-center gap-3 lg:hidden">
                <span class="grid h-10 w-10 shrink-0 place-items-center border border-ink font-serif text-[15px] font-semibold leading-none text-ink">AS</span>
                <div>
                    <p class="font-serif text-[17px] font-semibold leading-tight text-ink">Absensi Sekolah</p>
                    <p class="mt-0.5 font-mono text-[9px] uppercase tracking-[0.18em] text-ink-muted">Buku Induk Digital</p>
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <span class="h-px w-7 bg-accent"></span>
                <p class="font-mono text-[10px] uppercase tracking-[0.22em] text-ink-muted">Akses masuk</p>
            </div>

            <h2 class="mt-4 font-serif text-[30px] font-semibold leading-tight text-ink">Selamat datang kembali</h2>
            <p class="mt-2 text-[13px] leading-relaxed text-ink-muted">
                Masuk menggunakan akun yang diberikan administrator sekolah.
            </p>

            @if (session('sukses'))
                <div role="status" class="mt-6 flex items-start gap-2.5 border border-line border-l-2 border-l-moss bg-surface px-3.5 py-3 text-[13px] text-ink">
                    <i data-lucide="check-circle-2" class="mt-0.5 h-4 w-4 shrink-0 text-moss"></i>
                    <span>{{ session('sukses') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login.proses') }}" class="mt-7 space-y-5 border-t border-line pt-7">
                @csrf

                <div>
                    <label for="email" class="form-label">Email</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-ink-muted">
                            <i data-lucide="mail" class="h-4 w-4"></i>
                        </span>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                            class="form-input pl-9" placeholder="nama@sekolah.test">
                    </div>
                    @error('email')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="kata_sandi" class="form-label">Kata Sandi</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-ink-muted">
                            <i data-lucide="lock" class="h-4 w-4"></i>
                        </span>
                        <input id="kata_sandi" name="kata_sandi" type="password" required autocomplete="current-password"
                            class="form-input pl-9" placeholder="Masukkan kata sandi">
                    </div>
                    @error('kata_sandi')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-center gap-2.5 text-[13px] text-ink-soft">
                    <input type="checkbox" name="ingat_saya" value="1" @checked(old('ingat_saya'))
                        class="h-4 w-4 rounded-none border-line-strong bg-surface text-accent focus:ring-accent">
                    Ingat saya di perangkat ini
                </label>

                <button type="submit" class="btn-primary w-full">
                    Masuk ke sistem
                    <i data-lucide="arrow-right" class="h-4 w-4"></i>
                </button>
            </form>

            <div class="mt-9">
                <div class="flex items-center justify-between border-b border-line pb-2">
                    <p class="font-mono text-[9px] uppercase tracking-[0.2em] text-ink-muted">Akun demo</p>
                    <span class="font-mono text-[9px] uppercase tracking-[0.14em] text-ink-muted">Kata sandi: password</span>
                </div>
                <dl class="divide-y divide-line/80 text-[12.5px]">
                    @foreach ([
                        ['Admin', 'admin@sekolah.test'],
                        ['Guru', 'guru@sekolah.test'],
                        ['Siswa', 'siswa@sekolah.test'],
                    ] as $akun)
                        <div class="flex items-center justify-between gap-4 py-2">
                            <dt class="font-bold text-ink-soft">{{ $akun[0] }}</dt>
                            <dd class="font-mono text-[11.5px] text-ink-muted">{{ $akun[1] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            <p class="mt-8 text-[11.5px] leading-relaxed text-ink-muted">
                QR Code hanya dapat dibuka oleh pemilik akun. Jangan bagikan kode kepada orang lain.
            </p>
        </div>
    </section>
</div>
@endsection
