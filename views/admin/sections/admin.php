<div class="stats-grid col2">
    <div class="stat-card">
        <div class="stat-label">Total Admin</div>
        <div class="stat-row">
            <div class="stat-value"><?= $totalAdmin ?></div>
            <i class="fa-solid fa-user-shield stat-icon"></i>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Status Aktif</div>
        <div class="stat-row">
            <div class="stat-value"><?= $aktifCount ?><span class="sub"> / <?= $totalAdmin ?></span>
            </div>
            <i class="fa-solid fa-circle-check stat-icon"></i>
        </div>
    </div>
</div>

<div class="page-grid">
    <div class="table-card">
        <div class="table-card-header">
            <h2>Daftar Administrator</h2>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th class="col-hide">Kode Aktivasi</th>
                        <th>Status</th>
                        <th class="center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $a):
                        $isMe = ((int) $a['id_admin'] === $adminId);
                        $aktif = $a['status'] === 'aktif';
                        $kode = htmlspecialchars($a['kode_aktivasi']);
                        $kodeSensor = substr($kode, 0, 2) . str_repeat('•', max(0, strlen($kode) - 2));
                        ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($a['nama']) ?>
                                <?php if ($isMe): ?><span class="you-badge">Anda</span><?php endif; ?>
                            </td>
                            <td class="col-hide">
                                <div class="kode-wrap">
                                    <span id="kode-<?= $a['id_admin'] ?>" data-plain="<?= $kode ?>"
                                        data-hidden="1"><?= $kodeSensor ?></span>
                                    <button class="btn-reveal" onclick="revealKode(<?= $a['id_admin'] ?>)"><i
                                            class="fa-solid fa-eye" id="eye-<?= $a['id_admin'] ?>"></i></button>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= $aktif ? 'badge-aktif' : 'badge-nonaktif' ?>">
                                    <i class="fa-solid <?= $aktif ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
                                    <?= $aktif ? 'Aktif' : 'Nonaktif' ?>
                                </span>
                            </td>
                            <td class="center" style="white-space:nowrap">
                                <form method="POST" style="display:inline"
                                    onsubmit="return confirm('Reset password <?= htmlspecialchars($a['nama']) ?>?')">
                                    <input type="hidden" name="action" value="admin_reset">
                                    <input type="hidden" name="id" value="<?= $a['id_admin'] ?>">
                                    <input type="hidden" name="_section" value="admin">
                                    <button type="submit" class="btn-aksi reset" title="Reset Password"><i
                                            class="fa-solid fa-key"></i></button>
                                </form>
                                <?php if (!$isMe): ?>
                                    <form method="POST" style="display:inline"
                                        onsubmit="return confirm('<?= $aktif ? 'Nonaktifkan' : 'Aktifkan' ?> admin ini?')">
                                        <input type="hidden" name="action" value="admin_toggle">
                                        <input type="hidden" name="id" value="<?= $a['id_admin'] ?>">
                                        <input type="hidden" name="status" value="<?= $a['status'] ?>">
                                        <input type="hidden" name="_section" value="admin">
                                        <button type="submit" class="btn-aksi <?= $aktif ? 'danger' : 'toggle-on' ?>"
                                            title="<?= $aktif ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                            <i class="fa-solid <?= $aktif ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn-aksi toggle-off" disabled
                                        title="Tidak bisa menonaktifkan akun sendiri"><i
                                            class="fa-solid fa-user-slash"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="form-card">
        <h2><i class="fa-solid fa-user-plus" style="color:var(--green);margin-right:8px"></i>Tambah
            Admin Baru</h2>
        <form method="POST">
            <input type="hidden" name="action" value="admin_tambah">
            <input type="hidden" name="_section" value="admin">
            <div class="form-group">
                <label>Nama Admin</label>
                <input type="text" name="nama" placeholder="cth. Budi Santoso" required autocomplete="off">
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="password-wrap">
                    <input type="password" name="password" id="inputPass" placeholder="Minimal 8 karakter" required
                        autocomplete="new-password">
                    <button type="button" class="btn-eye" onclick="togglePass()"><i class="fa-solid fa-eye"
                            id="eyeIcon"></i></button>
                </div>
                <div class="form-note">Password akan di-hash untuk keamanan tambahan.</div>
            </div>
            <div style="margin-bottom:14px">
                <label
                    style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:6px">Kode
                    Aktivasi (otomatis)</label>
                <div class="kode-preview">
                    <span id="kodePreview">—</span>
                    <button type="button" class="btn-regen" onclick="regenKode()" title="Generate ulang"><i
                            class="fa-solid fa-rotate"></i></button>
                </div>
                <input type="hidden" name="kode_aktivasi" id="kodeHidden">
                <div class="form-note">Dibutuhkan admin saat login.</div>
            </div>
            <button type="submit" class="btn-submit"><i class="fa-solid fa-floppy-disk"
                    style="margin-right:6px"></i>Simpan Akun</button>
        </form>
    </div>
</div>