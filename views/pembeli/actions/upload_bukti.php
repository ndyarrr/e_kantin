<?php
// views/pembeli/actions/upload_bukti.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config/database.php';

$db = $koneksi ?? $conn ?? null;
if (!$db) {
    echo json_encode(["status" => "error", "message" => "Koneksi database tidak tersedia."]);
    exit;
}

// ── 1. VALIDASI SESI PEMBELI ──
$user_id   = $_SESSION['user_id']   ?? '';
$user_role = $_SESSION['user_role'] ?? '';
$user_nama = $_SESSION['user_nama'] ?? 'Pembeli';

if (empty($user_id) || !in_array($user_role, ['siswa', 'guru'])) {
    echo json_encode(["status" => "error", "message" => "Sesi habis. Silakan login kembali."]);
    exit;
}

// ── 2. VALIDASI REQUEST DATA ──
$ids_raw = $_POST['ids'] ?? '';
if (empty($ids_raw)) {
    echo json_encode(["status" => "error", "message" => "ID pesanan tidak valid."]);
    exit;
}

// Dekode ID pesanan (bisa berupa JSON array string atau ID tunggal)
$pesanan_ids = json_decode($ids_raw, true);
if (!is_array($pesanan_ids)) {
    $pesanan_ids = array_filter(array_map('intval', explode(',', $ids_raw)));
}
if (empty($pesanan_ids)) {
    echo json_encode(["status" => "error", "message" => "ID pesanan kosong."]);
    exit;
}

// ── 3. VALIDASI & PROSES UNGGAHAN BERKAS ──
if (!isset($_FILES['bukti_foto']) || $_FILES['bukti_foto']['error'] !== UPLOAD_ERR_OK) {
    $err_code = $_FILES['bukti_foto']['error'] ?? UPLOAD_ERR_NO_FILE;
    echo json_encode(["status" => "error", "message" => "Gagal menerima berkas gambar. Error Code: " . $err_code]);
    exit;
}

$file_tmp  = $_FILES['bukti_foto']['tmp_name'];
$file_size = $_FILES['bukti_foto']['size'];
$file_name = $_FILES['bukti_foto']['name'];

// Batasi ukuran file maksimal 5MB
if ($file_size > 5 * 1024 * 1024) {
    echo json_encode(["status" => "error", "message" => "Ukuran gambar terlalu besar. Maksimal 5MB."]);
    exit;
}

// Validasi tipe berkas (MIME Type)
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file_tmp);
finfo_close($finfo);

if (!in_array($mime_type, $allowed_types)) {
    echo json_encode(["status" => "error", "message" => "Format berkas tidak didukung. Hanya gambar (JPG, JPEG, PNG, WEBP)."]);
    exit;
}

// Dapatkan ekstensi berkas asli
$ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
    $ext = 'jpg'; // Fallback
}

// Generate nama file yang unik
$new_filename = 'bukti_' . $user_id . '_' . time() . '_' . uniqid() . '.' . $ext;
$target_dir = __DIR__ . '/../../../assets/img/bukti_bayar/';

// Pastikan folder tujuan ada
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true);
}

$target_file = $target_dir . $new_filename;

if (!move_uploaded_file($file_tmp, $target_file)) {
    echo json_encode(["status" => "error", "message" => "Gagal menyimpan berkas di server."]);
    exit;
}

// ── 4. UPDATE DATABASE & SINKRONISASI CHAT ──
mysqli_begin_transaction($db);
try {
    // Bangun ID Pengirim untuk chat
    $id_pengirim = ($user_role === 'siswa') ? 'murid_' . $user_id : 'guru_' . $user_id;

    foreach ($pesanan_ids as $id_pesanan) {
        $id_pesanan = (int) $id_pesanan;
        if ($id_pesanan <= 0) continue;

        // Ambil info pesanan & detail kantin
        $q_pesanan = mysqli_query($db, "
            SELECT p.total_harga, p.id_toko, t.nama_toko 
            FROM pesanan p
            JOIN toko t ON p.id_toko = t.id_toko
            WHERE p.id_pesanan = $id_pesanan
            LIMIT 1
        ");
        $pesanan_info = mysqli_fetch_assoc($q_pesanan);
        if (!$pesanan_info) {
            continue; // Lewati jika pesanan tidak ditemukan
        }

        $id_toko    = (int) $pesanan_info['id_toko'];
        $nama_toko  = $pesanan_info['nama_toko'];
        $total_harga = $pesanan_info['total_harga'];
        $id_penerima = 'toko_' . $id_toko;

        // A. Perbarui tabel pembayaran
        $stmt_pay = mysqli_prepare($db, "
            UPDATE pembayaran 
            SET bukti_foto = ?, dibayar_pada = NOW() 
            WHERE id_pesanan = ?
        ");
        if ($stmt_pay) {
            mysqli_stmt_bind_param($stmt_pay, 'si', $new_filename, $id_pesanan);
            mysqli_stmt_execute($stmt_pay);
            mysqli_stmt_close($stmt_pay);
        }

        // B. Catat log sistem
        $log_desc = "Pembeli $user_nama mengunggah bukti pembayaran untuk Pesanan #$id_pesanan senilai Rp " . number_format($total_harga, 0, ',', '.');
        $stmt_log = mysqli_prepare($db, "INSERT INTO log_sistem (aksi, keterangan, dibuat_pada, user_role, user_id, user_nama) VALUES ('Upload Bukti QRIS', ?, NOW(), ?, ?, ?)");
        if ($stmt_log) {
            mysqli_stmt_bind_param($stmt_log, 'ssss', $log_desc, $user_role, $user_id, $user_nama);
            mysqli_stmt_execute($stmt_log);
            mysqli_stmt_close($stmt_log);
        }

        // C. Buat HTML Chat Auto-Reply dengan Gambar Bukti Transfer
        $total_fmt = number_format($total_harga, 0, ',', '.');
        $card_html = '[AUTO_REPLY_ORDER]
        <div style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;max-width:320px; box-sizing:border-box;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                <div style="background:linear-gradient(135deg,#16a34a,#059669);border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' width=\'18\' height=\'18\' fill=\'white\'><path d=\'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z\'/></svg>
                </div>
                <div>
                    <div style="font-weight:800;font-size:13px;color:#0f172a;">Bukti Pembayaran Terkirim!</div>
                    <div style="font-size:11px;color:#64748b;">Pesanan #' . $id_pesanan . '</div>
                </div>
            </div>
            
            <div style="font-size:12.5px;color:#334155;line-height:1.5;margin-bottom:10px;">
                Halo <strong>' . htmlspecialchars($nama_toko) . '</strong>, saya telah melakukan transfer dan mengunggah bukti pembayaran untuk Pesanan #' . $id_pesanan . '.
            </div>

            <!-- Sematan Gambar Bukti Transfer -->
            <a href="{BASE_PATH}assets/img/bukti_bayar/' . $new_filename . '" target="_blank" style="display:block; text-decoration:none;">
                <div style="position:relative; border-radius:12px; overflow:hidden; border:1.5px solid #e2e8f0; background:#f8fafc; padding:3px; box-sizing:border-box; margin-bottom:10px; transition:border-color 0.2s;">
                    <img src="{BASE_PATH}assets/img/bukti_bayar/' . $new_filename . '" style="width:100%; height:auto; max-height:180px; object-fit:cover; border-radius:8px; display:block;" alt="Bukti Transfer">
                    <div style="position:absolute; bottom:6px; right:6px; background:rgba(15,23,42,0.7); color:#ffffff; font-size:10px; font-weight:700; padding:4px 8px; border-radius:6px; backdrop-filter:blur(4px); display:flex; align-items:center; gap:4px;">
                        <svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' width=\'10\' height=\'10\' fill=\'white\'><path d=\'M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z\'/></svg>
                        Ketuk untuk memperbesar
                    </div>
                </div>
            </a>

            <div style="padding:10px 12px;background:#f0fdf4;border-radius:10px;display:flex;justify-content:space-between;align-items:center;border:1px solid #bbf7d0;">
                <span style="font-size:11.5px;font-weight:600;color:#166534;">Total Pembayaran</span>
                <span style="font-size:13.5px;font-weight:800;color:#16a34a;">Rp ' . $total_fmt . '</span>
            </div>
        </div>';

        // D. Simpan pesan chat otomatis di database
        $msg_escaped = mysqli_real_escape_string($db, $card_html);
        mysqli_query($db, "
            INSERT INTO pesan_chat (id_pengirim, id_penerima, isi_pesan, waktu_kirim, sudah_dibaca)
            VALUES ('$id_pengirim', '$id_penerima', '$msg_escaped', NOW(), 0)
        ");
    }

    mysqli_commit($db);
    echo json_encode(["status" => "success", "message" => "Bukti pembayaran berhasil diunggah dan dikirim ke Chat Kantin!"]);

} catch (Exception $e) {
    mysqli_rollback($db);
    echo json_encode(["status" => "error", "message" => "Gagal memproses unggahan bukti: " . $e->getMessage()]);
}
