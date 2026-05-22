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

<div class="page-grid" style="<?= !$isAdminSuper ? 'grid-template-columns: 1fr;' : '' ?>">
    <div class="table-card">
        <div class="table-card-header">
            <h2>Daftar Administrator</h2>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Role Level</th>
                        <th class="col-hide">Kode Aktivasi</th>
                        <th>Status</th>
                        <?php if ($isAdminSuper): ?>
                            <th class="center">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $a):
                        $isMe = ((int) $a['id_admin'] === $adminId);
                        $aktif = $a['status'] === 'aktif';
                        $roleLevel = (int) ($a['role_level'] ?? 2);
                        $kode = htmlspecialchars($a['kode_aktivasi'] ?? '');
                        $kodeSensor = substr($kode, 0, 2) . str_repeat('•', max(0, strlen($kode) - 2));
                        ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($a['nama']) ?>
                                <?php if ($isMe): ?><span class="you-badge">Anda</span><?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $roleLevel === 1 ? 'badge-aktif' : 'badge-proses' ?>"
                                    style="font-size:11px;">
                                    <i class="fa-solid <?= $roleLevel === 1 ? 'fa-crown' : 'fa-user' ?>"></i>
                                    <?= $roleLevel === 1 ? 'Super Admin' : 'Admin' ?>
                                </span>
                            </td>
                            <td class="col-hide">
                                <div class="kode-wrap">
                                    <span id="kode-<?= $a['id_admin'] ?>" data-plain="<?= $kode ?>"
                                        data-hidden="1"><?= $kodeSensor ?></span>
                                    <button class="btn-reveal" onclick="revealKode(<?= $a['id_admin'] ?>)">
                                        <i class="fa-solid fa-eye" id="eye-<?= $a['id_admin'] ?>"></i>
                                    </button>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= $aktif ? 'badge-aktif' : 'badge-nonaktif' ?>">
                                    <i class="fa-solid <?= $aktif ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
                                    <?= $aktif ? 'Aktif' : 'Nonaktif' ?>
                                </span>
                            </td>

                            <?php if ($isAdminSuper): ?>
                                <td class="center" style="white-space:nowrap">
                                    <?php if (!$isMe && $roleLevel !== 1): ?>
                                        <button type="button" class="btn-aksi reset" title="Reset Password"
                                            onclick="bukaResetPassword(<?= $a['id_admin'] ?>, '<?= htmlspecialchars($a['nama']) ?>')">
                                            <i class="fa-solid fa-key"></i>
                                        </button>

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

                                        <form method="POST" style="display:inline"
                                            onsubmit="return confirm('Yakin ingin menghapus admin \'<?= htmlspecialchars($a['nama']) ?>\'? Data akan dipindahkan ke arsip sistem.')">
                                            <input type="hidden" name="action" value="admin_soft_delete">
                                            <input type="hidden" name="id" value="<?= $a['id_admin'] ?>">
                                            <input type="hidden" name="_section" value="admin">
                                            <button type="submit" class="btn-aksi danger" title="Soft Delete Admin"
                                                style="color: #ff0000ff;">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>

                                    <?php elseif ($isMe): ?>
                                        <button class="btn-aksi toggle-off" disabled title="Tidak bisa mengeksekusi akun sendiri">
                                            <i class="fa-solid fa-user-slash"></i>
                                        </button>

                                    <?php else: ?>
                                        <button class="btn-aksi toggle-off" disabled
                                            title="Tidak bisa mengeksekusi sesama Super Admin">
                                            <i class="fa-solid fa-crown" style="color: #9ca3af;"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($isAdminSuper): ?>
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
    <?php endif; ?>
</div>

<?php if ($isAdminSuper): ?>
    <div id="modalResetPw"
        style="display:none;position:fixed;inset:0;z-index:100;align-items:center;justify-content:center">
        <div onclick="tutupResetPw()"
            style="position:absolute;inset:0;background:rgba(0,0,0,.45);backdrop-filter:blur(2px)"></div>
        <div
            style="position:relative;background:#fff;border-radius:16px;padding:28px;width:90%;max-width:360px;box-shadow:0 8px 32px rgba(0,0,0,.15)">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
                <h2 style="font-size:16px;font-weight:700">Reset Password</h2>
                <button onclick="tutupResetPw()"
                    style="background:none;border:none;font-size:18px;cursor:pointer;color:#6b7280"><i
                        class="fa-solid fa-xmark"></i></button>
            </div>
            <p style="font-size:13px;color:#6b7280;margin-bottom:16px">Reset password untuk: <strong
                    id="namaResetTarget"></strong></p>
            <form method="POST" id="formResetPw"
                onsubmit="return confirm('Yakin reset password ' + document.getElementById('namaResetTarget').textContent + '?')">
                <input type="hidden" name="action" value="admin_reset">
                <input type="hidden" name="id" id="idResetTarget">
                <input type="hidden" name="_section" value="admin">
                <div class="form-group">
                    <label>Password Baru</label>
                    <div class="password-wrap">
                        <input type="password" name="pw_reset" id="inputPwReset" placeholder="Masukkan password baru"
                            required>
                        <button type="button" class="btn-eye" onclick="togglePw('inputPwReset','eyePwReset')">
                            <i class="fa-solid fa-eye" id="eyePwReset"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-key" style="margin-right:6px"></i>Reset Password
                </button>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
    function bukaResetPassword(id, nama) {
        if (document.getElementById('idResetTarget') && document.getElementById('namaResetTarget')) {
            document.getElementById('idResetTarget').value = id;
            document.getElementById('namaResetTarget').textContent = nama;
            document.getElementById('inputPwReset').value = '';
            document.getElementById('modalResetPw').style.display = 'flex';
        }
    }

    function tutupResetPw() {
        if (document.getElementById('modalResetPw')) {
            document.getElementById('modalResetPw').style.display = 'none';
        }
    }

    function togglePw(inputId, eyeId) {
        const inp = document.getElementById(inputId);
        const ico = document.getElementById(eyeId);
        if (inp && ico) {
            inp.type = inp.type === 'password' ? 'text' : 'password';
            ico.className = inp.type === 'password' ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
        }
    }
</script>