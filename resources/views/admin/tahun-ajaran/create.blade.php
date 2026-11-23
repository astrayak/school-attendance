@extends('layouts.app')

@section('judul', 'Tambah Tahun Ajaran')
@section('deskripsi', 'Tentukan rentang tahun ajaran baru.')

@section('aksi')
    <a href="{{ route('admin.tahun-ajaran.index') }}" class="btn-ghost btn-sm"><i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali</a>
@endsection

@section('konten')
<form method="POST" action="{{ route('admin.tahun-ajaran.store') }}">
    @csrf
    @include('admin.tahun-ajaran._form')
</form>
@endsection
