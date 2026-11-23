@extends('layouts.app')

@section('judul', 'Ubah Data Siswa')
@section('deskripsi', $siswa->nama_lengkap . ' · NIS ' . $siswa->nis)

@section('konten')
<form method="POST" action="{{ route('admin.siswa.update', $siswa) }}">
    @csrf
    @method('PUT')
    @include('admin.siswa._form', ['siswa' => $siswa])
</form>
@endsection
