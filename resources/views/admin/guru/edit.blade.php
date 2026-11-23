@extends('layouts.app')

@section('judul', 'Ubah Data Guru')
@section('deskripsi', $guru->nama_lengkap . ' · NIP ' . ($guru->nip ?: '—'))

@section('aksi')
    <a href="{{ route('admin.guru.show', $guru) }}" class="btn-ghost btn-sm">
        <i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali
    </a>
@endsection

@section('konten')
<form method="POST" action="{{ route('admin.guru.update', $guru) }}">
    @csrf
    @method('PUT')
    @include('admin.guru._form')
</form>
@endsection
