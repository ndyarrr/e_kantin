<?php // sections/pembeli.php ?>

<!-- ══ STATS ══ -->
<div class="stats-grid col2">
    <div class="stat-card">
        <div class="stat-label">Total Pembeli</div>
        <div class="stat-row">
            <div class="stat-value"><?= $totalPembeli ?></div>
            <i class="fa-solid fa-users stat-icon"></i>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Pembeli Aktif</div>
        <div class="stat-row">
            <div class="stat-value"><?= $totalAktif ?><span class="sub"> / <?= $totalPembeli ?></span></div>
            <i class="fa-solid fa-circle-check stat-icon"></i>
        </div>
    </div>
</div>

<!-- ══ SEARCH & FILTER ══ -->
<form method="GET" action="" id="formFilterPembeli"
    style="margin-bottom:16px;display:flex;gap:10px;flex-wrap:wrap;align-items:center">
    <input type="hidden" name="section" value="pembeli">
    <div style="position:relative;flex:1;min-width:200px">
        <i class="fa-solid fa-magnifying-glass"
            style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:13px"></i>
        <input type="text" name="q_pembeli" value="<?= htmlspecialchars($searchPembeli) ?>"
            placeholder="Cari nama atau NISN/NUPTK..."
            style="width:100%;padding:10px 12px 10px 36px;border:1.5px solid var(--border);border-radius:10px;font-size:13px;background:var(--card-bg);color:var(--text);outline:none;box-sizing:border-box">
    </div>
    <select name="filter_kategori" class="form-select"
        style="min-width:130px;padding:10px 12px;border:1.5px solid var(--border);border-radius:10px;font-size:13px;background:var(--card-bg);color:var(--text)">
        <option value="" <?= $filterKategori === '' ? 'selected' : '' ?>>Semua</option>
        <option value="murid" <?= $filterKategori === 'murid' ? 'selected' : '' ?>>Murid</option>
        <option value="guru" <?= $filterKategori === 'guru' ? 'selected' : '' ?>>Guru</option>
    </select>
    <!-- Tambah setelah select filter_kategori -->
    <select name="filter_kelas" id="filterKelas"
        style="min-width:110px;padding:10px 12px;border:1.5px solid var(--border);border-radius:10px;font-size:13px;background:var(--card-bg);color:var(--text)">
        <option value="">Semua Kelas</option>
        <?php foreach ($semuaKelas as $k): ?>
            <option value="<?= $k['id_kelas'] ?>" <?= ($_GET['filter_kelas'] ?? '') == $k['id_kelas'] ? 'selected' : '' ?>>
                Kelas
                <?= htmlspecialchars($k['nama_kelas']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="filter_jurusan" id="filterJurusan"
        style="min-width:130px;padding:10px 12px;border:1.5px solid var(--border);border-radius:10px;font-size:13px;background:var(--card-bg);color:var(--text)">
        <option value="">Semua Jurusan</option>
        <?php foreach ($semuaJurusan as $j): ?>
            <option value="<?= $j['id_jurusan'] ?>" <?= ($_GET['filter_jurusan'] ?? '') == $j['id_jurusan'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($j['nama_jurusan']) ?>
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
                            <td colspan="5">
                                <i class="fa-solid fa-users"
                                    style="color:var(--green-muted);font-size:22px;display:block;margin-bottom:8px"></i>
                                <?= $searchPembeli ? 'Tidak ada hasil untuk "' . htmlspecialchars($searchPembeli) . '"' : 'Belum ada data pembeli' ?>
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
                            <tr data-kelas="<?= htmlspecialchars($pb['id_kelas'] ?? '') ?>"
                                data-jurusan="<?= htmlspecialchars($pb['id_jurusan'] ?? '') ?>">
                                <td><?= htmlspecialchars($pb['nama']) ?></td>
                                <td class="col-hide" style="color:var(--text-muted);font-size:12px"><?= $idVal ?></td>
                                <td>
                                    <span class="badge"
                                        style="background:<?= $isMurid ? 'var(--green-muted)' : '#e8f0fe' ?>;color:<?= $isMurid ? 'var(--green-dark,#2d7a2d)' : '#1a56db' ?>;font-weight:600;font-size:11px;padding:3px 10px;border-radius:20px">
                                        <?= $pb['kategori'] ?>
                                    </span>
                                </td>

                                <td class="col-hide" style="font-size:12px;color:var(--text-muted)">
                                    <?php if ($pb['kategori'] === 'Murid'): ?>
                                        <?= htmlspecialchars($pb['info_tambahan'] ?? '-') ?> /
                                        <?= htmlspecialchars($pb['info2'] ?? '-') ?>
                                    <?php else: ?>
                                        <span style="color:#aaa">—</span>
                                    <?php endif; ?>
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
                                        onclick="bukaResetPembeli('<?= $idKey ?>','<?= $idVal ?>','<?= $resetAct ?>','<?= htmlspecialchars($pb['nama']) ?>')"><i
                                            class="fa-solid fa-key"></i></button>

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
                                            onclick="if(confirm('Hapus pembeli <?= htmlspecialchars($pb['nama']) ?>?')) this.closest('form').submit()">
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
            <button id="tabMurid" type="button" class="btn-tab active" onclick="switchTabPembeli('murid')"
                style="flex:1;padding:9px;border-radius:8px;border:1.5px solid var(--green);background:var(--green);color:#fff;font-weight:600;font-size:13px;cursor:pointer;transition:.2s"><i
                    class="fa-solid fa-graduation-cap" style="margin-right:5px"></i>Murid</button>
            <button id="tabGuru" type="button" class="btn-tab" onclick="switchTabPembeli('guru')"
                style="flex:1;padding:9px;border-radius:8px;border:1.5px solid var(--border);background:transparent;color:var(--text-muted);font-weight:600;font-size:13px;cursor:pointer;transition:.2s"><i
                    class="fa-solid fa-chalkboard-teacher" style="margin-right:5px"></i>Guru</button>
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
                            <option value="<?= $k['id_kelas'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Jurusan</label>
                    <select name="id_jurusan" class="form-select" required>
                        <option value="">Pilih jurusan...</option>
                        <?php foreach ($semuaJurusan as $j): ?>
                            <option value="<?= $j['id_jurusan'] ?>"><?= htmlspecialchars($j['nama_jurusan']) ?></option>
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
    /* ── Tab switcher Murid / Guru ── */
    function switchTabPembeli(tab) {
        const isMurid = tab === 'murid';
        document.getElementById('formPanelMurid').style.display = isMurid ? '' : 'none';
        document.getElementById('formPanelGuru').style.display = isMurid ? 'none' : '';

        const tabMurid = document.getElementById('tabMurid');
        const tabGuru = document.getElementById('tabGuru');

        if (isMurid) {
            tabMurid.style.cssText = 'flex:1;padding:9px;border-radius:8px;border:1.5px solid var(--green);background:var(--green);color:#fff;font-weight:600;font-size:13px;cursor:pointer;transition:.2s';
            tabGuru.style.cssText = 'flex:1;padding:9px;border-radius:8px;border:1.5px solid var(--border);background:transparent;color:var(--text-muted);font-weight:600;font-size:13px;cursor:pointer;transition:.2s';
        } else {
            tabGuru.style.cssText = 'flex:1;padding:9px;border-radius:8px;border:1.5px solid var(--green);background:var(--green);color:#fff;font-weight:600;font-size:13px;cursor:pointer;transition:.2s';
            tabMurid.style.cssText = 'flex:1;padding:9px;border-radius:8px;border:1.5px solid var(--border);background:transparent;color:var(--text-muted);font-weight:600;font-size:13px;cursor:pointer;transition:.2s';
        }
    }

    /* ── Modal Reset Password Pembeli ── */
    function bukaResetPembeli(idKey, idVal, action, nama) {
        document.getElementById('namaResetPembeli').textContent = nama;
        document.getElementById('resetPembeliAction').value = action;
        document.getElementById('inputPwResetPembeli').value = '';

        // set field name + value untuk id (nisn atau nuptk)
        const field = document.getElementById('resetPembeliIdField');
        field.name = idKey;
        field.value = idVal;

        document.getElementById('modalResetPembeli').style.display = 'flex';
    }

    function tutupResetPembeli() {
        document.getElementById('modalResetPembeli').style.display = 'none';
    }

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') tutupResetPembeli();
    });

    /* ── Filter pembeli client-side ── */
    const inputSearch = document.querySelector('input[name="q_pembeli"]');
    const selectKat = document.querySelector('select[name="filter_kategori"]');

    const filterKelasEl = document.getElementById('filterKelas');
    const filterJurusanEl = document.getElementById('filterJurusan');

    // Sembunyikan filter kelas/jurusan saat pilih Guru
    function toggleFilterKelasJurusan() {
        const kat = selectKat.value;
        const show = kat !== 'guru';
        filterKelasEl.style.display = show ? '' : 'none';
        filterJurusanEl.style.display = show ? '' : 'none';
        if (!show) {
            filterKelasEl.value = '';
            filterJurusanEl.value = '';
        }
        filterPembeli();
    }

    function filterPembeli() {
        const q = inputSearch.value.toLowerCase();
        const kat = selectKat.value;
        const kelasId = filterKelasEl.value;
        const jurusanId = filterJurusanEl.value;

        document.querySelectorAll('tbody tr:not(.empty-row)').forEach(row => {
            const nama = row.cells[0]?.textContent.toLowerCase() ?? '';
            const nisn = row.cells[1]?.textContent.toLowerCase() ?? '';
            const katCell = row.cells[2]?.textContent.trim().toLowerCase() ?? '';
            const info = row.cells[3]?.textContent ?? ''; // kolom Kelas / Jurusan

            const matchQ = nama.includes(q) || nisn.includes(q);
            const matchKat = kat === '' || katCell.includes(kat.toLowerCase());

            // Filter kelas & jurusan hanya berlaku untuk murid
            let matchKelas = true;
            let matchJurusan = true;

            if (kelasId && katCell.includes('murid')) {
                // data-kelas disimpan di row attribute
                matchKelas = row.dataset.kelas === kelasId;
            }
            if (jurusanId && katCell.includes('murid')) {
                matchJurusan = row.dataset.jurusan === jurusanId;
            }

            row.style.display = matchQ && matchKat && matchKelas && matchJurusan ? '' : 'none';
        });
    }

    if (inputSearch) inputSearch.addEventListener('input', filterPembeli);
    if (selectKat) selectKat.addEventListener('change', toggleFilterKelasJurusan);
    if (filterKelasEl) filterKelasEl.addEventListener('change', filterPembeli);
    if (filterJurusanEl) filterJurusanEl.addEventListener('change', filterPembeli);

    // init
    toggleFilterKelasJurusan();


</script>