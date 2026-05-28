<?php
// views/report.php — Laporan Kendala (shared: siswa, guru, penjual)
if (session_status() === PHP_SESSION_NONE)
    session_start();

// Deteksi koneksi
$db = $conn ?? $koneksi ?? null;
if (!$db) {
    // Coba load sendiri jika dipanggil langsung
    $possible_paths = [
        __DIR__ . '/../config/database.php',
        __DIR__ . '/../../config/database.php',
        __DIR__ . '/../../../config/database.php',
    ];
    foreach ($possible_paths as $p) {
        if (file_exists($p)) {
            require_once $p;
            break;
        }
    }
    $db = $conn ?? $koneksi ?? null;
}

$user_id = $_SESSION['user_id'] ?? '';
$user_nama = $_SESSION['user_nama'] ?? '';
$user_role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';

// Normalisasi role untuk disimpan ke DB
$role_db = match ($user_role) {
    'siswa', 'murid' => 'murid',
    'guru' => 'guru',
    'penjual' => 'penjual',
    default => 'murid'
};

$feedback = null;

// ── PROSES KIRIM LAPORAN ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'kirim_laporan') {
    $judul = mysqli_real_escape_string($db, trim($_POST['judul'] ?? ''));
    $deskripsi = mysqli_real_escape_string($db, trim($_POST['deskripsi'] ?? ''));
    $nama_esc = mysqli_real_escape_string($db, $user_nama);
    $id_esc = mysqli_real_escape_string($db, $user_id);

    if (empty($judul) || empty($deskripsi)) {
        $feedback = ['type' => 'error', 'msg' => 'Judul dan deskripsi wajib diisi.'];
    } else {
        catatLog($conn, 'Report','User'.$user_nama.' telah mengirim laporan');
        $ok = mysqli_query($db, "
            INSERT INTO laporan_kendala (user_role, user_id, user_nama, judul_kendala, deskripsi, status)
            VALUES ('$role_db', '$id_esc', '$nama_esc', '$judul', '$deskripsi', 'menunggu')
        ");
        $feedback = $ok
            ? ['type' => 'success', 'msg' => 'Laporan berhasil dikirim! Tim kami akan segera menanggapi.']
            : ['type' => 'error', 'msg' => 'Gagal mengirim laporan. Coba lagi.'];
    }
}

// ── AMBIL RIWAYAT LAPORAN USER INI ──
$riwayat = [];
if ($db && $user_id) {
    $uid = mysqli_real_escape_string($db, $user_id);
    $res = mysqli_query($db, "
        SELECT * FROM laporan_kendala
        WHERE user_id = '$uid' AND user_role = '$role_db'
        ORDER BY dibuat_pada DESC
        LIMIT 20
    ");
    if ($res)
        while ($r = mysqli_fetch_assoc($res))
            $riwayat[] = $r;
}
?>
<style>
    .report-wrap {
        max-width: 720px;
        margin: 0 auto;
        padding: 8px 0 32px;
        font-family: inherit;
    }

    .report-hero {
        background: linear-gradient(135deg, #1e3a2f 0%, #2d7a2d 100%);
        border-radius: 16px;
        padding: 28px 28px 24px;
        margin-bottom: 24px;
        color: #fff;
        position: relative;
        overflow: hidden;
    }

    .report-hero::before {
        content: '';
        position: absolute;
        right: -30px;
        top: -30px;
        width: 160px;
        height: 160px;
        background: rgba(255, 255, 255, .06);
        border-radius: 50%;
    }

    .report-hero::after {
        content: '';
        position: absolute;
        right: 40px;
        bottom: -40px;
        width: 100px;
        height: 100px;
        background: rgba(255, 255, 255, .04);
        border-radius: 50%;
    }

    .report-hero-icon {
        width: 48px;
        height: 48px;
        background: rgba(255, 255, 255, .15);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        margin-bottom: 14px;
    }

    .report-hero h2 {
        font-size: 20px;
        font-weight: 700;
        margin: 0 0 6px;
    }

    .report-hero p {
        font-size: 13px;
        opacity: .8;
        margin: 0;
        line-height: 1.5;
    }

    /* Feedback */
    .report-feedback {
        padding: 12px 16px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 500;
        margin-bottom: 18px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .report-feedback.success {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .report-feedback.error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    /* Form */
    .report-form-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 1px 4px rgba(0, 0, 0, .04);
    }

    .report-form-card h3 {
        font-size: 15px;
        font-weight: 700;
        color: #1f2937;
        margin: 0 0 18px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .report-form-card h3 i {
        color: #2d7a2d;
    }

    .report-field {
        margin-bottom: 16px;
    }

    .report-field label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: .4px;
    }

    .report-field input,
    .report-field textarea,
    .report-field select {
        width: 100%;
        padding: 10px 14px;
        border: 1.5px solid #e5e7eb;
        border-radius: 8px;
        font-size: 13px;
        font-family: inherit;
        color: #1f2937;
        background: #fafafa;
        outline: none;
        transition: border-color .2s;
        box-sizing: border-box;
    }

    .report-field input:focus,
    .report-field textarea:focus {
        border-color: #2d7a2d;
        background: #fff;
    }

    .report-field textarea {
        resize: vertical;
        min-height: 110px;
    }

    .report-field .field-hint {
        font-size: 11px;
        color: #9ca3af;
        margin-top: 4px;
    }

    .btn-kirim-laporan {
        width: 100%;
        padding: 12px;
        background: #2d7a2d;
        color: #fff;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        font-family: inherit;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: background .2s, transform .1s;
    }

    .btn-kirim-laporan:hover {
        background: #215921;
    }

    .btn-kirim-laporan:active {
        transform: scale(.98);
    }

    /* Riwayat */
    .report-riwayat-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 1px 4px rgba(0, 0, 0, .04);
    }

    .report-riwayat-header {
        padding: 16px 20px;
        border-bottom: 1px solid #f3f4f6;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .report-riwayat-header h3 {
        font-size: 15px;
        font-weight: 700;
        color: #1f2937;
        margin: 0;
    }

    .report-riwayat-header i {
        color: #2d7a2d;
    }

    .report-empty {
        padding: 36px 20px;
        text-align: center;
        color: #9ca3af;
        font-size: 13px;
    }

    .report-empty i {
        font-size: 32px;
        display: block;
        margin-bottom: 10px;
        color: #d1d5db;
    }

    .report-item {
        padding: 16px 20px;
        border-bottom: 1px solid #f9fafb;
        transition: background .15s;
    }

    .report-item:last-child {
        border-bottom: none;
    }

    .report-item:hover {
        background: #fafafa;
    }

    .report-item-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 6px;
    }

    .report-item-judul {
        font-size: 13px;
        font-weight: 600;
        color: #1f2937;
        flex: 1;
    }

    .report-status {
        font-size: 11px;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 20px;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .report-status.menunggu {
        background: #fef3c7;
        color: #92400e;
    }

    .report-status.proses {
        background: #dbeafe;
        color: #1e40af;
    }

    .report-status.selesai {
        background: #dcfce7;
        color: #166534;
    }

    .report-item-desc {
        font-size: 12px;
        color: #6b7280;
        line-height: 1.5;
        margin-bottom: 6px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .report-item-meta {
        font-size: 11px;
        color: #9ca3af;
        display: flex;
        align-items: center;
        gap: 4px;
    }
</style>

<div class="report-wrap">

    <!-- Hero -->
    <div class="report-hero">
        <div class="report-hero-icon">
            <i class="fa-solid fa-flag"></i>
        </div>
        <h2>Laporkan Kendala</h2>
        <p>Ada masalah atau pertanyaan? Kirim laporan dan tim admin akan segera menanggapi.</p>
    </div>

    <!-- Feedback -->
    <?php if ($feedback): ?>
        <div class="report-feedback <?= $feedback['type'] ?>">
            <i class="fa-solid <?= $feedback['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
            <?= htmlspecialchars($feedback['msg']) ?>
        </div>
    <?php endif; ?>

    <!-- Form -->
    <div class="report-form-card">
        <h3><i class="fa-solid fa-pen-to-square"></i> Kirim Laporan Baru</h3>
        <form method="POST">
            <input type="hidden" name="action" value="kirim_laporan">
            <div class="report-field">
                <label>Judul Kendala</label>
                <input type="text" name="judul" placeholder="Contoh: Tidak bisa melakukan pemesanan" required
                    maxlength="150">
            </div>
            <div class="report-field">
                <label>Deskripsi</label>
                <textarea name="deskripsi" placeholder="Jelaskan masalah yang kamu alami secara detail..."
                    required></textarea>
                <div class="field-hint">Semakin detail deskripsinya, semakin cepat tim kami bisa membantu.</div>
            </div>
            <button type="submit" class="btn-kirim-laporan">
                <i class="fa-solid fa-paper-plane"></i> Kirim Laporan
            </button>
        </form>
    </div>

    <!-- Riwayat -->
    <div class="report-riwayat-card">
        <div class="report-riwayat-header">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <h3>Riwayat Laporan Saya</h3>
        </div>
        <?php if (empty($riwayat)): ?>
            <div class="report-empty">
                <i class="fa-solid fa-inbox"></i>
                Belum ada laporan yang pernah dikirim.
            </div>
        <?php else: ?>
            <?php foreach ($riwayat as $r):
                $status_label = match ($r['status']) {
                    'proses' => 'Diproses',
                    'selesai' => 'Selesai',
                    default => 'Menunggu'
                };
                ?>
                <div class="report-item">
                    <div class="report-item-top">
                        <div class="report-item-judul"><?= htmlspecialchars($r['judul_kendala']) ?></div>
                        <span class="report-status <?= $r['status'] ?>"><?= $status_label ?></span>
                    </div>
                    <div class="report-item-desc"><?= htmlspecialchars($r['deskripsi']) ?></div>
                    <div class="report-item-meta">
                        <i class="fa-regular fa-clock"></i>
                        <?= date('d M Y, H:i', strtotime($r['dibuat_pada'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>