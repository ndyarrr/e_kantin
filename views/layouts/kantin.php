<?php
// kantin.php
// TODO: ganti query ini sesuai struktur DB lo
/*
    Contoh struktur tabel yang diasumsikan:
    - tabel: kantin
    - kolom: id, nama, deskripsi, foto (nama file), dll
*/

// $db = ...; // koneksi DB lo
// $query = "SELECT * FROM kantin";
// $kantins = $db->query($query)->fetchAll();

// DUMMY DATA — hapus kalau udah ada DB
$kantins = [
    ['id' => 1, 'nama' => 'Pak Fajar', 'deskripsi' => 'Kantin dengan pembeli terbanyak', 'foto' => null],
    ['id' => 2, 'nama' => 'Bu Sari', 'deskripsi' => 'Kantin dengan pembeli terbanyak', 'foto' => null],
    ['id' => 3, 'nama' => 'Pak Budi', 'deskripsi' => 'Kantin dengan pembeli terbanyak', 'foto' => null],
    ['id' => 4, 'nama' => 'Bu Dewi', 'deskripsi' => 'Kantin dengan pembeli terbanyak', 'foto' => null],
];
?>

<div class="section-label">List Kantin</div>
<div class="kantin-grid">
    <?php foreach ($kantins as $kantin): ?>
        <div class="kantin-card">

            <?php if (!empty($kantin['foto'])): ?>
                <img class="kantin-card-img" src="./assets/img/kantin/<?= htmlspecialchars($kantin['foto']) ?>"
                    alt="<?= htmlspecialchars($kantin['nama']) ?>" />
            <?php else: ?>
                <div class="kantin-card-img-placeholder"></div>
            <?php endif; ?>

            <div class="kantin-card-body">
                <span class="kantin-card-badge"><?= htmlspecialchars($kantin['nama']) ?></span>
                <p class="kantin-card-desc"><?= htmlspecialchars($kantin['deskripsi']) ?></p>
                <button class="btn-selengkap" onclick="window.location.href='./kantin/detail.php?id=<?= $kantin['id'] ?>'">
                    Selengkapnya
                </button>
            </div>
        </div>
    <?php endforeach; ?>
</div>