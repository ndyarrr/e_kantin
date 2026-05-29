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
                        <?php
                        $nama = strtolower($k['nama']);
                        if ($type === 'menu') {
                            if (str_contains($nama, 'minum') || str_contains($nama, 'es') || str_contains($nama, 'jus') || str_contains($nama, 'teh') || str_contains($nama, 'kopi')) {
                                $icon_fill = '#1890ff';
                                $icon_path = 'M3 2l2.01 18.23C5.13 21.23 5.97 22 7 22h10c1.03 0 1.87-.77 1.99-1.77L21 2H3zm9 17c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm1-9H8V8h5v2z';
                                $bg = '#eff6ff';
                                $border = '#bfdbfe';
                            } elseif (str_contains($nama, 'snack') || str_contains($nama, 'gorengan') || str_contains($nama, 'keripik')) {
                                $icon_fill = '#9254de';
                                $icon_path = 'M18.06 22.99h1.66c.84 0 1.53-.64 1.63-1.46L23 5.05h-5V1h-1.97v4.05h-4.97l.3 2.34c1.71.47 3.31 1.32 4.27 2.26 1.44 1.42 2.43 2.89 2.43 5.29v8.05zM1 21.99V21h15.03v.99c0 .55-.45 1-1.01 1H2.01c-.56 0-1.01-.45-1.01-1zm15.03-7c0-4.5-6.72-5-8.99-5-2.28 0-9.03.5-9.03 5h18.02z';
                                $bg = '#f5f3ff';
                                $border = '#ddd6fe';
                            } else {
                                $icon_fill = '#ff7a45';
                                $icon_path = 'M11 9H9V2H7v7H5V2H3v7c0 2.12 1.66 3.84 3.75 3.97V22h2.5v-9.03C11.34 12.84 13 11.12 13 9V2h-2v7zm5-3v8h2.5v8H21V2c-2.76 0-5 2.24-5 4z';
                                $bg = '#fff2e8';
                                $border = '#fed7aa';
                            }
                            echo "<div style='width:100%;height:100%;background:{$bg};border:2px solid {$border};display:flex;align-items:center;justify-content:center;'>
            <svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' style='width:32px;height:32px;fill:{$icon_fill};'>
                <path d='{$icon_path}'/>
            </svg>
        </div>";
                        } else {
                            // fallback kantin — icon toko
                            echo "<div style='width:100%;height:100%;background:#f3f4f6;display:flex;align-items:center;justify-content:center;'>
            <svg xmlns='http://www.w3.org/2000/svg' width='36' height='36' viewBox='0 0 24 24' fill='none' stroke='#9ca3af' stroke-width='1.2' stroke-linecap='round' stroke-linejoin='round'>
                <path d='M3 9l1-5h16l1 5'/>
                <path d='M3 9a2 2 0 0 0 2 2 2 2 0 0 0 2-2 2 2 0 0 0 2 2 2 2 0 0 0 2-2 2 2 0 0 0 2 2 2 2 0 0 0 2-2'/>
                <path d='M5 11v8a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-8'/>
                <line x1='10' y1='15' x2='14' y2='15'/>
            </svg>
        </div>";
                        }
                        ?>
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