<?php // views/admin/sections/pembeli.php ?>

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
            <div class="stat-value"><?= $pembeliAktif ?><span class="sub"> / <?= $totalPembeli ?></span></div>
            <i class="fa-solid fa-circle-check stat-icon"></i>
        </div>
    </div>
</div>

<div class="page-grid">
    <div class="table-card">
        <div class="table-card-header">
            <h2>Daftar Pembeli</h2>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th class="col-hide">NISN/NUPTK</th>
                        <th class="col-hide">Kategori</th>
                        <th>Status</th>
                        <th class="center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pembelis)): ?>
                        <tr class="empty-row">
                            <td colspan="5">
                                <i class="fa-solid fa-users"
                                    style="color:var(--green-muted);font-size:22px;display:block;margin-bottom:8px"></i>
                                Belum ada pembeli terdaftar
                            </td>
                        </tr>
                    <?php else:
                        foreach ($pembelis as $pb):
                            $aktif = $pb['status'] === 'aktif';
                        ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($pb['nama']) ?>
                                    <?php if (!empty($pb['kategori'])): ?>
                                        <span class="badge-kategori <?= $pb['kategori'] === 'guru' ? 'badge-guru' : 'badge-murid' ?>">
                                            <?= ucfirst($pb['kategori']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-hide">
                                    <?= $pb['nisn_nuptk'] ? htmlspecialchars($pb['nisn_nuptk']) : '<span style="color:var(--text-muted)">—</span>' ?>
                                </td>
                                <td class="col-hide">
                                    <?= $pb['kategori'] ? ucfirst($pb['kategori']) : '<span style="color:var(--text-muted)">—</span>' ?>
                                </td>
                                <td>
                                    <span class="badge <?= $aktif ? 'badge-aktif' : 'badge-nonaktif' ?>">
                                        <i class="fa-solid <?= $aktif ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
                                        <?= $aktif ? 'Aktif' : 'Nonaktif' ?>
                                    </span>
                                </td>
                                <td class="center" style="white-space:nowrap">
                                    <!-- Reset Password -->
                                    <form method="POST" style="display:inline"
                                        onsubmit="return confirm('Reset password <?= htmlspecialchars($pb['nama']) ?>?')">
                                        <input type="hidden" name="action" value="pembeli_reset">
                                        <input type="hidden" name="id" value="<?= $pb['id_pembeli'] ?>">
                                        <input type="hidden" name="_section" value="pembeli">
                                        <button type="submit" class="btn-aksi reset" title="Reset Password">
                                            <i class="fa-solid fa-key"></i>
                                        </button>
                                    </form>
                                    <!-- Toggle Aktif/Nonaktif -->
                                    <form method="POST" style="display:inline"
                                        onsubmit="return confirm('<?= $aktif ? 'Nonaktifkan' : 'Aktifkan' ?> pembeli ini?')">
                                        <input type="hidden" name="action" value="pembeli_toggle">
                                        <input type="hidden" name="id" value="<?= $pb['id_pembeli'] ?>">
                                        <input type="hidden" name="status" value="<?= $pb['status'] ?>">
                                        <input type="hidden" name="_section" value="pembeli">
                                        <button type="submit" class="btn-aksi <?= $aktif ? 'danger' : 'toggle-on' ?>"
                                            title="<?= $aktif ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                            <i class="fa-solid <?= $aktif ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                                        </button>
                                    </form>
                                    <!-- Hapus -->
                                    <form method="POST" style="display:inline"
                                        id="form-hapus-pembeli-<?= $pb['id_pembeli'] ?>">
                                        <input type="hidden" name="action" value="pembeli_hapus">
                                        <input type="hidden" name="id" value="<?= $pb['id_pembeli'] ?>">
                                        <input type="hidden" name="_section" value="pembeli">
                                        <button type="button" class="btn-aksi danger" title="Hapus"
                                            onclick="if(confirm('Hapus pembeli <?= htmlspecialchars($pb['nama']) ?>?')) document.getElementById('form-hapus-pembeli-<?= $pb['id_pembeli'] ?>').submit()">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="form-card">
        <h2><i class="fa-solid fa-user-plus" style="color:var(--green);margin-right:8px"></i>Tambah Akun Pembeli</h2>
        <form method="POST">
            <input type="hidden" name="action" value="pembeli_tambah">
            <input type="hidden" name="_section" value="pembeli">
            <div class="form-group">
                <label>Nama Pembeli</label>
                <input type="text" name="nama" placeholder="cth. Andi Setiawan" required autocomplete="off">
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="password-wrap">
                    <input type="password" name="password" id="inputPassPembeli" placeholder="Minimal 8 karakter"
                        required autocomplete="new-password">
                    <button type="button" class="btn-eye" onclick="togglePassPembeli()">
                        <i class="fa-solid fa-eye" id="eyeIconPembeli"></i>
                    </button>
                </div>
                <div class="form-note">Password akan di-hash untuk keamanan tambahan.</div>
            </div>
            <div class="form-group">
                <label>NISN / NUPTK</label>
                <input type="text" name="nisn_nuptk" placeholder="cth. 0012345678" autocomplete="off">
                <div class="form-note">NISN untuk murid, NUPTK untuk guru. Opsional.</div>
            </div>
            <div class="form-group">
                <label>Kategori</label>
                <select name="kategori" class="form-select" required>
                    <option value="">— Pilih kategori —</option>
                    <option value="murid">Murid</option>
                    <option value="guru">Guru</option>
                </select>
            </div>
            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-floppy-disk" style="margin-right:6px"></i>Simpan Akun
            </button>
        </form>
    </div>
</div>

<style>
    /* Badge kategori inline di nama */
    .badge-kategori {
        display: inline-block;
        font-size: 10px;
        font-weight: 600;
        padding: 2px 7px;
        border-radius: 99px;
        margin-left: 6px;
        vertical-align: middle;
    }

    .badge-murid {
        background: #e8f4fd;
        color: #2980b9;
    }

    .badge-guru {
        background: #fef3e2;
        color: #d68910;
    }
</style>

<script>
    function togglePassPembeli() {
        const input = document.getElementById('inputPassPembeli');
        const icon = document.getElementById('eyeIconPembeli');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }
</script>

<?php
// views/admin/actions/pembeli.php
// Handler semua POST action untuk section pembeli

if (!isset($_POST['action'])) return;

require_once __DIR__ . '/../../../config/database.php';

$action = $_POST['action'];

// ── Tambah Pembeli ───────────────────────────────────────────────────────────
if ($action === 'pembeli_tambah') {
    $nama        = trim($_POST['nama'] ?? '');
    $password    = $_POST['password'] ?? '';
    $nisn_nuptk  = trim($_POST['nisn_nuptk'] ?? '') ?: null;
    $kategori    = $_POST['kategori'] ?? '';

    if ($nama === '' || strlen($password) < 8 || !in_array($kategori, ['murid', 'guru'])) {
        $_SESSION['error'] = 'Nama, password (min 8 karakter), dan kategori wajib diisi.';
        header('Location: ?section=pembeli');
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        "INSERT INTO pembeli (nama, password, nisn_nuptk, kategori) VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$nama, $hash, $nisn_nuptk, $kategori]);

    $_SESSION['success'] = "Akun pembeli \"$nama\" berhasil ditambahkan.";
    header('Location: ?section=pembeli');
    exit;
}

// ── Toggle Status Aktif / Nonaktif ──────────────────────────────────────────
if ($action === 'pembeli_toggle') {
    $id        = (int)($_POST['id'] ?? 0);
    $statusNow = $_POST['status'] ?? 'aktif';
    $newStatus = $statusNow === 'aktif' ? 'nonaktif' : 'aktif';

    $stmt = $pdo->prepare("UPDATE pembeli SET status = ? WHERE id_pembeli = ?");
    $stmt->execute([$newStatus, $id]);

    $_SESSION['success'] = "Status pembeli berhasil diubah menjadi $newStatus.";
    header('Location: ?section=pembeli');
    exit;
}

// ── Reset Password ───────────────────────────────────────────────────────────
if ($action === 'pembeli_reset') {
    $id          = (int)($_POST['id'] ?? 0);
    $newPassword = 'pembeli123'; // password default setelah reset
    $hash        = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE pembeli SET password = ? WHERE id_pembeli = ?");
    $stmt->execute([$hash, $id]);

    $_SESSION['success'] = "Password pembeli berhasil direset ke: <strong>$newPassword</strong>";
    header('Location: ?section=pembeli');
    exit;
}

// ── Hapus Pembeli ────────────────────────────────────────────────────────────
if ($action === 'pembeli_hapus') {
    $id = (int)($_POST['id'] ?? 0);

    $stmt = $pdo->prepare("DELETE FROM pembeli WHERE id_pembeli = ?");
    $stmt->execute([$id]);

    $_SESSION['success'] = 'Akun pembeli berhasil dihapus.';
    header('Location: ?section=pembeli');
    exit;
}