<?php // sections/pembeli.php ?>

<!-- ══ STATS ══ -->
<div class="stats-grid col2">
    <div class="stat-card">
        <div class="stat-label">Total Pembeli</div>
        <div class="stat-row">
            <div class="stat-value">
                <?= $totalPembeli ?>
            </div>
            <i class="fa-solid fa-users stat-icon"></i>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Pembeli Aktif</div>
        <div class="stat-row">
            <div class="stat-value">
                <?= $totalAktif ?><span class="sub"> /
                    <?= $totalPembeli ?>
                </span>
            </div>
            <i class="fa-solid fa-circle-check stat-icon"></i>
        </div>
    </div>
</div>

<!-- ══ SEARCH & FILTER ══ -->
<form method="GET" action="" id="formFilterPembeli"
    style="margin-bottom:16px;display:flex;gap:10px;flex-wrap:wrap;align-items:center">
    <input type="hidden" name="section" value="pembeli">

    <!-- Search -->
    <div style="position:relative;flex:1;min-width:200px">
        <i class="fa-solid fa-magnifying-glass"
            style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:13px"></i>
        <input type="text" name="q_pembeli" value="<?= htmlspecialchars($searchPembeli) ?>"
            placeholder="Cari nama atau NISN/NUPTK..."
            style="width:100%;padding:10px 12px 10px 36px;border:1.5px solid var(--border);border-radius:10px;font-size:13px;background:var(--card-bg);color:var(--text);outline:none;box-sizing:border-box">
    </div>

    <!-- Filter Kategori -->
    <select name="filter_kategori" id="filterKategori"
        style="min-width:130px;padding:10px 12px;border:1.5px solid var(--border);border-radius:10px;font-size:13px;background:var(--card-bg);color:var(--text)">
        <option value="" <?= $filterKategori === '' ? 'selected' : '' ?>>Semua</option>
        <option value="murid" <?= $filterKategori === 'murid' ? 'selected' : '' ?>>Murid</option>
        <option value="guru" <?= $filterKategori === 'guru' ? 'selected' : '' ?>>Guru</option>
    </select>

    <!-- Filter Tingkat/Kelas: 10, 11, 12 -->
    <select id="filterKelas"
        style="min-width:110px;padding:10px 12px;border:1.5px solid var(--border);border-radius:10px;font-size:13px;background:var(--card-bg);color:var(--text)">
        <option value="">Semua Kelas</option>
        <?php foreach ($semuaTingkat as $t): ?>
            <option value="<?= $t['kelas'] ?>">Kelas
                <?= $t['kelas'] ?>
            </option>
        <?php endforeach; ?>
    </select>

    <!-- Filter Rombel/Jurusan: dependent ke tingkat -->
    <select id="filterJurusan"
        style="min-width:130px;padding:10px 12px;border:1.5px solid var(--border);border-radius:10px;font-size:13px;background:var(--card-bg);color:var(--text)">
        <option value="">Semua Jurusan</option>
        <?php foreach ($semuaKelas as $k): ?>
            <option value="<?= $k['id_kelas'] ?>" data-tingkat="<?= $k['tingkat'] ?>">
                <?= htmlspecialchars(trim(implode(' ', array_slice(explode(' ', $k['nama_kelas']), 1)))) ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>

<!-- ══ GRID ══ -->
<div class="page-grid">

    <!-- Tabel Daftar Pembeli -->
    <div class="table-card">
        <div class="table-card-header">
            <h2>Daftar Pembeli</h2>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Nama Pembeli</th>
                        <th class="col-hide">NISN / NUPTK</th>
                        <th>Sub Role</th>
                        <th class="col-hide">Kelas / Jurusan</th>
                        <th class="col-hide">Terakhir Aktif</th>
                        <th>Status</th>
                        <th class="center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarPembeli)): ?>
                        <tr class="empty-row">
                            <td colspan="7">
                                <i class="fa-solid fa-users"
                                    style="color:var(--green-muted);font-size:22px;display:block;margin-bottom:8px"></i>
                                <?= $searchPembeli
                                    ? 'Tidak ada hasil untuk "' . htmlspecialchars($searchPembeli) . '"'
                                    : 'Belum ada data pembeli' ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($daftarPembeli as $pb):
                            $aktif = $pb['status'] === 'aktif';
                            $isMurid = $pb['kategori'] === 'Murid';
                            $idKey = $isMurid ? 'nisn' : 'nuptk';
                            $idVal = htmlspecialchars($pb['nisn_nuptk']);
                            $toggleAct = $isMurid ? 'pembeli_toggle_murid' : 'pembeli_toggle_guru';
                            $hapusAct = $isMurid ? 'pembeli_hapus_murid' : 'pembeli_hapus_guru';
                            $resetAct = $isMurid ? 'pembeli_reset_murid' : 'pembeli_reset_guru';
                            ?>
                            <tr data-kategori="<?= strtolower($pb['kategori']) ?>"
                                data-tingkat="<?= htmlspecialchars($pb['tingkat'] ?? '') ?>"
                                data-kelas="<?= htmlspecialchars($pb['id_kelas'] ?? '') ?>">
                                <td>
                                    <?= htmlspecialchars($pb['nama']) ?>
                                </td>
                                <td class="col-hide" style="color:var(--text-muted);font-size:12px">
                                    <?= $idVal ?>
                                </td>
                                <td>
                                    <span class="badge"
                                        style="background:<?= $isMurid ? 'var(--green-muted)' : '#e8f0fe' ?>;color:<?= $isMurid ? 'var(--green-dark,#2d7a2d)' : '#1a56db' ?>;font-weight:600;font-size:11px;padding:3px 10px;border-radius:20px">
                                        <?= $pb['kategori'] ?>
                                    </span>
                                </td>
                                <td class="col-hide" style="font-size:12px;color:var(--text-muted)">
                                    <?= $isMurid ? htmlspecialchars($pb['info_tambahan'] ?? '-') : '<span style="color:#aaa">—</span>' ?>
                                </td>
                                <td class="col-hide" style="font-size:12px;color:var(--text-muted)">
                                    <?= !empty($pb['terakhir_login'])
                                        ? date('d/m/Y H:i', strtotime($pb['terakhir_login']))
                                        : '<span style="color:#aaa">Belum pernah</span>' ?>
                                </td>
                                <td>
                                    <span class="badge <?= $aktif ? 'badge-aktif' : 'badge-nonaktif' ?>">
                                        <i class="fa-solid <?= $aktif ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
                                        <?= $aktif ? 'Aktif' : 'Nonaktif' ?>
                                    </span>
                                </td>
                                <td class="center" style="white-space:nowrap">
                                    <!-- Reset Password -->
                                    <button type="button" class="btn-aksi reset" title="Reset Password"
                                        onclick="bukaResetPembeli('<?= $idKey ?>','<?= $idVal ?>','<?= $resetAct ?>','<?= htmlspecialchars($pb['nama'], ENT_QUOTES) ?>')">
                                        <i class="fa-solid fa-key"></i>
                                    </button>

                                    <!-- Toggle Status -->
                                    <form method="POST" style="display:inline"
                                        onsubmit="return confirm('<?= $aktif ? 'Nonaktifkan' : 'Aktifkan' ?> pembeli ini?')">
                                        <input type="hidden" name="action" value="<?= $toggleAct ?>">
                                        <input type="hidden" name="<?= $idKey ?>" value="<?= $idVal ?>">
                                        <input type="hidden" name="status" value="<?= $pb['status'] ?>">
                                        <input type="hidden" name="_section" value="pembeli">
                                        <button type="submit" class="btn-aksi <?= $aktif ? 'toggle-off' : 'toggle-on' ?>"
                                            title="<?= $aktif ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                            <i class="fa-solid <?= $aktif ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                                        </button>
                                    </form>

                                    <!-- Hapus -->
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="action" value="<?= $hapusAct ?>">
                                        <input type="hidden" name="<?= $idKey ?>" value="<?= $idVal ?>">
                                        <input type="hidden" name="_section" value="pembeli">
                                        <button type="button" class="btn-aksi danger" title="Hapus"
                                            onclick="if(confirm('Hapus pembeli <?= htmlspecialchars($pb['nama'], ENT_QUOTES) ?>?')) this.closest('form').submit()">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Form Tambah Pembeli -->
    <div class="form-card">
        <div style="display:flex;gap:8px;margin-bottom:18px">
            <button id="tabMurid" type="button" onclick="switchTabPembeli('murid')"
                style="flex:1;padding:9px;border-radius:8px;border:1.5px solid var(--green);background:var(--green);color:#fff;font-weight:600;font-size:13px;cursor:pointer;transition:.2s">
                <i class="fa-solid fa-graduation-cap" style="margin-right:5px"></i>Murid
            </button>
            <button id="tabGuru" type="button" onclick="switchTabPembeli('guru')"
                style="flex:1;padding:9px;border-radius:8px;border:1.5px solid var(--border);background:transparent;color:var(--text-muted);font-weight:600;font-size:13px;cursor:pointer;transition:.2s">
                <i class="fa-solid fa-chalkboard-teacher" style="margin-right:5px"></i>Guru
            </button>
        </div>

        <!-- Form Tambah Murid -->
        <div id="formPanelMurid">
            <h2><i class="fa-solid fa-user-plus" style="color:var(--green);margin-right:8px"></i>Tambah Akun Murid</h2>
            <form method="POST">
                <input type="hidden" name="action" value="pembeli_tambah_murid">
                <input type="hidden" name="_section" value="pembeli">
                <div class="form-group">
                    <label>Nama Murid</label>
                    <input type="text" name="nama" placeholder="cth. Budi Santoso" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>NISN</label>
                    <input type="text" name="nisn" placeholder="cth. 0012345678" required autocomplete="off"
                        maxlength="10" minlength="10" pattern="\d{10}" title="NISN harus tepat 10 digit angka">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="password-wrap">
                        <input type="password" name="password" id="inputPassMurid"
                            placeholder="Kosongkan = pakai NISN sebagai password" autocomplete="new-password">
                        <button type="button" class="btn-eye" onclick="togglePw('inputPassMurid','eyePassMurid')">
                            <i class="fa-solid fa-eye" id="eyePassMurid"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label>Kelas</label>
                    <select name="id_kelas" class="form-select" required>
                        <option value="">Pilih kelas...</option>
                        <?php foreach ($semuaKelas as $k): ?>
                            <option value="<?= $k['id_kelas'] ?>">
                                <?= htmlspecialchars($k['nama_kelas']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Jurusan</label>
                    <select name="id_jurusan" class="form-select" required>
                        <option value="">Pilih jurusan...</option>
                        <?php foreach ($semuaJurusan as $j): ?>
                            <option value="<?= $j['id_jurusan'] ?>">
                                <?= htmlspecialchars($j['nama_jurusan']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-floppy-disk" style="margin-right:6px"></i>Simpan Akun
                </button>
            </form>
        </div>

        <!-- Form Tambah Guru -->
        <div id="formPanelGuru" style="display:none">
            <h2><i class="fa-solid fa-user-plus" style="color:var(--green);margin-right:8px"></i>Tambah Akun Guru</h2>
            <form method="POST">
                <input type="hidden" name="action" value="pembeli_tambah_guru">
                <input type="hidden" name="_section" value="pembeli">
                <div class="form-group">
                    <label>Nama Guru</label>
                    <input type="text" name="nama" placeholder="cth. Pak Fajar" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>NUPTK</label>
                    <input type="text" name="nuptk" placeholder="cth. 1234567890123456" required autocomplete="off"
                        maxlength="16" minlength="16" pattern="\d{16}" title="NUPTK harus tepat 16 digit angka">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="password-wrap">
                        <input type="password" name="password" id="inputPassGuru"
                            placeholder="Kosongkan = pakai NUPTK sebagai password" autocomplete="new-password">
                        <button type="button" class="btn-eye" onclick="togglePw('inputPassGuru','eyePassGuru')">
                            <i class="fa-solid fa-eye" id="eyePassGuru"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-floppy-disk" style="margin-right:6px"></i>Simpan Akun
                </button>
            </form>
        </div>
    </div>

</div>

<!-- ══ MODAL RESET PASSWORD ══ -->
<div id="modalResetPembeli"
    style="display:none;position:fixed;inset:0;z-index:100;align-items:center;justify-content:center">
    <div onclick="tutupResetPembeli()"
        style="position:absolute;inset:0;background:rgba(0,0,0,.45);backdrop-filter:blur(2px)"></div>
    <div
        style="position:relative;background:#fff;border-radius:16px;padding:28px;width:90%;max-width:360px;box-shadow:0 8px 32px rgba(0,0,0,.15)">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
            <h2 style="font-size:16px;font-weight:700">Reset Password</h2>
            <button onclick="tutupResetPembeli()"
                style="background:none;border:none;font-size:18px;cursor:pointer;color:#6b7280">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <p style="font-size:13px;color:#6b7280;margin-bottom:16px">
            Reset password untuk: <strong id="namaResetPembeli"></strong>
        </p>
        <form method="POST" id="formResetPembeli">
            <input type="hidden" name="action" id="resetPembeliAction">
            <input type="hidden" name="_section" value="pembeli">
            <input type="hidden" name="" id="resetPembeliIdField">
            <div class="form-group">
                <label>Password Baru</label>
                <div class="password-wrap">
                    <input type="password" name="pw_reset" id="inputPwResetPembeli" placeholder="Masukkan password baru"
                        required>
                    <button type="button" class="btn-eye" onclick="togglePw('inputPwResetPembeli','eyePwResetPembeli')">
                        <i class="fa-solid fa-eye" id="eyePwResetPembeli"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-key" style="margin-right:6px"></i>Reset Password
            </button>
        </form>
    </div>
</div>

<script>
    /* ══════════════════════════════════════
       Tab Switcher Murid / Guru
    ══════════════════════════════════════ */
    function switchTabPembeli(tab) {
        const isMurid = tab === 'murid';
        document.getElementById('formPanelMurid').style.display = isMurid ? '' : 'none';
        document.getElementById('formPanelGuru').style.display = isMurid ? 'none' : '';

        const on = 'flex:1;padding:9px;border-radius:8px;border:1.5px solid var(--green);background:var(--green);color:#fff;font-weight:600;font-size:13px;cursor:pointer;transition:.2s';
        const off = 'flex:1;padding:9px;border-radius:8px;border:1.5px solid var(--border);background:transparent;color:var(--text-muted);font-weight:600;font-size:13px;cursor:pointer;transition:.2s';
        document.getElementById('tabMurid').style.cssText = isMurid ? on : off;
        document.getElementById('tabGuru').style.cssText = isMurid ? off : on;
    }

    /* ══════════════════════════════════════
       Modal Reset Password
    ══════════════════════════════════════ */
    function bukaResetPembeli(idKey, idVal, action, nama) {
        document.getElementById('namaResetPembeli').textContent = nama;
        document.getElementById('resetPembeliAction').value = action;
        document.getElementById('inputPwResetPembeli').value = '';
        const field = document.getElementById('resetPembeliIdField');
        field.name = idKey;
        field.value = idVal;
        document.getElementById('modalResetPembeli').style.display = 'flex';
    }
    function tutupResetPembeli() {
        document.getElementById('modalResetPembeli').style.display = 'none';
    }
    document.addEventListener('keydown', e => { if (e.key === 'Escape') tutupResetPembeli(); });

    /* ══════════════════════════════════════
       Filter Client-Side
    ══════════════════════════════════════ */
    const elSearch = document.querySelector('input[name="q_pembeli"]');
    const elKategori = document.querySelector('select[name="filter_kategori"]');
    const elKelas = document.getElementById('filterKelas');
    const elJurusan = document.getElementById('filterJurusan');

    // Sync opsi jurusan sesuai tingkat yang dipilih
    // Pertahankan pilihan jurusan kalau masih relevan
    function syncOpsiJurusan() {
        const tingkat = elKelas.value;
        const currentJurusan = elJurusan.value; // simpan pilihan sekarang

        elJurusan.disabled = false;

        elJurusan.querySelectorAll('option[data-tingkat]').forEach(opt => {
            opt.style.display = (tingkat === '' || opt.dataset.tingkat === tingkat) ? '' : 'none';
        });

        // Reset jurusan HANYA kalau pilihan sekarang tidak cocok dengan tingkat baru
        if (tingkat !== '' && currentJurusan !== '') {
            const selectedOpt = elJurusan.querySelector(`option[value="${currentJurusan}"]`);
            if (selectedOpt && selectedOpt.dataset.tingkat !== tingkat) {
                elJurusan.value = '';
            }
        }
    }

    // Sembunyikan filter kelas & jurusan saat pilih Guru
    // lalu langsung re-filter tabel supaya baris guru/murid ikut update
    function toggleFilterKelasJurusan() {
        const isGuru = elKategori.value === 'guru';
        elKelas.style.display = isGuru ? 'none' : '';
        elJurusan.style.display = isGuru ? 'none' : '';
        if (isGuru) {
            elKelas.value = '';
            elJurusan.value = '';
            syncOpsiJurusan();
        }
        filterPembeli(); // ← wajib, biar baris tabel ikut ke-filter
    }

    // Filter baris tabel
    function filterPembeli() {
        const q = elSearch.value.toLowerCase();
        const kat = elKategori ? elKategori.value.toLowerCase() : '';
        const tingkat = elKelas.value;
        const kelasId = elJurusan.value;

        document.querySelectorAll('tbody tr:not(.empty-row)').forEach(row => {
            const nama = row.cells[0]?.textContent.toLowerCase() ?? '';
            const nisn = row.cells[1]?.textContent.toLowerCase() ?? '';
            const katRow = row.dataset.kategori ?? '';
            const tingkatRow = row.dataset.tingkat ?? '';
            const kelasRow = row.dataset.kelas ?? '';

            // kalau pilih kelas atau jurusan, guru wajib hidden — tidak peduli kat
            if ((tingkat || kelasId) && katRow === 'guru') {
                row.style.display = 'none';
                return;
            }

            const matchQ = q === '' || nama.includes(q) || nisn.includes(q);
            const matchKat = kat === '' || katRow === kat;

            let matchKelas = true, matchJurusan = true;
            if (katRow === 'murid') {
                if (tingkat) matchKelas = tingkatRow === tingkat;
                if (kelasId) matchJurusan = kelasRow === kelasId;
            }

            row.style.display = (matchQ && matchKat && matchKelas && matchJurusan) ? '' : 'none';
        });
    }

    // Event listeners
    elSearch.addEventListener('input', filterPembeli);
    elKategori.addEventListener('change', () => { toggleFilterKelasJurusan(); });
    elKelas.addEventListener('change', () => {
        // sembunyikan baris guru kalau pilih kelas
        document.querySelectorAll('tbody tr:not(.empty-row)').forEach(row => {
            if (row.dataset.kategori === 'guru') row.style.display = 'none';
        });
        syncOpsiJurusan();
        filterPembeli();
    });

    elJurusan.addEventListener('change', () => {
        // sembunyikan baris guru kalau pilih jurusan
        document.querySelectorAll('tbody tr:not(.empty-row)').forEach(row => {
            if (row.dataset.kategori === 'guru') row.style.display = 'none';
        });
        filterPembeli();
    });

    // Init
    toggleFilterKelasJurusan();
    syncOpsiJurusan();
    filterPembeli();
</script>