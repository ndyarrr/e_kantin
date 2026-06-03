<?php // sections/pembeli.php ?>

<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); max-width: 800px; margin-bottom: 20px;">
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
    <div class="stat-card" style="display: flex; flex-direction: column; justify-content: center; gap: 8px;">
        <div class="stat-label" style="margin-bottom: 4px;">Pengaturan Kelas & Jurusan</div>
        <div style="display: flex; gap: 8px;">
            <button class="btn-submit" onclick="bukaModalTambahKelasJurusan()" style="padding: 8px 12px; font-size: 12px; flex: 1; white-space: nowrap; margin: 0; height: auto;">
                <i class="fa-solid fa-plus" style="margin-right: 4px;"></i> Tambah
            </button>
            <button class="btn-submit" onclick="bukaModalKelolaKelasJurusan()" style="padding: 8px 12px; font-size: 12px; flex: 1; white-space: nowrap; background: #4b5563; margin: 0; height: auto;">
                <i class="fa-solid fa-gear" style="margin-right: 4px;"></i> Kelola
            </button>
        </div>
    </div>
</div>

<div id="panelDaftarPembeli">

    <div style="margin-bottom:16px; display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
        <form method="GET" action="index.php" id="formFilterPembeli"
            style="flex:1; display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin:0;"
            onsubmit="return false;">
            <input type="hidden" name="section" value="pembeli">

            <div style="position:relative; flex:1; min-width:200px">
                <i class="fa-solid fa-magnifying-glass"
                    style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:13px"></i>
                <input type="text" id="inputCariPembeli" name="q_pembeli"
                    value="<?= htmlspecialchars($searchPembeli ?? '') ?>" placeholder="Cari nama atau NISN/NUPTK..."
                    style="width:100%; padding:10px 12px 10px 36px; border:1.5px solid var(--border); border-radius:10px; font-size:13px; background:var(--card-bg); color:var(--text); outline:none; box-sizing:border-box">
            </div>

            <select id="filterKategori"
                style="min-width:130px; padding:10px 12px; border:1.5px solid var(--border); border-radius:10px; font-size:13px; background:var(--card-bg); color:var(--text)">
                <option value="">Semua Kategori</option>
                <option value="murid" <?= $filterKategori === 'murid' ? 'selected' : '' ?>>Murid</option>
                <option value="guru" <?= $filterKategori === 'guru' ? 'selected' : '' ?>>Guru</option>
            </select>

            <select id="filterKelas"
                style="min-width:110px; padding:10px 12px; border:1.5px solid var(--border); border-radius:10px; font-size:13px; background:var(--card-bg); color:var(--text); display:none;">
                <option value="">Semua Kelas</option>
                <?php foreach ($semuaTingkat as $t): ?>
                    <option value="<?= $t['kelas'] ?>">Kelas
                        <?= $t['kelas'] ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select id="filterJurusan"
                style="min-width:130px; padding:10px 12px; border:1.5px solid var(--border); border-radius:10px; font-size:13px; background:var(--card-bg); color:var(--text); display:none;">
                <option value="">Semua Jurusan</option>
                <?php foreach ($semuaJurusan as $j): ?>
                    <option value="<?= htmlspecialchars($j['id_jurusan']) ?>"
                        data-nama-jurusan="<?= htmlspecialchars($j['nama_jurusan']) ?>">
                        <?= htmlspecialchars($j['nama_jurusan']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select id="filterRombel"
                style="min-width:120px; padding:10px 12px; border:1.5px solid var(--border); border-radius:10px; font-size:13px; background:var(--card-bg); color:var(--text); display:none;">
                <option value="">Semua Rombel</option>
                <?php
                $disaringRombel = [];
                foreach ($semuaKelas as $k) {
                    $rombelNo = isset($k['rombel']) ? trim($k['rombel']) : '';
                    $idJurusan = isset($k['id_jurusan']) ? trim($k['id_jurusan']) : '';

                    if ($rombelNo !== '' && $idJurusan !== '') {
                        $keyGabungan = $idJurusan . '-' . $rombelNo;
                        if (!isset($disaringRombel[$keyGabungan])) {
                            $disaringRombel[$keyGabungan] = [
                                'rombel' => $rombelNo,
                                'id_jurusan' => $idJurusan
                            ];
                        }
                    }
                }

                asort($disaringRombel);

                foreach ($disaringRombel as $r):
                    ?>
                    <option value="<?= htmlspecialchars($r['rombel']) ?>"
                        data-id-jurusan-opsi="<?= htmlspecialchars($r['id_jurusan']) ?>">
                        Rombel
                        <?= htmlspecialchars($r['rombel']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="table-card" style="width:100%;">
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
                                    style="color:var(--green-muted); font-size:22px; display:block; margin-bottom:8px"></i>
                                <?= ($searchPembeli ?? '') ? 'Tidak ada hasil untuk "' . htmlspecialchars($searchPembeli) . '"' : 'Belum ada data pembeli' ?>
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
                                data-jurusan="<?= htmlspecialchars($pb['id_jurusan'] ?? '') ?>"
                                data-rombel="<?= htmlspecialchars($pb['rombel'] ?? '') ?>">
                                <td>
                                    <?= htmlspecialchars($pb['nama']) ?>
                                </td>
                                <td class="col-hide" style="color:var(--text-muted); font-size:12px">
                                    <?= $idVal ?>
                                </td>
                                <td>
                                    <span class="badge"
                                        style="background:<?= $isMurid ? 'var(--green-muted)' : '#e8f0fe' ?>; color:<?= $isMurid ? 'var(--green-dark,#2d7a2d)' : '#1a56db' ?>; font-weight:600; font-size:11px; padding:3px 10px; border-radius:20px">
                                        <?= $pb['kategori'] ?>
                                    </span>
                                </td>
                                <td class="col-hide" style="font-size:12px; color:var(--text-muted)">
                                    <?= $isMurid ? htmlspecialchars($pb['info_tambahan'] ?? '-') : '<span style="color:#aaa">—</span>' ?>
                                </td>
                                <td class="col-hide" style="font-size:12px; color:var(--text-muted)">
                                    <?= !empty($pb['terakhir_login']) ? date('d/m/Y H:i', strtotime($pb['terakhir_login'])) : '<span style="color:#aaa">Belum pernah</span>' ?>
                                </td>
                                <td>
                                    <span class="badge <?= $aktif ? 'badge-aktif' : 'badge-nonaktif' ?>">
                                        <i class="fa-solid <?= $aktif ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
                                        <?= $aktif ? 'Aktif' : 'Nonaktif' ?>
                                    </span>
                                </td>
                                <td class="center" style="white-space:nowrap">
                                    <button type="button" class="btn-aksi reset" title="Reset Password"
                                        onclick="bukaResetPembeli('<?= $idKey ?>','<?= $idVal ?>','<?= $resetAct ?>','<?= htmlspecialchars($pb['nama'], ENT_QUOTES) ?>')">
                                        <i class="fa-solid fa-key"></i>
                                    </button>

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
</div>

<div id="modalResetPembeli"
    style="display:none; position:fixed; inset:0; z-index:100; align-items:center; justify-content:center">
    <div onclick="tutupResetPembeli()"
        style="position:absolute; inset:0; background:rgba(0,0,0,.45); backdrop-filter:blur(2px)"></div>
    <div
        style="position:relative; background:#fff; border-radius:16px; padding:28px; width:90%; max-width:360px; box-shadow:0 8px 32px rgba(0,0,0,.15)">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px">
            <h2 style="font-size:16px; font-weight:700">Reset Password</h2>
            <button onclick="tutupResetPembeli()"
                style="background:none; border:none; font-size:18px; cursor:pointer; color:#6b7280"><i
                    class="fa-solid fa-xmark"></i></button>
        </div>
        <p style="font-size:13px; color:#6b7280; margin-bottom:16px">Reset password untuk: <strong
                id="namaResetPembeli"></strong></p>
        <form method="POST" id="formResetPembeli">
            <input type="hidden" name="action" id="resetPembeliAction">
            <input type="hidden" name="_section" value="pembeli">
            <input type="hidden" name="" id="resetPembeliIdField">
            <div class="form-group">
                <label>Password Baru</label>
                <div class="password-wrap">
                    <input type="password" name="pw_reset" id="inputPwResetPembeli" placeholder="Masukkan password baru"
                        required>
                    <button type="button" class="btn-eye"
                        onclick="togglePw('inputPwResetPembeli','eyePassResetPembeli')"><i class="fa-solid fa-eye"
                            id="eyePassResetPembeli"></i></button>
                </div>
            </div>
            <button type="submit" class="btn-submit"><i class="fa-solid fa-key" style="margin-right:6px"></i>Reset
                Password</button>
        </form>
    </div>
</div>

<script>
    function bukaResetPembeli(idKey, idVal, action, nama) {
        document.getElementById('namaResetPembeli').textContent = nama;
        document.getElementById('resetPembeliAction').value = action;
        document.getElementById('inputPwResetPembeli').value = '';
        const field = document.getElementById('resetPembeliIdField');
        field.name = idKey;
        field.value = idVal;
        document.getElementById('modalResetPembeli').style.display = 'flex';
    }
    function tutupResetPembeli() { document.getElementById('modalResetPembeli').style.display = 'none'; }
    document.addEventListener('keydown', e => { if (e.key === 'Escape') tutupResetPembeli(); });

    // Inisialisasi selector filter pembeli
    const elSearch = document.getElementById('inputCariPembeli');
    const elKategori = document.getElementById('filterKategori');
    const elKelas = document.getElementById('filterKelas');
    const elJurusan = document.getElementById('filterJurusan');
    const elRombel = document.getElementById('filterRombel');

    function updateDropdownFilterState() {
        if (!elKategori) return;

        const isMurid = elKategori.value === 'murid';
        const jurusanIdTerpilih = elJurusan.value;

        elKelas.style.display = isMurid ? '' : 'none';
        elJurusan.style.display = isMurid ? '' : 'none';
        elRombel.style.display = isMurid ? '' : 'none';

        if (!isMurid) {
            elKelas.value = '';
            elJurusan.value = '';
            elRombel.value = '';
        }

        const opsiRombel = elRombel.querySelectorAll('option:not([value=""])');
        let rombelSesuaiMasihAda = false;
        const rombelTampil = new Set();

        opsiRombel.forEach(opsi => {
            const idJurusanOpsi = opsi.dataset.idJurusanOpsi;
            const val = opsi.value;
            const cocokJurusan = (jurusanIdTerpilih === '' || idJurusanOpsi === jurusanIdTerpilih);

            if (cocokJurusan && !rombelTampil.has(val)) {
                opsi.style.display = '';
                rombelTampil.add(val);
                if (elRombel.value === val) {
                    rombelSesuaiMasihAda = true;
                }
            } else {
                opsi.style.display = 'none';
            }
        });

        if (!rombelSesuaiMasihAda && elRombel.value !== '') {
            elRombel.value = '';
        }

        filterPembeli();
    }

    function filterPembeli() {
        const q = elSearch ? elSearch.value.toLowerCase().trim() : '';
        const kat = elKategori ? elKategori.value.toLowerCase() : '';
        const tingkat = elKelas ? elKelas.value : '';
        let jurusanId = elJurusan ? elJurusan.value : '';
        const rombelNo = elRombel ? elRombel.value : '';

        const areaPembeli = document.getElementById('panelDaftarPembeli');
        if (!areaPembeli) return;

        areaPembeli.querySelectorAll('tbody tr:not(.empty-row)').forEach(row => {
            const nama = row.cells[0]?.textContent.toLowerCase() ?? '';
            const nisn = row.cells[1]?.textContent.toLowerCase() ?? '';

            const katRow = row.dataset.kategori ?? '';
            const tingkatRow = row.dataset.tingkat ?? '';
            const jurusanRow = row.dataset.jurusan ?? '';
            const rombelRow = row.dataset.rombel ?? '';

            const matchQ = q === '' || nama.includes(q) || nisn.includes(q);
            const matchKat = kat === '' || katRow === kat;

            let matchKelas = true;
            let matchJurusan = true;
            let matchRombel = true;

            if (katRow === 'murid') {
                if (tingkat) matchKelas = (tingkatRow === tingkat);
                if (jurusanId) matchJurusan = (jurusanRow === jurusanId);
                if (rombelNo) matchRombel = (rombelRow === rombelNo);
            } else if (katRow === 'guru' && (tingkat || jurusanId || rombelNo)) {
                row.style.display = 'none';
                return;
            }

            if (matchQ && matchKat && matchKelas && matchJurusan && matchRombel) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    if (elSearch) elSearch.addEventListener('input', filterPembeli);
    if (elKategori) elKategori.addEventListener('change', updateDropdownFilterState);
    if (elKelas) elKelas.addEventListener('change', filterPembeli);
    if (elJurusan) elJurusan.addEventListener('change', updateDropdownFilterState);
    if (elRombel) elRombel.addEventListener('change', filterPembeli);

    window.addEventListener('DOMContentLoaded', () => {
        updateDropdownFilterState();
    });

    // ── MODAL TAMBAH KELAS / JURUSAN ──
    function bukaModalTambahKelasJurusan() {
        document.getElementById('modalTambahKelasJurusan').style.display = 'flex';
    }
    function tutupModalTambahKelasJurusan() {
        document.getElementById('modalTambahKelasJurusan').style.display = 'none';
    }
    function switchTambahTab(tab) {
        const tabKelasBtn = document.getElementById('tabKelasBtn');
        const tabJurusanBtn = document.getElementById('tabJurusanBtn');
        const formTambahKelas = document.getElementById('formTambahKelas');
        const formTambahJurusan = document.getElementById('formTambahJurusan');
        
        if (tab === 'kelas') {
            tabKelasBtn.style.background = 'var(--green)';
            tabKelasBtn.style.color = '#fff';
            tabJurusanBtn.style.background = '#f3f4f6';
            tabJurusanBtn.style.color = 'var(--text)';
            formTambahKelas.style.display = 'block';
            formTambahJurusan.style.display = 'none';
        } else {
            tabJurusanBtn.style.background = 'var(--green)';
            tabJurusanBtn.style.color = '#fff';
            tabKelasBtn.style.background = '#f3f4f6';
            tabKelasBtn.style.color = 'var(--text)';
            formTambahKelas.style.display = 'none';
            formTambahJurusan.style.display = 'block';
        }
    }

    // ── MODAL KELOLA KELAS / JURUSAN ──
    function bukaModalKelolaKelasJurusan() {
        document.getElementById('modalKelolaKelasJurusan').style.display = 'flex';
    }
    function tutupModalKelolaKelasJurusan() {
        document.getElementById('modalKelolaKelasJurusan').style.display = 'none';
    }
    function switchKelolaTab(tab) {
        const tabKelolaKelasBtn = document.getElementById('tabKelolaKelasBtn');
        const tabKelolaJurusanBtn = document.getElementById('tabKelolaJurusanBtn');
        const kelolaKelasSection = document.getElementById('kelolaKelasSection');
        const kelolaJurusanSection = document.getElementById('kelolaJurusanSection');
        
        if (tab === 'kelas') {
            tabKelolaKelasBtn.style.background = 'var(--green)';
            tabKelolaKelasBtn.style.color = '#fff';
            tabKelolaJurusanBtn.style.background = '#f3f4f6';
            tabKelolaJurusanBtn.style.color = 'var(--text)';
            kelolaKelasSection.style.display = 'block';
            kelolaJurusanSection.style.display = 'none';
        } else {
            tabKelolaJurusanBtn.style.background = 'var(--green)';
            tabKelolaJurusanBtn.style.color = '#fff';
            tabKelolaKelasBtn.style.background = '#f3f4f6';
            tabKelolaKelasBtn.style.color = 'var(--text)';
            kelolaKelasSection.style.display = 'none';
            kelolaJurusanSection.style.display = 'block';
        }
    }

    function hapusKelasAlurKonfirmasi(id_kelas, nama_kelas, jumlah_murid) {
        // Konfirmasi 1: Hapus kelas / jurusan
        const conf1 = confirm('Apakah Anda yakin ingin menghapus kelas ' + nama_kelas + '?');
        if (!conf1) return;
        
        // Konfirmasi 2: Hapus seluruh murid di kelas tersebut
        const detailMurid = jumlah_murid > 0 ? ' (terdapat ' + jumlah_murid + ' murid aktif)' : ' (kelas kosong)';
        const conf2 = confirm('Peringatan: Tindakan ini juga akan menghapus seluruh murid di kelas tersebut secara permanen' + detailMurid + '. Apakah Anda yakin ingin melanjutkan?');
        if (!conf2) return;
        
        const form = document.getElementById('formHapusKelas_' + id_kelas);
        if (form) form.submit();
    }

    // Bind Escape key to close modals
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            tutupModalTambahKelasJurusan();
            tutupModalKelolaKelasJurusan();
        }
    });
</script>

<!-- ── OVERLAY MODAL TAMBAH KELAS / JURUSAN ── -->
<div id="modalTambahKelasJurusan" class="modalResetPembeli" style="display:none; position:fixed; inset:0; z-index:99999; align-items:center; justify-content:center; background:rgba(0,0,0,0.5); backdrop-filter:blur(2px);">
    <div style="position:relative; background:#fff; border-radius:16px; padding:28px; width:90%; max-width:440px; box-shadow:0 8px 32px rgba(0,0,0,.15); z-index:101; font-family:'Segoe UI',sans-serif;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; border-bottom: 1.5px solid var(--border); padding-bottom: 10px;">
            <h2 style="font-size:16px; font-weight:700; margin:0; color:var(--text);">Tambah Kelas / Jurusan</h2>
            <button onclick="tutupModalTambahKelasJurusan()" style="background:none; border:none; font-size:18px; cursor:pointer; color:#6b7280"><i class="fa-solid fa-xmark"></i></button>
        </div>
        
        <!-- Tabs -->
        <div style="display: flex; gap: 8px; margin-bottom: 16px;">
            <button type="button" id="tabKelasBtn" onclick="switchTambahTab('kelas')" style="flex:1; padding:10px; border-radius:10px; border:1.5px solid var(--border); background:var(--green); color:#fff; font-weight:600; font-size:13px; cursor:pointer; font-family:inherit;">Kelas</button>
            <button type="button" id="tabJurusanBtn" onclick="switchTambahTab('jurusan')" style="flex:1; padding:10px; border-radius:10px; border:1.5px solid var(--border); background:#f3f4f6; color:var(--text); font-weight:600; font-size:13px; cursor:pointer; font-family:inherit;">Jurusan</button>
        </div>

        <!-- Form Tambah Kelas -->
        <form method="POST" id="formTambahKelas" style="display: block; margin:0;">
            <input type="hidden" name="action" value="pembeli_tambah_kelas">
            <div class="form-group">
                <label style="display:block; font-size:12px; font-weight:600; color:var(--text-muted); margin-bottom:6px;">Tingkat Kelas</label>
                <select name="kelas" style="width:100%; padding:10px 12px; border:1.5px solid var(--border); border-radius:10px; background:var(--bg); font-size:14px; outline:none;" required>
                    <option value="">Pilih Tingkat</option>
                    <option value="10">Kelas 10</option>
                    <option value="11">Kelas 11</option>
                    <option value="12">Kelas 12</option>
                </select>
            </div>
            <div class="form-group" style="margin-top:14px;">
                <label style="display:block; font-size:12px; font-weight:600; color:var(--text-muted); margin-bottom:6px;">Jurusan</label>
                <select name="id_jurusan" style="width:100%; padding:10px 12px; border:1.5px solid var(--border); border-radius:10px; background:var(--bg); font-size:14px; outline:none;" required>
                    <option value="">Pilih Jurusan</option>
                    <?php foreach ($semuaJurusan as $j): ?>
                        <option value="<?= $j['id_jurusan'] ?>"><?= htmlspecialchars($j['nama_jurusan']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-top:14px;">
                <label style="display:block; font-size:12px; font-weight:600; color:var(--text-muted); margin-bottom:6px;">Rombel / Urutan Kelas (cth: 1, 2)</label>
                <input type="number" name="rombel" min="1" value="1" placeholder="cth: 1" style="width:100%; padding:10px 12px; border:1.5px solid var(--border); border-radius:10px; background:var(--bg); font-size:14px; outline:none; box-sizing:border-box;" required>
            </div>
            <button type="submit" class="btn-submit" style="margin-top:18px; width:100%; padding:12px; background:var(--green); color:#fff; border:none; border-radius:10px; font-size:14px; font-weight:700; cursor:pointer;"><i class="fa-solid fa-plus" style="margin-right:6px"></i>Tambah Kelas</button>
        </form>

        <!-- Form Tambah Jurusan -->
        <form method="POST" id="formTambahJurusan" style="display: none; margin:0;">
            <input type="hidden" name="action" value="pembeli_tambah_jurusan">
            <div class="form-group">
                <label style="display:block; font-size:12px; font-weight:600; color:var(--text-muted); margin-bottom:6px;">Nama Singkat Jurusan (cth: RPL, TKJ, AKL)</label>
                <input type="text" name="nama_jurusan" placeholder="cth: RPL" style="width:100%; padding:10px 12px; border:1.5px solid var(--border); border-radius:10px; background:var(--bg); font-size:14px; outline:none; box-sizing:border-box; text-transform:uppercase;" required>
            </div>
            <button type="submit" class="btn-submit" style="margin-top:18px; width:100%; padding:12px; background:var(--green); color:#fff; border:none; border-radius:10px; font-size:14px; font-weight:700; cursor:pointer;"><i class="fa-solid fa-plus" style="margin-right:6px"></i>Tambah Jurusan</button>
        </form>
    </div>
</div>

<!-- ── OVERLAY MODAL KELOLA KELAS / JURUSAN ── -->
<div id="modalKelolaKelasJurusan" class="modalResetPembeli" style="display:none; position:fixed; inset:0; z-index:99999; align-items:center; justify-content:center; background:rgba(0,0,0,0.5); backdrop-filter:blur(2px);">
    <div style="position:relative; background:#fff; border-radius:16px; padding:28px; width:90%; max-width:500px; box-shadow:0 8px 32px rgba(0,0,0,.15); z-index:101; display:flex; flex-direction:column; max-height:80vh; font-family:'Segoe UI',sans-serif;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; border-bottom: 1.5px solid var(--border); padding-bottom: 10px; flex-shrink:0;">
            <h2 style="font-size:16px; font-weight:700; margin:0; color:var(--text);">Kelola Kelas & Jurusan</h2>
            <button onclick="tutupModalKelolaKelasJurusan()" style="background:none; border:none; font-size:18px; cursor:pointer; color:#6b7280"><i class="fa-solid fa-xmark"></i></button>
        </div>
        
        <!-- Tabs -->
        <div style="display: flex; gap: 8px; margin-bottom: 16px; flex-shrink:0;">
            <button type="button" id="tabKelolaKelasBtn" onclick="switchKelolaTab('kelas')" style="flex:1; padding:10px; border-radius:10px; border:1.5px solid var(--border); background:var(--green); color:#fff; font-weight:600; font-size:13px; cursor:pointer; font-family:inherit;">Daftar Kelas</button>
            <button type="button" id="tabKelolaJurusanBtn" onclick="switchKelolaTab('jurusan')" style="flex:1; padding:10px; border-radius:10px; border:1.5px solid var(--border); background:#f3f4f6; color:var(--text); font-weight:600; font-size:13px; cursor:pointer; font-family:inherit;">Daftar Jurusan</button>
        </div>

        <!-- Kelola Kelas Section -->
        <div id="kelolaKelasSection" style="display: block; overflow-y: auto; flex:1;">
            <table style="width:100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="padding:10px; font-size:12px; border-bottom: 2px solid var(--border); font-weight:700; color:var(--text-muted); text-transform:uppercase;">Nama Kelas</th>
                        <th style="padding:10px; font-size:12px; border-bottom: 2px solid var(--border); font-weight:700; color:var(--text-muted); text-transform:uppercase; text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($semuaKelas)): ?>
                        <tr>
                            <td colspan="2" style="padding:16px; text-align:center; color:#9ca3af; font-size:13px;">Belum ada kelas terdaftar.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($semuaKelas as $k): ?>
                            <tr style="border-bottom:1px solid #f3f4f6;">
                                <td style="padding:10px; font-size:13px; font-weight:600; color:var(--text);"><?= htmlspecialchars($k['nama_kelas']) ?></td>
                                <td style="padding:10px; text-align:center;">
                                    <?php
                                    $q_count = mysqli_query($conn, "SELECT COUNT(*) as c FROM murid WHERE id_kelas=" . (int)$k['id_kelas'] . " AND deleted_at IS NULL");
                                    $c_murid = $q_count ? (int)mysqli_fetch_assoc($q_count)['c'] : 0;
                                    ?>
                                    <form method="POST" style="display:inline; margin:0;" id="formHapusKelas_<?= $k['id_kelas'] ?>">
                                        <input type="hidden" name="action" value="pembeli_hapus_kelas">
                                        <input type="hidden" name="id_kelas" value="<?= $k['id_kelas'] ?>">
                                        <button type="button" class="btn-aksi danger" style="padding: 6px; border:none; background:none; cursor:pointer; color:var(--red);" title="Hapus Kelas"
                                            onclick="hapusKelasAlurKonfirmasi(<?= $k['id_kelas'] ?>, '<?= htmlspecialchars($k['nama_kelas'], ENT_QUOTES) ?>', <?= $c_murid ?>)">
                                            <i class="fa-solid fa-trash" style="font-size:14px;"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Kelola Jurusan Section -->
        <div id="kelolaJurusanSection" style="display: none; overflow-y: auto; flex:1;">
            <table style="width:100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="padding:10px; font-size:12px; border-bottom: 2px solid var(--border); font-weight:700; color:var(--text-muted); text-transform:uppercase;">Nama Jurusan</th>
                        <th style="padding:10px; font-size:12px; border-bottom: 2px solid var(--border); font-weight:700; color:var(--text-muted); text-transform:uppercase; text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($semuaJurusan)): ?>
                        <tr>
                            <td colspan="2" style="padding:16px; text-align:center; color:#9ca3af; font-size:13px;">Belum ada jurusan terdaftar.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($semuaJurusan as $j): ?>
                            <tr style="border-bottom:1px solid #f3f4f6;">
                                <td style="padding:10px; font-size:13px; font-weight:600; color:var(--text);"><?= htmlspecialchars($j['nama_jurusan']) ?></td>
                                <td style="padding:10px; text-align:center;">
                                    <form method="POST" style="display:inline; margin:0;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus jurusan <?= htmlspecialchars($j['nama_jurusan'], ENT_QUOTES) ?>?')">
                                        <input type="hidden" name="action" value="pembeli_hapus_jurusan">
                                        <input type="hidden" name="id_jurusan" value="<?= $j['id_jurusan'] ?>">
                                        <button type="submit" class="btn-aksi danger" style="padding: 6px; border:none; background:none; cursor:pointer; color:var(--red);" title="Hapus Jurusan">
                                            <i class="fa-solid fa-trash" style="font-size:14px;"></i>
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
</div>