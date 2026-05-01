<?php
// leaderboard.php
// TODO: ganti query ini sesuai struktur DB lo
/*
    Contoh struktur tabel yang diasumsikan:
    - tabel: kantin, orders (atau transaksi)
    - kolom: kantin.id, kantin.nama, kantin.foto, COUNT(orders.id) as total_pembeli
*/

// $db = ...; // koneksi DB lo
// $query = "
//     SELECT k.id, k.nama, k.foto, COUNT(o.id) as total_pembeli
//     FROM kantin k
//     LEFT JOIN orders o ON o.kantin_id = k.id
//     GROUP BY k.id
//     ORDER BY total_pembeli DESC
//     LIMIT 10
// ";
// $rows = $db->query($query)->fetchAll();

// DUMMY DATA — hapus kalau udah ada DB
$rows = [
    ['id' => 1, 'nama' => 'Pak Fajar', 'foto' => null, 'total_pembeli' => 36],
    ['id' => 2, 'nama' => 'Pak Fajar', 'foto' => null, 'total_pembeli' => 36],
    ['id' => 3, 'nama' => 'Pak Fajar', 'foto' => null, 'total_pembeli' => 36],
    ['id' => 4, 'nama' => 'Pak Fajar', 'foto' => null, 'total_pembeli' => 36],
    ['id' => 5, 'nama' => 'Pak Fajar', 'foto' => null, 'total_pembeli' => 36],
    ['id' => 6, 'nama' => 'Pak Fajar', 'foto' => null, 'total_pembeli' => 36],
];

// ambil top 3 buat podium
$top3 = array_slice($rows, 0, 3);
?>

<div class="lb-header">
    <span class="section-label">Leaderboard</span>
    <select class="lb-select">
        <option value="kantin">Kantin</option>
        <!-- TODO: tambah opsi filter lain kalau perlu -->
    </select>
</div>

<div class="lb-body">

    <!-- PODIUM -->
    <div class="podium">
        <!-- Urutan tampilan: rank 2, rank 1, rank 3 -->
        <?php
        $podium_order = [1, 0, 2]; // index di $top3
        foreach ($podium_order as $i):
            if (!isset($top3[$i]))
                continue;
            $k = $top3[$i];
            $rank = $i + 1;
            ?>
            <div class="pod">
                <div class="pod-photo rank-<?= $rank ?>">
                    <?php if (!empty($k['foto'])): ?>
                        <img src="./assets/img/kantin/<?= htmlspecialchars($k['foto']) ?>"
                            alt="<?= htmlspecialchars($k['nama']) ?>" />
                    <?php endif; ?>
                </div>
                <div class="pod-stand rank-<?= $rank ?>"><?= $rank ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- TABEL -->
    <div class="lb-table-wrap">
        <table class="lb-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Kantin</th>
                    <th>Total Pembeli</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $i => $row): ?>
                    <tr>
                        <td><span class="lb-rank"><?= $i + 1 ?></span></td>
                        <td><?= htmlspecialchars($row['nama']) ?></td>
                        <td class="lb-count"><?= $row['total_pembeli'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>