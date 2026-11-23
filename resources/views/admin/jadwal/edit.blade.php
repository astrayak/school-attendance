@extends('layouts.app')

@section('judul', 'Ubah Jadwal')
@section('deskripsi')
    {{ $jadwal->mataPelajaran?->nama_mata_pelajaran }} · {{ $jadwal->kelas?->nama_kelas }} · {{ ucfirst($jadwal->hari) }}
@endsection

@section('aksi')
    <a href="{{ route('admin.jadwal.index', ['semester' => $versi->id_semester, 'versi' => $versi->id_versi_jadwal]) }}" class="btn-ghost btn-sm"><i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali</a>
@endsection

@section('konten')
<form method="POST" action="{{ route('admin.jadwal.update', $jadwal) }}">
    @csrf
    @method('PUT')
    @include('admin.jadwal._form')
</form>
@endsection
