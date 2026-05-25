<?php
// views/penjual/owner/sections/pesanan.php

// DUMMY PESANAN PINTAR TERINTEGRASI ID DATABASE
$pesananMasuk = [
    [
        'id_pesanan' => 991, 
        'nomor_antrean' => 'A-05',
        'nama_pembeli' => 'Asep Pembalap',
        'nama_menu' => 'BMW IJO', 
        'id_menu' => 6, 
        'jumlah' => 1,
        'total_harga' => 10000,
        'catatan' => 'Bang, setir kanan ya, mau balapan di Ngawi.',
        'status' => 'pending'
    ],
    [
        'id_pesanan' => 992,
        'nomor_antrean' => 'A-06',
        'nama_pembeli' => 'Mas Rusdi',
        'nama_menu' => 'BMW BIRU',
        'id_menu' => 7, 
        'jumlah' => 2,
        'total_harga' => 20000,
        'catatan' => 'Kirim pake Khodam Sugeng Rahayu.',
        'status' => 'pending'
    ]
];
?>

<div class="inbox-container">
    <h2><i class="fa-solid fa-tray"></i> Antrean Pesanan</h2>

    <div class="pesanan-grid">
        <?php foreach ($pesananMasuk as $p): ?>
        <div class="pesanan-card">
            
            <div class="pesanan-header">
                <div>
                    <span class="antrean-num"><?= $p['nomor_antrean'] ?></span>
                    <h4><?= htmlspecialchars($p['nama_pembeli']) ?></h4>
                </div>
                <span class="status-badge"><?= $p['status'] ?></span>
            </div>

            <div class="pesanan-body">
                <p class="menu-item">
                    <?= htmlspecialchars($p['nama_menu']) ?> <span class="menu-qty">x<?= $p['jumlah'] ?></span>
                </p>
                <?php if (!empty($p['catatan'])): ?>
                    <p class="pesanan-catatan">
                        <i class="fa-solid fa-comment-dots"></i> "<?= htmlspecialchars($p['catatan']) ?>"
                    </p>
                <?php endif; ?>
            </div>

            <div>
                <div class="pesanan-footer-info">
                    <span class="label">Total Bayar:</span>
                    <span class="total">Rp <?= number_format($p['total_harga'], 0, ',', '.') ?></span>
                </div>

                <form method="POST" action="index.php">
                    <input type="hidden" name="action" value="selesaikan_pesanan">
                    <input type="hidden" name="id_pesanan" value="<?= $p['id_pesanan'] ?>">
                    <input type="hidden" name="id_menu" value="<?= $p['id_menu'] ?>">
                    <input type="hidden" name="jumlah_beli" value="<?= $p['jumlah'] ?>">
                    <input type="hidden" name="_section" value="pesanan">
                    
                    <button type="submit" class="btn-selesai-submit">
                        <i class="fa-solid fa-circle-check"></i> Selesaikan Pesanan
                    </button>
                </form>
            </div>

        </div>
        <?php endforeach; ?>
    </div>
</div>