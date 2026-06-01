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

$col_pembeli = ($user_role === 'siswa') ? 'nisn_pembeli' : 'nuptk_pembeli';

// Query pesanan pembeli
$pesanan_list = [];
$q_pesanan = mysqli_query($db, "
    SELECT p.*, t.nama_toko, t.id_toko
    FROM pesanan p
    JOIN toko t ON p.id_toko = t.id_toko
    WHERE p.$col_pembeli = '$user_id'
    ORDER BY p.waktu_pesan DESC
");

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
        $q_pay = mysqli_query($db, "SELECT status, metode FROM pembayaran WHERE id_pesanan = $id_pesanan LIMIT 1");
        $pay = mysqli_fetch_assoc($q_pay) ?? ['status' => 'belum_bayar', 'metode' => 'tunai'];
        $r['pembayaran'] = $pay;

        $pesanan_list[] = $r;
    }
}
?>
<!-- ═══════ SECTION: PESANAN ═══════ -->
<div class="page-section" id="section-pesanan">
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
            </style>
            
            <div class="pesanan-container">
                <?php foreach ($pesanan_list as $pesanan): 
                    $badge_class = 'status-' . $pesanan['status'];
                    $status_text = $pesanan['status'];
                    if ($pesanan['status'] === 'menunggu') $status_text = 'Menunggu Konfirmasi';
                    elseif ($pesanan['status'] === 'diproses') $status_text = 'Sedang Disiapkan';
                    elseif ($pesanan['status'] === 'siap') $status_text = 'Siap Diambil';
                    
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
                        
                        <div class="pesanan-card-footer">
                            <div class="pesanan-total-info">
                                <span class="pesanan-total-label">Total Pembayaran</span>
                                <span class="pesanan-total-val">Rp. <?= number_format($pesanan['total_harga'], 0, ',', '.') ?></span>
                            </div>
                            <div class="pesanan-footer-right">
                                <?php if ($is_lunas): ?>
                                    <span class="pesanan-payment-status pay-lunas">
                                        <i class="fa-solid fa-circle-check"></i> Lunas
                                        <small style="color: #64748b; font-weight: 500; margin-left: 2px;">(<?= $pesanan['pembayaran']['metode'] === 'transfer' ? 'QRIS' : 'Tunai' ?>)</small>
                                    </span>
                                <?php else: ?>
                                    <span class="pesanan-payment-status pay-belum_bayar">
                                        <i class="fa-solid fa-circle-xmark"></i> Belum Bayar
                                        <small style="color: #64748b; font-weight: 500; margin-left: 2px;">(<?= $pesanan['pembayaran']['metode'] === 'transfer' ? 'QRIS' : 'Tunai' ?>)</small>
                                    </span>
                                <?php endif; ?>
                                
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
