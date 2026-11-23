<?php

namespace Database\Seeders;

use App\Models\AuditAbsensi;
use App\Models\CatatanAbsensi;
use App\Models\Guru;
use App\Models\JadwalGuruPiket;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\LogAktivitas;
use App\Models\MataPelajaran;
use App\Models\Notifikasi;
use App\Models\PengaturanSistem;
use App\Models\Pengguna;
use App\Models\PenugasanGuru;
use App\Models\PermintaanKonfirmasi;
use App\Models\PetugasKonfirmasi;
use App\Models\RiwayatKelasSiswa;
use App\Models\Semester;
use App\Models\SesiAbsensi;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\VersiJadwal;
use App\Services\LayananJadwal;
use App\Services\LayananLogAktivitas;
use App\Services\LayananPengaturan;
use App\Services\LayananQrCode;
use App\Services\LayananRekapAbsensi;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    private const KATA_SANDI_DEMO = 'password';

    private const TAHUN_AJARAN_MULAI = '2026-07-13';

    private const TAHUN_AJARAN_SELESAI = '2027-06-30';

    private const SEMESTER_GANJIL_MULAI = '2026-09-01';

    private const SEMESTER_GANJIL_SELESAI = '2026-12-31';

    private const SEMESTER_GENAP_MULAI = '2027-01-05';

    private const SEMESTER_GENAP_SELESAI = '2027-06-20';

    /** @var array<string, string> */
    private const GURU_MAPEL = [
        'MAT' => 'budi',
        'BIN' => 'siti',
        'BIG' => 'ahmad',
        'IPA' => 'ahmad',
        'IPS' => 'siti',
        'INF' => 'budi',
    ];

    /** @var array<string, array<string, array<int, array{0: string, 1: string, 2: string, 3: int}>>> */
    private const DEFINISI_JADWAL = [
        'X IPA 1' => [
            'senin' => [
                ['MAT', '07:30', '08:50', 1],
                ['BIN', '09:05', '10:25', 2],
                ['IPA', '10:40', '12:00', 3],
                ['INF', '13:00', '14:20', 4],
            ],
            'selasa' => [
                ['BIG', '07:30', '08:50', 1],
                ['IPS', '09:05', '10:25', 2],
                ['MAT', '10:40', '12:00', 3],
                ['BIN', '13:00', '14:20', 4],
            ],
            'rabu' => [
                ['MAT', '07:30', '08:50', 1],
                ['BIG', '09:05', '10:25', 2],
                ['IPA', '10:40', '12:00', 3],
                ['IPS', '13:00', '14:20', 4],
            ],
            'kamis' => [
                ['INF', '07:30', '08:50', 1],
                ['MAT', '09:05', '10:25', 2],
                ['BIN', '10:40', '12:00', 3],
                ['IPA', '13:00', '14:20', 4],
            ],
            'jumat' => [
                ['MAT', '07:30', '08:50', 1],
                ['BIG', '09:05', '10:25', 2],
                ['IPS', '10:40', '12:00', 3],
            ],
        ],
        'X IPS 1' => [
            'senin' => [
                ['BIN', '07:30', '08:50', 1],
                ['MAT', '09:05', '10:25', 2],
                ['IPS', '10:40', '12:00', 3],
                ['BIG', '13:00', '14:20', 4],
            ],
            'selasa' => [
                ['INF', '07:30', '08:50', 1],
                ['IPA', '09:05', '10:25', 2],
                ['BIN', '10:40', '12:00', 3],
                ['MAT', '13:00', '14:20', 4],
            ],
            'rabu' => [
                ['BIN', '07:30', '08:50', 1],
                ['IPS', '09:05', '10:25', 2],
                ['INF', '10:40', '12:00', 3],
                ['MAT', '13:00', '14:20', 4],
            ],
            'kamis' => [
                ['IPA', '07:30', '08:50', 1],
                ['BIG', '09:05', '10:25', 2],
                ['INF', '10:40', '12:00', 3],
                ['BIN', '13:00', '14:20', 4],
            ],
            'jumat' => [
                ['BIG', '07:30', '08:50', 1],
                ['MAT', '09:05', '10:25', 2],
                ['IPA', '10:40', '12:00', 3],
            ],
        ],
    ];

    /** @var array<string, string> */
    private const SESI_DIBATALKAN = [
        '2026-10-16|X IPA 1|2' => 'Kegiatan sekolah: persiapan lomba kebersihan antar kelas.',
        '2026-11-06|X IPS 1|3' => 'Kegiatan sekolah: senam bersama dan rapat komite.',
    ];

    /** @var array<string, true> */
    private const SESI_TANPA_ABSENSI = [
        '2026-09-18|X IPA 1|3' => true,
        '2026-10-23|X IPS 1|1' => true,
        '2026-11-13|X IPA 1|2' => true,
    ];

    /** @var array<string, true> */
    private const SESI_PERLU_PEMERIKSAAN = [
        '2026-10-09|X IPA 1|3' => true,
        '2026-11-10|X IPS 1|4' => true,
    ];

    /** @var array<string, true> */
    private const SESI_BELUM_DIKONFIRMASI = [
        '2026-09-25|X IPS 1|3' => true,
        '2026-11-04|X IPA 1|4' => true,
    ];

    /** @var array<string, true> */
    private const SESI_QR = [
        '2026-09-08|X IPA 1|1' => true,
        '2026-09-22|X IPS 1|2' => true,
        '2026-10-06|X IPA 1|3' => true,
        '2026-10-20|X IPS 1|1' => true,
        '2026-11-03|X IPA 1|2' => true,
        '2026-11-17|X IPS 1|3' => true,
    ];

    /** @var array<string, true> */
    private const SESI_GABUNGAN = [
        '2026-10-13|X IPA 1|1' => true,
        '2026-10-27|X IPS 1|4' => true,
    ];

    /** @var array<string, array{guru: string, alasan: string}> */
    private const PENGAMBILALIHAN = [
        '2026-10-02|X IPA 1|3' => [
            'guru' => 'budi',
            'alasan' => 'Guru pengampu berhalangan hadir; sesi diambil alih guru piket.',
        ],
    ];

    /**
     * Status khusus dipakai agar riwayat demo tidak hanya berisi hadir.
     *
     * @var array<string, array{nis: string, status: string, keterangan: string}>
     */
    private const KEHADIRAN_KHUSUS = [
        '2026-09-15|X IPA 1|3' => [
            'nis' => '2425001',
            'status' => CatatanAbsensi::IZIN,
            'keterangan' => 'Izin menghadiri acara keluarga.',
        ],
        '2026-10-08|X IPA 1|2' => [
            'nis' => '2425001',
            'status' => CatatanAbsensi::SAKIT,
            'keterangan' => 'Sakit demam dengan surat keterangan orang tua.',
        ],
        '2026-10-22|X IPA 1|1' => [
            'nis' => '2425001',
            'status' => CatatanAbsensi::ALPHA,
            'keterangan' => 'Tidak hadir tanpa keterangan.',
        ],
        '2026-10-13|X IPA 1|1' => [
            'nis' => '2425003',
            'status' => CatatanAbsensi::IZIN,
            'keterangan' => 'Izin mengikuti lomba debat sekolah.',
        ],
        '2026-10-27|X IPS 1|4' => [
            'nis' => '2425014',
            'status' => CatatanAbsensi::ALPHA,
            'keterangan' => 'Tidak hadir tanpa keterangan.',
        ],
        // Awalnya alpha, lalu dikoreksi admin pada bagian koreksi audit.
        '2026-10-29|X IPA 1|3' => [
            'nis' => '2425002',
            'status' => CatatanAbsensi::ALPHA,
            'keterangan' => 'Tidak hadir tanpa keterangan.',
        ],
    ];

    public function run(): void
    {
        if (Pengguna::query()->exists()) {
            $this->command?->warn(
                'Database sudah berisi data. Seeder demo dilewati agar data yang ada tidak tertimpa. '
                .'Jalankan php artisan migrate:fresh --seed untuk membangun ulang data demo.'
            );

            return;
        }

        $log = app(LayananLogAktivitas::class);
        $rekap = app(LayananRekapAbsensi::class);
        $qr = app(LayananQrCode::class);

        $this->command?->info('Menyiapkan data master sekolah...');

        DB::transaction(function () use ($log, $rekap, $qr) {
            [$admin, $guru] = $this->buatPenggunaDanGuru();
            [$tahunAjaran, $semesterGanjil, $semesterGenap, $kelas] = $this->buatTahunAjaranDanKelas($guru);
            $mapel = $this->buatMataPelajaran();
            $this->buatPenugasanGuru($guru, $kelas, $mapel, $semesterGanjil);
            [$versiJadwal, $jadwal] = $this->buatJadwal($admin, $semesterGanjil, $kelas, $mapel, $guru);
            $this->buatJadwalPiket($guru, $semesterGanjil);
            $siswaPerKelas = $this->buatSiswa($kelas, $tahunAjaran, $qr);
            $petugas = $this->buatPetugasKonfirmasi($kelas, $siswaPerKelas, $semesterGanjil);
            $this->seedPengaturan($admin, $semesterGanjil);

            $konteks = [
                'admin' => $admin,
                'guru' => $guru,
                'tahun_ajaran' => $tahunAjaran,
                'semester' => $semesterGanjil,
                'semester_genap' => $semesterGenap,
                'kelas' => $kelas,
                'mapel' => $mapel,
                'versi_jadwal' => $versiJadwal,
                'jadwal' => $jadwal,
                'siswa' => $siswaPerKelas,
                'petugas' => $petugas,
                'log' => $log,
                'rekap' => $rekap,
            ];

            $this->command?->info('Membuat riwayat sesi dan absensi...');
            $this->seedRiwayat($konteks);

            $this->command?->info('Menyiapkan sesi konfirmasi aktif untuk demo hari ini...');
            $this->seedDemoHariIni($konteks);

            $this->command?->info('Menghitung ringkasan absensi harian...');
            $this->hitungRingkasan($konteks);

            $this->seedLogDanNotifikasi($konteks);
        });

        $this->command?->newLine();
        $this->command?->info('Data demo berhasil dibuat.');
        $this->command?->table(
            ['Peran', 'Email', 'Kata sandi'],
            [
                ['Admin', 'admin@sekolah.test', self::KATA_SANDI_DEMO],
                ['Guru', 'guru@sekolah.test', self::KATA_SANDI_DEMO],
                ['Siswa', 'siswa@sekolah.test', self::KATA_SANDI_DEMO],
            ]
        );
        $this->command?->line('Akun guru demo: Budi Santoso. Akun siswa demo: Andi Pratama (X IPA 1).');
        $this->command?->line('Sesi konfirmasi aktif untuk siswa demo dibuat dengan batas waktu sekitar 4 menit setelah seeder dijalankan.');
    }

    /**
     * @return array{0: Pengguna, 1: array<string, Guru>}
     */
    private function buatPenggunaDanGuru(): array
    {
        $admin = Pengguna::create([
            'nama_pengguna' => 'Administrator Sekolah',
            'email' => 'admin@sekolah.test',
            'kata_sandi' => Hash::make(self::KATA_SANDI_DEMO),
            'peran' => 'admin',
            'status_aktif' => true,
        ]);

        $daftar = [
            [
                'kunci' => 'budi',
                'nama' => 'Budi Santoso',
                'email' => 'guru@sekolah.test',
                'nip' => '198503122010011001',
                'nuptk' => '1234567890123456',
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Makassar',
                'tanggal_lahir' => '1985-03-12',
                'nomor_telepon' => '081234567801',
                'alamat' => 'Jl. Pendidikan No. 12, Makassar',
            ],
            [
                'kunci' => 'siti',
                'nama' => 'Siti Rahmawati',
                'email' => 'siti@sekolah.test',
                'nip' => '198807152012022002',
                'nuptk' => '2234567890123456',
                'jenis_kelamin' => 'P',
                'tempat_lahir' => 'Gowa',
                'tanggal_lahir' => '1988-07-15',
                'nomor_telepon' => '081234567802',
                'alamat' => 'Jl. Pendidikan No. 24, Gowa',
            ],
            [
                'kunci' => 'ahmad',
                'nama' => 'Ahmad Fauzi',
                'email' => 'ahmad@sekolah.test',
                'nip' => '199102202015011003',
                'nuptk' => '3234567890123456',
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Maros',
                'tanggal_lahir' => '1991-02-20',
                'nomor_telepon' => '081234567803',
                'alamat' => 'Jl. Pendidikan No. 36, Maros',
            ],
        ];

        $guru = [];

        foreach ($daftar as $item) {
            $pengguna = Pengguna::create([
                'nama_pengguna' => $item['nama'],
                'email' => $item['email'],
                'kata_sandi' => Hash::make(self::KATA_SANDI_DEMO),
                'peran' => 'guru',
                'status_aktif' => true,
            ]);

            $guru[$item['kunci']] = Guru::create([
                'id_pengguna' => $pengguna->id_pengguna,
                'nip' => $item['nip'],
                'nuptk' => $item['nuptk'],
                'nama_lengkap' => $item['nama'],
                'jenis_kelamin' => $item['jenis_kelamin'],
                'tempat_lahir' => $item['tempat_lahir'],
                'tanggal_lahir' => $item['tanggal_lahir'],
                'nomor_telepon' => $item['nomor_telepon'],
                'alamat' => $item['alamat'],
                'status_guru' => 'aktif',
            ]);
        }

        return [$admin, $guru];
    }

    /**
     * @param  array<string, Guru>  $guru
     * @return array{0: TahunAjaran, 1: Semester, 2: Semester, 3: array<string, Kelas>}
     */
    private function buatTahunAjaranDanKelas(array $guru): array
    {
        $tahunAjaran = TahunAjaran::create([
            'nama_tahun_ajaran' => '2026/2027',
            'tanggal_mulai' => self::TAHUN_AJARAN_MULAI,
            'tanggal_selesai' => self::TAHUN_AJARAN_SELESAI,
            'status_aktif' => true,
        ]);

        $semesterGanjil = Semester::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'nama_semester' => 'ganjil',
            'tanggal_mulai' => self::SEMESTER_GANJIL_MULAI,
            'tanggal_selesai' => self::SEMESTER_GANJIL_SELESAI,
            'status_aktif' => true,
        ]);

        $semesterGenap = Semester::create([
            'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
            'nama_semester' => 'genap',
            'tanggal_mulai' => self::SEMESTER_GENAP_MULAI,
            'tanggal_selesai' => self::SEMESTER_GENAP_SELESAI,
            'status_aktif' => false,
        ]);

        $kelas = [
            'X IPA 1' => Kelas::create([
                'nama_kelas' => 'X IPA 1',
                'tingkat' => 'X',
                'jurusan' => 'IPA',
                'wali_kelas' => $guru['budi']->id_guru,
                'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
                'status_kelas' => 'aktif',
            ]),
            'X IPS 1' => Kelas::create([
                'nama_kelas' => 'X IPS 1',
                'tingkat' => 'X',
                'jurusan' => 'IPS',
                'wali_kelas' => $guru['siti']->id_guru,
                'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
                'status_kelas' => 'aktif',
            ]),
        ];

        return [$tahunAjaran, $semesterGanjil, $semesterGenap, $kelas];
    }

    /**
     * @return array<string, MataPelajaran>
     */
    private function buatMataPelajaran(): array
    {
        $daftar = [
            'MAT' => ['Matematika', 'Matematika wajib untuk kelas X.'],
            'BIN' => ['Bahasa Indonesia', 'Keterampilan berbahasa dan bersastra Indonesia.'],
            'BIG' => ['Bahasa Inggris', 'Bahasa Inggris komunikatif untuk kelas X.'],
            'IPA' => ['Ilmu Pengetahuan Alam', 'Fisika, kimia, dan biologi dasar.'],
            'IPS' => ['Ilmu Pengetahuan Sosial', 'Sejarah, geografi, ekonomi, dan sosiologi dasar.'],
            'INF' => ['Informatika', 'Berpikir komputasional dan literasi digital.'],
        ];

        $mapel = [];

        foreach ($daftar as $kode => [$nama, $deskripsi]) {
            $mapel[$kode] = MataPelajaran::create([
                'kode_mata_pelajaran' => $kode,
                'nama_mata_pelajaran' => $nama,
                'deskripsi' => $deskripsi,
                'status_aktif' => true,
            ]);
        }

        return $mapel;
    }

    /**
     * @param  array<string, Guru>  $guru
     * @param  array<string, Kelas>  $kelas
     * @param  array<string, MataPelajaran>  $mapel
     */
    private function buatPenugasanGuru(array $guru, array $kelas, array $mapel, Semester $semester): void
    {
        $sudah = [];

        foreach (self::DEFINISI_JADWAL as $namaKelas => $perHari) {
            foreach ($perHari as $daftarSlot) {
                foreach ($daftarSlot as $slot) {
                    $kodeMapel = $slot[0];
                    $kunci = $namaKelas.'|'.$kodeMapel;

                    if (isset($sudah[$kunci])) {
                        continue;
                    }

                    $kunciGuru = self::GURU_MAPEL[$kodeMapel];

                    PenugasanGuru::create([
                        'id_guru' => $guru[$kunciGuru]->id_guru,
                        'id_mata_pelajaran' => $mapel[$kodeMapel]->id_mata_pelajaran,
                        'id_kelas' => $kelas[$namaKelas]->id_kelas,
                        'id_semester' => $semester->id_semester,
                        'tanggal_mulai' => self::SEMESTER_GANJIL_MULAI,
                        'tanggal_selesai' => self::SEMESTER_GANJIL_SELESAI,
                        'status_aktif' => true,
                    ]);

                    $sudah[$kunci] = true;
                }
            }
        }
    }

    /**
     * @param  array<string, Kelas>  $kelas
     * @param  array<string, MataPelajaran>  $mapel
     * @param  array<string, Guru>  $guru
     * @return array{0: VersiJadwal, 1: Collection<int, JadwalPelajaran>}
     */
    private function buatJadwal(
        Pengguna $admin,
        Semester $semester,
        array $kelas,
        array $mapel,
        array $guru,
    ): array {
        $versi = VersiJadwal::create([
            'id_semester' => $semester->id_semester,
            'nama_versi' => 'Jadwal Semester Ganjil 2026/2027',
            'tanggal_berlaku' => self::SEMESTER_GANJIL_MULAI,
            'tanggal_berakhir' => self::SEMESTER_GANJIL_SELESAI,
            'status_versi' => VersiJadwal::STATUS_AKTIF,
            'dibuat_oleh' => $admin->id_pengguna,
        ]);

        foreach (self::DEFINISI_JADWAL as $namaKelas => $perHari) {
            foreach ($perHari as $hari => $daftarSlot) {
                foreach ($daftarSlot as $slot) {
                    [$kodeMapel, $jamMulai, $jamSelesai, $urutan] = $slot;
                    $kunciGuru = self::GURU_MAPEL[$kodeMapel];

                    JadwalPelajaran::create([
                        'id_versi_jadwal' => $versi->id_versi_jadwal,
                        'id_kelas' => $kelas[$namaKelas]->id_kelas,
                        'id_mata_pelajaran' => $mapel[$kodeMapel]->id_mata_pelajaran,
                        'id_guru' => $guru[$kunciGuru]->id_guru,
                        'hari' => $hari,
                        'jam_mulai' => $jamMulai.':00',
                        'jam_selesai' => $jamSelesai.':00',
                        'ruang_kelas' => 'R-'.str_replace(' ', '-', $namaKelas),
                        'urutan_pelajaran' => $urutan,
                        'status_jadwal' => 'aktif',
                    ]);
                }
            }
        }

        $jadwal = JadwalPelajaran::query()
            ->with(['kelas', 'mataPelajaran', 'guru.pengguna'])
            ->where('id_versi_jadwal', $versi->id_versi_jadwal)
            ->orderBy('jam_mulai')
            ->get();

        return [$versi, $jadwal];
    }

    /**
     * @param  array<string, Guru>  $guru
     */
    private function buatJadwalPiket(array $guru, Semester $semester): void
    {
        $piket = [
            'siti' => ['senin', 'selasa'],
            'ahmad' => ['rabu', 'kamis'],
            'budi' => ['jumat'],
        ];

        foreach ($piket as $kunciGuru => $daftarHari) {
            foreach ($daftarHari as $hari) {
                JadwalGuruPiket::create([
                    'id_guru' => $guru[$kunciGuru]->id_guru,
                    'hari' => $hari,
                    'tanggal_mulai_berlaku' => self::SEMESTER_GANJIL_MULAI,
                    'tanggal_berakhir' => self::SEMESTER_GANJIL_SELESAI,
                    'id_semester' => $semester->id_semester,
                    'status_aktif' => true,
                ]);
            }
        }
    }

    /**
     * @param  array<string, Kelas>  $kelas
     * @return array<string, Collection<int, Siswa>>
     */
    private function buatSiswa(array $kelas, TahunAjaran $tahunAjaran, LayananQrCode $qr): array
    {
        $daftar = [
            // X IPA 1
            ['X IPA 1', 'Andi Pratama', 'L', 'siswa@sekolah.test', '2009-01-12'],
            ['X IPA 1', 'Bunga Lestari', 'P', null, '2009-03-08'],
            ['X IPA 1', 'Citra Maharani', 'P', null, '2009-04-19'],
            ['X IPA 1', 'Dimas Saputra', 'L', null, '2008-12-02'],
            ['X IPA 1', 'Eka Wulandari', 'P', null, '2009-05-27'],
            ['X IPA 1', 'Fajar Nugroho', 'L', null, '2009-06-14'],
            ['X IPA 1', 'Gita Permata', 'P', null, '2009-07-03'],
            ['X IPA 1', 'Hendra Wijaya', 'L', null, '2009-02-21'],
            ['X IPA 1', 'Intan Sari', 'P', null, '2009-08-10'],
            ['X IPA 1', 'Joko Susilo', 'L', null, '2009-09-17'],
            ['X IPA 1', 'Kartika Dewi', 'P', null, '2009-10-05'],
            ['X IPA 1', 'Lukman Hakim', 'L', null, '2009-11-23'],
            // X IPS 1
            ['X IPS 1', 'Maya Anggraini', 'P', null, '2009-01-30'],
            ['X IPS 1', 'Naufal Rizky', 'L', null, '2009-02-11'],
            ['X IPS 1', 'Oksana Putri', 'P', null, '2009-03-25'],
            ['X IPS 1', 'Putra Aditya', 'L', null, '2009-04-07'],
            ['X IPS 1', 'Qori Amelia', 'P', null, '2009-05-16'],
            ['X IPS 1', 'Rizky Ramadhan', 'L', null, '2009-06-29'],
            ['X IPS 1', 'Salsabila', 'P', null, '2009-07-08'],
            ['X IPS 1', 'Taufik Hidayat', 'L', null, '2009-08-14'],
            ['X IPS 1', 'Ulfa Nabila', 'P', null, '2009-09-06'],
            ['X IPS 1', 'Wahyu Setiawan', 'L', null, '2009-10-19'],
        ];

        $siswaPerKelas = [];
        $agama = ['Islam', 'Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha'];

        foreach ($daftar as $index => $item) {
            [$namaKelas, $nama, $jenisKelamin, $emailKhusus, $tanggalLahir] = $item;

            $nis = (string) (2425001 + $index);
            $nisn = '00712345'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
            $email = $emailKhusus ?? Str::lower(Str::slug($nama, '.')).'@sekolah.test';

            $pengguna = Pengguna::create([
                'nama_pengguna' => $nama,
                'email' => $email,
                'kata_sandi' => Hash::make(self::KATA_SANDI_DEMO),
                'peran' => 'siswa',
                'status_aktif' => true,
            ]);

            $siswa = Siswa::create([
                'id_pengguna' => $pengguna->id_pengguna,
                'nis' => $nis,
                'nisn' => $nisn,
                'nama_lengkap' => $nama,
                'jenis_kelamin' => $jenisKelamin,
                'tempat_lahir' => 'Makassar',
                'tanggal_lahir' => $tanggalLahir,
                'agama' => $agama[$index % count($agama)],
                'alamat' => 'Jl. Pendidikan No. '.($index + 1).', Makassar',
                'nomor_telepon' => '0812'.str_pad((string) (30000000 + $index), 8, '0', STR_PAD_LEFT),
                'nama_orang_tua' => 'Bapak/Ibu '.explode(' ', $nama)[0],
                'nomor_telepon_orang_tua' => '0813'.str_pad((string) (40000000 + $index), 8, '0', STR_PAD_LEFT),
                'status_siswa' => 'aktif',
            ]);

            RiwayatKelasSiswa::create([
                'id_siswa' => $siswa->id_siswa,
                'id_kelas' => $kelas[$namaKelas]->id_kelas,
                'id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran,
                'tanggal_mulai' => self::TAHUN_AJARAN_MULAI,
                'tanggal_selesai' => null,
                'status_aktif' => true,
            ]);

            $qr->buat($siswa);

            $siswa->setRelation('pengguna', $pengguna);
            $siswaPerKelas[$namaKelas][] = $siswa;
        }

        return array_map(fn (array $daftarSiswa) => collect($daftarSiswa), $siswaPerKelas);
    }

    /**
     * @param  array<string, Kelas>  $kelas
     * @param  array<string, Collection<int, Siswa>>  $siswaPerKelas
     * @return array<string, Collection<int, PetugasKonfirmasi>>
     */
    private function buatPetugasKonfirmasi(array $kelas, array $siswaPerKelas, Semester $semester): array
    {
        $penugasan = [
            'X IPA 1' => ['Andi Pratama', 'Bunga Lestari', 'Citra Maharani'],
            'X IPS 1' => ['Maya Anggraini', 'Naufal Rizky'],
        ];

        $hasil = [];

        foreach ($penugasan as $namaKelas => $daftarNama) {
            $hasil[$namaKelas] = collect();

            foreach ($daftarNama as $prioritas => $nama) {
                $siswa = $siswaPerKelas[$namaKelas]->firstWhere('nama_lengkap', $nama);

                if (! $siswa) {
                    continue;
                }

                $hasil[$namaKelas]->push(PetugasKonfirmasi::create([
                    'id_siswa' => $siswa->id_siswa,
                    'id_kelas' => $kelas[$namaKelas]->id_kelas,
                    'id_semester' => $semester->id_semester,
                    'urutan_prioritas' => $prioritas + 1,
                    'tanggal_mulai' => self::SEMESTER_GANJIL_MULAI,
                    'tanggal_selesai' => self::SEMESTER_GANJIL_SELESAI,
                    'status_aktif' => true,
                ]));
            }
        }

        return $hasil;
    }

    private function seedPengaturan(Pengguna $admin, Semester $semester): void
    {
        foreach (LayananPengaturan::BAWAAN as $kunci => $nilai) {
            $tipe = is_bool($nilai) ? 'boolean' : (is_int($nilai) ? 'angka' : 'teks');

            PengaturanSistem::create([
                'kunci' => $kunci,
                'nilai' => is_bool($nilai) ? ($nilai ? '1' : '0') : (string) $nilai,
                'tipe' => $tipe,
                'deskripsi' => LayananPengaturan::LABEL[$kunci] ?? null,
                'tanggal_berlaku' => $semester->tanggal_mulai,
                'status_aktif' => true,
                'dibuat_oleh' => $admin->id_pengguna,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $konteks
     */
    private function seedRiwayat(array $konteks): void
    {
        $semester = $konteks['semester'];
        $mulai = $semester->tanggal_mulai->copy();
        $kemarin = now()->copy()->subDay()->startOfDay();
        $akhir = $kemarin->lt($semester->tanggal_selesai) ? $kemarin->copy() : $semester->tanggal_selesai->copy();

        if ($akhir->lt($mulai)) {
            return;
        }

        /** @var Collection<int, JadwalPelajaran> $jadwal */
        $jadwal = $konteks['jadwal'];
        $jadwalPerHari = $jadwal->groupBy('hari');

        $sesiRows = [];
        $meta = [];

        for ($tanggal = $mulai->copy(); $tanggal->lte($akhir); $tanggal->addDay()) {
            if ($tanggal->isWeekend()) {
                continue;
            }

            $hari = LayananJadwal::namaHari($tanggal);

            foreach ($jadwalPerHari->get($hari, collect()) as $itemJadwal) {
                $kunciSpesial = $tanggal->toDateString().'|'.$itemJadwal->kelas->nama_kelas.'|'.$itemJadwal->urutan_pelajaran;
                $klasifikasi = $this->klasifikasiSesi($kunciSpesial);

                $mulaiSesi = Carbon::parse($tanggal->toDateString().' '.$itemJadwal->jam_mulai);
                $selesaiSesi = Carbon::parse($tanggal->toDateString().' '.$itemJadwal->jam_selesai);
                $dibuka = $selesaiSesi->copy()->subMinutes((int) LayananPengaturan::BAWAAN['menit_buka_sebelum_selesai']);
                $ditutup = $selesaiSesi->copy()->addMinutes((int) LayananPengaturan::BAWAAN['menit_tutup_setelah_selesai']);
                $batas = $selesaiSesi->copy()->addMinutes((int) LayananPengaturan::BAWAAN['menit_batas_konfirmasi']);
                $disimpan = $klasifikasi['terisi'] ? $selesaiSesi->copy()->subMinutes(2) : null;

                $pelaksana = $klasifikasi['terisi'] ? $itemJadwal->guru : null;
                $jenisPelaksana = $klasifikasi['terisi'] ? SesiAbsensi::PELAKSANA_PENGAMPU : null;
                $diambilAlihPada = null;
                $alasanPengambilalihan = null;

                if ($klasifikasi['terisi'] && isset(self::PENGAMBILALIHAN[$kunciSpesial])) {
                    $ambil = self::PENGAMBILALIHAN[$kunciSpesial];
                    $pelaksana = $konteks['guru'][$ambil['guru']];
                    $jenisPelaksana = SesiAbsensi::PELAKSANA_PIKET;
                    $diambilAlihPada = $selesaiSesi->copy()->subMinutes(10);
                    $alasanPengambilalihan = $ambil['alasan'];
                }

                $metode = $klasifikasi['terisi'] ? $this->metodeSesi($kunciSpesial) : null;

                $sesiRows[] = [
                    'id_jadwal' => $itemJadwal->id_jadwal,
                    'tanggal_pelajaran' => $tanggal->toDateString(),
                    'waktu_mulai' => $mulaiSesi->toDateTimeString(),
                    'waktu_selesai' => $selesaiSesi->toDateTimeString(),
                    'waktu_absensi_dibuka' => $dibuka->toDateTimeString(),
                    'waktu_absensi_ditutup' => $ditutup->toDateTimeString(),
                    'waktu_batas_konfirmasi' => $batas->toDateTimeString(),
                    'id_guru_penanggung_jawab' => $itemJadwal->id_guru,
                    'id_guru_pelaksana' => $pelaksana?->id_guru,
                    'jenis_pelaksana' => $jenisPelaksana,
                    'metode_absensi' => $metode,
                    'status_sesi' => $klasifikasi['status'],
                    'waktu_disimpan' => $disimpan?->toDateTimeString(),
                    'diambil_alih_pada' => $diambilAlihPada?->toDateTimeString(),
                    'alasan_pengambilalihan' => $alasanPengambilalihan,
                    'alasan_pembatalan' => $klasifikasi['alasan'] ?? null,
                    'dibuat_pada' => $tanggal->copy()->startOfDay()->toDateTimeString(),
                    'diperbarui_pada' => ($disimpan ?? $ditutup)->toDateTimeString(),
                ];

                $meta[$itemJadwal->id_jadwal.'|'.$tanggal->toDateString()] = [
                    'jadwal' => $itemJadwal,
                    'tanggal' => $tanggal->toDateString(),
                    'kunci_spesial' => $kunciSpesial,
                    'selesai' => $selesaiSesi,
                    'batas' => $batas,
                    'disimpan' => $disimpan,
                    'status' => $klasifikasi['status'],
                    'konfirmasi' => $klasifikasi['konfirmasi'],
                    'terisi' => $klasifikasi['terisi'],
                    'metode' => $metode,
                    'pelaksana' => $pelaksana,
                ];
            }
        }

        foreach (array_chunk($sesiRows, 250) as $chunk) {
            DB::table('sesi_absensi')->insert($chunk);
        }

        $idSesi = DB::table('sesi_absensi')
            ->whereBetween('tanggal_pelajaran', [$mulai->toDateString(), $akhir->toDateString()])
            ->get(['id_sesi_absensi', 'id_jadwal', 'tanggal_pelajaran'])
            ->mapWithKeys(fn (object $baris) => [
                $baris->id_jadwal.'|'.$baris->tanggal_pelajaran => (int) $baris->id_sesi_absensi,
            ]);

        $catatanRows = [];
        $permintaanRows = [];

        foreach ($meta as $kunci => $item) {
            if (! $item['terisi']) {
                continue;
            }

            $id = $idSesi[$kunci] ?? null;

            if (! $id) {
                continue;
            }

            $siswaKelas = $konteks['siswa'][$item['jadwal']->kelas->nama_kelas];
            $idPencatat = $item['pelaksana']?->id_pengguna ?? $item['jadwal']->guru->id_pengguna;
            $khusus = self::KEHADIRAN_KHUSUS[$item['kunci_spesial']] ?? null;

            foreach ($siswaKelas as $indeks => $murid) {
                if ($khusus && $khusus['nis'] === $murid->nis) {
                    $status = $khusus['status'];
                    $keterangan = $khusus['keterangan'];
                } elseif ($item['metode'] === SesiAbsensi::METODE_QR) {
                    $status = CatatanAbsensi::HADIR;
                    $keterangan = null;
                } else {
                    $acak = crc32($murid->id_siswa.'|'.$item['tanggal'].'|'.$item['jadwal']->id_mata_pelajaran) % 100;
                    $status = match (true) {
                        $acak < 2 => CatatanAbsensi::ALPHA,
                        $acak < 5 => CatatanAbsensi::IZIN,
                        $acak < 7 => CatatanAbsensi::SAKIT,
                        default => CatatanAbsensi::HADIR,
                    };
                    $keterangan = match ($status) {
                        CatatanAbsensi::IZIN => 'Izin keperluan keluarga.',
                        CatatanAbsensi::SAKIT => 'Sakit dengan surat keterangan orang tua.',
                        CatatanAbsensi::ALPHA => 'Tidak hadir tanpa keterangan.',
                        default => null,
                    };
                }

                $metodeCatatan = $status === CatatanAbsensi::HADIR
                    && in_array($item['metode'], [SesiAbsensi::METODE_QR, SesiAbsensi::METODE_GABUNGAN], true)
                    ? CatatanAbsensi::METODE_QR
                    : CatatanAbsensi::METODE_MANUAL;

                $waktuHadir = $status === CatatanAbsensi::HADIR
                    ? $item['selesai']->copy()->subMinutes($item['metode'] === SesiAbsensi::METODE_QR ? 10 : 4)->addSeconds($indeks * 4)
                    : null;

                $catatanRows[] = [
                    'id_sesi_absensi' => $id,
                    'id_siswa' => $murid->id_siswa,
                    'status_kehadiran' => $status,
                    'metode_absensi' => $metodeCatatan,
                    'waktu_kehadiran' => $waktuHadir?->toDateTimeString(),
                    'id_pengguna_pencatat' => $idPencatat,
                    'keterangan' => $keterangan,
                    'dibuat_pada' => $item['disimpan']?->toDateTimeString(),
                    'diperbarui_pada' => $item['disimpan']?->toDateTimeString(),
                ];
            }

            $daftarPetugas = $konteks['petugas'][$item['jadwal']->kelas->nama_kelas] ?? collect();
            $pertama = $daftarPetugas->first();

            if (! $pertama) {
                continue;
            }

            if ($item['konfirmasi'] === 'tidak_respons' && $daftarPetugas->count() > 1) {
                $kedua = $daftarPetugas->get(1);
                $permintaanRows[] = [
                    'id_sesi_absensi' => $id,
                    'id_siswa_penerima' => $pertama->id_siswa,
                    'id_petugas_konfirmasi' => $pertama->id_petugas_konfirmasi,
                    'urutan_penerima' => 1,
                    'status_permintaan' => PermintaanKonfirmasi::KEDALUWARSA,
                    'waktu_dikirim' => $item['selesai']->copy()->subMinute()->toDateTimeString(),
                    'waktu_direspons' => null,
                    'waktu_kedaluwarsa' => $item['selesai']->copy()->addSeconds(30)->toDateTimeString(),
                    'jawaban' => null,
                    'keterangan' => 'Tidak ada respons sebelum batas pengalihan.',
                    'dibuat_pada' => $item['selesai']->toDateTimeString(),
                    'diperbarui_pada' => $item['selesai']->copy()->addSeconds(30)->toDateTimeString(),
                ];

                $permintaanRows[] = [
                    'id_sesi_absensi' => $id,
                    'id_siswa_penerima' => $kedua->id_siswa,
                    'id_petugas_konfirmasi' => $kedua->id_petugas_konfirmasi,
                    'urutan_penerima' => 2,
                    'status_permintaan' => PermintaanKonfirmasi::TIDAK_DIKONFIRMASI,
                    'waktu_dikirim' => $item['selesai']->copy()->addSeconds(30)->toDateTimeString(),
                    'waktu_direspons' => null,
                    'waktu_kedaluwarsa' => $item['batas']->toDateTimeString(),
                    'jawaban' => null,
                    'keterangan' => 'Tidak ada respons valid sampai batas waktu.',
                    'dibuat_pada' => $item['selesai']->copy()->addSeconds(30)->toDateTimeString(),
                    'diperbarui_pada' => $item['batas']->toDateTimeString(),
                ];

                continue;
            }

            $negative = $item['konfirmasi'] === 'negatif';

            $permintaanRows[] = [
                'id_sesi_absensi' => $id,
                'id_siswa_penerima' => $pertama->id_siswa,
                'id_petugas_konfirmasi' => $pertama->id_petugas_konfirmasi,
                'urutan_penerima' => 1,
                'status_permintaan' => PermintaanKonfirmasi::DIKONFIRMASI,
                'waktu_dikirim' => $item['selesai']->copy()->subMinute()->toDateTimeString(),
                'waktu_direspons' => $item['selesai']->copy()->addMinute()->toDateTimeString(),
                'waktu_kedaluwarsa' => $item['batas']->toDateTimeString(),
                'jawaban' => $negative
                    ? PermintaanKonfirmasi::TIDAK_BERLANGSUNG
                    : PermintaanKonfirmasi::BERLANGSUNG,
                'keterangan' => $negative
                    ? 'Siswa melaporkan pembelajaran tidak berlangsung.'
                    : 'Pembelajaran dikonfirmasi berlangsung.',
                'dibuat_pada' => $item['selesai']->copy()->subMinute()->toDateTimeString(),
                'diperbarui_pada' => $item['selesai']->copy()->addMinute()->toDateTimeString(),
            ];
        }

        foreach (array_chunk($catatanRows, 500) as $chunk) {
            DB::table('catatan_absensi')->insert($chunk);
        }

        foreach (array_chunk($permintaanRows, 500) as $chunk) {
            DB::table('permintaan_konfirmasi')->insert($chunk);
        }

        $this->seedKoreksiAudit($konteks);
    }

    /**
     * @param  array<string, mixed>  $konteks
     */
    private function seedKoreksiAudit(array $konteks): void
    {
        $sesiKoreksi = SesiAbsensi::query()
            ->whereDate('tanggal_pelajaran', '2026-10-29')
            ->whereHas('jadwal', function ($q) use ($konteks) {
                $q->where('id_kelas', $konteks['kelas']['X IPA 1']->id_kelas)
                    ->where('urutan_pelajaran', 3);
            })
            ->first();

        if (! $sesiKoreksi) {
            return;
        }

        $siswa = $konteks['siswa']['X IPA 1']->firstWhere('nis', '2425002');
        $catatan = CatatanAbsensi::query()
            ->where('id_sesi_absensi', $sesiKoreksi->id_sesi_absensi)
            ->where('id_siswa', $siswa?->id_siswa)
            ->first();

        if (! $catatan) {
            return;
        }

        $statusSebelumnya = $catatan->status_kehadiran;
        $catatan->update([
            'status_kehadiran' => CatatanAbsensi::HADIR,
            'metode_absensi' => CatatanAbsensi::METODE_MANUAL,
            'waktu_kehadiran' => $sesiKoreksi->waktu_selesai->copy()->subMinutes(2),
            'id_pengguna_pencatat' => $konteks['admin']->id_pengguna,
            'keterangan' => 'Dikoreksi admin setelah verifikasi bukti kehadiran.',
        ]);

        AuditAbsensi::create([
            'id_catatan_absensi' => $catatan->id_catatan_absensi,
            'id_pengguna' => $konteks['admin']->id_pengguna,
            'status_sebelumnya' => $statusSebelumnya,
            'status_sesudahnya' => CatatanAbsensi::HADIR,
            'alasan_perubahan' => 'Verifikasi bukti kehadiran oleh admin.',
            'waktu_perubahan' => $sesiKoreksi->waktu_selesai->copy()->addHours(2),
        ]);

        $konteks['log']->catat(
            $konteks['admin'],
            'koreksi_absensi',
            'Absensi',
            'Mengoreksi status absensi siswa '.$siswa->nama_lengkap.' dari '.$statusSebelumnya.' menjadi hadir.',
            'sesi_absensi',
            $sesiKoreksi->id_sesi_absensi,
            ['status_kehadiran' => $statusSebelumnya],
            ['status_kehadiran' => CatatanAbsensi::HADIR],
        );
    }

    /**
     * @param  array<string, mixed>  $konteks
     */
    private function seedDemoHariIni(array $konteks): void
    {
        $hariIni = now()->copy()->startOfDay();

        if ($hariIni->lt($konteks['semester']->tanggal_mulai) || $hariIni->gt($konteks['semester']->tanggal_selesai)) {
            $this->command?->warn('Tanggal hari ini berada di luar semester aktif; sesi konfirmasi demo dilewati.');

            return;
        }

        $jadwalIpa = $konteks['jadwal']->where('kelas.nama_kelas', 'X IPA 1');
        $hari = LayananJadwal::namaHari($hariIni);

        $sesiHariIni = collect();

        if ($jadwalIpa->contains('hari', $hari)) {
            $sesiHariIni = app(LayananJadwal::class)
                ->pastikanSesiTanggal($hariIni)
                ->filter(fn (SesiAbsensi $item) => $item->jadwal?->id_kelas === $konteks['kelas']['X IPA 1']->id_kelas)
                ->values();
        }

        // Pilih sesi yang sudah dimulai paling akhir agar waktu demo tidak
        // bertumpang tindih dengan sesi lain pada kelas yang sama.
        $sesi = $sesiHariIni
            ->filter(fn (SesiAbsensi $item) => $item->waktu_mulai->lte(now()))
            ->last() ?? $sesiHariIni->first();

        if (! $sesi) {
            // Akhir pekan atau hari tanpa jadwal: tetap buat satu sesi demo.
            $jadwal = $jadwalIpa->first();

            if (! $jadwal) {
                return;
            }

            $sesi = SesiAbsensi::create([
                'id_jadwal' => $jadwal->id_jadwal,
                'tanggal_pelajaran' => $hariIni->toDateString(),
                'waktu_mulai' => Carbon::parse($hariIni->toDateString().' '.$jadwal->jam_mulai),
                'waktu_selesai' => Carbon::parse($hariIni->toDateString().' '.$jadwal->jam_selesai),
                'waktu_absensi_dibuka' => Carbon::parse($hariIni->toDateString().' '.$jadwal->jam_selesai)->subMinutes(15),
                'waktu_absensi_ditutup' => Carbon::parse($hariIni->toDateString().' '.$jadwal->jam_selesai),
                'waktu_batas_konfirmasi' => Carbon::parse($hariIni->toDateString().' '.$jadwal->jam_selesai)->addMinutes(5),
                'id_guru_penanggung_jawab' => $jadwal->id_guru,
                'status_sesi' => SesiAbsensi::STATUS_MENUNGGU,
            ]);
        }

        $sesi->loadMissing('jadwal.guru.pengguna');
        $jadwal = $sesi->jadwal;

        $selesai = now()->copy()->subMinute()->startOfMinute();

        if ($selesai->lt($hariIni)) {
            $selesai = $hariIni->copy();
        }

        $mulai = $sesi->waktu_mulai->copy();

        if ($mulai->gte($selesai)) {
            $mulai = $selesai->copy()->subMinutes(5);
        }

        $dibuka = $selesai->copy()->subMinutes(15);
        $ditutup = $selesai->copy();
        $batas = $selesai->copy()->addMinutes(5);
        $disimpan = $dibuka->copy()->addMinutes(5);

        $pelaksana = $jadwal->guru;
        $jenisPelaksana = SesiAbsensi::PELAKSANA_PENGAMPU;
        $diambilAlihPada = null;
        $alasanPengambilalihan = null;
        $guruBudi = $konteks['guru']['budi'];

        if ($pelaksana->id_guru !== $guruBudi->id_guru && $hari === 'jumat') {
            $pelaksana = $guruBudi;
            $jenisPelaksana = SesiAbsensi::PELAKSANA_PIKET;
            $diambilAlihPada = $dibuka->copy()->addMinutes(2);
            $alasanPengambilalihan = 'Guru pengampu berhalangan; sesi demo diambil alih guru piket.';
        }

        $sesi->update([
            'waktu_mulai' => $mulai,
            'waktu_selesai' => $selesai,
            'waktu_absensi_dibuka' => $dibuka,
            'waktu_absensi_ditutup' => $ditutup,
            'waktu_batas_konfirmasi' => $batas,
            'id_guru_pelaksana' => $pelaksana->id_guru,
            'jenis_pelaksana' => $jenisPelaksana,
            'metode_absensi' => SesiAbsensi::METODE_MANUAL,
            'status_sesi' => SesiAbsensi::STATUS_MENUNGGU_KONFIRMASI,
            'waktu_disimpan' => $disimpan,
            'diambil_alih_pada' => $diambilAlihPada,
            'alasan_pengambilalihan' => $alasanPengambilalihan,
            'alasan_pembatalan' => null,
        ]);

        CatatanAbsensi::query()
            ->where('id_sesi_absensi', $sesi->id_sesi_absensi)
            ->delete();

        PermintaanKonfirmasi::query()
            ->where('id_sesi_absensi', $sesi->id_sesi_absensi)
            ->delete();

        $siswaKelas = $konteks['siswa']['X IPA 1'];
        $idPencatat = $pelaksana->id_pengguna;

        $catatanRows = $siswaKelas->values()->map(function (Siswa $murid, int $indeks) use ($sesi, $idPencatat, $disimpan) {
            return [
                'id_sesi_absensi' => $sesi->id_sesi_absensi,
                'id_siswa' => $murid->id_siswa,
                'status_kehadiran' => CatatanAbsensi::HADIR,
                'metode_absensi' => CatatanAbsensi::METODE_MANUAL,
                'waktu_kehadiran' => $disimpan->copy()->addSeconds($indeks * 3)->toDateTimeString(),
                'id_pengguna_pencatat' => $idPencatat,
                'keterangan' => null,
                'dibuat_pada' => $disimpan->toDateTimeString(),
                'diperbarui_pada' => $disimpan->toDateTimeString(),
            ];
        })->all();

        DB::table('catatan_absensi')->insert($catatanRows);

        $petugasPertama = $konteks['petugas']['X IPA 1']->first();

        if (! $petugasPertama) {
            return;
        }

        $permintaan = PermintaanKonfirmasi::create([
            'id_sesi_absensi' => $sesi->id_sesi_absensi,
            'id_siswa_penerima' => $petugasPertama->id_siswa,
            'id_petugas_konfirmasi' => $petugasPertama->id_petugas_konfirmasi,
            'urutan_penerima' => 1,
            'status_permintaan' => PermintaanKonfirmasi::MENUNGGU,
            'waktu_dikirim' => now(),
            'waktu_kedaluwarsa' => $batas,
        ]);

        $siswaPetugas = $petugasPertama->siswa;

        if ($siswaPetugas?->pengguna) {
            Notifikasi::create([
                'id_pengguna' => $siswaPetugas->pengguna->id_pengguna,
                'judul' => 'Permintaan konfirmasi pembelajaran',
                'pesan' => 'Konfirmasi apakah pembelajaran '.$jadwal->mataPelajaran->nama_mata_pelajaran
                    .' kelas X IPA 1 benar-benar berlangsung. Batas respons pukul '
                    .$permintaan->waktu_kedaluwarsa->format('H:i').'.',
                'jenis' => 'konfirmasi',
                'url' => route('siswa.konfirmasi.index'),
                'sudah_dibaca' => false,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $konteks
     */
    private function hitungRingkasan(array $konteks): void
    {
        $semester = $konteks['semester'];
        $mulai = $semester->tanggal_mulai->copy();
        $kemarin = now()->copy()->subDay()->startOfDay();
        $akhir = $kemarin->lt($semester->tanggal_selesai) ? $kemarin->copy() : $semester->tanggal_selesai->copy();

        if ($akhir->lt($mulai)) {
            return;
        }

        $semuaSiswa = collect($konteks['siswa'])->flatten(1);

        for ($tanggal = $mulai->copy(); $tanggal->lte($akhir); $tanggal->addDay()) {
            if ($tanggal->isWeekend()) {
                continue;
            }

            foreach ($semuaSiswa as $siswa) {
                $konteks['rekap']->hitungUlangHarian($siswa->id_siswa, $tanggal->copy());
            }
        }
    }

    /**
     * @param  array<string, mixed>  $konteks
     */
    private function seedLogDanNotifikasi(array $konteks): void
    {
        $admin = $konteks['admin'];
        $guru = $konteks['guru'];
        $versi = $konteks['versi_jadwal'];
        $kelasIpa = $konteks['kelas']['X IPA 1'];
        $kelasIps = $konteks['kelas']['X IPS 1'];
        $siswaDemo = $konteks['siswa']['X IPA 1']->firstWhere('nis', '2425001');

        $sesiPerlu = SesiAbsensi::query()
            ->whereDate('tanggal_pelajaran', '2026-10-09')
            ->whereHas('jadwal', fn ($q) => $q->where('id_kelas', $kelasIpa->id_kelas)->where('urutan_pelajaran', 3))
            ->first();

        $sesiBelum = SesiAbsensi::query()
            ->whereDate('tanggal_pelajaran', '2026-11-04')
            ->whereHas('jadwal', fn ($q) => $q->where('id_kelas', $kelasIpa->id_kelas)->where('urutan_pelajaran', 4))
            ->first();

        $sesiAmbilAlih = SesiAbsensi::query()
            ->whereDate('tanggal_pelajaran', '2026-10-02')
            ->whereHas('jadwal', fn ($q) => $q->where('id_kelas', $kelasIpa->id_kelas)->where('urutan_pelajaran', 3))
            ->first();

        $sesiDemo = SesiAbsensi::query()
            ->whereDate('tanggal_pelajaran', now()->toDateString())
            ->whereHas('jadwal', fn ($q) => $q->where('id_kelas', $kelasIpa->id_kelas))
            ->where('status_sesi', SesiAbsensi::STATUS_MENUNGGU_KONFIRMASI)
            ->orderBy('waktu_mulai')
            ->first();

        $catatan = [
            [
                'pengguna' => $admin,
                'jenis' => 'buat_jadwal',
                'modul' => 'Jadwal',
                'deskripsi' => 'Menetapkan versi jadwal Semester Ganjil 2026/2027 untuk kelas X IPA 1 dan X IPS 1.',
                'jenis_data' => 'versi_jadwal',
                'id_data' => $versi->id_versi_jadwal,
                'waktu' => now()->copy()->subDays(10)->setTime(9, 15),
            ],
            [
                'pengguna' => $admin,
                'jenis' => 'tambah_siswa',
                'modul' => 'Data Siswa',
                'deskripsi' => 'Menambahkan 22 siswa aktif ke kelas X IPA 1 dan X IPS 1.',
                'jenis_data' => 'kelas',
                'id_data' => $kelasIpa->id_kelas,
                'waktu' => now()->copy()->subDays(9)->setTime(10, 5),
            ],
            [
                'pengguna' => $admin,
                'jenis' => 'buat_penugasan',
                'modul' => 'Penugasan Guru',
                'deskripsi' => 'Menetapkan penugasan mengajar guru untuk seluruh mata pelajaran kelas X.',
                'jenis_data' => 'semester',
                'id_data' => $konteks['semester']->id_semester,
                'waktu' => now()->copy()->subDays(8)->setTime(14, 20),
            ],
            [
                'pengguna' => $admin,
                'jenis' => 'ubah_pengaturan',
                'modul' => 'Pengaturan',
                'deskripsi' => 'Memastikan aturan waktu absensi, batas konfirmasi, dan zona waktu sesuai kebijakan sekolah.',
                'jenis_data' => null,
                'id_data' => null,
                'waktu' => now()->copy()->subDays(7)->setTime(8, 40),
            ],
            [
                'pengguna' => $guru['budi']->pengguna,
                'jenis' => 'simpan_absensi',
                'modul' => 'Absensi',
                'deskripsi' => 'Menyimpan absensi Matematika kelas X IPA 1 secara manual.',
                'jenis_data' => 'sesi_absensi',
                'id_data' => $sesiDemo?->id_sesi_absensi,
                'waktu' => now()->copy()->subDays(4)->setTime(9, 20),
            ],
            [
                'pengguna' => $guru['budi']->pengguna,
                'jenis' => 'ambil_alih',
                'modul' => 'Guru Piket',
                'deskripsi' => 'Mengambil alih sesi IPS kelas X IPA 1 sebagai guru piket pada hari Jumat.',
                'jenis_data' => 'sesi_absensi',
                'id_data' => $sesiAmbilAlih?->id_sesi_absensi,
                'waktu' => now()->copy()->subDays(3)->setTime(10, 45),
            ],
            [
                'pengguna' => $siswaDemo?->pengguna,
                'jenis' => 'konfirmasi',
                'modul' => 'Konfirmasi Pembelajaran',
                'deskripsi' => 'Mengonfirmasi bahwa pembelajaran Matematika kelas X IPA 1 berlangsung.',
                'jenis_data' => 'permintaan_konfirmasi',
                'id_data' => null,
                'waktu' => now()->copy()->subDays(2)->setTime(12, 30),
            ],
            [
                'pengguna' => $admin,
                'jenis' => 'ekspor_laporan',
                'modul' => 'Laporan',
                'deskripsi' => 'Mengekspor laporan absensi kelas X IPS 1 untuk evaluasi wali kelas.',
                'jenis_data' => 'kelas',
                'id_data' => $kelasIps->id_kelas,
                'waktu' => now()->copy()->subDay()->setTime(15, 5),
            ],
        ];

        foreach ($catatan as $item) {
            if (! $item['pengguna']) {
                continue;
            }

            LogAktivitas::create([
                'id_pengguna' => $item['pengguna']->id_pengguna,
                'jenis_aktivitas' => $item['jenis'],
                'nama_modul' => $item['modul'],
                'deskripsi_aktivitas' => Str::limit($item['deskripsi'], 250),
                'jenis_data' => $item['jenis_data'],
                'id_data_terkait' => $item['id_data'] !== null ? (string) $item['id_data'] : null,
                'waktu_aktivitas' => $item['waktu'],
            ]);
        }

        if ($sesiPerlu) {
            Notifikasi::create([
                'id_pengguna' => $admin->id_pengguna,
                'judul' => 'Pemeriksaan pembelajaran diperlukan',
                'pesan' => 'Siswa melaporkan pembelajaran IPS kelas X IPA 1 tidak berlangsung. Periksa bukti dan catatan absensi sebelum mengambil keputusan.',
                'jenis' => 'pemeriksaan',
                'url' => route('admin.sesi.show', $sesiPerlu->id_sesi_absensi),
                'sudah_dibaca' => false,
            ]);
        }

        if ($sesiBelum) {
            Notifikasi::create([
                'id_pengguna' => $admin->id_pengguna,
                'judul' => 'Sesi belum dikonfirmasi',
                'pesan' => 'Sesi IPS kelas X IPA 1 tanggal 04/11/2026 tidak mendapatkan konfirmasi siswa sampai batas waktu.',
                'jenis' => 'konfirmasi',
                'url' => route('admin.sesi.show', $sesiBelum->id_sesi_absensi),
                'sudah_dibaca' => false,
            ]);
        }

        Notifikasi::create([
            'id_pengguna' => $admin->id_pengguna,
            'judul' => 'Selamat datang di Sistem Absensi Sekolah',
            'pesan' => 'Data demo telah siap. Gunakan menu Laporan untuk melihat rekap kelas dan menu Log Aktivitas untuk menelusuri audit.',
            'jenis' => 'sistem',
            'url' => route('admin.laporan.index'),
            'sudah_dibaca' => false,
        ]);
    }

    /**
     * @return array{status: string, alasan?: string, terisi: bool, konfirmasi: string}
     */
    private function klasifikasiSesi(string $kunci): array
    {
        if (isset(self::SESI_DIBATALKAN[$kunci])) {
            return [
                'status' => SesiAbsensi::STATUS_DIBATALKAN,
                'alasan' => self::SESI_DIBATALKAN[$kunci],
                'terisi' => false,
                'konfirmasi' => 'tidak_ada',
            ];
        }

        if (isset(self::SESI_TANPA_ABSENSI[$kunci])) {
            return [
                'status' => SesiAbsensi::STATUS_DITUTUP,
                'terisi' => false,
                'konfirmasi' => 'tidak_ada',
            ];
        }

        if (isset(self::SESI_PERLU_PEMERIKSAAN[$kunci])) {
            return [
                'status' => SesiAbsensi::STATUS_PERLU_PEMERIKSAAN,
                'terisi' => true,
                'konfirmasi' => 'negatif',
            ];
        }

        if (isset(self::SESI_BELUM_DIKONFIRMASI[$kunci])) {
            return [
                'status' => SesiAbsensi::STATUS_BELUM_DIKONFIRMASI,
                'terisi' => true,
                'konfirmasi' => 'tidak_respons',
            ];
        }

        return [
            'status' => SesiAbsensi::STATUS_TERKONFIRMASI,
            'terisi' => true,
            'konfirmasi' => 'positif',
        ];
    }

    private function metodeSesi(string $kunci): string
    {
        if (isset(self::SESI_QR[$kunci])) {
            return SesiAbsensi::METODE_QR;
        }

        if (isset(self::SESI_GABUNGAN[$kunci])) {
            return SesiAbsensi::METODE_GABUNGAN;
        }

        return SesiAbsensi::METODE_MANUAL;
    }
}
