<?php // sections/tools.php ?>

<div class="tools-grid">

    <!-- ══ UPLOAD CSV ══ -->
    <div class="form-card">
        <h2><i class="fa-solid fa-file-csv" style="color:var(--green);margin-right:8px"></i>Import Data CSV</h2>

        <!-- Tab -->
        <div class="tools-tab-wrap">
            <button class="tools-tab active" id="tabCsvMurid" onclick="switchCsvTab('murid')">
                <i class="fa-solid fa-graduation-cap"></i> Murid
            </button>
            <button class="tools-tab" id="tabCsvGuru" onclick="switchCsvTab('guru')">
                <i class="fa-solid fa-chalkboard-user"></i> Guru
            </button>
        </div>

        <!-- Form Murid -->
        <div id="csvPanelMurid">
            <div class="csv-info">
                <i class="fa-solid fa-circle-info"></i>
                Format kolom: <code>nisn, nama, password, id_kelas, id_jurusan</code>
                <a href="?section=tools&download=template_murid" class="csv-download-link">
                    <i class="fa-solid fa-download"></i> Download Template
                </a>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="tools_import_murid">
                <input type="hidden" name="_section" value="tools">
                <div class="form-group">
                    <label>File CSV Murid</label>
                    <input type="file" name="csv_file" accept=".csv" required>
                    <div class="form-note">Maksimal 2MB. Baris pertama harus header.</div>
                </div>
                <div class="form-group">
                    <label
                        style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:500;color:var(--text)">
                        <input type="checkbox" name="skip_duplikat" value="1" checked style="accent-color:var(--green)">
                        Lewati NISN yang sudah terdaftar
                    </label>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-upload" style="margin-right:6px"></i>Import Murid
                </button>
            </form>
        </div>

        <!-- Form Guru -->
        <div id="csvPanelGuru" style="display:none">
            <div class="csv-info">
                <i class="fa-solid fa-circle-info"></i>
                Format kolom: <code>nuptk, nama, password</code>
                <a href="?section=tools&download=template_guru" class="csv-download-link">
                    <i class="fa-solid fa-download"></i> Download Template
                </a>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="tools_import_guru">
                <input type="hidden" name="_section" value="tools">
                <div class="form-group">
                    <label>File CSV Guru</label>
                    <input type="file" name="csv_file" accept=".csv" required>
                    <div class="form-note">Maksimal 2MB. Baris pertama harus header.</div>
                </div>
                <div class="form-group">
                    <label
                        style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:500;color:var(--text)">
                        <input type="checkbox" name="skip_duplikat" value="1" checked style="accent-color:var(--green)">
                        Lewati NUPTK yang sudah terdaftar
                    </label>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-upload" style="margin-right:6px"></i>Import Guru
                </button>
            </form>
        </div>

        <!-- Hasil import -->
        <?php if (!empty($importResult)): ?>
            <div class="import-result">
                <div class="import-result-title">
                    <i class="fa-solid fa-circle-check" style="color:var(--green)"></i>
                    Hasil Import
                </div>
                <div class="import-result-stats">
                    <div class="import-stat">
                        <span class="import-stat-val success"><?= $importResult['berhasil'] ?></span>
                        <span class="import-stat-label">Berhasil</span>
                    </div>
                    <div class="import-stat">
                        <span class="import-stat-val skip"><?= $importResult['dilewati'] ?></span>
                        <span class="import-stat-label">Dilewati</span>
                    </div>
                    <div class="import-stat">
                        <span class="import-stat-val error"><?= $importResult['gagal'] ?></span>
                        <span class="import-stat-label">Gagal</span>
                    </div>
                </div>
                <?php if (!empty($importResult['errors'])): ?>
                    <div class="import-errors">
                        <div style="font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:6px">Detail Error:
                        </div>
                        <?php foreach (array_slice($importResult['errors'], 0, 5) as $err): ?>
                            <div class="import-error-item">⚠ <?= htmlspecialchars($err) ?></div>
                        <?php endforeach; ?>
                        <?php if (count($importResult['errors']) > 5): ?>
                            <div style="font-size:12px;color:var(--text-muted)">...dan <?= count($importResult['errors']) - 5 ?>
                                error lainnya</div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ══ LOG SISTEM ══ -->
    <div class="table-card">
        <div class="table-card-header">
            <h2>Log Sistem</h2>
            <div style="display:flex;gap:8px;align-items:center">
                <!-- Filter role -->
                <form method="GET" style="display:flex;gap:6px;align-items:center">
                    <input type="hidden" name="section" value="tools">
                    <select name="log_role" class="form-select" style="padding:6px 10px;font-size:12px;min-width:100px"
                        onchange="this.form.submit()">
                        <option value="">Semua Role</option>
                        <option value="admin" <?= ($logRole === 'admin') ? 'selected' : '' ?>>Admin</option>
                        <option value="penjual" <?= ($logRole === 'penjual') ? 'selected' : '' ?>>Penjual</option>
                        <option value="guru" <?= ($logRole === 'guru') ? 'selected' : '' ?>>Guru</option>
                        <option value="siswa" <?= ($logRole === 'siswa') ? 'selected' : '' ?>>Siswa</option>
                    </select>
                    <!-- Hapus log -->
                    <form method="POST" style="display:inline"
                        onsubmit="return confirm('Hapus semua log? Aksi ini tidak bisa dibatalkan.')">
                        <input type="hidden" name="action" value="tools_hapus_log">
                        <input type="hidden" name="_section" value="tools">
                        <button type="submit" class="btn-aksi danger" title="Hapus semua log">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                </form>
            </div>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>User</th>
                        <th class="col-hide">Role</th>
                        <th>Aksi</th>
                        <th class="col-hide">Keterangan</th>
                        <th class="col-hide">IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logSistem)): ?>
                        <tr class="empty-row">
                            <td colspan="6">
                                <i class="fa-solid fa-scroll"
                                    style="font-size:22px;display:block;margin-bottom:8px;color:var(--green-muted)"></i>
                                Belum ada log
                            </td>
                        </tr>
                    <?php else:
                        foreach ($logSistem as $log): ?>
                            <tr>
                                <td style="white-space:nowrap;font-size:12px;color:var(--text-muted)">
                                    <?= date('d/m/y H:i', strtotime($log['dibuat_pada'])) ?>
                                </td>
                                <td style="font-weight:500"><?= htmlspecialchars($log['user_nama']) ?></td>
                                <td class="col-hide">
                                    <span class="badge <?= match ($log['user_role']) {
                                        'admin' => 'badge-aktif',
                                        'penjual' => 'badge-proses',
                                        'guru' => 'badge-siap',
                                        default => 'badge-nonaktif'
                                    } ?>">
                                        <?= ucfirst($log['user_role']) ?>
                                    </span>
                                </td>
                                <td style="font-size:13px"><?= htmlspecialchars($log['aksi']) ?></td>
                                <td class="col-hide"
                                    style="font-size:12px;color:var(--text-muted);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                    <?= htmlspecialchars($log['keterangan'] ?? '-') ?>
                                </td>
                                <td class="col-hide" style="font-size:12px;color:var(--text-muted)">
                                    <?= htmlspecialchars($log['ip_address'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($logTotal > $logPerPage): ?>
            <div class="log-pagination">
                <?php
                $totalPages = ceil($logTotal / $logPerPage);
                for ($i = 1; $i <= $totalPages; $i++):
                    ?>
                    <a href="?section=tools&log_page=<?= $i ?>&log_role=<?= urlencode($logRole) ?>"
                        class="log-page-btn <?= $logPage == $i ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- CSS khusus tools -->
<style>
    .tools-grid {
        display: grid;
        grid-template-columns: 360px 1fr;
        gap: 16px;
        align-items: start;
    }

    .tools-tab-wrap {
        display: flex;
        gap: 6px;
        margin-bottom: 16px;
    }

    .tools-tab {
        flex: 1;
        padding: 8px;
        border-radius: 8px;
        border: 1.5px solid var(--border);
        background: transparent;
        color: var(--text-muted);
        font-weight: 600;
        font-size: 13px;
        cursor: pointer;
        transition: .2s;
        font-family: inherit;
    }

    .tools-tab.active {
        border-color: var(--green);
        background: var(--green);
        color: #fff;
    }

    .csv-info {
        background: var(--green-pale);
        border: 1px solid var(--green-muted);
        border-radius: 8px;
        padding: 10px 12px;
        font-size: 12px;
        color: var(--green-dark);
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .csv-info code {
        background: rgba(0, 0, 0, .08);
        padding: 2px 6px;
        border-radius: 4px;
        font-family: monospace;
    }

    .csv-download-link {
        margin-left: auto;
        color: var(--green-dark);
        font-weight: 600;
        font-size: 12px;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .csv-download-link:hover {
        text-decoration: underline;
    }

    .import-result {
        margin-top: 16px;
        background: var(--card-bg);
        border-radius: 10px;
        border: 1px solid var(--border);
        padding: 14px;
    }

    .import-result-title {
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .import-result-stats {
        display: flex;
        gap: 16px;
        margin-bottom: 10px;
    }

    .import-stat {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
    }

    .import-stat-val {
        font-size: 22px;
        font-weight: 800;
        line-height: 1;
    }

    .import-stat-val.success {
        color: var(--green-dark);
    }

    .import-stat-val.skip {
        color: var(--yellow);
    }

    .import-stat-val.error {
        color: var(--red);
    }

    .import-stat-label {
        font-size: 11px;
        color: var(--text-muted);
    }

    .import-errors {
        background: var(--red-pale);
        border-radius: 8px;
        padding: 10px 12px;
    }

    .import-error-item {
        font-size: 12px;
        color: #991b1b;
        padding: 2px 0;
    }

    .log-pagination {
        display: flex;
        gap: 4px;
        padding: 12px 16px;
        border-top: 1px solid var(--border);
        flex-wrap: wrap;
    }

    .log-page-btn {
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        color: var(--text-muted);
        background: var(--bg);
        border: 1px solid var(--border);
        transition: .2s;
    }

    .log-page-btn.active {
        background: var(--green);
        color: #fff;
        border-color: var(--green);
    }

    .log-page-btn:hover:not(.active) {
        background: var(--green-light);
    }

    @media(max-width:1100px) {
        .tools-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    function switchCsvTab(tab) {
        const isMurid = tab === 'murid';
        document.getElementById('csvPanelMurid').style.display = isMurid ? '' : 'none';
        document.getElementById('csvPanelGuru').style.display = isMurid ? 'none' : '';
        document.getElementById('tabCsvMurid').classList.toggle('active', isMurid);
        document.getElementById('tabCsvGuru').classList.toggle('active', !isMurid);
    }
</script>