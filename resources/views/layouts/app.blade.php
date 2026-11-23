@php
    $pengguna = auth()->user();
    $jumlahNotifikasi = $pengguna?->notifikasi()->where('sudah_dibaca', false)->count() ?? 0;
    $peran = \App\Support\Status::label('PERAN', $pengguna?->peran);

    $menu = [];

    if ($pengguna?->isAdmin()) {
        $menu = [
            ['judul' => 'Utama', 'item' => [
                ['label' => 'Dashboard', 'rute' => 'admin.dashboard', 'ikon' => 'layout-dashboard', 'aktif' => ['admin.dashboard']],
            ]],
            ['judul' => 'Akademik', 'item' => [
                ['label' => 'Tahun Ajaran', 'rute' => 'admin.tahun-ajaran.index', 'ikon' => 'calendar', 'aktif' => ['admin.tahun-ajaran.*']],
                ['label' => 'Semester', 'rute' => 'admin.semester.index', 'ikon' => 'calendar-clock', 'aktif' => ['admin.semester.*']],
                ['label' => 'Kelas', 'rute' => 'admin.kelas.index', 'ikon' => 'school', 'aktif' => ['admin.kelas.*']],
                ['label' => 'Mata Pelajaran', 'rute' => 'admin.mata-pelajaran.index', 'ikon' => 'book-open', 'aktif' => ['admin.mata-pelajaran.*']],
            ]],
            ['judul' => 'Pengguna', 'item' => [
                ['label' => 'Siswa', 'rute' => 'admin.siswa.index', 'ikon' => 'users', 'aktif' => ['admin.siswa.*']],
                ['label' => 'Guru', 'rute' => 'admin.guru.index', 'ikon' => 'graduation-cap', 'aktif' => ['admin.guru.*']],
            ]],
            ['judul' => 'Penjadwalan', 'item' => [
                ['label' => 'Penugasan Guru', 'rute' => 'admin.penugasan.index', 'ikon' => 'clipboard-list', 'aktif' => ['admin.penugasan.*']],
                ['label' => 'Jadwal Pelajaran', 'rute' => 'admin.jadwal.index', 'ikon' => 'calendar-days', 'aktif' => ['admin.jadwal.*']],
                ['label' => 'Guru Piket', 'rute' => 'admin.piket.index', 'ikon' => 'shield-check', 'aktif' => ['admin.piket.*']],
                ['label' => 'Petugas Konfirmasi', 'rute' => 'admin.petugas-konfirmasi.index', 'ikon' => 'user-check', 'aktif' => ['admin.petugas-konfirmasi.*']],
            ]],
            ['judul' => 'Absensi', 'item' => [
                ['label' => 'Sesi Absensi', 'rute' => 'admin.sesi.index', 'ikon' => 'list-checks', 'aktif' => ['admin.sesi.*']],
                ['label' => 'Rekap Siswa', 'rute' => 'admin.rekap-siswa.index', 'ikon' => 'clipboard-list', 'aktif' => ['admin.rekap-siswa.*']],
                ['label' => 'Laporan', 'rute' => 'admin.laporan.index', 'ikon' => 'bar-chart-3', 'aktif' => ['admin.laporan.*']],
            ]],
            ['judul' => 'Sistem', 'item' => [
                ['label' => 'Pembaruan Sistem', 'rute' => 'sistem.pembaruan.index', 'ikon' => 'refresh-cw', 'aktif' => ['sistem.pembaruan.*']],
                ['label' => 'Log Aktivitas', 'rute' => 'admin.log.index', 'ikon' => 'history', 'aktif' => ['admin.log.*']],
                ['label' => 'Riwayat Troubleshooting', 'rute' => 'sistem.troubleshooting.index', 'ikon' => 'wrench', 'aktif' => ['sistem.troubleshooting.*']],
                ['label' => 'Pengaturan', 'rute' => 'admin.pengaturan.index', 'ikon' => 'settings', 'aktif' => ['admin.pengaturan.*']],
            ]],
        ];
    } elseif ($pengguna?->isGuru()) {
        $menu = [
            ['judul' => 'Utama', 'item' => [
                ['label' => 'Dashboard', 'rute' => 'guru.dashboard', 'ikon' => 'layout-dashboard', 'aktif' => ['guru.dashboard']],
            ]],
            ['judul' => 'Mengajar', 'item' => [
                ['label' => 'Jadwal Saya', 'rute' => 'guru.jadwal.index', 'ikon' => 'calendar-days', 'aktif' => ['guru.jadwal.*']],
                ['label' => 'Sesi Absensi', 'rute' => 'guru.sesi.index', 'ikon' => 'list-checks', 'aktif' => ['guru.sesi.*', 'guru.absensi.*']],
                ['label' => 'Scanner QR', 'rute' => 'guru.scanner.index', 'ikon' => 'scan-line', 'aktif' => ['guru.scanner.*']],
            ]],
            ['judul' => 'Piket', 'item' => [
                ['label' => 'Guru Piket', 'rute' => 'guru.piket.index', 'ikon' => 'shield-check', 'aktif' => ['guru.piket.*']],
            ]],
            ['judul' => 'Sistem', 'item' => [
                ['label' => 'Pembaruan Sistem', 'rute' => 'sistem.pembaruan.index', 'ikon' => 'refresh-cw', 'aktif' => ['sistem.pembaruan.*']],
                ['label' => 'Riwayat Troubleshooting', 'rute' => 'sistem.troubleshooting.index', 'ikon' => 'wrench', 'aktif' => ['sistem.troubleshooting.*']],
            ]],
            ['judul' => 'Akun', 'item' => [
                ['label' => 'Profil Saya', 'rute' => 'guru.profil', 'ikon' => 'user-round', 'aktif' => ['guru.profil']],
                ['label' => 'Notifikasi', 'rute' => 'notifikasi.index', 'ikon' => 'bell', 'aktif' => ['notifikasi.*']],
            ]],
        ];
    } elseif ($pengguna?->isSiswa()) {
        $menu = [
            ['judul' => 'Utama', 'item' => [
                ['label' => 'Dashboard', 'rute' => 'siswa.dashboard', 'ikon' => 'layout-dashboard', 'aktif' => ['siswa.dashboard']],
            ]],
            ['judul' => 'Kehadiran', 'item' => [
                ['label' => 'QR Code Saya', 'rute' => 'siswa.qr', 'ikon' => 'qr-code', 'aktif' => ['siswa.qr']],
                ['label' => 'Riwayat Absensi', 'rute' => 'siswa.riwayat.index', 'ikon' => 'history', 'aktif' => ['siswa.riwayat.*']],
                ['label' => 'Rekap Mapel', 'rute' => 'siswa.rekap.mapel', 'ikon' => 'bar-chart-3', 'aktif' => ['siswa.rekap.mapel']],
                ['label' => 'Rekap Harian', 'rute' => 'siswa.rekap.harian', 'ikon' => 'calendar-check', 'aktif' => ['siswa.rekap.harian']],
                ['label' => 'Konfirmasi', 'rute' => 'siswa.konfirmasi.index', 'ikon' => 'badge-check', 'aktif' => ['siswa.konfirmasi.*']],
            ]],
            ['judul' => 'Sistem', 'item' => [
                ['label' => 'Pembaruan Sistem', 'rute' => 'sistem.pembaruan.index', 'ikon' => 'refresh-cw', 'aktif' => ['sistem.pembaruan.*']],
                ['label' => 'Riwayat Troubleshooting', 'rute' => 'sistem.troubleshooting.index', 'ikon' => 'wrench', 'aktif' => ['sistem.troubleshooting.*']],
            ]],
            ['judul' => 'Akun', 'item' => [
                ['label' => 'Profil Saya', 'rute' => 'siswa.profil', 'ikon' => 'user-round', 'aktif' => ['siswa.profil']],
                ['label' => 'Notifikasi', 'rute' => 'notifikasi.index', 'ikon' => 'bell', 'aktif' => ['notifikasi.*']],
            ]],
        ];
    }
@endphp
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('judul', 'Dashboard') &middot; {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-wrapper">
<a href="#konten" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:border focus:border-ink focus:bg-surface focus:px-4 focus:py-2 focus:text-sm focus:font-bold">
    Lewati ke konten
</a>

<div x-data="{ sidebarTerbuka: false }" class="min-h-screen lg:flex">
    <div x-cloak x-show="sidebarTerbuka" x-transition.opacity class="fixed inset-0 z-30 bg-ink/60 lg:hidden" @click="sidebarTerbuka = false"></div>

    <aside
        class="fixed inset-y-0 left-0 z-40 flex w-[272px] flex-col border-r border-line bg-surface transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
        :class="sidebarTerbuka ? 'translate-x-0' : '-translate-x-full'"
        aria-label="Navigasi utama"
    >
        <div class="flex h-[72px] items-center gap-3 border-b border-line px-5">
            <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3">
                <span class="grid h-9 w-9 shrink-0 place-items-center border border-ink font-serif text-lg font-semibold leading-none text-ink">AS</span>
                <span class="min-w-0">
                    <span class="block truncate font-serif text-[17px] font-semibold leading-tight text-ink">Absensi Sekolah</span>
                    <span class="mt-0.5 block font-mono text-[9px] uppercase tracking-[0.18em] text-ink-muted">Buku Induk Digital</span>
                </span>
            </a>
            <button type="button" class="ml-auto border border-line p-1.5 text-ink-muted transition hover:border-ink hover:text-ink lg:hidden" @click="sidebarTerbuka = false" aria-label="Tutup menu">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <nav class="scrollbar-thin flex-1 overflow-y-auto px-3 pb-4">
            @foreach ($menu as $grup)
                @if (count($grup['item']))
                    <p class="px-2 pb-1 pt-5 font-mono text-[9px] font-medium uppercase tracking-[0.2em] text-ink-muted">{{ $grup['judul'] }}</p>
                    <div class="space-y-0.5">
                        @foreach ($grup['item'] as $item)
                            @php($aktif = request()->routeIs(...$item['aktif']))
                            <a
                                href="{{ route($item['rute']) }}"
                                @if ($aktif) aria-current="page" @endif
                                class="group flex items-center gap-3 border-l-2 px-2.5 py-2 text-[12.5px] font-bold transition-colors duration-150 {{ $aktif ? 'border-accent bg-paper-deep text-ink' : 'border-transparent text-ink-soft hover:border-line-strong hover:bg-paper' }}"
                            >
                                <i data-lucide="{{ $item['ikon'] }}" class="h-4 w-4 shrink-0 {{ $aktif ? 'text-accent' : 'text-ink-muted group-hover:text-ink-soft' }}"></i>
                                <span class="truncate">{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            @endforeach
        </nav>

        <div class="border-t border-line px-4 py-3">
            @php($periodeBerjalan = app(\App\Services\LayananPeriode::class))
            <p class="font-mono text-[9px] uppercase tracking-[0.16em] text-ink-muted">Periode berjalan</p>
            <p class="mt-1 text-[12px] font-bold text-ink">{{ $periodeBerjalan->label($periodeBerjalan->aktif()) }}</p>
        </div>

        <div class="border-t border-line p-4">
            <div class="flex items-center gap-3">
                <x-avatar :pengguna="$pengguna" :nama="$pengguna?->nama_pengguna" ukuran="sm" />
                <div class="min-w-0 flex-1">
                    <p class="truncate text-[12px] font-bold text-ink">{{ $pengguna?->nama_pengguna }}</p>
                    <p class="mt-0.5 font-mono text-[9px] uppercase tracking-[0.16em] text-ink-muted">{{ $peran }}</p>
                </div>
                <a href="{{ route('notifikasi.index') }}" class="relative border border-line p-2 text-ink-muted transition hover:border-ink hover:text-ink" title="Notifikasi" aria-label="Notifikasi ({{ $jumlahNotifikasi }} belum dibaca)">
                    <i data-lucide="bell" class="h-4 w-4"></i>
                    @if ($jumlahNotifikasi > 0)
                        <span class="absolute -right-1 -top-1 grid h-4 min-w-4 place-items-center border border-surface bg-accent px-1 font-mono text-[9px] font-bold leading-none text-paper">{{ $jumlahNotifikasi > 9 ? '9+' : $jumlahNotifikasi }}</span>
                    @endif
                </a>
            </div>

            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button type="submit" class="btn-ghost btn-sm w-full justify-start">
                    <i data-lucide="log-out" class="h-4 w-4"></i>
                    <span>Keluar dari akun</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-20 border-b border-line bg-paper/95">
            <div class="mx-auto flex max-w-[1480px] flex-wrap items-center gap-4 px-4 py-4 sm:px-6 lg:px-10">
                <button type="button" class="border border-line p-2 text-ink-soft transition hover:border-ink hover:text-ink lg:hidden" @click="sidebarTerbuka = true" aria-label="Buka menu">
                    <i data-lucide="menu" class="h-4 w-4"></i>
                </button>

                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="h-px w-5 bg-accent"></span>
                        <p class="font-mono text-[9px] uppercase tracking-[0.2em] text-ink-muted">{{ $peran }} &middot; {{ now()->locale('id')->translatedFormat('d F Y') }}</p>
                    </div>
                    <h1 class="page-title mt-1.5">@yield('judul', 'Dashboard')</h1>
                    @hasSection('deskripsi')
                        <p class="mt-1 max-w-3xl text-[12.5px] leading-relaxed text-ink-muted">@yield('deskripsi')</p>
                    @endif
                </div>

                <div class="flex items-center gap-2">
                    @yield('aksi')
                    <div class="hidden items-center gap-2 border border-line bg-surface px-3 py-2 md:flex">
                        <i data-lucide="clock" class="h-3.5 w-3.5 text-accent"></i>
                        <span class="font-mono text-[10px] uppercase tracking-[0.08em] text-ink-soft">{{ now()->format('H:i') }} WITA</span>
                    </div>
                    <a href="{{ route('notifikasi.index') }}" class="relative border border-line bg-surface p-2 text-ink-muted transition hover:border-ink hover:text-ink md:hidden" title="Notifikasi">
                        <i data-lucide="bell" class="h-4 w-4"></i>
                        @if ($jumlahNotifikasi > 0)
                            <span class="absolute -right-1 -top-1 h-2 w-2 rounded-full bg-accent ring-2 ring-paper"></span>
                        @endif
                    </a>
                </div>
            </div>
        </header>

        <main id="konten" class="mx-auto w-full max-w-[1480px] flex-1 px-4 py-8 sm:px-6 lg:px-10">
            @if (session('sukses'))
                <div role="status" class="mb-6 flex items-start gap-3 border border-line border-l-2 border-l-moss bg-surface px-4 py-3">
                    <i data-lucide="check-circle-2" class="mt-0.5 h-4 w-4 shrink-0 text-moss"></i>
                    <div class="flex-1">
                        <p class="font-mono text-[9px] uppercase tracking-[0.18em] text-moss">Berhasil</p>
                        <p class="mt-0.5 text-sm text-ink">{{ session('sukses') }}</p>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div role="alert" class="mb-6 flex items-start gap-3 border border-line border-l-2 border-l-clay bg-surface px-4 py-3">
                    <i data-lucide="alert-circle" class="mt-0.5 h-4 w-4 shrink-0 text-clay"></i>
                    <div class="flex-1">
                        <p class="font-mono text-[9px] uppercase tracking-[0.18em] text-clay">Gagal</p>
                        <p class="mt-0.5 text-sm text-ink">{{ session('error') }}</p>
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div role="alert" class="mb-6 border border-line border-l-2 border-l-accent bg-surface px-4 py-3">
                    <div class="flex items-start gap-3">
                        <i data-lucide="alert-triangle" class="mt-0.5 h-4 w-4 shrink-0 text-accent"></i>
                        <div class="flex-1">
                            <p class="font-mono text-[9px] uppercase tracking-[0.18em] text-accent">Periksa isian</p>
                            <p class="mt-1 text-sm font-bold text-ink">Ada isian yang belum sesuai.</p>
                            <ul class="mt-1.5 list-disc space-y-0.5 pl-5 text-sm text-ink-soft">
                                @foreach ($errors->all() as $pesan)
                                    <li>{{ $pesan }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            @yield('konten')
        </main>

        <footer class="border-t border-line px-4 py-4 sm:px-6 lg:px-10">
            <div class="mx-auto flex max-w-[1480px] flex-wrap items-center justify-between gap-2 font-mono text-[9px] uppercase tracking-[0.12em] text-ink-muted">
                <span>&copy; {{ now()->year }} {{ config('app.name') }}</span>
                <span class="hidden sm:inline">{{ config('app.timezone') }} &middot; Data dengan jejak audit</span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="h-1.5 w-1.5 rounded-full bg-moss"></span>
                    Sistem aktif
                </span>
            </div>
        </footer>
    </div>
</div>
@stack('skrip')
</body>
</html>
