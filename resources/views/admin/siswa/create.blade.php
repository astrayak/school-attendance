@extends('layouts.app')

@section('judul', 'Tambah Siswa')
@section('deskripsi', 'Buat akun dan data kesiswaan baru. QR Code aktif dibuat otomatis.')

@section('konten')
<form method="POST" action="{{ route('admin.siswa.store') }}">
    @csrf
    @include('admin.siswa._form')
</form>
@endsection
