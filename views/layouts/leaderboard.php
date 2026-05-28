<?php
// Ambil data kantin
$rowsKantin = mysqli_fetch_all(mysqli_query(
    $conn,
    "SELECT t.id_toko as id, t.nama_toko as nama, t.foto_toko as foto,
    COUNT(DISTINCT p.nisn_pembeli) + COUNT(DISTINCT p.nuptk_pembeli) as total_pembeli
    FROM toko t
    LEFT JOIN pesanan p ON p.id_toko = t.id_toko
    WHERE t.deleted_at IS NULL
    GROUP BY t.id_toko
    ORDER BY total_pembeli DESC"
), MYSQLI_ASSOC);

// Ambil data menu
$rowsMenu = mysqli_fetch_all(mysqli_query(
    $conn,
    "SELECT m.id_menu as id, m.nama_menu as nama, m.foto_menu as foto,
    COUNT(dp.id_detail_pesanan) as total_pembeli
    FROM menu m
    LEFT JOIN detail_pesanan dp ON dp.id_menu = m.id_menu
    WHERE m.deleted_at IS NULL
    GROUP BY m.id_menu
    ORDER BY total_pembeli DESC"
), MYSQLI_ASSOC);

function renderLb($rows, $type)
{
    $top3 = array_slice($rows, 0, 3);
    $podium_order = [1, 0, 2];
    ob_start();
    ?>
    <div class="podium">
        <?php foreach ($podium_order as $i):
            if (!isset($top3[$i]))
                continue;
            $k = $top3[$i];
            $rank = $i + 1; ?>
            <div class="pod">
                <div class="pod-photo rank-<?= $rank ?>">
                    <?php if (!empty($k['foto'])): ?>
                        <img src="./assets/img/<?= $type ?>/<?= htmlspecialchars($k['foto']) ?>" alt="">
                    <?php else: ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none"
                            stroke="#d1d5db" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l1-5h16l1 5" />
                            <path d="M3 9a2 2 0 0 0 2 2 2 2 0 0 0 2-2 2 2 0 0 0 2 2 2 2 0 0 0 2-2 2 2 0 0 0 2 2 2 2 0 0 0 2-2" />
                            <path d="M5 11v8a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-8" />
                            <line x1="10" y1="15" x2="14" y2="15" />
                        </svg>
                    <?php endif; ?>
                </div>
                <div class="pod-stand rank-<?= $rank ?>"><?= $rank ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="lb-table-wrap">
        <table class="lb-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama <?= $type === 'menu' ? 'Menu' : 'Kantin' ?></th>
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
    <?php
    return ob_get_clean();
}
?>

<div class="lb-header">
    <span class="section-label">Leaderboard</span>
    <select class="lb-select" onchange="gantiFilterLb(this.value)">
        <option value="kantin">Kantin</option>
        <option value="menu">Menu</option>
    </select>
</div>

<div class="lb-body">
    <div id="lb-kantin">
        <?= renderLb($rowsKantin, 'kantin') ?>
    </div>
    <div id="lb-menu" style="display:none">
        <?= renderLb($rowsMenu, 'menu') ?>
    </div>
</div>

<script>
    function gantiFilterLb(filter) {
        document.getElementById('lb-kantin').style.display = filter === 'kantin' ? 'flex' : 'none';
        document.getElementById('lb-menu').style.display = filter === 'menu' ? 'flex' : 'none';
    }
</script>