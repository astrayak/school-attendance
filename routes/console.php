<?php

use App\Services\LayananSesi;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('absensi:pemeliharaan', function () {
    app(LayananSesi::class)->pemeliharaan();
    $this->info('Pemeliharaan sesi absensi dan konfirmasi selesai.');
})->purpose('Sinkronkan status sesi dan proses permintaan konfirmasi yang kedaluwarsa');

Schedule::call(fn () => app(LayananSesi::class)->pemeliharaan())
    ->everyMinute()
    ->name('pemeliharaan-absensi')
    ->withoutOverlapping();
