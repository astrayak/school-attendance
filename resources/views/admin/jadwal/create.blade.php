@extends('layouts.app')

@section('judul', 'Tambah Jadwal')
@section('deskripsi')
    {{ $versi->nama_versi }} · {{ ucfirst($versi->semester?->nama_semester) }} {{ $versi->semester?->tahunAjaran?->nama_tahun_ajaran }}
@endsection

@section('aksi')
    <a href="{{ route('admin.jadwal.index', ['semester' => $versi->id_semester, 'versi' => $versi->id_versi_jadwal]) }}" class="btn-ghost btn-sm"><i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali</a>
@endsection

@section('konten')
<form method="POST" action="{{ route('admin.jadwal.store') }}">
    @csrf
    @include('admin.jadwal._form')
</form>
@endsection
