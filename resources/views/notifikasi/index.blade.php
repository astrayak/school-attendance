@extends('layouts.app')

@section('judul', 'Notifikasi')
@section('deskripsi', 'Pemberitahuan sesi absensi, konfirmasi, dan aktivitas sistem.')

@section('aksi')
    <form method="POST" action="{{ route('notifikasi.baca-semua') }}">
        @csrf
        <button type="submit" class="btn-secondary btn-sm">
            <i data-lucide="check-check" class="h-4 w-4"></i>
            Tandai semua dibaca
        </button>
    </form>
@endsection

@section('konten')
<div class="card overflow-hidden">
    <div class="card-header">
        <div>
            <p class="text-sm font-semibold text-ink">Kotak masuk</p>
            <p class="mt-0.5 text-xs text-ink-muted">{{ $notifikasi->total() }} notifikasi tersimpan</p>
        </div>
        <x-badge teks="7 hari terakhir ditampilkan terbaru" warna="bg-paper-deep text-ink-soft ring-ink-muted/20" />
    </div>

    @if ($notifikasi->isEmpty())
        <x-empty judul="Belum ada notifikasi" pesan="Pemberitahuan dari sistem akan muncul di sini." ikon="bell" />
    @else
        <ul class="divide-y divide-line">
            @foreach ($notifikasi as $item)
                <li class="flex gap-4 px-5 py-4 {{ $item->sudah_dibaca ? '' : 'bg-moss-soft/40' }}">
                    <span class="mt-0.5 grid h-10 w-10 shrink-0 place-items-center rounded-sm {{ $item->sudah_dibaca ? 'bg-paper-deep text-ink-muted' : 'bg-moss-soft text-moss' }}">
                        <i data-lucide="{{ match($item->jenis) { 'absensi' => 'list-checks', 'konfirmasi' => 'badge-check', 'pemeriksaan' => 'alert-triangle', default => 'bell' } }}" class="h-5 w-5"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-semibold text-ink">{{ $item->judul }}</p>
                            @if (! $item->sudah_dibaca)
                                <span class="badge bg-moss-soft text-moss ring-moss/20">Baru</span>
                            @endif
                        </div>
                        <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-ink-soft">{{ $item->pesan }}</p>
                        <p class="mt-2 text-xs text-ink-muted">{{ $item->dibuat_pada?->locale('id')->translatedFormat('d M Y H:i') }}</p>
                    </div>
                    <div class="flex shrink-0 flex-col items-end gap-2">
                        @if (! $item->sudah_dibaca)
                            <form method="POST" action="{{ route('notifikasi.baca', $item) }}">
                                @csrf
                                <button type="submit" class="btn-ghost btn-sm">
                                    Tandai dibaca
                                </button>
                            </form>
                        @endif
                        @if ($item->url)
                            <a href="{{ route('notifikasi.baca', $item) }}" class="btn-secondary btn-sm">Buka</a>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="border-t border-line px-5 py-3">
            {{ $notifikasi->links() }}
        </div>
    @endif
</div>
@endsection
