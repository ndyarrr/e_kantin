<?php
// views/pembeli/sections/pesanan.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = $koneksi ?? $conn ?? null;
if (!$db) {
    echo "<div class='empty-state'>Koneksi database tidak tersedia.</div>";
    return;
}

$s_role = $user_role ?? $_SESSION['user_role'] ?? 'siswa';
$s_id = $user_id ?? $_SESSION['user_id'] ?? '';
$col_pembeli = ($s_role === 'siswa') ? 'nisn_pembeli' : 'nuptk_pembeli';

// Query pesanan pembeli
$pesanan_list = [];
$q_pesanan = mysqli_query($db, "
    SELECT p.*, t.nama_toko, t.id_toko, t.qris_image, t.foto_toko
    FROM pesanan p
    JOIN toko t ON p.id_toko = t.id_toko
    WHERE p.$col_pembeli = '$s_id'
    ORDER BY p.waktu_pesan DESC
");

echo "<!-- DEBUG PESANAN: role=$s_role, id=$s_id, col=$col_pembeli, rows=" . ($q_pesanan ? mysqli_num_rows($q_pesanan) : 'query failed') . " -->\n";

if ($q_pesanan) {
    while ($r = mysqli_fetch_assoc($q_pesanan)) {
        // Ambil detail items untuk pesanan ini
        $id_pesanan = $r['id_pesanan'];
        $q_detail = mysqli_query($db, "
            SELECT dp.*, m.nama_menu, m.foto_menu 
            FROM detail_pesanan dp
            JOIN menu m ON dp.id_menu = m.id_menu
            WHERE dp.id_pesanan = $id_pesanan
        ");
        $details = [];
        if ($q_detail) {
            while ($d = mysqli_fetch_assoc($q_detail)) {
                $details[] = $d;
            }
        }
        $r['items'] = $details;

        // Ambil status pembayaran
        $q_pay = mysqli_query($db, "SELECT status, metode, bukti_foto FROM pembayaran WHERE id_pesanan = $id_pesanan LIMIT 1");
        $pay = mysqli_fetch_assoc($q_pay) ?? ['status' => 'belum_bayar', 'metode' => 'tunai', 'bukti_foto' => ''];
        $r['pembayaran'] = $pay;

        // Ambil nama dan kelas pembeli
        $col_nama = ($s_role === 'siswa') ? 'nama_siswa' : 'nama_guru';
        $tbl_pembeli = ($s_role === 'siswa') ? 'siswa' : 'guru';
        $col_kelas = ($s_role === 'siswa') ? 'kelas' : 'nuptk';
        $col_id_tbl = ($s_role === 'siswa') ? 'nisn' : 'nuptk';
        $q_nama = mysqli_query($db, "SELECT $col_nama, $col_kelas FROM $tbl_pembeli WHERE $col_id_tbl = '$s_id' LIMIT 1");
        $data_pembeli = $q_nama ? mysqli_fetch_assoc($q_nama) : [];
        $r['nama_pembeli'] = $data_pembeli[$col_nama] ?? $s_id;
        $r['kelas_pembeli'] = $data_pembeli[$col_kelas] ?? '-';

        // Cari kasir dan shift dari log_sistem
        $kasirNama = '';
        $kasirShift = '';
        $q_log = mysqli_query($db, "
            SELECT l.user_nama, tp.shift
            FROM log_sistem l
            LEFT JOIN toko_penjual tp ON tp.id_penjual = l.user_id AND tp.status = 'aktif'
            WHERE l.keterangan LIKE '%pesanan #$id_pesanan%'
              AND l.user_role = 'penjual'
            ORDER BY l.dibuat_pada DESC
            LIMIT 1
        ");
        if ($q_log && $r_log = mysqli_fetch_assoc($q_log)) {
            $kasirNama  = $r_log['user_nama'];
            $kasirShift = $r_log['shift'] ?? 'Bebas';
        }
        $r['kasir_nama']  = $kasirNama;
        $r['kasir_shift'] = $kasirShift;

        // Resolve foto_toko URL
        $foto_toko_file = $r['foto_toko'] ?? '';
        $foto_toko_url  = '';
        if (!empty($foto_toko_file)) {
            $foto_toko_url = '../../assets/img/kantin/' . $foto_toko_file;
        }
        $r['foto_toko_url'] = $foto_toko_url;

        $pesanan_list[] = $r;
    }
}
?>
<!-- ═══════ SECTION: PESANAN ═══════ -->
<div class="page-section <?= $active_tab === 'pesanan' ? 'active' : '' ?>" id="section-pesanan">
    <section class="section-block">
        <h2 class="section-title">Pesanan Saya</h2>
        
        <?php if (empty($pesanan_list)): ?>
            <div class="empty-state" id="pesananEmpty">
                <i class="fa-solid fa-receipt"></i>
                <h3>Belum Ada Pesanan</h3>
                <p>Pesanan yang kamu buat akan muncul di sini. Yuk mulai pesan dari kantin favoritmu!</p>
                <br>
                <button class="btn-promo-blank" onclick="switchNav('kantin')"
                    style="font-size:13px;padding:10px 28px">Jelajahi Kantin</button>
            </div>
        <?php else: ?>
            <!-- ══ MODAL NOTA PEMBELI ══ -->
            <div id="notaModalPembeli" class="nota-overlay-pembeli" onclick="tutupNotaPembeli(event)">
                <div class="nota-box-pembeli" id="notaBoxPembeli">
                    <div id="notaKontenPembeli">
                        <div class="nota-jagged-top-pembeli"></div>
                        <div class="nota-header-pembeli">
                            <div class="nota-logo-pembeli" id="notaLogoPembeli">🏪</div>
                            <div class="nota-toko-nama-pembeli" id="notaTokoNamaPembeli"></div>
                            <div class="nota-sub-pembeli">E-Kantin SMKN 1</div>
                        </div>
                        <div class="nota-garis-pembeli"></div>
                        <div class="nota-info-pembeli">
                            <div class="nota-info-row-pembeli"><span>No. Pesanan</span><span id="notaIdPembeli"></span></div>
                            <div class="nota-info-row-pembeli"><span>Pembeli</span><span id="notaNamaPembeli"></span></div>
                            <div class="nota-info-row-pembeli"><span>Kelas</span><span id="notaKelasPembeli"></span></div>
                            <div class="nota-info-row-pembeli"><span>Waktu</span><span id="notaWaktuPembeli"></span></div>
                            <div class="nota-info-row-pembeli"><span>Kasir</span><span id="notaKasirPembeli"></span></div>
                            <div class="nota-info-row-pembeli"><span>Shift</span><span id="notaShiftPembeli"></span></div>
                            <div class="nota-info-row-pembeli"><span>Pembayaran</span><span id="notaMetodePembeli"></span></div>
                        </div>
                        <div class="nota-garis-pembeli"></div>
                        <table class="nota-table-pembeli" id="notaTablePembeli">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th class="center">Qty</th>
                                    <th class="right">Harga</th>
                                </tr>
                            </thead>
                            <tbody id="notaItemsPembeli"></tbody>
                        </table>
                        <div class="nota-garis-pembeli"></div>
                        <div class="nota-total-row-pembeli"><span>TOTAL</span><span id="notaTotalPembeli"></span></div>
                        <div class="nota-garis-pembeli"></div>
                        <div class="nota-footer-pembeli">
                            <div class="nota-footer-bold-pembeli">Terima kasih atas pesanan Anda!</div>
                            <div class="nota-footer-sub-pembeli">Semoga hari Anda menyenangkan</div>
                        </div>
                        <div class="nota-jagged-bottom-pembeli"></div>
                    </div>
                    <div class="nota-actions-pembeli no-print-pembeli">
                        <button type="button" class="btn-nota-tutup" onclick="tutupNotaPembeli()">&#x2715; Tutup</button>
                        <button type="button" class="btn-nota-cetak" onclick="cetakNotaPembeli()">&#x1F5A8; Cetak Nota</button>
                    </div>
                </div>
            </div>

            <style>
                .pesanan-container {
                    display: flex;
                    flex-direction: column;
                    gap: 16px;
                    margin-top: 16px;
                }
                .pesanan-card {
                    background: #ffffff;
                    border: 1px solid #f1f5f9;
                    border-radius: 20px;
                    padding: 18px;
                    box-shadow: 0 4px 20px rgba(15, 23, 42, 0.015);
                    display: flex;
                    flex-direction: column;
                    gap: 14px;
                    box-sizing: border-box;
                }
                .pesanan-card-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    border-bottom: 1px dashed #f1f5f9;
                    padding-bottom: 12px;
                }
                .pesanan-canteen-info {
                    display: flex;
                    flex-direction: column;
                    gap: 2px;
                }
                .pesanan-canteen-name {
                    font-size: 15px;
                    font-weight: 800;
                    color: #1e293b;
                }
                .pesanan-date {
                    font-size: 11px;
                    color: #94a3b8;
                    font-weight: 500;
                }
                .pesanan-status-badge {
                    font-size: 11px;
                    font-weight: 800;
                    padding: 5px 12px;
                    border-radius: 20px;
                    text-transform: capitalize;
                }
                .status-menunggu {
                    background: #fff8e1;
                    color: #ffb300;
                }
                .status-diproses {
                    background: #e3f2fd;
                    color: #1e88e5;
                }
                .status-siap {
                    background: #e8f5e9;
                    color: #4caf50;
                }
                .status-selesai {
                    background: #e0f2f1;
                    color: #00897b;
                }
                .status-dibatalkan {
                    background: #ffebee;
                    color: #e53935;
                }
                
                .pesanan-items-list {
                    display: flex;
                    flex-direction: column;
                    gap: 12px;
                }
                .pesanan-item-row {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                .pesanan-item-left {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    flex: 1;
                    min-width: 0;
                }
                .pesanan-item-img-wrap {
                    width: 44px;
                    height: 44px;
                    border-radius: 8px;
                    overflow: hidden;
                    border: 1px solid #f1f5f9;
                    flex-shrink: 0;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: #f8fafc;
                }
                .pesanan-item-img-wrap img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                }
                .pesanan-item-details {
                    display: flex;
                    flex-direction: column;
                    min-width: 0;
                }
                .pesanan-item-name {
                    font-size: 13px;
                    font-weight: 700;
                    color: #1e293b;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }
                .pesanan-item-note {
                    font-size: 10.5px;
                    color: #64748b;
                    font-style: italic;
                    margin-top: 1px;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }
                .pesanan-item-qty {
                    font-size: 11px;
                    color: #64748b;
                    font-weight: 600;
                    margin-top: 1px;
                }
                .pesanan-item-price {
                    font-size: 13px;
                    font-weight: 700;
                    color: #475569;
                    flex-shrink: 0;
                }
                
                .pesanan-card-footer {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    border-top: 1px solid #f1f5f9;
                    padding-top: 12px;
                    margin-top: 4px;
                }
                .pesanan-total-info {
                    display: flex;
                    flex-direction: column;
                    line-height: 1.3;
                }
                .pesanan-total-label {
                    font-size: 10px;
                    color: #94a3b8;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                .pesanan-total-val {
                    font-size: 15px;
                    font-weight: 900;
                    color: #0f172a;
                }
                
                .pesanan-footer-right {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                }
                .pesanan-payment-status {
                    font-size: 11.5px;
                    font-weight: 700;
                    display: inline-flex;
                    align-items: center;
                    gap: 4px;
                }
                .pay-lunas {
                    color: #4caf50;
                }
                .pay-belum_bayar {
                    color: #e53935;
                }
                
                .btn-chat-kantin {
                    background: #f1f5f9;
                    border: none;
                    border-radius: 12px;
                    padding: 8px 14px;
                    font-size: 11.5px;
                    font-weight: 700;
                    color: #475569;
                    cursor: pointer;
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    transition: all 0.2s;
                }
                .btn-chat-kantin:hover {
                    background: #e2e8f0;
                    color: #1e293b;
                }
                .btn-batalkan-pesanan:hover {
                    background: #fecaca !important;
                    color: #b91c1c !important;
                    border-color: #f87171 !important;
                }
                .btn-nota {
                    background: #1e293b;
                    border: none;
                    border-radius: 12px;
                    padding: 8px 14px;
                    font-size: 11.5px;
                    font-weight: 700;
                    color: #ffffff;
                    cursor: pointer;
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    transition: all 0.2s;
                }
                .btn-nota:hover {
                    background: #0f172a;
                    transform: translateY(-1px);
                }

                /* ══ Nota Modal Pembeli ══ */
                .nota-overlay-pembeli {
                    display: none;
                    position: fixed;
                    inset: 0;
                    background: rgba(15, 23, 42, 0.65);
                    backdrop-filter: blur(8px);
                    -webkit-backdrop-filter: blur(8px);
                    z-index: 99999;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                }
                .nota-overlay-pembeli.show { display: flex; }
                .nota-box-pembeli {
                    background: #ffffff;
                    border-radius: 8px;
                    width: 100%;
                    max-width: 320px;
                    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.4);
                    overflow: hidden;
                    border: 1px solid rgba(0,0,0,0.08);
                }
                #notaKontenPembeli {
                    background: #fdfdf9;
                    color: #1e293b;
                    padding: 36px 20px 30px;
                    position: relative;
                    font-family: 'Courier New', Courier, monospace;
                    font-size: 12.5px;
                    line-height: 1.4;
                }
                .nota-jagged-top-pembeli, .nota-jagged-bottom-pembeli {
                    position: absolute;
                    left: 0; right: 0;
                    height: 6px;
                    background-image: radial-gradient(circle, transparent 3px, #fdfdf9 3.5px);
                    background-size: 8px 12px;
                    background-repeat: repeat-x;
                    z-index: 5;
                }
                .nota-jagged-top-pembeli { top: 0; background-position: 0 -6px; }
                .nota-jagged-bottom-pembeli { bottom: 0; background-position: 0 6px; }
                .nota-header-pembeli { text-align: center; margin-bottom: 14px; }
                .nota-logo-pembeli {
                    display: flex; justify-content: center; align-items: center;
                    margin-bottom: 8px; font-size: 26px;
                }
                .nota-toko-nama-pembeli {
                    font-size: 15px; font-weight: 800; color: #0f172a;
                    text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px;
                }
                .nota-sub-pembeli { font-size: 11px; color: #64748b; font-weight: 500; }
                .nota-garis-pembeli {
                    display: block; width: 100%; height: 0;
                    border: none; border-top: 2px dashed #94a3b8; margin: 12px 0;
                }
                .nota-info-pembeli { margin-bottom: 4px; }
                .nota-info-row-pembeli {
                    display: flex; justify-content: space-between;
                    font-size: 12px; padding: 3px 0;
                }
                .nota-info-row-pembeli span:first-child { color: #64748b; flex-shrink: 0; }
                .nota-info-row-pembeli span:last-child {
                    font-weight: 700; color: #0f172a; text-align: right;
                    max-width: 60%; word-break: break-word;
                }
                .nota-table-pembeli {
                    width: 100%; border-collapse: collapse;
                    font-size: 12px; color: #1e293b;
                }
                .nota-table-pembeli th {
                    font-size: 10px; font-weight: 700; text-transform: uppercase;
                    color: #64748b; padding: 5px 0; letter-spacing: 0.5px;
                    border-bottom: 1.5px dashed #cbd5e1; text-align: left;
                }
                .nota-table-pembeli th.center, .nota-table-pembeli td.center { text-align: center; }
                .nota-table-pembeli th.right, .nota-table-pembeli td.right { text-align: right; }
                .nota-table-pembeli td { padding: 6px 0; vertical-align: top; color: #334155; }
                .nota-total-row-pembeli {
                    display: flex; justify-content: space-between;
                    font-size: 15px; font-weight: 800; color: #0f172a;
                    padding: 5px 0; letter-spacing: 0.2px;
                }
                .nota-footer-pembeli { text-align: center; margin-top: 12px; padding-bottom: 4px; }
                .nota-footer-bold-pembeli { font-weight: 700; font-size: 12px; color: #334155; margin-bottom: 2px; }
                .nota-footer-sub-pembeli { font-size: 10.5px; color: #64748b; font-style: italic; }
                .nota-actions-pembeli {
                    display: flex; gap: 12px;
                    padding: 16px 20px;
                    border-top: 1px solid #f1f5f9;
                    background: #f8fafc;
                }
                .btn-nota-tutup {
                    flex: 1; padding: 10px; font-size: 13px; font-weight: 700;
                    color: #475569; background: #f1f5f9; border: 1px solid #e2e8f0;
                    border-radius: 10px; cursor: pointer;
                    font-family: 'Poppins', sans-serif;
                }
                .btn-nota-cetak {
                    flex: 1.5; padding: 10px; font-size: 13px; font-weight: 700;
                    color: #ffffff; background: #1e293b; border: none;
                    border-radius: 10px; cursor: pointer;
                    font-family: 'Poppins', sans-serif;
                }

                /* ── Print thermal pembeli ── */
                @media print {
                    @page { size: 80mm auto; margin: 4mm; }
                    body * { visibility: hidden; }
                    #notaKontenPembeli, #notaKontenPembeli * { visibility: visible; }
                    #notaKontenPembeli {
                        position: fixed !important;
                        top: 0 !important;
                        left: 0 !important;
                        width: 100% !important;
                        background: #fff !important;
                        color: #000 !important;
                        font-family: 'Courier New', Courier, monospace !important;
                        font-size: 22px !important;
                        padding: 8px 4px !important;
                        line-height: 1.5 !important;
                    }
                    .nota-toko-nama-pembeli { font-size: 28px !important; }
                    .nota-info-row-pembeli { font-size: 22px !important; }
                    .nota-table-pembeli, .nota-table-pembeli th, .nota-table-pembeli td { font-size: 22px !important; }
                    .nota-total-row-pembeli { font-size: 26px !important; }
                    .nota-footer-bold-pembeli { font-size: 22px !important; }
                    .nota-footer-sub-pembeli { font-size: 18px !important; }
                    .nota-garis-pembeli { border-top: 2px dashed #000 !important; margin: 14px 0 !important; }
                    .nota-actions-pembeli { display: none !important; }
                    .nota-logo-pembeli img { max-width: 60px !important; height: auto !important; }
                }
            </style>
            
            <div class="pesanan-container">
                <?php foreach ($pesanan_list as $pesanan): 
                    $badge_class = 'status-' . $pesanan['status'];
                    $status_text = $pesanan['status'];
                    if ($pesanan['status'] === 'menunggu') {
                        $status_text = 'Menunggu Konfirmasi';
                    } elseif ($pesanan['status'] === 'dikonfirmasi') {
                        $badge_class = 'status-diproses';
                        $status_text = 'Sedang Disiapkan';
                    } elseif ($pesanan['status'] === 'siap_diambil') {
                        $badge_class = 'status-siap';
                        $status_text = 'Siap Diambil';
                    } elseif ($pesanan['status'] === 'selesai') {
                        $status_text = 'Selesai';
                    } elseif ($pesanan['status'] === 'dibatalkan') {
                        $status_text = 'Dibatalkan';
                    }
                    
                    $waktu_format = date('d M Y, H:i', strtotime($pesanan['waktu_pesan']));
                    $is_lunas = isset($pesanan['pembayaran']['status']) && $pesanan['pembayaran']['status'] === 'lunas';
                ?>
                    <div class="pesanan-card">
                        <div class="pesanan-card-header">
                            <div class="pesanan-canteen-info">
                                <span class="pesanan-canteen-name"><?= htmlspecialchars($pesanan['nama_toko']) ?></span>
                                <span class="pesanan-date"><?= $waktu_format ?></span>
                            </div>
                            <span class="pesanan-status-badge <?= $badge_class ?>"><?= $status_text ?></span>
                        </div>
                        
                        <div class="pesanan-items-list">
                            <?php foreach ($pesanan['items'] as $item): 
                                $foto = $item['foto_menu'] ?? '';
                                $img_src = !empty($foto) && file_exists(__DIR__ . '/../../../assets/img/menu/' . $foto) ? '../../assets/img/menu/' . $foto : '';
                                $catatan = !empty($item['catatan']) ? $item['catatan'] : 'Tidak ada catatan';
                            ?>
                                <div class="pesanan-item-row">
                                    <div class="pesanan-item-left">
                                        <div class="pesanan-item-img-wrap">
                                            <?php if ($img_src): ?>
                                                <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($item['nama_menu']) ?>">
                                            <?php else: ?>
                                                <i class="fa-solid fa-utensils" style="color: #cbd5e1; font-size: 16px;"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="pesanan-item-details">
                                            <span class="pesanan-item-name"><?= htmlspecialchars($item['nama_menu']) ?></span>
                                            <span class="pesanan-item-note">Catatan: <?= htmlspecialchars($catatan) ?></span>
                                            <span class="pesanan-item-qty">Jumlah: <?= $item['jumlah'] ?>x</span>
                                        </div>
                                    </div>
                                    <span class="pesanan-item-price">Rp. <?= number_format($item['harga_satuan'] * $item['jumlah'], 0, ',', '.') ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php 
                            $is_transfer = ($pesanan['pembayaran']['metode'] === 'transfer');
                        ?>
                        <div class="pesanan-card-footer">
                            <div class="pesanan-total-info">
                                <span class="pesanan-total-label">Total Pembayaran</span>
                                <span class="pesanan-total-val">Rp. <?= number_format($pesanan['total_harga'], 0, ',', '.') ?></span>
                            </div>
                            <div class="pesanan-footer-right" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; justify-content: flex-end;">
                                <?php if ($is_lunas): ?>
                                    <span class="pesanan-payment-status pay-lunas">
                                        <i class="fa-solid fa-circle-check"></i> Lunas
                                        <small style="color: #64748b; font-weight: 500; margin-left: 2px;">(<?= $is_transfer ? 'QRIS' : 'Tunai' ?>)</small>
                                    </span>
                                <?php else: ?>
                                    <span class="pesanan-payment-status pay-belum_bayar" style="display: flex; flex-direction: column; align-items: flex-end; gap: 2px;">
                                        <span>
                                            <i class="fa-solid fa-circle-xmark"></i> Belum Bayar <small style="color: #64748b; font-weight: 500; margin-left: 2px;">(<?= $is_transfer ? 'QRIS' : 'Tunai' ?>)</small>
                                        </span>
                                        <?php if ($is_transfer): ?>
                                            <span style="font-size: 10.5px; color: #0284c7; font-weight: 600;">Menunggu konfirmasi penjual</span>
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($is_transfer && !$is_lunas && !empty($pesanan['qris_image'])): ?>
                                    <button class="btn-bayar-qris" id="btn-qris-pay-<?= $pesanan['id_pesanan'] ?>" onclick="openPesananQrisModal('<?= htmlspecialchars(addslashes($pesanan['nama_toko']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($pesanan['qris_image']), ENT_QUOTES) ?>', <?= $pesanan['id_pesanan'] ?>)" style="background: #16a34a; border: none; border-radius: 12px; padding: 8px 14px; font-size: 11.5px; font-weight: 700; color: #ffffff; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;">
                                        <i class="fa-solid fa-qrcode"></i> Bayar QRIS
                                    </button>
                                <?php endif; ?>

                                <?php if ($pesanan['status'] === 'menunggu' && !$is_lunas): ?>
                                    <button class="btn-batalkan-pesanan" onclick="batalkanPesanan(<?= $pesanan['id_pesanan'] ?>)" style="background: #fee2e2; border: 1px solid #fca5a5; border-radius: 12px; padding: 8px 14px; font-size: 11.5px; font-weight: 700; color: #dc2626; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;">
                                        <i class="fa-solid fa-ban"></i> Batalkan Pesanan
                                    </button>
                                <?php endif; ?>

                                <button class="btn-nota" onclick="bukaNotaPembeli(<?= htmlspecialchars(json_encode([
                                    'id'      => $pesanan['id_pesanan'],
                                    'toko'    => $pesanan['nama_toko'],
                                    'foto'    => $pesanan['foto_toko_url'],
                                    'pembeli' => $pesanan['nama_pembeli'],
                                    'kelas'   => $pesanan['kelas_pembeli'],
                                    'waktu'   => date('d/m/Y H:i', strtotime($pesanan['waktu_pesan'])),
                                    'kasir'   => $pesanan['kasir_nama'],
                                    'shift'   => $pesanan['kasir_shift'],
                                    'metode'  => ($pesanan['pembayaran']['metode'] === 'transfer') ? 'QRIS' : 'Tunai',
                                    'total'   => $pesanan['total_harga'],
                                    'items'   => array_map(function($it) {
                                        return ['nama' => $it['nama_menu'], 'jumlah' => $it['jumlah'], 'harga' => $it['harga_satuan'] * $it['jumlah']];
                                    }, $pesanan['items']),
                                ]), ENT_QUOTES, 'UTF-8') ?>)">
                                    <i class="fa-solid fa-receipt"></i> Nota
                                </button>

                                <button class="btn-chat-kantin" onclick="switchNav('chat'); setTimeout(() => { bukaRoomChat('toko_<?= $pesanan['id_toko'] ?>', '<?= htmlspecialchars(addslashes($pesanan['nama_toko']), ENT_QUOTES) ?>'); }, 200);">
                                    <i class="fa-solid fa-comment-dots"></i> Hubungi Kantin
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<script>
function bukaNotaPembeli(data) {
    const modal = document.getElementById('notaModalPembeli');
    if (modal && modal.parentNode !== document.body) {
        document.body.appendChild(modal);
    }

    // Update header toko
    document.getElementById('notaTokoNamaPembeli').textContent = data.toko;

    // Logo kantin (foto atau emoji fallback)
    const logoEl = document.getElementById('notaLogoPembeli');
    if (data.foto && data.foto.trim() !== '') {
        logoEl.innerHTML = `<img src="${data.foto}" alt="Logo" style="width:50px;height:50px;object-fit:cover;border-radius:50%;border:2px solid #e2e8f0;">`;
    } else {
        logoEl.innerHTML = '🏪';
    }

    document.getElementById('notaIdPembeli').textContent = '#' + data.id;
    document.getElementById('notaNamaPembeli').textContent = data.pembeli;
    document.getElementById('notaKelasPembeli').textContent = data.kelas;
    document.getElementById('notaWaktuPembeli').textContent = data.waktu;
    document.getElementById('notaKasirPembeli').textContent = data.kasir || '-';
    document.getElementById('notaShiftPembeli').textContent = data.shift || 'Bebas';
    document.getElementById('notaMetodePembeli').textContent = data.metode || 'Tunai';
    document.getElementById('notaTotalPembeli').textContent = 'Rp ' + Number(data.total).toLocaleString('id-ID');

    const tbody = document.getElementById('notaItemsPembeli');
    tbody.innerHTML = '';
    data.items.forEach(item => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${item.nama}</td>
            <td class="center">${item.jumlah}&times;</td>
            <td class="right">Rp ${Number(item.harga).toLocaleString('id-ID')}</td>`;
        tbody.appendChild(tr);
    });

    document.getElementById('notaModalPembeli').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function tutupNotaPembeli(e) {
    if (e && e.target !== document.getElementById('notaModalPembeli')) return;
    document.getElementById('notaModalPembeli').classList.remove('show');
    document.body.style.overflow = '';
}

function cetakNotaPembeli() {
    window.print();
    setTimeout(() => {
        document.getElementById('notaModalPembeli').classList.remove('show');
        document.body.style.overflow = '';
    }, 400);
}

</script>
<script>
function openPesananQrisModal(namaToko, qrisImage, idPesanan) {
    let oldModal = document.getElementById('qrisPaymentModal');
    if (oldModal) oldModal.remove();

    const modal = document.createElement('div');
    modal.id = 'qrisPaymentModal';
    modal.style.cssText = `
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0,0,0,0.5);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        display: flex; align-items: center; justify-content: center;
        z-index: 9999999;
        opacity: 0; transition: opacity 0.3s ease;
        padding: 12px; box-sizing: border-box;
    `;

    const card = document.createElement('div');
    card.style.cssText = `
        background: #ffffff;
        width: 100%; max-width: 400px;
        border-radius: 24px; padding: 24px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        text-align: center;
        transform: scale(0.9); transition: transform 0.3s ease;
        max-height: 90vh; display: flex; flex-direction: column;
        box-sizing: border-box;
    `;

    card.innerHTML = `
        <div style="overflow-y:auto; flex:1; padding-right:2px; position:relative;">
            <button id="btnPesananQrisX" style="position:absolute;top:0;right:0;width:32px;height:32px;border-radius:50%;border:none;background:#f1f5f9;color:#64748b;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s;z-index:1;" onmouseover="this.style.background='#fee2e2';this.style.color='#ef4444';" onmouseout="this.style.background='#f1f5f9';this.style.color='#64748b';">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div style="font-size:26px;color:#16a34a;margin-bottom:10px;">
                <i class="fa-solid fa-qrcode"></i>
            </div>
            <h3 style="margin:0 0 4px;font-size:16px;font-weight:800;color:#0f172a;font-family:'Poppins',sans-serif;">QRIS - ${namaToko}</h3>
            <p style="margin:0 0 14px;font-size:12px;color:#64748b;font-family:'Poppins',sans-serif;">Scan QR di bawah ini untuk melakukan pembayaran.</p>

            <div style="background:#fff;padding:10px;border-radius:14px;border:1.5px solid #e2e8f0;display:inline-block;margin-bottom:20px;">
                <img src="../../assets/img/qris/${qrisImage}" alt="QRIS" style="max-width:200px;width:100%;height:auto;display:block;border-radius:6px;">
            </div>
        </div>

        <button id="btnPesananQrisClose" style="width:100%;padding:11px;font-weight:800;font-size:13px;color:#fff;background:#16a34a;border:none;border-radius:12px;cursor:pointer;font-family:'Poppins',sans-serif;transition:all 0.2s;box-shadow:0 4px 12px rgba(22,163,74,0.2);">
            <i class="fa-solid fa-check"></i> Selesai
        </button>
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

    card.querySelector('#btnPesananQrisClose').addEventListener('click', closeModal);
    card.querySelector('#btnPesananQrisX').addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
}

function batalkanPesanan(idPesanan) {
    // Buat modal konfirmasi kustom yang cantik
    let oldModal = document.getElementById('cancelOrderConfirmModal');
    if (oldModal) oldModal.remove();

    const modal = document.createElement('div');
    modal.id = 'cancelOrderConfirmModal';
    modal.style.cssText = `
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0,0,0,0.5);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        display: flex; align-items: center; justify-content: center;
        z-index: 9999999;
        opacity: 0; transition: opacity 0.3s ease;
        padding: 16px; box-sizing: border-box;
    `;

    const card = document.createElement('div');
    card.style.cssText = `
        background: #ffffff;
        width: 100%; max-width: 360px;
        border-radius: 24px; padding: 24px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        text-align: center;
        transform: scale(0.9); transition: transform 0.3s ease;
        box-sizing: border-box;
    `;

    card.innerHTML = `
        <div style="font-size:40px;color:#ef4444;margin-bottom:16px;">
            <i class="fa-solid fa-circle-exclamation"></i>
        </div>
        <h3 style="margin:0 0 8px;font-size:16px;font-weight:800;color:#0f172a;font-family:'Poppins',sans-serif;">Batalkan Pesanan?</h3>
        <p style="margin:0 0 20px;font-size:12.5px;color:#64748b;font-family:'Poppins',sans-serif;line-height:1.5;">Apakah Anda yakin ingin membatalkan pesanan <strong>#${idPesanan}</strong>? Tindakan ini tidak dapat dibatalkan.</p>
        
        <div style="display:flex;gap:8px;">
            <button id="btnCancelOrderNo" style="flex:1;padding:11px;font-weight:700;font-size:13px;color:#475569;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:12px;cursor:pointer;font-family:'Poppins',sans-serif;transition:all 0.2s;">
                Kembali
            </button>
            <button id="btnCancelOrderYes" style="flex:1;padding:11px;font-weight:800;font-size:13px;color:#fff;background:#ef4444;border:none;border-radius:12px;cursor:pointer;font-family:'Poppins',sans-serif;transition:all 0.2s;box-shadow:0 4px 12px rgba(239,68,68,0.2);">
                Ya, Batalkan
            </button>
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

    modal.querySelector('#btnCancelOrderNo').addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

    modal.querySelector('#btnCancelOrderYes').addEventListener('click', function() {
        const btnYes = modal.querySelector('#btnCancelOrderYes');
        const btnNo = modal.querySelector('#btnCancelOrderNo');
        
        btnYes.disabled = true;
        btnYes.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses...';
        btnYes.style.pointerEvents = 'none';
        btnYes.style.opacity = '0.7';
        btnNo.disabled = true;
        btnNo.style.pointerEvents = 'none';
        btnNo.style.opacity = '0.5';

        const fd = new FormData();
        fd.append('id_pesanan', idPesanan);

        fetch('actions/batalkan_pesanan.php', {
            method: 'POST',
            body: fd
        })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                btnYes.innerHTML = '<i class="fa-solid fa-circle-check"></i> Berhasil';
                btnYes.style.background = '#16a34a';
                btnYes.style.boxShadow = 'none';

                if (typeof showToast === 'function') {
                    showToast('🎉 ' + res.message, 'success');
                } else {
                    alert(res.message);
                }

                setTimeout(() => {
                    closeModal();
                    window.location.reload();
                }, 1500);
            } else {
                btnYes.disabled = false;
                btnYes.innerHTML = 'Ya, Batalkan';
                btnYes.style.pointerEvents = 'auto';
                btnYes.style.opacity = '1';
                btnNo.disabled = false;
                btnNo.style.pointerEvents = 'auto';
                btnNo.style.opacity = '1';

                if (typeof showToast === 'function') {
                    showToast('Gagal: ' + res.message, 'error');
                } else {
                    alert('Gagal: ' + res.message);
                }
            }
        })
        .catch(err => {
            console.error(err);
            btnYes.disabled = false;
            btnYes.innerHTML = 'Ya, Batalkan';
            btnYes.style.pointerEvents = 'auto';
            btnYes.style.opacity = '1';
            btnNo.disabled = false;
            btnNo.style.pointerEvents = 'auto';
            btnNo.style.opacity = '1';

            if (typeof showToast === 'function') {
                showToast('Koneksi gagal! Silakan coba lagi.', 'error');
            } else {
                alert('Koneksi gagal!');
            }
        });
    });
}
</script>
