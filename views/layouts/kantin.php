<?php
// kantin.php
$kantins = mysqli_fetch_all(mysqli_query(
    $conn,
    "SELECT t.id_toko, t.nama_toko, t.deskripsi, t.foto_toko, t.urutan,
            s.nomor AS nomor_lapak,
            COUNT(DISTINCT tp.id_penjual) as jumlah_penjual
    FROM toko t
    LEFT JOIN slot_stand_kantin s ON s.id_toko = t.id_toko
    LEFT JOIN toko_penjual tp ON tp.id_toko = t.id_toko AND tp.status = 'aktif'
    WHERE t.deleted_at IS NULL
    GROUP BY t.id_toko
    ORDER BY COALESCE(s.nomor, t.urutan + 1) ASC, t.dibuat_pada ASC"
), MYSQLI_ASSOC);
?>

<div class="section-label">List Kantin</div>
<div class="kantin-grid">
    <?php foreach ($kantins as $kantin):
        $nomor_lapak = (int) ($kantin['nomor_lapak'] ?? 0);
        if ($nomor_lapak < 1) {
            $nomor_lapak = (int) ($kantin['urutan'] ?? 0) + 1;
        }
    ?>
        <div class="kantin-card">
            <?php if (!empty($kantin['foto_toko'])): ?>
                <img class="kantin-card-img" src="./assets/img/kantin/<?= htmlspecialchars($kantin['foto_toko']) ?>"
                    alt="<?= htmlspecialchars($kantin['nama_toko']) ?>">
            <?php else: ?>
                <div class="kantin-card-img-placeholder">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none"
                        stroke="#d1d5db" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l1-5h16l1 5" />
                        <path d="M3 9a2 2 0 0 0 2 2 2 2 0 0 0 2-2 2 2 0 0 0 2 2 2 2 0 0 0 2-2 2 2 0 0 0 2 2 2 2 0 0 0 2-2" />
                        <path d="M5 11v8a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-8" />
                        <line x1="10" y1="15" x2="14" y2="15" />
                    </svg>
                </div>
            <?php endif; ?>
            <div class="kantin-card-body">
                <span class="kantin-card-badge"><?= htmlspecialchars($kantin['nama_toko']) ?></span>
                <p class="kantin-card-desc"><?= htmlspecialchars($kantin['deskripsi'] ?? '-') ?></p>
                <span class="lapak-badge">lapak no.<?= $nomor_lapak ?></span>
                <button class="btn-selengkap" onclick="bukaModal('<?= $kantin['id_toko'] ?>')">
                    Selengkapnya
                </button>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (empty($kantins)): ?>
        <p style="color:#888;padding:20px">Belum ada kantin yang buka.</p>
    <?php endif; ?>
</div>