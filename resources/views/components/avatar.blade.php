@props([
    'pengguna' => null,
    'nama' => null,
    'ukuran' => 'md',
    'kelas' => '',
])

@php
    $namaTampil = $nama ?? $pengguna?->nama_pengguna ?? 'Pengguna';

    $inisial = collect(preg_split('/\s+/', trim($namaTampil)))
        ->filter()
        ->take(2)
        ->map(fn ($kata) => mb_strtoupper(mb_substr($kata, 0, 1)))
        ->implode('');

    $ukuranKelas = match ($ukuran) {
        'sm' => 'h-9 w-9 text-[11px]',
        'lg' => 'h-16 w-16 text-[21px]',
        'xl' => 'h-20 w-20 text-[25px]',
        default => 'h-11 w-11 text-[13px]',
    };

    $urlFoto = $pengguna?->foto_profil_url;
@endphp

@if ($urlFoto)
    <img
        src="{{ $urlFoto }}"
        alt="Foto {{ $namaTampil }}"
        loading="lazy"
        class="shrink-0 theme-avatar {{ $ukuranKelas }} {{ $kelas }}"
    >
@else
    <span
        aria-hidden="true"
        class="inline-grid shrink-0 place-items-center theme-avatar font-sans font-semibold {{ $ukuranKelas }} {{ $kelas }}"
    >{{ $inisial }}</span>
@endif