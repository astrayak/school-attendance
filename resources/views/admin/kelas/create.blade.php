@extends('layouts.app')

@section('judul', 'Tambah Kelas')
@section('deskripsi', 'Buat kelas baru pada tahun ajaran tertentu.')

@section('aksi')
    <a href="{{ route('admin.kelas.index') }}" class="btn-ghost btn-sm"><i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali</a>
@endsection

@section('konten')
<form method="POST" action="{{ route('admin.kelas.store') }}">
    @csrf
    @include('admin.kelas._form')
</form>
@endsection
