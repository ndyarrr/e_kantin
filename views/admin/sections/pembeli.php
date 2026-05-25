<?php // sections/pembeli.php ?>

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
</script>