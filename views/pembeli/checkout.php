<?php
// views/pembeli/checkout.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

// Proteksi agar pembeli yang login saja yang bisa akses
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: ../../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_nama = $_SESSION['user_nama'];

// Ambil avatar pembeli
$avatar_file = $_SESSION['user_foto'] ?? '';
$has_avatar  = !empty($avatar_file) && file_exists(__DIR__ . '/../../assets/img/' . $avatar_file);
$avatar_path = $has_avatar ? '../../assets/img/' . $avatar_file : '';

// ── PROSES POST: BUAT PESANAN BARU ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'buat_pesanan') {
    header('Content-Type: application/json');
    
    // Jalankan transaksi database
    mysqli_begin_transaction($conn);
    try {
        $cart_data = json_decode($_POST['cart_data'], true);
        $tipe_pengiriman = $_POST['tipe_pengiriman'] ?? 'di_ambil'; // 'di_antar' atau 'di_ambil'
        $kode_promo = $_POST['kode_promo'] ?? '';
        
        if (empty($cart_data)) {
            throw new Exception("Keranjang kosong.");
        }

        // Kelompokkan item berdasarkan id_toko
        $pesanan_per_toko = [];
        foreach ($cart_data as $item) {
            if (isset($item['selected']) && $item['selected'] === false) {
                continue;
            }
            $id_toko = (int) $item['id_toko'];
            $pesanan_per_toko[$id_toko][] = $item;
        }

        if (empty($pesanan_per_toko)) {
            throw new Exception("Tidak ada item terpilih.");
        }

        $id_pesanan_dibuat = [];

        foreach ($pesanan_per_toko as $id_toko => $items) {
            // Validasi status buka/tutup toko
            $cek_toko = mysqli_query($conn, "SELECT nama_toko, status FROM toko WHERE id_toko = $id_toko LIMIT 1");
            if ($cek_toko && mysqli_num_rows($cek_toko) > 0) {
                $r_toko = mysqli_fetch_assoc($cek_toko);
                if (strtolower($r_toko['status'] ?? '') !== 'buka') {
                    throw new Exception("Kantin '" . $r_toko['nama_toko'] . "' sedang tutup. Tidak dapat melakukan pesanan saat ini.");
                }
            } else {
                throw new Exception("Kantin tidak ditemukan.");
            }

            // Hitung total harga
            $subtotal = 0;
            foreach ($items as $item) {
                $subtotal += $item['harga'] * $item['jumlah'];
            }
            
            // Terapkan diskon jika ada kode promo KANTINJOSS25
            $biaya_admin = 500;
            $diskon = 0;
            if ($kode_promo === 'KANTINJOSS25') {
                $diskon = round($subtotal * 0.25); // Diskon 25%
            }
            
            $total_pembayaran = $subtotal + $biaya_admin - $diskon;
            if ($total_pembayaran < 0) $total_pembayaran = 0;

            // Tentukan kolom pembeli berdasarkan role (nisn_pembeli atau nuptk_pembeli)
            $col_pembeli = ($user_role === 'siswa') ? 'nisn_pembeli' : 'nuptk_pembeli';
            
            // Buat pesanan baru
            $stmt = mysqli_prepare($conn, "INSERT INTO pesanan (id_toko, status, waktu_pesan, total_harga, $col_pembeli) VALUES (?, 'menunggu', NOW(), ?, ?)");
            mysqli_stmt_bind_param($stmt, 'iis', $id_toko, $total_pembayaran, $user_id);
            mysqli_stmt_execute($stmt);
            $id_pesanan = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            $id_pesanan_dibuat[] = $id_pesanan;

            // Catat detail_pesanan
            foreach ($items as $item) {
                $id_menu = (int) $item['id_menu'];
                $jumlah = (int) $item['jumlah'];
                
                // Validasi ketersediaan stok
                $cek_stok = mysqli_query($conn, "SELECT nama_menu, stok, tersedia FROM menu WHERE id_menu = $id_menu LIMIT 1");
                if (mysqli_num_rows($cek_stok) > 0) {
                    $r_menu = mysqli_fetch_assoc($cek_stok);
                    if (!$r_menu['tersedia']) {
                        throw new Exception("Menu '" . $r_menu['nama_menu'] . "' sedang tidak tersedia.");
                    }
                    if ($jumlah > $r_menu['stok']) {
                        throw new Exception("Stok '" . $r_menu['nama_menu'] . "' tidak mencukupi (Tersedia: " . $r_menu['stok'] . ", Diminta: " . $jumlah . ").");
                    }
                } else {
                    throw new Exception("Menu tidak ditemukan.");
                }

                $harga_satuan = (int) $item['harga'];
                $catatan = isset($item['catatan']) ? trim($item['catatan']) : '';
                
                $stmt_detail = mysqli_prepare($conn, "INSERT INTO detail_pesanan (id_pesanan, id_menu, jumlah, harga_satuan, catatan) VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt_detail, 'iiiis', $id_pesanan, $id_menu, $jumlah, $harga_satuan, $catatan);
                mysqli_stmt_execute($stmt_detail);
                mysqli_stmt_close($stmt_detail);
                
                // Kurangi stok menu di database
                mysqli_query($conn, "UPDATE menu SET stok = GREATEST(stok - $jumlah, 0) WHERE id_menu = $id_menu");
            }

            // Catat pembayaran (default belum_bayar tunai)
            $stmt_pay = mysqli_prepare($conn, "INSERT INTO pembayaran (id_pesanan, jumlah_bayar, metode, status) VALUES (?, ?, 'tunai', 'belum_bayar')");
            mysqli_stmt_bind_param($stmt_pay, 'ii', $id_pesanan, $total_pembayaran);
            mysqli_stmt_execute($stmt_pay);
            mysqli_stmt_close($stmt_pay);

            // Catat log sistem
            catatLog($conn, 'Buat Pesanan', "Pembeli $user_nama membuat pesanan #$id_pesanan senilai Rp $total_pembayaran");

            // ── AUTO-REPLY CHAT dari kantin ke pembeli ──
            // Kumpulkan detail item pesanan lengkap (dengan foto + kategori)
            $chat_items = [];
            foreach ($items as $item) {
                $mid = (int) $item['id_menu'];
                $r_detail = mysqli_fetch_assoc(mysqli_query($conn,
                    "SELECT nama_menu, foto_menu, kategori, harga FROM menu WHERE id_menu = $mid LIMIT 1"
                ));
                if ($r_detail) {
                    $chat_items[] = [
                        'nama'     => $r_detail['nama_menu'],
                        'foto'     => $r_detail['foto_menu'],
                        'kategori' => strtolower($r_detail['kategori'] ?? 'makanan'),
                        'jumlah'   => (int) $item['jumlah'],
                        'harga'    => (int) $item['harga'],
                    ];
                }
            }

            // Bangun HTML kartu order untuk dikirim ke chat
            $items_html = '';
            foreach ($chat_items as $ci) {
                $subtotal_item = number_format($ci['harga'] * $ci['jumlah'], 0, ',', '.');
                $harga_fmt     = number_format($ci['harga'], 0, ',', '.');

                // Tentukan SVG fallback berdasarkan kategori
                if ($ci['kategori'] === 'minuman') {
                    $svg_path  = 'M3 2l2.01 18.23C5.13 21.23 5.97 22 7 22h10c1.03 0 1.87-.77 1.99-1.77L21 2H3zm9 17c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm1-9H8V8h5v2z';
                    $svg_color = '#1890ff';
                    $svg_bg    = '#eff6ff';
                } elseif ($ci['kategori'] === 'snack') {
                    $svg_path  = 'M18.06 22.99h1.66c.84 0 1.53-.64 1.63-1.46L23 5.05h-5V1h-1.97v4.05h-4.97l.3 2.34c1.71.47 3.31 1.32 4.27 2.26 1.44 1.42 2.43 2.89 2.43 5.29v8.05zM1 21.99V21h15.03v.99c0 .55-.45 1-1.01 1H2.01c-.56 0-1.01-.45-1.01-1zm15.03-7c0-4.5-6.72-5-8.99-5-2.28 0-9.03.5-9.03 5h18.02z';
                    $svg_color = '#9254de';
                    $svg_bg    = '#f5f3ff';
                } else {
                    $svg_path  = 'M11 9H9V2H7v7H5V2H3v7c0 2.12 1.66 3.84 3.75 3.97V22h2.5v-9.03C11.34 12.84 13 11.12 13 9V2h-2v7zm5-3v8h2.5v8H21V2c-2.76 0-5 2.24-5 4z';
                    $svg_color = '#ff7a45';
                    $svg_bg    = '#fff2e8';
                }

                // Gambar atau SVG fallback
                if (!empty($ci['foto'])) {
                    $img_html = '<img src="{BASE_PATH}assets/img/menu/' . htmlspecialchars($ci['foto']) . '" 
                        onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\';" 
                        style="width:56px;height:56px;object-fit:cover;border-radius:10px;flex-shrink:0;">
                        <div style="display:none;width:56px;height:56px;border-radius:10px;background:' . $svg_bg . ';align-items:center;justify-content:center;flex-shrink:0;">
                            <svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' width=\'24\' height=\'24\' fill=\'' . $svg_color . '\'><path d=\'' . $svg_path . '\'/></svg>
                        </div>';
                } else {
                    $img_html = '<div style="width:56px;height:56px;border-radius:10px;background:' . $svg_bg . ';display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' width=\'24\' height=\'24\' fill=\'' . $svg_color . '\'><path d=\'' . $svg_path . '\'/></svg>
                        </div>';
                }

                $items_html .= '
                <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f1f5f9;">
                    ' . $img_html . '
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:700;font-size:13px;color:#0f172a;margin-bottom:2px;">' . htmlspecialchars($ci['nama']) . '</div>
                        <div style="font-size:12px;color:#64748b;">Rp ' . $harga_fmt . ' &times; ' . $ci['jumlah'] . '</div>
                    </div>
                    <div style="font-weight:700;font-size:13px;color:#16a34a;flex-shrink:0;">Rp ' . $subtotal_item . '</div>
                </div>';
            }

            $total_fmt = number_format($total_pembayaran, 0, ',', '.');
            $auto_msg  = '[AUTO_REPLY_ORDER]
            <div style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;max-width:320px;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                    <div style="background:linear-gradient(135deg,#ff9900,#ff5500);border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' width=\'18\' height=\'18\' fill=\'white\'><path d=\'M11 9H9V2H7v7H5V2H3v7c0 2.12 1.66 3.84 3.75 3.97V22h2.5v-9.03C11.34 12.84 13 11.12 13 9V2h-2v7zm5-3v8h2.5v8H21V2c-2.76 0-5 2.24-5 4z\'/></svg>
                    </div>
                    <div>
                        <div style="font-weight:800;font-size:13px;color:#0f172a;">Pesanan #' . $id_pesanan . ' Berhasil Dikirim!</div>
                        <div style="font-size:11px;color:#64748b;">Menunggu konfirmasi pihak kantin...</div>
                    </div>
                </div>
                <div>' . $items_html . '</div>
                <div style="margin-top:12px;padding:10px 12px;background:#f0fdf4;border-radius:10px;display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:12px;font-weight:600;color:#374151;">Total Pembayaran</span>
                    <span style="font-size:14px;font-weight:800;color:#16a34a;">Rp ' . $total_fmt . '</span>
                </div>
                <div style="margin-top:10px;font-size:11px;color:#94a3b8;text-align:center;">Menunggu persetujuan/konfirmasi pihak kantin ⏳</div>
            </div>';

            // Tentukan prefix ID pembeli
            $prefix_pembeli = ($user_role === 'siswa') ? 'murid_' : 'guru_';
            $id_pembeli_chat = $prefix_pembeli . $user_id;
            $id_toko_chat    = 'toko_' . $id_toko;

            $msg_escaped = mysqli_real_escape_string($conn, $auto_msg);
            mysqli_query($conn, "INSERT INTO pesan_chat (id_pengirim, id_penerima, isi_pesan, waktu_kirim, sudah_dibaca)
                                 VALUES ('$id_toko_chat', '$id_pembeli_chat', '$msg_escaped', NOW(), 0)");

        }
        mysqli_commit($conn);
        echo json_encode(['status' => 'success', 'message' => 'Pesanan berhasil dibuat.', 'ids' => $id_pesanan_dibuat]);
        exit;

    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// Ambil rekomendasi produk upselling secara acak (3 menu yang tersedia, murah)
$recommended_items = [];
$q_reco = mysqli_query($conn, "SELECT m.*, t.nama_toko FROM menu m 
                               JOIN toko t ON m.id_toko = t.id_toko 
                               WHERE m.tersedia = 1 AND m.stok > 0 AND m.deleted_at IS NULL 
                               ORDER BY m.harga ASC, RAND() LIMIT 3");
if ($q_reco) {
    while ($r = mysqli_fetch_assoc($q_reco)) {
        $recommended_items[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Pesanan - E-Kantin</title>
    <link rel="stylesheet" href="../../assets/css/pembeli.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Override header sub-nav to match the mockup */
        .nav-menu-container {
            box-shadow: none !important;
            background: transparent !important;
            padding: 0 !important;
            gap: 16px !important;
        }
        .nav-item {
            padding: 6px 14px !important;
            font-size: 13px !important;
            gap: 0 !important;
            background: transparent !important;
            color: #64748b !important;
            box-shadow: none !important;
            border-radius: 20px !important;
        }
        .nav-item i {
            display: none !important;
        }
        .nav-item span {
            display: inline !important;
        }
        .nav-item.active {
            background-color: #5cb85c !important;
            color: #ffffff !important;
            box-shadow: none !important;
        }

        .checkout-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 16px;
            padding-bottom: 160px; /* Jarak agar tidak ketutup sticky bottom bar yang sekarang bertumpuk */
        }
        .checkout-section-title {
            font-size: 17px;
            font-weight: 800;
            color: #0f172a;
            margin: 22px 0 12px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .checkout-card {
            background: #ffffff;
            border: 1px solid #f1f5f9;
            border-radius: 20px;
            padding: 18px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.02);
            margin-bottom: 16px;
            box-sizing: border-box;
        }
        .checkout-item-row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 16px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .checkout-item-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .checkout-item-row:first-child {
            padding-top: 0;
        }
        .checkout-item-info {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .checkout-item-title {
            font-size: 15px;
            font-weight: 800;
            color: #1e293b;
            margin: 0 0 4px 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .checkout-item-meta {
            font-size: 12px;
            color: #64748b;
            margin: 3px 0;
            font-weight: 500;
        }
        .checkout-item-price {
            font-size: 14px;
            font-weight: 700;
            color: #64748b;
            margin-top: 6px;
        }
        .checkout-item-img-wrap {
            width: 75px;
            height: 75px;
            border-radius: 14px;
            overflow: hidden;
            flex-shrink: 0;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .checkout-item-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .btn-edit-note {
            background: #f1f5f9;
            border: none;
            border-radius: 20px;
            padding: 5px 14px;
            font-size: 11px;
            font-weight: 700;
            color: #475569;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .btn-edit-note:hover {
            background: #e2e8f0;
            color: #1e293b;
        }
        
        /* Circular Quantity Buttons */
        .qty-btn-circle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            border: 1.5px solid #5cb85c;
            color: #5cb85c;
            background: transparent;
            cursor: pointer;
            font-size: 10px;
            padding: 0;
            transition: all 0.2s ease;
        }
        .qty-btn-circle:hover {
            background-color: #5cb85c;
            color: #ffffff;
        }
        .checkout-qty-val {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            min-width: 14px;
            text-align: center;
        }

        /* Rekomendasi "Ada lagi yang mau dibeli?" */
        .reco-grid {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            padding: 6px 4px 12px 4px;
            scroll-snap-type: x mandatory;
            scrollbar-width: none;
        }
        .reco-grid::-webkit-scrollbar {
            display: none;
        }
        .reco-item {
            flex-shrink: 0;
            width: 86px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            scroll-snap-align: start;
        }
        .reco-img-wrap {
            width: 66px;
            height: 66px;
            border-radius: 50%;
            position: relative;
            background: #ffffff;
            border: 1.5px dashed #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.03);
        }
        .reco-img-wrap img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        .reco-img-wrap .btn-add-reco {
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: #5cb85c;
            border: 2px solid #ffffff;
            color: #ffffff;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(92,184,92,0.3);
            transition: transform 0.2s;
            padding: 0;
        }
        .reco-img-wrap .btn-add-reco:hover {
            transform: scale(1.15);
        }
        .reco-name {
            font-size: 11px;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 2px 0;
            max-width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .reco-price {
            font-size: 11px;
            font-weight: 800;
            color: #64748b;
        }

        /* Delivery Options - Clean style without cell borders */
        .delivery-options-card {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 0;
        }
        .delivery-option-label {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            padding: 4px 0;
            transition: opacity 0.2s ease;
        }
        .delivery-option-label:hover {
            opacity: 0.8;
        }
        .delivery-option-radio {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 1.5px solid #cbd5e1;
            outline: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            cursor: pointer;
            margin: 0;
        }
        .delivery-option-radio:checked {
            border-color: #5cb85c;
            background: #5cb85c;
        }
        .delivery-option-radio:checked::after {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #ffffff;
        }
        .delivery-option-text {
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
        }

        /* Payment Summary */
        .summary-table {
            width: 100%;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 13px;
        }
        .summary-row .label {
            color: #64748b;
            font-weight: 600;
        }
        .summary-row .val {
            color: #1e293b;
            font-weight: 700;
        }
        .summary-row.discount .label {
            color: #5cb85c;
        }
        .summary-row.discount .val {
            color: #5cb85c;
        }
        .summary-row.total {
            border-top: 1.5px solid #f1f5f9;
            margin-top: 8px;
            padding-top: 12px;
            font-size: 14px;
        }
        .summary-row.total .label {
            color: #0f172a;
            font-weight: 800;
        }
        .summary-row.total .val {
            color: #0f172a;
            font-weight: 900;
        }

        /* Promo section - Styled to match mockup colors */
        .promo-apply-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 18px;
            background: #e8f5e9;
            border: 1px solid #c8e6c9;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 12px;
        }
        .promo-apply-box i {
            color: #4caf50;
            font-size: 20px;
        }
        .promo-link-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 18px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 700;
            color: #475569;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 24px;
        }
        .promo-link-row:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }
        .promo-link-row i {
            color: #4caf50; /* Green arrow */
            font-size: 16px;
        }

        /* Sticky bottom bar - Stacked vertically */
        .checkout-bottom-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: #eaeaea; /* Light gray background to match mockup */
            box-shadow: 0 -8px 30px rgba(0, 0, 0, 0.05);
            padding: 16px 20px;
            box-sizing: border-box;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .checkout-payment-row {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .checkout-wallet-icon {
            color: #5cb85c; /* Green wallet icon */
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .checkout-payment-text {
            display: flex;
            flex-direction: column;
            line-height: 1.3;
        }
        .checkout-payment-method {
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
        }
        .checkout-payment-amount {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
        }
        .btn-submit-order {
            width: 100%;
            padding: 14px;
            background: #5cb85c; /* Green button */
            color: #ffffff;
            border: none;
            border-radius: 14px;
            font-weight: 800;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 4px 12px rgba(92, 184, 92, 0.2);
            text-align: center;
        }
        .btn-submit-order:hover {
            background: #4cae4c;
            transform: translateY(-1px);
        }
        .btn-submit-order:active {
            transform: translateY(0);
        }
    </style>
</head>

<body>

    <!-- ── TOP HEADER ── -->
    <header class="main-header">
        <div class="header-inner">
            <div class="top-bar">
                <div class="logo-area">
                    <img src="../../assets/img/logo-esemkita.png" class="school-logo" alt="Logo">
                    <span class="brand-name">E-Kantin</span>
                </div>
                
                <div class="search-container">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" placeholder="Cari..." readonly style="background: #f1f5f9; cursor: not-allowed;">
                </div>
                
                <div class="header-icons">
                    <div class="dropdown-wrapper">
                        <div class="icon-badge" onclick="window.location.href='index.php'">
                            <i class="fa-regular fa-bell"></i>
                            <span class="badge" id="notifBadge" style="display: none;">0</span>
                        </div>
                    </div>

                    <div class="dropdown-wrapper">
                        <div class="icon-badge" onclick="window.location.href='index.php'">
                            <i class="fa-solid fa-cart-shopping"></i>
                            <span class="badge" id="headerCartBadge">0</span>
                        </div>
                    </div>

                    <div class="dropdown-wrapper">
                        <?php if ($has_avatar): ?>
                            <img src="<?= $avatar_path; ?>" class="blank-avatar" alt="Profil">
                        <?php else: ?>
                            <div class="avatar-initials size-sm">
                                <?= strtoupper(substr($user_nama, 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <nav class="nav-menu-wrapper">
                <div class="nav-menu-container">
                    <a href="index.php?tab=beranda" class="nav-item">
                        <i class="fa-solid fa-house"></i> <span>Beranda</span>
                    </a>
                    <a href="index.php?tab=pesanan" class="nav-item active">
                        <i class="fa-solid fa-receipt"></i> <span>Pesanan</span>
                    </a>
                    <a href="index.php?tab=favorit" class="nav-item">
                        <i class="fa-solid fa-heart"></i> <span>Favorit</span>
                    </a>
                    <a href="index.php?tab=kantin" class="nav-item">
                        <i class="fa-solid fa-store"></i> <span>Kantin</span>
                    </a>
                    <a href="index.php?tab=chat" class="nav-item">
                        <i class="fa-solid fa-comment-dots"></i> <span>Chat</span>
                    </a>
                </div>
            </nav>
        </div>
    </header>

    <main class="content-container">
        <div class="checkout-container">
            <!-- Back navigation link -->
            <a href="index.php" style="text-decoration:none; color:#1e293b; display:inline-flex; align-items:center; gap:8px; margin-bottom:18px; font-weight:800; font-size:14px;">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Keranjang
            </a>

            <!-- Section 1: Ordered Items -->
            <h2 class="checkout-section-title">Total Pesanan</h2>
            <div class="checkout-card" id="checkoutItemsContainer">
                <!-- Dynamically populated by JS from localStorage -->
            </div>

            <!-- Section 2: Upselling Recommendations -->
            <h2 class="checkout-section-title">Ada lagi yang mau dibeli?</h2>
            <div class="reco-grid">
                <?php foreach ($recommended_items as $item): 
                    $foto = $item['foto_menu'] ?? '';
                    $img_src = !empty($foto) && file_exists(__DIR__ . '/../../assets/img/menu/' . $foto) ? '../../assets/img/menu/' . $foto : '';
                ?>
                    <div class="reco-item">
                        <div class="reco-img-wrap">
                            <?php if ($img_src): ?>
                                <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($item['nama_menu']) ?>">
                            <?php else: ?>
                                <i class="fa-solid fa-utensils" style="color: #cbd5e1; font-size: 20px;"></i>
                            <?php endif; ?>
                            <button class="btn-add-reco" onclick="addRecommendationToCart(<?= $item['id_menu']; ?>, '<?= htmlspecialchars(addslashes($item['nama_menu']), ENT_QUOTES); ?>', <?= $item['harga']; ?>, '<?= htmlspecialchars(addslashes($foto), ENT_QUOTES); ?>', '<?= htmlspecialchars(addslashes($item['nama_toko']), ENT_QUOTES); ?>', <?= $item['id_toko']; ?>, <?= (int)$item['stok']; ?>)">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>
                        <div class="reco-name"><?= htmlspecialchars($item['nama_menu']); ?></div>
                        <div class="reco-price">Rp.<?= number_format($item['harga'], 0, ',', '.'); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Section 3: Delivery Options -->
            <div class="checkout-card">
                <div class="delivery-options-card">
                    <label class="delivery-option-label">
                        <input type="radio" name="delivery_type" value="di_antar" class="delivery-option-radio" checked>
                        <span class="delivery-option-text">di antar</span>
                    </label>
                    <label class="delivery-option-label">
                        <input type="radio" name="delivery_type" value="di_ambil" class="delivery-option-radio">
                        <span class="delivery-option-text">di ambil</span>
                    </label>
                </div>
            </div>

            <!-- Section 4: Payment Summary -->
            <h2 class="checkout-section-title">Ringkasan Pembayaran</h2>
            <div class="checkout-card" id="summaryCard">
                <div class="summary-table">
                    <div class="summary-row">
                        <span class="label">Harga</span>
                        <span class="val" id="summarySubtotal">Rp. 0</span>
                    </div>
                    <div class="summary-row">
                        <span class="label">Biaya Admin</span>
                        <span class="val" id="summaryAdmin">Rp. 500</span>
                    </div>
                    <div class="summary-row discount">
                        <span class="label">Diskon</span>
                        <span class="val" id="summaryDiscount">-Rp. 0</span>
                    </div>
                    <div class="summary-row total">
                        <span class="label">Total Pembayaran</span>
                        <span class="val" id="summaryTotal">Rp. 0</span>
                    </div>
                </div>
            </div>

            <!-- Section 5: Promo Code apply box -->
            <div class="promo-apply-box">
                <span>Kode Promo : KANTINJOSS25</span>
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div class="promo-link-row">
                <span>Cek Promo Lainnya</span>
                <i class="fa-solid fa-arrow-right"></i>
            </div>

        </div>
    </main>

    <!-- Sticky Bottom Bar -->
    <div class="checkout-bottom-bar" id="checkoutBottomBar">
        <div class="checkout-payment-row">
            <div class="checkout-wallet-icon">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <div class="checkout-payment-text">
                <span class="checkout-payment-method">Cash</span>
                <span id="bottomBarTotal" class="checkout-payment-amount">Rp.0</span>
            </div>
        </div>
        <button class="btn-submit-order" id="btnSubmitOrder" onclick="submitOrder()">Pesan Sekarang</button>
    </div>

    <!-- Toast container -->
    <div class="toast-container" id="toastContainer"></div>

    <script>
        const CART_KEY = 'ekantin_cart';

        // ── Get cart from localStorage ──
        function getCart() {
            try {
                const data = localStorage.getItem(CART_KEY);
                return data ? JSON.parse(data) : [];
            } catch (e) {
                return [];
            }
        }

        // ── Save cart to localStorage ──
        function saveCart(cart) {
            localStorage.setItem(CART_KEY, JSON.stringify(cart));
            updateBadges();
        }

        // ── Update all header badge counts ──
        function updateBadges() {
            const cart = getCart();
            const totalItems = cart.reduce((sum, item) => sum + (item.jumlah || 0), 0);
            const headerBadge = document.getElementById('headerCartBadge');
            if (headerBadge) {
                headerBadge.textContent = totalItems;
                headerBadge.style.display = totalItems > 0 ? 'flex' : 'none';
            }
        }

        // ── Render Checkout Page items from local storage ──
        function renderCheckoutPage() {
            const cart = getCart();
            const selectedItems = cart.filter(item => item.selected !== false);
            
            const itemsContainer = document.getElementById('checkoutItemsContainer');
            if (!itemsContainer) return;
            
            if (selectedItems.length === 0) {
                itemsContainer.innerHTML = `
                    <div class="empty-state" style="padding: 40px 20px; text-align:center;">
                        <i class="fa-solid fa-cart-flatbed-suitcase" style="font-size: 48px; color: #cbd5e1; margin-bottom: 12px; display: block; margin: 0 auto 12px auto;"></i>
                        <h3 style="font-size: 16px; font-weight:700; color:#475569; margin-bottom:6px;">Tidak ada item terpilih</h3>
                        <p style="font-size:13px; color:#94a3b8; margin-bottom:16px;">Silakan kembali dan pilih item menu dari keranjang belanjaan Anda.</p>
                        <a href="index.php" class="btn-promo-slide" style="text-decoration:none; padding: 10px 20px; display:inline-block; border-radius:10px;">Belanja Sekarang</a>
                    </div>
                `;
                document.getElementById('checkoutBottomBar').style.display = 'none';
                document.getElementById('summaryCard').style.display = 'none';
                return;
            }
            
            document.getElementById('checkoutBottomBar').style.display = 'flex';
            document.getElementById('summaryCard').style.display = 'block';

            let html = '';
            selectedItems.forEach(item => {
                let imgHTML = '';
                if (item.foto_menu) {
                    imgHTML = `<img src="../../assets/img/menu/${item.foto_menu}" alt="${item.nama_menu}" onerror="this.outerHTML='<div class=\\'toast-img-fallback\\'><i class=\\'fa-solid fa-utensils\\' style=\\'color: #5cb85c; font-size:24px;\\'></i></div>';">`;
                } else {
                    imgHTML = `<div class="toast-img-fallback"><i class="fa-solid fa-utensils" style="color: #5cb85c; font-size:24px;"></i></div>`;
                }
                
                const catatan = item.catatan || 'Tidak ada catatan';
                
                html += `
                    <div class="checkout-item-row" style="align-items: flex-start;">
                        <div class="checkout-item-info" style="justify-content: flex-start; gap: 4px;">
                            <div>
                                <h4 class="checkout-item-title">${item.nama_menu}</h4>
                                <div class="checkout-item-meta">Kantin : ${item.nama_toko} <span style="color:#eab308; font-weight:800; margin-left:8px;">(Stok: ${item.stok !== undefined ? item.stok : '?'})</span></div>
                                <div class="checkout-item-meta">Catatan : <i>${catatan}</i></div>
                            </div>
                            <div class="checkout-item-price">Rp. ${(item.harga).toLocaleString('id-ID')}</div>
                            
                            <div style="margin-top: 8px;">
                                <button class="btn-edit-note" onclick="editItemNote(${item.id_menu})"><i class="fa-regular fa-pen-to-square"></i> Edit</button>
                            </div>
                        </div>
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 10px; flex-shrink: 0;">
                            <div class="checkout-item-img-wrap">
                                ${imgHTML}
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <button class="qty-btn-circle" onclick="updateCheckoutQty(${item.id_menu}, -1)"><i class="fa-solid fa-minus"></i></button>
                                <span class="checkout-qty-val">${item.jumlah}</span>
                                <button class="qty-btn-circle" onclick="updateCheckoutQty(${item.id_menu}, 1)"><i class="fa-solid fa-plus"></i></button>
                            </div>
                        </div>
                    </div>
                `;
            });
            itemsContainer.innerHTML = html;
            
            // Update Payment Summary
            updateCheckoutSummary();
        }

        // ── Edit Notes ──
        function editItemNote(id) {
            const cart = getCart();
            const item = cart.find(c => c.id_menu === id);
            if (!item) return;
            
            const newNote = prompt("Masukkan catatan untuk " + item.nama_menu + ":", item.catatan || "");
            if (newNote !== null) {
                item.catatan = newNote.trim();
                saveCart(cart);
                renderCheckoutPage();
            }
        }

        // ── Edit Quantity in Checkout Page ──
        function updateCheckoutQty(id, delta) {
            const cart = getCart();
            const item = cart.find(c => c.id_menu === id);
            if (item) {
                if (delta > 0) {
                    const stock = item.stok !== undefined ? item.stok : 999;
                    if (item.jumlah >= stock) {
                        showToast('Stok tidak mencukupi! Maksimum stok: ' + stock, 'error');
                        return;
                    }
                }
                item.jumlah += delta;
                if (item.jumlah <= 0) {
                    if (confirm("Hapus " + item.nama_menu + " dari pesanan?")) {
                        cart.splice(cart.indexOf(item), 1);
                    } else {
                        item.jumlah = 1;
                    }
                }
            }
            saveCart(cart);
            renderCheckoutPage();
        }

        // ── Recalculate summary details ──
        function updateCheckoutSummary() {
            const cart = getCart();
            const selectedItems = cart.filter(item => item.selected !== false);
            
            const subtotal = selectedItems.reduce((sum, item) => sum + (item.harga * item.jumlah), 0);
            const biaya_admin = 500;
            
            // Kode Promo KANTINJOSS25 diskon 25%
            const diskon = Math.round(subtotal * 0.25);
            const total = subtotal + biaya_admin - diskon;
            
            document.getElementById('summarySubtotal').textContent = 'Rp. ' + subtotal.toLocaleString('id-ID');
            document.getElementById('summaryAdmin').textContent = 'Rp. ' + biaya_admin.toLocaleString('id-ID');
            document.getElementById('summaryDiscount').textContent = '-Rp. ' + diskon.toLocaleString('id-ID');
            document.getElementById('summaryTotal').textContent = 'Rp. ' + total.toLocaleString('id-ID');
            
            // Bottom Bar Cash Payment text
            document.getElementById('bottomBarTotal').textContent = 'Rp.' + total.toLocaleString('id-ID');
        }

        // ── Add recommended menu item to current order selection ──
        function addRecommendationToCart(id, nama, harga, foto, toko, idToko, stok) {
            let cart = getCart();
            const existing = cart.find(c => c.id_menu === id);
            if (existing) {
                if (existing.jumlah >= stok) {
                    showToast('Stok tidak mencukupi! Maksimum stok: ' + stok, 'error');
                    return;
                }
                existing.jumlah++;
                existing.selected = true;
            } else {
                if (stok <= 0) {
                    showToast('Stok habis!', 'error');
                    return;
                }
                cart.push({
                    id_menu: id,
                    nama_menu: nama,
                    harga: harga,
                    jumlah: 1,
                    foto_menu: foto,
                    nama_toko: toko,
                    id_toko: idToko,
                    selected: true,
                    catatan: '',
                    stok: stok
                });
            }
            saveCart(cart);
            renderCheckoutPage();
            showToast('✅ ' + nama + ' ditambahkan ke pesanan!', 'success');
        }

        // ── Submit Order to Server via AJAX ──
        function submitOrder() {
            const cart = getCart();
            const selectedItems = cart.filter(item => item.selected !== false);
            if (selectedItems.length === 0) {
                showToast('Tidak ada item terpilih!', 'error');
                return;
            }
            
            // Tampilkan modal konfirmasi mirip logout
            let existingModal = document.getElementById('orderConfirmModal');
            if (existingModal) existingModal.remove();
            
            const modal = document.createElement('div');
            modal.id = 'orderConfirmModal';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.4);
                backdrop-filter: blur(8px);
                -webkit-backdrop-filter: blur(8px);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999999;
                opacity: 0;
                transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            `;
            
            const card = document.createElement('div');
            card.style.cssText = `
                background: #ffffff;
                padding: 30px 24px;
                border-radius: 24px;
                width: 90%;
                max-width: 360px;
                text-align: center;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
                transform: scale(0.9);
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            `;
            
            card.innerHTML = `
                <div style="width: 56px; height: 56px; background: #dcfce7; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                    <svg style="width: 28px; height: 28px; stroke: #5cb85c;" viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
                <h3 style="margin: 0 0 8px; font-family: 'Poppins', sans-serif; font-size: 18px; font-weight: 750; color: #1e293b;">Konfirmasi Pesanan</h3>
                <p style="margin: 0 0 24px; font-family: 'Poppins', sans-serif; font-size: 13.5px; color: #64748b; line-height: 1.5;">Apakah Anda yakin ingin melakukan pemesanan ini sekarang?</p>
                <div style="display: flex; gap: 10px; justify-content: center;">
                    <button id="orderCancelBtn" style="flex: 1; padding: 11px; border-radius: 12px; border: 1.5px solid #cbd5e1; background: #ffffff; color: #475569; font-family: 'Poppins', sans-serif; font-size: 13.5px; font-weight: 700; cursor: pointer; transition: all 0.2s;">Batal</button>
                    <button id="orderConfirmBtn" style="flex: 1; padding: 11px; border-radius: 12px; border: none; background: #5cb85c; color: #ffffff; font-family: 'Poppins', sans-serif; font-size: 13.5px; font-weight: 700; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 12px rgba(92, 184, 92, 0.25);">Ya, Pesan</button>
                </div>
            `;
            
            modal.appendChild(card);
            document.body.appendChild(modal);
            
            setTimeout(() => {
                modal.style.opacity = '1';
                card.style.transform = 'scale(1)';
            }, 10);
            
            function closeModal() {
                modal.style.opacity = '0';
                card.style.transform = 'scale(0.9)';
                setTimeout(() => modal.remove(), 300);
            }
            
            document.getElementById('orderCancelBtn').addEventListener('click', closeModal);
            document.getElementById('orderConfirmBtn').addEventListener('click', () => {
                closeModal();
                processOrder(cart);
            });
            
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });
        }

        function processOrder(cart) {
            const shippingRadio = document.querySelector('input[name="delivery_type"]:checked');
            const tipe_pengiriman = shippingRadio ? shippingRadio.value : 'di_ambil';
            
            const btn = document.getElementById('btnSubmitOrder');
            btn.disabled = true;
            btn.textContent = "Sedang Memproses...";
            
            const formData = new FormData();
            formData.append('action', 'buat_pesanan');
            formData.append('cart_data', JSON.stringify(cart));
            formData.append('tipe_pengiriman', tipe_pengiriman);
            formData.append('kode_promo', 'KANTINJOSS25'); // Otomatis terpasang
            
            fetch('checkout.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast('🎉 Pesanan berhasil dibuat!', 'success');
                    
                    // Filter keluar item yang sudah dibeli dari keranjang lokal
                    const remainingCart = cart.filter(item => item.selected === false);
                    saveCart(remainingCart);
                    
                    setTimeout(() => {
                        window.location.href = 'index.php?tab=pesanan';
                    }, 1500);
                } else {
                    showToast('Gagal membuat pesanan: ' + data.message, 'error');
                    btn.disabled = false;
                    btn.textContent = "Pesan Sekarang";
                }
            })
            .catch(err => {
                showToast('Koneksi gagal!', 'error');
                btn.disabled = false;
                btn.textContent = "Pesan Sekarang";
            });
        }

        function showToast(message, type) {
            const container = document.getElementById('toastContainer');
            if (!container) return;
            container.querySelectorAll('.toast').forEach(t => t.remove());

            const toast = document.createElement('div');
            toast.className = 'toast ' + (type || '');

            let titleText = 'Notifikasi';

            if (type === 'success') {
                titleText = 'Berhasil';
            } else if (type === 'error') {
                titleText = 'Gagal';
            }

            toast.innerHTML = `
                <div class="toast-info">
                    <div class="toast-title">${titleText}</div>
                    <div class="toast-desc">${message}</div>
                </div>
            `;

            container.appendChild(toast);
            setTimeout(() => {
                toast.classList.add('fade-out');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // ── Init on page load ──
        document.addEventListener('DOMContentLoaded', () => {
            updateBadges();
            renderCheckoutPage();
        });
    </script>
</body>

</html>
