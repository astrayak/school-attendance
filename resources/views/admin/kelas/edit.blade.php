@extends('layouts.app')

@section('judul', 'Ubah Kelas')
@section('deskripsi', $kelas->nama_kelas . ' · ' . $kelas->tahunAjaran?->nama_tahun_ajaran)

@section('aksi')
    <a href="{{ route('admin.kelas.index') }}" class="btn-ghost btn-sm"><i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali</a>
@endsection

@section('konten')
<form method="POST" action="{{ route('admin.kelas.update', $kelas) }}">
    @csrf
    @method('PUT')
    @include('admin.kelas._form')
</form>
@endsection
