@extends('layouts.app')

@section('judul', 'Jadwal Pelajaran')
@section('deskripsi', 'Susun jadwal per versi dan aktifkan saat siap digunakan.')

@section('aksi')
    @if ($versiTerpilih && $bolehUbah)
        <a href="{{ route('admin.jadwal.create', ['versi' => $versiTerpilih->id_versi_jadwal]) }}" class="btn-primary btn-sm">
            <i data-lucide="plus" class="h-4 w-4"></i> Tambah Jadwal
        </a>
    @endif
@endsection

@section('konten')
<div class="card mb-6" x-data="{ bukaVersi: false }">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.jadwal.index') }}" class="grid gap-3 lg:grid-cols-[220px_260px_200px_160px_auto]">
            <select name="semester" class="form-select" onchange="this.form.submit()">
                @foreach ($semester as $item)
                    <option value="{{ $item->id_semester }}" @selected((string) $idSemester === (string) $item->id_semester)>
                        {{ ucfirst($item->nama_semester) }} · {{ $item->tahunAjaran?->nama_tahun_ajaran }}
                    </option>
                @endforeach
            </select>
            <select name="versi" class="form-select" onchange="this.form.submit()">
                @foreach ($versi as $item)
                    <option value="{{ $item->id_versi_jadwal }}" @selected((string) $versiTerpilih?->id_versi_jadwal === (string) $item->id_versi_jadwal)>
                        {{ $item->nama_versi }} ({{ ucfirst($item->status_versi) }})
                    </option>
                @endforeach
            </select>
            <select name="kelas" class="form-select">
                <option value="">Semua kelas</option>
                @foreach ($kelas as $item)
                    <option value="{{ $item->id_kelas }}" @selected((string) $idKelas === (string) $item->id_kelas)>{{ $item->nama_kelas }}</option>
                @endforeach
            </select>
            <select name="hari" class="form-select">
                <option value="">Semua hari</option>
                @foreach (\App\Support\Status::HARI as $nilai => $label)
                    <option value="{{ $nilai }}" @selected($hari === $nilai)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button type="submit" class="btn-secondary">Filter</button>
                <a href="{{ route('admin.jadwal.index') }}" class="btn-ghost">Reset</a>
            </div>
        </form>

        @if ($semesterTerpilih && ! $semesterTerpilih->status_aktif)
            <div class="mt-4 rounded-sm border border-ochre-soft bg-ochre-soft px-4 py-3 text-sm text-ochre">
                Semester <strong>{{ ucfirst($semesterTerpilih->nama_semester) }} {{ $semesterTerpilih->tahunAjaran?->nama_tahun_ajaran }}</strong>
                belum aktif. Jadwal tetap dapat disusun, tetapi hanya tampil ke guru setelah semester diaktifkan.
            </div>
        @endif

        @if ($semesterDitandaiAktif && $semesterAktif && $semesterDitandaiAktif->id_semester !== $semesterAktif->id_semester)
            <div class="mt-4 rounded-sm border border-slateblue-soft bg-slateblue-soft px-4 py-3 text-sm text-slateblue">
                Semester <strong>{{ ucfirst($semesterDitandaiAktif->nama_semester) }} {{ $semesterDitandaiAktif->tahunAjaran?->nama_tahun_ajaran }}</strong>
                ditandai aktif, tetapi periode berjalan saat ini adalah
                <strong>{{ ucfirst($semesterAktif->nama_semester) }} {{ $semesterAktif->tahunAjaran?->nama_tahun_ajaran }}</strong>.
                Jadwal operasional mengikuti periode berjalan; pilih semester lain bila perlu.
            </div>
        @endif

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-line pt-4">
            <div class="flex flex-wrap items-center gap-2 text-xs text-ink-muted">
                <span class="inline-flex items-center gap-1.5 rounded-sm bg-paper-deep px-2.5 py-1 font-medium text-ink-soft">
                    <i data-lucide="layers" class="h-3.5 w-3.5"></i> {{ $versi->count() }} versi
                </span>
                @if ($versiTerpilih)
                    <x-status :status="$versiTerpilih->status_versi" kelompok="SESI" />
                    <span>Berlaku {{ $versiTerpilih->tanggal_berlaku?->format('d/m/Y') }}{{ $versiTerpilih->tanggal_berakhir ? ' – '.$versiTerpilih->tanggal_berakhir->format('d/m/Y') : '' }}</span>
                    <span>· {{ $jadwal->count() }} jadwal · {{ $jumlahSesi }} sesi</span>
                @endif
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" class="btn-secondary btn-sm" @click="bukaVersi = true">
                    <i data-lucide="file-plus-2" class="h-4 w-4"></i> Versi Baru
                </button>
                @if ($versiTerpilih && $versiTerpilih->status_versi !== 'draf')
                    <button type="button" class="btn-secondary btn-sm" data-buka-revisi>
                        <i data-lucide="git-branch" class="h-4 w-4"></i> Buat Revisi
                    </button>
                @endif
                @if ($versiTerpilih && $versiTerpilih->status_versi !== 'aktif')
                    <form method="POST" action="{{ route('admin.jadwal.versi.aktifkan', $versiTerpilih) }}">
                        @csrf
                        <button type="submit" class="btn-primary btn-sm" @if ($versiTerpilih->status_versi === 'draf') onclick="return confirm('Aktifkan versi {{ $versiTerpilih->nama_versi }}? Versi aktif sebelumnya akan diarsipkan.')" @endif>
                            <i data-lucide="check-circle-2" class="h-4 w-4"></i> Aktifkan Versi
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div x-cloak x-show="bukaVersi" x-transition class="mt-4 rounded-sm border border-line bg-paper p-4">
            <form method="POST" action="{{ route('admin.jadwal.versi.store') }}" class="grid gap-3 sm:grid-cols-[1fr_180px_auto]">
                @csrf
                <input type="hidden" name="id_semester" value="{{ $idSemester }}">
                <div>
                    <label class="form-label">Nama versi</label>
                    <input type="text" name="nama_versi" class="form-input" placeholder="Contoh: Jadwal Ganjil 2026/2027" required maxlength="100">
                </div>
                <div>
                    <label class="form-label">Tanggal berlaku</label>
                    <input type="date" name="tanggal_berlaku" value="{{ now()->toDateString() }}" class="form-input" required>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="btn-primary">Simpan</button>
                    <button type="button" class="btn-ghost" @click="bukaVersi = false">Batal</button>
                </div>
            </form>
        </div>

        @if ($versiTerpilih && $versiTerpilih->status_versi !== 'draf')
            <div class="mt-4 rounded-sm border border-ochre-soft bg-ochre-soft px-4 py-3 text-sm text-ochre" x-data="{ bukaRevisi: false }">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="pr-4">
                        Versi ini berstatus {{ $versiTerpilih->status_versi }} dan tidak dapat diubah. Buat revisi sebagai draf baru;
                        seluruh jadwal disalin otomatis.
                    </p>
                    <button type="button" class="btn-secondary btn-sm shrink-0" @click="bukaRevisi = !bukaRevisi">Buat Revisi</button>
                </div>
                <form x-cloak x-show="bukaRevisi" method="POST" action="{{ route('admin.jadwal.versi.revisi', $versiTerpilih) }}" class="mt-3 grid gap-3 border-t border-ochre-soft pt-3 sm:grid-cols-[1fr_180px_auto]">
                    @csrf
                    <div>
                        <label class="form-label">Nama versi revisi</label>
                        <input type="text" name="nama_versi" class="form-input" placeholder="Contoh: Revisi 1 - {{ $versiTerpilih->nama_versi }}" required maxlength="100">
                    </div>
                    <div>
                        <label class="form-label">Tanggal berlaku</label>
                        <input type="date" name="tanggal_berlaku" value="{{ now()->toDateString() }}" class="form-input" required>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="btn-primary">Buat Revisi</button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</div>

@if (! $versiTerpilih)
    <div class="card">
        <x-empty judul="Belum ada versi jadwal" pesan="Buat versi jadwal terlebih dahulu, lalu tambahkan mata pelajaran." ikon="calendar-days" />
    </div>
@elseif ($jadwal->isEmpty())
    <div class="card">
        <x-empty judul="Belum ada jadwal pada versi ini" pesan="Tambahkan jadwal pelajaran untuk setiap kelas dan hari." ikon="calendar-days">
            @if ($bolehUbah)
                <a href="{{ route('admin.jadwal.create', ['versi' => $versiTerpilih->id_versi_jadwal]) }}" class="btn-primary btn-sm">Tambah jadwal</a>
            @endif
        </x-empty>
    </div>
@else
    <div class="space-y-5">
        @foreach ($urutanHari as $namaHari)
            @continue (! $kelompok->has($namaHari))
            <div class="card overflow-hidden">
                <div class="card-header">
                    <div class="flex items-center gap-2">
                        <span class="grid h-8 w-8 place-items-center rounded-sm bg-moss-soft text-moss">
                            <i data-lucide="calendar" class="h-4 w-4"></i>
                        </span>
                        <div>
                            <p class="text-sm font-semibold capitalize text-ink">{{ $namaHari }}</p>
                            <p class="text-xs text-ink-muted">{{ $kelompok[$namaHari]->count() }} jadwal</p>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line">
                        <thead class="table-head">
                            <tr>
                                <th class="px-5 py-3">Waktu</th>
                                <th class="px-4 py-3">Mata pelajaran</th>
                                <th class="px-4 py-3">Kelas</th>
                                <th class="px-4 py-3">Guru</th>
                                <th class="px-4 py-3">Ruang</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-5 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($kelompok[$namaHari] as $item)
                                <tr class="hover:bg-paper/70">
                                    <td class="px-5 py-3 text-sm text-ink-soft">
                                        {{ substr($item->jam_mulai, 0, 5) }} – {{ substr($item->jam_selesai, 0, 5) }}
                                        <span class="block text-xs text-ink-muted">Urutan {{ $item->urutan_pelajaran }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="text-sm font-semibold text-ink">{{ $item->mataPelajaran?->nama_mata_pelajaran ?? '—' }}</p>
                                        <p class="text-xs text-ink-muted">{{ $item->mataPelajaran?->kode_mata_pelajaran }}</p>
                                    </td>
                                    <td class="table-cell">{{ $item->kelas?->nama_kelas ?? '—' }}</td>
                                    <td class="table-cell">{{ $item->guru?->nama_lengkap ?? '—' }}</td>
                                    <td class="table-cell">{{ $item->ruang_kelas ?: '—' }}</td>
                                    <td class="px-4 py-3">
                                        <x-badge :teks="$item->status_jadwal === 'aktif' ? 'Aktif' : 'Nonaktif'" :warna="$item->status_jadwal === 'aktif' ? 'bg-moss-soft text-moss ring-moss/20' : 'bg-paper-deep text-ink-soft ring-ink-muted/20'" />
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        @if ($bolehUbah)
                                            <div class="flex items-center justify-end gap-1">
                                                <a href="{{ route('admin.jadwal.edit', $item) }}" class="btn-ghost btn-sm" title="Ubah"><i data-lucide="pencil" class="h-3.5 w-3.5"></i></a>
                                                <form method="POST" action="{{ route('admin.jadwal.status', $item) }}">
                                                    @csrf
                                                    <button type="submit" class="btn-ghost btn-sm" title="{{ $item->status_jadwal === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                        <i data-lucide="{{ $item->status_jadwal === 'aktif' ? 'toggle-right' : 'toggle-left' }}" class="h-3.5 w-3.5"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="text-xs text-ink-muted">Terkunci</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
