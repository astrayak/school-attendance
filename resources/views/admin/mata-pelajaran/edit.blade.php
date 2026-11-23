@extends('layouts.app')

@section('judul', 'Ubah Mata Pelajaran')
@section('deskripsi', $mataPelajaran->kode_mata_pelajaran . ' · ' . $mataPelajaran->nama_mata_pelajaran)

@section('aksi')
    <a href="{{ route('admin.mata-pelajaran.index') }}" class="btn-ghost btn-sm"><i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali</a>
@endsection

@section('konten')
<form method="POST" action="{{ route('admin.mata-pelajaran.update', $mataPelajaran) }}">
    @csrf
    @method('PUT')
    @include('admin.mata-pelajaran._form')
</form>
@endsection
