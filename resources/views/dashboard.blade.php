@extends('layouts.app')

@section('judul', 'Dashboard')

@section('konten')
<div class="px-6 py-8 lg:px-10">
    <header class="mb-8">
        <div class="flex items-center gap-2.5">
            <span class="h-px w-7 bg-accent"></span>
            <p class="kicker">Ringkasan</p>
        </div>
        <h1 class="page-title mt-3">Selamat datang, {{ auth()->user()->nama_pengguna }}.</h1>
        <p class="mt-2 text-sm text-ink-muted">Sistem absensi sekolah siap digunakan.</p>
        <div class="mt-5 flex flex-wrap gap-2">
            <a href="{{ route('login.proses') }}" class="btn-primary" onclick="event.preventDefault(); this.closest('form')?.requestSubmit ? null : null;">Masuk</a>
        </div>
    </header>
</div>
@endsection