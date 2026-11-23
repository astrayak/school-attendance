@props(['status', 'kelompok' => 'SESI'])
@php
    $teks = \App\Support\Status::label($kelompok, $status);
    $warna = \App\Support\Status::badge($status);
@endphp
<x-badge :teks="$teks" :warna="$warna" {{ $attributes }} />
