<?php
// kantin.php
$kantins = mysqli_fetch_all(mysqli_query(
    $conn,
    "SELECT t.id_toko, t.nama_toko, t.deskripsi, t.foto_toko,
            COUNT(DISTINCT tp.id_penjual) as jumlah_penjual
    FROM toko t
    LEFT JOIN toko_penjual tp ON tp.id_toko = t.id_toko AND tp.status = 'aktif'
    GROUP BY t.id_toko
    ORDER BY t.dibuat_pada ASC"
), MYSQLI_ASSOC);
?>

<div class="section-label">List Kantin</div>
<div class="kantin-grid">
    <?php foreach ($kantins as $kantin): ?>
        <div class="kantin-card">
            <?php if (!empty($kantin['foto_toko'])): ?>
                <img class="kantin-card-img" src="./assets/img/kantin/<?= htmlspecialchars($kantin['foto_toko']) ?>"
                    alt="<?= htmlspecialchars($kantin['nama_toko']) ?>">
            <?php else: ?>
                <div class="kantin-card-img-placeholder"></div>
            <?php endif; ?>
            <div class="kantin-card-body">
                <span class="kantin-card-badge"><?= htmlspecialchars($kantin['nama_toko']) ?></span>
                <p class="kantin-card-desc"><?= htmlspecialchars($kantin['deskripsi'] ?? '-') ?></p>
                <button class="btn-selengkap"
                    onclick="window.location.href='./kantin/detail.php?id=<?= $kantin['id_toko'] ?>'">
                    Selengkapnya
                </button>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (empty($kantins)): ?>
        <p style="color:#888;padding:20px">Belum ada kantin yang buka.</p>
    <?php endif; ?>
</div>