@extends('layouts.app')

@section('judul', 'Tambah Mata Pelajaran')
@section('deskripsi', 'Daftarkan mata pelajaran yang diajarkan di sekolah.')

@section('aksi')
    <a href="{{ route('admin.mata-pelajaran.index') }}" class="btn-ghost btn-sm"><i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali</a>
@endsection

@section('konten')
<form method="POST" action="{{ route('admin.mata-pelajaran.store') }}">
    @csrf
    @include('admin.mata-pelajaran._form')
</form>
@endsection
