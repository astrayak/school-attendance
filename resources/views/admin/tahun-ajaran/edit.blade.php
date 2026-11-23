@extends('layouts.app')

@section('judul', 'Ubah Tahun Ajaran')
@section('deskripsi', $tahunAjaran->nama_tahun_ajaran)

@section('aksi')
    <a href="{{ route('admin.tahun-ajaran.index') }}" class="btn-ghost btn-sm"><i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali</a>
@endsection

@section('konten')
<form method="POST" action="{{ route('admin.tahun-ajaran.update', $tahunAjaran) }}">
    @csrf
    @method('PUT')
    @include('admin.tahun-ajaran._form')
</form>
@endsection
