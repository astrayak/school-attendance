@extends('layouts.app')

@section('judul', 'Tambah Guru')
@section('deskripsi', 'Buat akun dan data kepegawaian guru baru.')

@section('aksi')
    <a href="{{ route('admin.guru.index') }}" class="btn-ghost btn-sm">
        <i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali
    </a>
@endsection

@section('konten')
<form method="POST" action="{{ route('admin.guru.store') }}">
    @csrf
    @include('admin.guru._form')
</form>
@endsection
