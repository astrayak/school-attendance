# Penjelasan Database Sistem Absensi

Panduan presentasi dengan bahasa sederhana. Cocok untuk audiens guru, staf sekolah, atau penguji yang bukan programmer.

---

## File gambar yang dipakai

| File | Isi | Dipakai untuk |
|---|---|---|
| `docs/alur-absensi.png` | Alur 6 langkah, ukuran 16:9 | Slide utama saat menjelaskan cara kerja |
| `docs/schema-database.png` | Poster 22 tabel + relasi | Slide teknis saat menunjukkan struktur |
| `docs/alur-absensi.html` | Sumber gambar alur | Edit teks/warna lalu render ulang |
| `docs/schema-database.html` | Sumber poster schema | Edit tabel/relasi lalu render ulang |

Saran cetak: poster schema dicetak **A3 landscape**, gambar alur cukup ditampilkan di proyektor.

---

## 1. Kalimat pembuka (hafalkan ini)

> "Database ini ibarat **buku induk digital** sekolah. Semua data guru, siswa, kelas, jadwal, dan kehadiran disimpan di satu tempat, saling terhubung, dan tidak ada yang dobel."

Itu inti seluruh presentasi. Sisanya hanya menjelaskan kalimat tersebut.

---

## 2. Analogi dasar: lemari arsip sekolah

Sebelum masuk ke istilah teknis, pakai perumpamaan ini:

| Istilah database | Analogi lemari arsip |
|---|---|
| **Database** | Lemari arsip |
| **Tabel** | Satu laci atau satu map |
| **Baris** | Satu lembar formulir di dalam map |
| **Kolom** | Isian pada formulir (nama, tanggal, status) |
| **Primary Key (PK)** | Nomor unik formulir, misalnya nomor urut |
| **Foreign Key (FK)** | Catatan "lihat formulir nomor berapa" |
| **Unique** | Aturan "tidak boleh ada dua formulir dengan nomor sama" |

Kalau audiens hanya ingat satu hal, ingat ini: **PK adalah nomor identitas, FK adalah penghubung ke nomor identitas lain.**

---

## 3. Kenapa tabelnya banyak? Kenapa tidak satu tabel saja?

Pertanyaan ini paling sering muncul. Jawab dengan contoh.

Bayangkan semua data dimasukkan ke satu tabel besar:

| Nama siswa | Kelas | Mapel | Guru | Tanggal | Status |
|---|---|---|---|---|---|
| Siti | X IPA 1 | Matematika | Budi | 23/11/2026 | Hadir |
| Siti | X IPA 1 | Matematika | Budi | 24/11/2026 | Hadir |
| Siti | X IPA 1 | Matematika | Budi | 25/11/2026 | Izin |

Nama Siti, kelas, mapel, dan guru **diulang setiap hari**. Akibatnya:

- Boros tempat.
- Kalau Siti pindah kelas, harus mengubah ratusan baris.
- Mudah terjadi salah ketik: "Siti" dan "Siti " dianggap dua orang berbeda.

Dengan banyak tabel, data cukup disimpan **sekali**, lalu dihubungkan dengan ID:

- Nama Siti disimpan sekali di tabel `siswa`.
- Di tabel `catatan_absensi` hanya disimpan `id_siswa`.
- Kalau nama berubah, cukup ubah satu baris.

**Kalimat untuk audiens:**

> "Kami memecah data seperti sekolah memecah arsip: ada map siswa, map jadwal, map absen. Masing-masing map menyimpan satu jenis informasi, lalu dihubungkan dengan nomor induk."

---

## 4. Enam kelompok tabel

Tunjukkan poster `schema-database.png`, lalu jelaskan per kelompok. Di poster, setiap kelompok punya warna sendiri.

### Kelompok 1 — Identitas & Akun (biru)
**Tabel:** `pengguna`, `guru`, `siswa`

- `pengguna` menyimpan akun untuk login: nama, email, kata sandi, dan peran (admin / guru / siswa).
- `guru` dan `siswa` menyimpan profil lengkap: NIP, NIS, alamat, orang tua, dan sebagainya.
- Satu akun hanya punya **satu** profil. Ini dijaga oleh aturan `id_pengguna` yang unik.

**Analogi:** `pengguna` adalah kartu akses, `guru` dan `siswa` adalah biodata di belakangnya.

### Kelompok 2 — Akademik (hijau)
**Tabel:** `tahun_ajaran`, `semester`, `kelas`, `riwayat_kelas_siswa`, `mata_pelajaran`

- Tahun ajaran punya dua semester.
- Kelas berada di bawah tahun ajaran dan punya wali kelas.
- `riwayat_kelas_siswa` mencatat siswa pernah ada di kelas mana saja.
- `mata_pelajaran` adalah daftar mapel sekolah.

**Analogi:** kalender akademik dan daftar kelas.

### Kelompok 3 — Jadwal & Penugasan (ungu)
**Tabel:** `penugasan_guru`, `versi_jadwal`, `jadwal_pelajaran`, `jadwal_guru_piket`

- `penugasan_guru` menjawab: guru A mengajar mapel B di kelas C pada semester D.
- `versi_jadwal` menyimpan jadwal sebagai versi (draf, aktif, arsip), sehingga jadwal lama tidak hilang saat ada perubahan.
- `jadwal_pelajaran` adalah jadwal per hari dan jam: mapel, kelas, guru, ruang.
- `jadwal_guru_piket` mencatat guru yang bertugas piket.

**Analogi:** papan jadwal di ruang guru, lengkap dengan arsip jadwal semester lalu.

### Kelompok 4 — Absensi (kuning)
**Tabel:** `sesi_absensi`, `catatan_absensi`, `kode_qr_siswa`, `audit_absensi`

- `sesi_absensi` adalah satu pertemuan nyata: jadwal X pada tanggal Y.
- `catatan_absensi` adalah isi absennya: siswa mana, statusnya apa.
- `kode_qr_siswa` menyimpan QR unik milik siswa.
- `audit_absensi` mencatat setiap perubahan status absensi.

**Analogi:** buku absen per pertemuan, plus buku catatan koreksi.

### Kelompok 5 — Konfirmasi & Rekap (teal)
**Tabel:** `petugas_konfirmasi`, `permintaan_konfirmasi`, `ringkasan_absensi_harian`

- `petugas_konfirmasi` memilih siswa yang bertugas memastikan kelas benar-benar berlangsung.
- `permintaan_konfirmasi` adalah pesan yang dikirim ke petugas tersebut, beserta jawabannya.
- `ringkasan_absensi_harian` merangkum kehadiran satu siswa dalam satu hari.

**Analogi:** petugas piket kelas dan buku rekap harian.

### Kelompok 6 — Sistem (abu-abu)
**Tabel:** `notifikasi`, `log_aktivitas`, `pengaturan_sistem`

- `notifikasi` mengirim pemberitahuan ke pengguna.
- `log_aktivitas` mencatat aktivitas umum di aplikasi.
- `pengaturan_sistem` menyimpan pengaturan aplikasi dalam bentuk kunci–nilai.

**Analogi:** ruang kontrol dan buku catatan petugas keamanan.

> Jumlah total: **22 tabel aplikasi** + 1 tabel `migrasi` bawaan Laravel untuk mencatat versi database.

---

## 5. Alur satu hari (tunjukkan `alur-absensi.png`)

Ini bagian paling penting untuk presentasi. Bacakan sebagai cerita, bukan sebagai daftar.

### Langkah 1 — Akun & Profil
Budi adalah guru, Siti adalah siswa. Masing-masing punya satu akun di `pengguna`, dan profil di `guru` / `siswa`.

> "Sebelum bisa absen, setiap orang harus punya identitas di sistem."

### Langkah 2 — Kelas & Jadwal
Sekolah menyusun jadwal: Matematika, Senin jam 07.30, di kelas X IPA 1, diajar Budi.

**Tabel:** `kelas`, `jadwal_pelajaran`.

### Langkah 3 — Sesi Absensi
Pada hari Senin, 23 November 2026, sistem membuat **sesi** untuk jadwal tersebut.

**Tabel:** `sesi_absensi`.

> "Jadwal itu rencana. Sesi adalah kejadian nyatanya. Satu jadwal bisa punya banyak sesi, tapi hanya satu sesi per tanggal."

### Langkah 4 — Catat Kehadiran
Budi membuka sesi, lalu mencatat Siti **hadir** pukul 07.32. Bisa manual, bisa juga Siti scan QR.

**Tabel:** `catatan_absensi`, `kode_qr_siswa`.

> "Di sini lahir data yang paling sering dilihat orang tua: hadir, izin, sakit, atau alpha."

### Langkah 5 — Konfirmasi
Sistem mengirim permintaan ke petugas konfirmasi kelas: "Apakah pembelajaran benar-benar berlangsung?"

Siswa menjawab **berlangsung** atau **tidak berlangsung**. Kalau tidak dijawab, permintaan bisa kedaluwarsa atau dialihkan ke petugas berikutnya.

**Tabel:** `petugas_konfirmasi`, `permintaan_konfirmasi`.

> "Ini pengaman tambahan. Bukan hanya guru yang mencatat, tetapi siswa juga memastikan kelasnya benar-benar ada."

### Langkah 6 — Rekap & Laporan
Sistem menghitung: Siti hadir 4 dari 5 mapel hari ini. Hasilnya disimpan di `ringkasan_absensi_harian` dan ditampilkan di dashboard serta laporan.

**Tabel:** `ringkasan_absensi_harian`.

> "Guru dan admin tidak perlu menghitung manual. Rekap sudah jadi."

---

## 6. Aturan penting: kenapa data tidak bisa dobel

Tunjukkan bagian **Aturan Unik Penting** di poster schema.

| Aturan | Artinya dalam bahasa sehari-hari |
|---|---|
| `pengguna.email` unik | Satu email hanya untuk satu akun. Tidak bisa dua orang memakai email yang sama. |
| `guru.id_pengguna` unik | Satu akun tidak bisa punya dua profil guru. |
| `siswa.id_pengguna` unik | Satu akun tidak bisa punya dua profil siswa. |
| `(id_jadwal, tanggal_pelajaran)` unik | Satu jadwal hanya punya satu sesi per tanggal. Tidak ada sesi ganda. |
| `(id_sesi_absensi, id_siswa)` unik | Satu siswa hanya punya satu catatan per sesi. Tidak bisa tercatat dua kali. |
| `(id_siswa, tanggal)` unik | Rekap harian satu siswa tidak dobel. |
| `kode_qr_siswa.token_hash` unik | Satu QR hanya milik satu siswa. |
| `(id_guru, mapel, kelas, semester)` unik | Penugasan guru tidak dobel di semester yang sama. |

**Kalimat untuk audiens:**

> "Aturan unik itu seperti nomor absen di kelas. Tidak boleh ada dua siswa dengan nomor yang sama. Bedanya, di database aturan ini dijaga otomatis oleh sistem, bukan oleh ingatan manusia."

---

## 7. Tiga hal yang membuat desain ini kuat

### 1. Tidak ada data yang diulang
Nama siswa, nama guru, dan nama mapel disimpan sekali. Sisanya hanya menyimpan ID.

### 2. Riwayat tidak hilang
- `riwayat_kelas_siswa` menyimpan perpindahan kelas.
- `versi_jadwal` menyimpan jadwal lama sebagai arsip.
- `audit_absensi` menyimpan status sebelum dan sesudah diubah.

### 3. Bisa diaudit
Kalau ada absensi yang berubah dari **alpha** menjadi **hadir**, `audit_absensi` mencatat:

- siapa yang mengubah,
- kapan diubah,
- status sebelumnya,
- status sesudahnya,
- alasannya.

**Kalimat untuk audiens:**

> "Jadi kalau ada pertanyaan 'kenapa anak saya tiba-tiba tercatat hadir?', jawabannya bisa dilacak, bukan sekadar 'katanya'."

### Bonus: keamanan
- Kata sandi disimpan dalam bentuk hash, bukan teks asli.
- QR siswa disimpan sebagai `token_hash`. Token aslinya tidak disimpan mentah, sehingga kalau database bocor, QR tidak bisa langsung dipalsukan.

---

## 8. Naskah presentasi (± 6 menit)

### Slide 1 — Pembuka (30 detik)

> "Selamat pagi. Hari ini saya akan menjelaskan database di balik sistem absensi sekolah.
>
> Bayangkan buku absen yang biasa dipakai guru. Sekarang bayangkan buku itu hidup: bisa diisi lewat HP, bisa discan, dan langsung membuat laporan. Di balik layar, ada 22 tabel yang bekerja sama.
>
> Saya akan jelaskan dengan bahasa sederhana, tanpa istilah pemrograman yang rumit."

### Slide 2 — Poster schema (1,5 menit)

> "Ini peta databasenya. Jangan panik melihat banyak kotak. Perhatikan warnanya: ada enam kelompok.
>
> Biru untuk akun dan profil. Hijau untuk data akademik. Ungu untuk jadwal. Kuning untuk absensi. Teal untuk konfirmasi dan rekap. Abu-abu untuk sistem.
>
> Setiap kotak adalah satu tabel, ibarat satu map arsip. Di dalamnya ada kolom-kolom, seperti isian pada formulir.
>
> Tanda PK adalah nomor identitas. Tanda FK adalah penghubung ke tabel lain. Garis-garis tipis itu menunjukkan hubungan antar tabel."

### Slide 3 — Alur 6 langkah (2,5 menit)

> "Sekarang saya ceritakan satu hari di sekolah.
>
> Pagi hari, Budi guru Matematika masuk. Dia sudah punya akun. Siti, siswa kelas X IPA 1, juga sudah punya akun. Itu langkah pertama.
>
> Sekolah sudah menyusun jadwal: Matematika, Senin jam 07.30. Itu langkah kedua.
>
> Saat pelajaran dimulai, sistem membuka sesi absensi untuk tanggal itu. Itu langkah ketiga.
>
> Budi mencatat Siti hadir pukul 07.32. Bisa manual, bisa Siti scan QR. Itu langkah keempat.
>
> Setelah itu, sistem bertanya ke petugas konfirmasi: apakah kelas benar-benar berlangsung? Siswa menjawab. Itu langkah kelima.
>
> Terakhir, sistem merangkum: Siti hadir 4 dari 5 mapel hari ini. Langsung masuk laporan. Itu langkah keenam.
>
> Enam langkah, enam kelompok tabel, selesai dalam satu hari."

### Slide 4 — Aturan unik (1 menit)

> "Pertanyaan yang sering muncul: bagaimana kalau data dobel?
>
> Di database, ada aturan yang disebut unik. Contohnya, satu jadwal hanya boleh punya satu sesi per tanggal. Satu siswa hanya boleh punya satu catatan per sesi. Satu email hanya untuk satu akun.
>
> Aturan ini dijaga oleh sistem, bukan oleh manusia. Jadi kalau ada yang mencoba memasukkan data ganda, sistem akan menolak."

### Slide 5 — Penutup (30 detik)

> "Jadi kesimpulannya: database ini menyimpan data sekali saja, menghubungkannya dengan rapi, dan menjaga riwayat perubahan.
>
> Hasilnya, absensi tidak hanya cepat, tetapi juga bisa dipercaya dan bisa diaudit.
>
> Terima kasih. Saya siap menjawab pertanyaan."

---

## 9. Kemungkinan pertanyaan dan jawaban

**T: Kenapa tidak satu tabel saja?**
J: Kalau satu tabel, nama siswa dan guru akan diulang setiap hari. Itu boros dan rawan salah. Dengan banyak tabel, data disimpan sekali dan dihubungkan dengan ID.

**T: Apa itu primary key dan foreign key?**
J: Primary key adalah nomor identitas unik setiap baris, seperti nomor induk siswa. Foreign key adalah kolom yang menyimpan nomor identitas dari tabel lain, seperti "lihat siswa nomor 12".

**T: Kenapa ada tabel sesi, tidak langsung catatan absensi?**
J: Karena satu jadwal bisa berlangsung berkali-kali. Sesi memisahkan "pertemuan" dari "catatan kehadiran". Jadi satu sesi punya banyak catatan siswa.

**T: Kenapa satu sesi punya dua guru?**
J: `id_guru_penanggung_jawab` adalah guru pengampu aslinya. `id_guru_pelaksana` adalah guru yang benar-benar menjalankan sesi, misalnya guru piket yang menggantikan. Dengan begitu, penggantian tetap tercatat.

**T: Apa gunanya audit_absensi?**
J: Untuk merekam perubahan status absensi: dari apa menjadi apa, oleh siapa, kapan, dan alasannya. Jadi kalau ada sengketa, ada buktinya.

**T: Kenapa QR siswa disimpan sebagai hash?**
J: Hash itu seperti sidik jari token. Sistem mencocokkan sidik jarinya, bukan menyimpan token aslinya. Kalau database bocor, QR tidak bisa langsung dipakai orang lain.

**T: Apa bedanya tahun ajaran, semester, dan kelas?**
J: Tahun ajaran adalah periode besar, misalnya 2026/2027. Di dalamnya ada dua semester: ganjil dan genap. Kelas adalah kelompok siswa, misalnya X IPA 1. Satu kelas berada di bawah satu tahun ajaran.

**T: Kalau siswa pindah kelas, datanya bagaimana?**
J: Tidak ditimpa. Data lama tetap di `riwayat_kelas_siswa` dengan tanggal selesai, lalu ditambah baris baru untuk kelas yang baru. Jadi riwayatnya utuh.

**T: Berapa jumlah tabelnya?**
J: 22 tabel aplikasi, ditambah 1 tabel `migrasi` bawaan framework untuk mencatat versi database.

**T: Apakah data bisa hilang?**
J: Perubahan pada data induk dibatasi. Contohnya, guru yang masih punya jadwal tidak bisa dihapus sembarangan. Selain itu, ada `audit_absensi` dan `log_aktivitas` yang mencatat kejadian penting.

**T: Kenapa ada tabel pengaturan_sistem?**
J: Supaya hal-hal seperti batas waktu konfirmasi atau aturan absensi bisa diubah tanpa mengubah program. Pengaturan disimpan sebagai kunci–nilai dan punya tanggal berlaku.

---

## 10. Kalimat penutup alternatif

Kalau ingin lebih singkat:

> "Intinya, database ini seperti buku induk sekolah yang rapi: satu data disimpan sekali, dihubungkan dengan benar, dan setiap perubahan tercatat. Itulah yang membuat absensi cepat, akurat, dan bisa dipertanggungjawabkan."

---

## 11. Tips saat presentasi

1. **Jangan membaca poster.** Tunjuk satu kelompok warna, jelaskan fungsinya, lalu pindah.
2. **Mulai dari alur, bukan dari tabel.** Audiens lebih mudah paham cerita satu hari daripada daftar nama tabel.
3. **Gunakan satu contoh konsisten.** Pakai Budi (guru) dan Siti (siswa) dari awal sampai akhir.
4. **Sebut angka secukupnya.** "22 tabel" cukup. Tidak perlu menyebut panjang kolom atau tipe data.
5. **Siapkan satu pertanyaan balik.** Misalnya: "Kalau absensi hanya dicatat di buku, apa yang terjadi kalau bukunya hilang?" Ini membuat audiens ikut berpikir.
6. **Tutup dengan manfaat, bukan teknologi.** Manfaatnya: cepat, akurat, bisa diaudit, dan riwayat tidak hilang.
