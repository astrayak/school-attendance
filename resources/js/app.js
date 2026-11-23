import Alpine from 'alpinejs';
import QrScanner from 'qr-scanner';
import { createIcons, icons } from 'lucide';

window.Alpine = Alpine;
window.QrScanner = QrScanner;

const gambarIkon = () => {
    if (typeof createIcons === 'function') {
        createIcons({
            icons,
            attrs: {
                'stroke-width': 1.6,
            },
        });
    }
};

window.gambarIkon = gambarIkon;

window.pemindaiQr = (konfigurasi) => ({
    memindai: false,
    memproses: false,
    pesan: konfigurasi.pesan ?? '',
    jenis: 'info',
    hasil: konfigurasi.awal ?? [],
    ringkasan: konfigurasi.ringkasan ?? { total: 0, terisi: 0, hadir: 0 },
    pemindai: null,

    async mulai() {
        if (this.pemindai) {
            return;
        }

        this.pemindai = new QrScanner(
            this.$refs.video,
            (hasil) => this.kirim(hasil.data),
            {
                highlightScanRegion: true,
                highlightCodeOutline: true,
                maxScansPerSecond: 4,
                preferredCamera: 'environment',
            },
        );

        try {
            await this.pemindai.start();
            this.memindai = true;
            this.pesan = 'Kamera aktif. Arahkan QR Code siswa ke dalam bingkai.';
            this.jenis = 'info';
        } catch (error) {
            this.pemindai = null;
            this.pesan = 'Kamera tidak dapat diakses. Gunakan HTTPS atau localhost, lalu izinkan akses kamera.';
            this.jenis = 'error';
        }
    },

    async hentikan() {
        if (!this.pemindai) {
            return;
        }

        this.pemindai.stop();
        this.pemindai.destroy();
        this.pemindai = null;
        this.memindai = false;
        this.pesan = 'Kamera dihentikan.';
        this.jenis = 'info';
    },

    async kirim(token) {
        if (this.memproses || !token) {
            return;
        }

        this.memproses = true;

        try {
            const respons = await fetch(konfigurasi.url, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': konfigurasi.csrf,
                },
                body: JSON.stringify({ token }),
            });

            if (!respons.ok) {
                throw new Error('Permintaan gagal');
            }

            const data = await respons.json();

            this.ringkasan = data.ringkasan ?? this.ringkasan;
            this.pesan = data.pesan;
            this.jenis = data.status === 'hadir'
                ? 'sukses'
                : (data.status === 'sudah_diabsen' ? 'info' : 'error');

            if (data.siswa) {
                this.hasil.unshift({
                    nama_lengkap: data.siswa.nama_lengkap,
                    nis: data.siswa.nis,
                    waktu: data.waktu,
                    status: data.status,
                    pesan: data.pesan,
                });
                this.hasil = this.hasil.slice(0, 15);
            }
        } catch (error) {
            this.pesan = 'Gagal menghubungi server. Periksa koneksi lalu coba lagi.';
            this.jenis = 'error';
        } finally {
            this.memproses = false;
        }
    },
});

window.hitungMundur = (batasIso) => ({
    sisa: '',
    lewat: false,
    pengatur: null,

    init() {
        this.perbarui();
        this.pengatur = setInterval(() => this.perbarui(), 1000);
    },

    destroy() {
        if (this.pengatur) {
            clearInterval(this.pengatur);
        }
    },

    perbarui() {
        const selisih = new Date(batasIso).getTime() - Date.now();

        if (selisih <= 0) {
            this.sisa = 'Waktu habis';
            this.lewat = true;

            if (this.pengatur) {
                clearInterval(this.pengatur);
            }

            return;
        }

        const totalDetik = Math.floor(selisih / 1000);
        const menit = Math.floor(totalDetik / 60);
        const detik = totalDetik % 60;
        this.sisa = `${menit}:${String(detik).padStart(2, '0')}`;
        this.lewat = false;
    },
});

Alpine.start();
document.addEventListener('DOMContentLoaded', gambarIkon);
document.addEventListener('alpine:initialized', gambarIkon);
