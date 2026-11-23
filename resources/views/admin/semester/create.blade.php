@extends('layouts.app')

@section('judul', 'Tambah Semester')
@section('deskripsi', 'Tentukan periode semester ganjil atau genap.')

@section('aksi')
    <a href="{{ route('admin.semester.index') }}" class="btn-ghost btn-sm"><i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali</a>
@endsection

@section('konten')
<form method="POST" action="{{ route('admin.semester.store') }}">
    @csrf
    @include('admin.semester._form')
</form>
@endsection
