@php($jadwal = $jadwal ?? null)
@php($jamMulai = old('jam_mulai', $jadwal?->jam_mulai ? substr($jadwal->jam_mulai, 0, 5) : ''))
@php($jamSelesai = old('jam_selesai', $jadwal?->jam_selesai ? substr($jadwal->jam_selesai, 0, 5) : ''))

<div class="card">
    <div class="card-header">
        <div>
            <p class="text-sm font-semibold text-ink">Detail Jadwal</p>
            <p class="mt-0.5 text-xs text-ink-muted">Versi: {{ $versi->nama_versi }} · {{ $versi->semester?->tahunAjaran?->nama_tahun_ajaran }}</p>
        </div>
    </div>
    <div class="card-body space-y-4">
        <input type="hidden" name="id_versi_jadwal" value="{{ $versi->id_versi_jadwal }}">

        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label for="hari" class="form-label">Hari <span class="text-clay">*</span></label>
                <select id="hari" name="hari" class="form-select" required>
                    <option value="">Pilih hari</option>
                    @foreach (\App\Support\Status::HARI as $nilai => $label)
                        <option value="{{ $nilai }}" @selected(old('hari', $jadwal?->hari) === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('hari') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="jam_mulai" class="form-label">Jam mulai <span class="text-clay">*</span></label>
                <input type="time" id="jam_mulai" name="jam_mulai" value="{{ $jamMulai }}" class="form-input" required>
                @error('jam_mulai') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="jam_selesai" class="form-label">Jam selesai <span class="text-clay">*</span></label>
                <input type="time" id="jam_selesai" name="jam_selesai" value="{{ $jamSelesai }}" class="form-input" required>
                @error('jam_selesai') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="id_kelas" class="form-label">Kelas <span class="text-clay">*</span></label>
                <select id="id_kelas" name="id_kelas" class="form-select" required>
                    <option value="">Pilih kelas</option>
                    @foreach ($kelas as $item)
                        <option value="{{ $item->id_kelas }}" @selected((string) old('id_kelas', $jadwal?->id_kelas) === (string) $item->id_kelas)>{{ $item->nama_kelas }}</option>
                    @endforeach
                </select>
                @error('id_kelas') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="id_mata_pelajaran" class="form-label">Mata pelajaran <span class="text-clay">*</span></label>
                <select id="id_mata_pelajaran" name="id_mata_pelajaran" class="form-select" required>
                    <option value="">Pilih mata pelajaran</option>
                    @foreach ($mataPelajaran as $item)
                        <option value="{{ $item->id_mata_pelajaran }}" @selected((string) old('id_mata_pelajaran', $jadwal?->id_mata_pelajaran) === (string) $item->id_mata_pelajaran)>{{ $item->nama_mata_pelajaran }}</option>
                    @endforeach
                </select>
                @error('id_mata_pelajaran') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="id_guru" class="form-label">Guru pengampu <span class="text-clay">*</span></label>
                <select id="id_guru" name="id_guru" class="form-select" required>
                    <option value="">Pilih guru</option>
                    @foreach ($guru as $item)
                        <option value="{{ $item->id_guru }}" @selected((string) old('id_guru', $jadwal?->id_guru) === (string) $item->id_guru)>{{ $item->nama_lengkap }}</option>
                    @endforeach
                </select>
                <p id="info-guru" class="form-help">Pilih kelas dan mata pelajaran untuk melihat guru yang ditugaskan pada semester ini.</p>
                @error('id_guru') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="urutan_pelajaran" class="form-label">Urutan</label>
                    <input type="number" id="urutan_pelajaran" name="urutan_pelajaran" value="{{ old('urutan_pelajaran', $jadwal?->urutan_pelajaran ?? 1) }}" class="form-input" min="1" max="30">
                    @error('urutan_pelajaran') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="ruang_kelas" class="form-label">Ruang</label>
                    <input type="text" id="ruang_kelas" name="ruang_kelas" value="{{ old('ruang_kelas', $jadwal?->ruang_kelas) }}" class="form-input" maxlength="50" placeholder="Opsional">
                    @error('ruang_kelas') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        @error('jadwal')
            <p class="rounded-sm border border-clay-soft bg-clay-soft px-4 py-3 text-sm text-clay">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="mt-6 flex flex-wrap items-center justify-end gap-3">
    <a href="{{ route('admin.jadwal.index', ['semester' => $versi->id_semester, 'versi' => $versi->id_versi_jadwal]) }}" class="btn-secondary">Batal</a>
    <button type="submit" class="btn-primary">
        <i data-lucide="save" class="h-4 w-4"></i>
        {{ $jadwal ? 'Simpan Perubahan' : 'Simpan Jadwal' }}
    </button>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const penugasanMap = @json($penugasanMap ?? []);
        const kelas = document.getElementById('id_kelas');
        const mapel = document.getElementById('id_mata_pelajaran');
        const guru = document.getElementById('id_guru');
        const info = document.getElementById('info-guru');

        if (!kelas || !mapel || !guru) {
            return;
        }

        const semuaOpsi = [...guru.options].map((o) => ({
            value: o.value,
            label: o.textContent,
        }));
        const editId = {{ $jadwal?->id_guru ?? 'null' }};

        const perbarui = () => {
            const kunci = `${kelas.value}:${mapel.value}`;
            const ids = penugasanMap[kunci] || null;
            const terpilih = guru.value;
            const lengkap = Boolean(kelas.value && mapel.value);

            guru.innerHTML = '';

            semuaOpsi.forEach((o) => {
                if (o.value === '') {
                    guru.appendChild(new Option(o.label, o.value));
                    return;
                }

                if (lengkap && !ids) {
                    return;
                }

                if (lengkap && ids && !ids.includes(Number(o.value)) && Number(o.value) !== editId) {
                    return;
                }

                const opt = new Option(o.label, o.value);
                if (Number(o.value) === Number(terpilih)) {
                    opt.selected = true;
                }
                guru.appendChild(opt);
            });

            if (!lengkap) {
                info.textContent = 'Pilih kelas dan mata pelajaran untuk melihat guru yang ditugaskan pada semester ini.';
            } else if (!ids) {
                info.textContent = 'Belum ada guru yang ditugaskan untuk kelas dan mapel ini pada semester terkait.';
            } else {
                info.textContent = `${ids.length} guru ditugaskan pada semester ini.`;
            }
        };

        kelas.addEventListener('change', perbarui);
        mapel.addEventListener('change', perbarui);
        perbarui();
    });
</script>
