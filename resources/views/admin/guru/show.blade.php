@extends('layouts.app')

@section('judul', 'Detail Guru')
@section('deskripsi', $guru->nama_lengkap . ' · NIP ' . ($guru->nip ?: '—'))

@section('aksi')
    <a href="{{ route('admin.guru.edit', $guru) }}" class="btn-secondary btn-sm">
        <i data-lucide="pencil" class="h-4 w-4"></i> Ubah
    </a>
    <a href="{{ route('admin.guru.index') }}" class="btn-ghost btn-sm">Kembali</a>
@endsection

@section('konten')
<div class="grid gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        <div class="card overflow-hidden">
            <div class="border-l-2 border-l-accent bg-paper px-6 py-6">
                <div class="flex flex-wrap items-center gap-5">
                    <x-avatar :pengguna="$guru->pengguna" :nama="$guru->nama_lengkap" ukuran="lg" />
                    <div class="min-w-0 flex-1">
                        <p class="kicker">Profil guru</p>
                        <h2 class="mt-1 font-serif text-xl font-semibold tracking-tight text-ink">{{ $guru->nama_lengkap }}</h2>
                        <p class="mt-1 text-[12.5px] text-ink-muted">
                            {{ $guru->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}
                            · {{ $guru->pengguna?->email }}
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <x-badge :teks="ucfirst($guru->status_guru)" :warna="$guru->status_guru === 'aktif' ? 'bg-moss-soft text-moss ring-moss/25' : 'bg-paper-deep text-ink-muted ring-line-strong'" />
                            <x-badge :teks="$guru->pengguna?->status_aktif ? 'Akun aktif' : 'Akun nonaktif'"
                                :warna="$guru->pengguna?->status_aktif ? 'bg-moss-soft text-moss ring-moss/25' : 'bg-clay-soft text-clay ring-clay/25'" />
                        </div>
                    </div>
                    <div class="border-l border-line pl-5 text-right">
                        <p class="kicker">Total sesi ditangani</p>
                        <p class="mt-1 font-serif text-[28px] font-semibold leading-none tabular-nums text-ink">{{ $jumlahSesi }}</p>
                    </div>
                </div>
            </div>
            <div class="grid gap-5 px-6 py-5 sm:grid-cols-3">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">NIP</p>
                    <p class="mt-1 text-sm font-medium text-ink">{{ $guru->nip ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">NUPTK</p>
                    <p class="mt-1 text-sm font-medium text-ink">{{ $guru->nuptk ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Nomor telepon</p>
                    <p class="mt-1 text-sm font-medium text-ink">{{ $guru->nomor_telepon ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Tempat, tanggal lahir</p>
                    <p class="mt-1 text-sm font-medium text-ink">
                        {{ $guru->tempat_lahir ?: '—' }}{{ $guru->tanggal_lahir ? ', '.$guru->tanggal_lahir->format('d/m/Y') : '' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Wali kelas</p>
                    <p class="mt-1 text-sm font-medium text-ink">
                        {{ $guru->kelasWali->pluck('nama_kelas')->filter()->join(', ') ?: '—' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Terakhir login</p>
                    <p class="mt-1 text-sm font-medium text-ink">
                        {{ $guru->pengguna?->terakhir_login?->locale('id')->translatedFormat('d M Y H:i') ?? 'Belum pernah' }}
                    </p>
                </div>
                <div class="sm:col-span-3">
                    <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Alamat</p>
                    <p class="mt-1 text-sm text-ink-soft">{{ $guru->alamat ?: '—' }}</p>
                </div>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="card-header">
                <div>
                    <p class="text-sm font-semibold text-ink">Penugasan mengajar</p>
                    <p class="mt-0.5 text-xs text-ink-muted">Mata pelajaran dan kelas yang diampu.</p>
                </div>
                <a href="{{ route('admin.penugasan.create', ['guru' => $guru->id_guru]) }}" class="btn-secondary btn-sm">
                    <i data-lucide="plus" class="h-4 w-4"></i> Tambah
                </a>
            </div>
            @if ($penugasan->isEmpty())
                <x-empty judul="Belum ada penugasan" pesan="Tambahkan penugasan agar guru dapat mengisi absensi." ikon="clipboard-list" />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line">
                        <thead class="table-head">
                            <tr>
                                <th class="px-5 py-3">Mata pelajaran</th>
                                <th class="px-4 py-3">Kelas</th>
                                <th class="px-4 py-3">Semester</th>
                                <th class="px-4 py-3">Periode</th>
                                <th class="px-5 py-3 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($penugasan as $item)
                                <tr>
                                    <td class="px-5 py-3 font-medium text-ink">{{ $item->mataPelajaran?->nama_mata_pelajaran ?? '—' }}</td>
                                    <td class="table-cell">{{ $item->kelas?->nama_kelas ?? '—' }}</td>
                                    <td class="table-cell">
                                        {{ ucfirst($item->semester?->nama_semester ?? '—') }}
                                        <span class="block text-xs text-ink-muted">{{ $item->semester?->tahunAjaran?->nama_tahun_ajaran }}</span>
                                    </td>
                                    <td class="table-cell">
                                        {{ $item->tanggal_mulai?->format('d/m/Y') }}
                                        – {{ $item->tanggal_selesai?->format('d/m/Y') ?? 'sekarang' }}
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <x-badge :teks="$item->status_aktif ? 'Aktif' : 'Nonaktif'" :warna="$item->status_aktif ? 'bg-moss-soft text-moss ring-moss/20' : 'bg-paper-deep text-ink-soft ring-ink-muted/20'" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="card overflow-hidden">
            <div class="card-header">
                <div>
                    <p class="text-sm font-semibold text-ink">Sesi absensi terbaru</p>
                    <p class="mt-0.5 text-xs text-ink-muted">10 sesi terakhir yang ditangani guru ini.</p>
                </div>
            </div>
            @if ($sesi->isEmpty())
                <x-empty judul="Belum ada sesi" ikon="list-checks" />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line">
                        <thead class="table-head">
                            <tr>
                                <th class="px-5 py-3">Tanggal</th>
                                <th class="px-4 py-3">Kelas & mapel</th>
                                <th class="px-4 py-3">Pelaksana</th>
                                <th class="px-5 py-3 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($sesi as $item)
                                <tr>
                                    <td class="px-5 py-3 text-ink-soft">
                                        {{ $item->tanggal_pelajaran?->format('d/m/Y') }}
                                        <span class="block text-xs text-ink-muted">{{ $item->waktu_mulai?->format('H:i') }}–{{ $item->waktu_selesai?->format('H:i') }}</span>
                                    </td>
                                    <td class="table-cell">
                                        <p class="font-medium text-ink">{{ $item->jadwal?->mataPelajaran?->nama_mata_pelajaran ?? '—' }}</p>
                                        <p class="text-xs text-ink-muted">{{ $item->jadwal?->kelas?->nama_kelas }}</p>
                                    </td>
                                    <td class="table-cell">{{ $item->guruPelaksana?->nama_lengkap ?? '—' }}</td>
                                    <td class="px-5 py-3 text-right"><x-status :status="$item->status_sesi" kelompok="SESI" /></td>
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
                    <p class="text-sm font-semibold text-ink">Jadwal piket</p>
                    <p class="mt-0.5 text-xs text-ink-muted">10 penugasan piket terakhir.</p>
                </div>
                <a href="{{ route('admin.piket.create', ['guru' => $guru->id_guru]) }}" class="btn-ghost btn-sm">
                    <i data-lucide="plus" class="h-3.5 w-3.5"></i> Tambah
                </a>
            </div>
            @if ($piket->isEmpty())
                <x-empty judul="Belum ada jadwal piket" ikon="shield-check" />
            @else
                <ul class="divide-y divide-line">
                    @foreach ($piket as $item)
                        <li class="flex items-center gap-3 px-5 py-3">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-sm bg-slateblue-soft text-slateblue">
                                <i data-lucide="calendar-clock" class="h-4 w-4"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium capitalize text-ink">{{ $item->hari }}</p>
                                <p class="text-xs text-ink-muted">
                                    {{ $item->tanggal_mulai_berlaku?->format('d/m/Y') }} –
                                    {{ $item->tanggal_berakhir?->format('d/m/Y') ?? 'sekarang' }}
                                </p>
                            </div>
                            <x-badge :teks="$item->status_aktif ? 'Aktif' : 'Nonaktif'" :warna="$item->status_aktif ? 'bg-moss-soft text-moss ring-moss/20' : 'bg-paper-deep text-ink-soft ring-ink-muted/20'" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
@endsection
