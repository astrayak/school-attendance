@extends('layouts.app')

@section('judul', 'Ubah Semester')
@section('deskripsi', ucfirst($semester->nama_semester) . ' · ' . $semester->tahunAjaran?->nama_tahun_ajaran)

@section('aksi')
    <a href="{{ route('admin.semester.index') }}" class="btn-ghost btn-sm"><i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali</a>
@endsection

@section('konten')
<form method="POST" action="{{ route('admin.semester.update', $semester) }}">
    @csrf
    @method('PUT')
    @include('admin.semester._form')
</form>
@endsection
