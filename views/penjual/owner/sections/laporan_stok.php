<?php
// views/penjual/owner/sections/laporan_stok.php

/** @var mysqli $conn */
/** @var int $idToko */
/** @var string $base_path */

// Ambil semua menu toko yang aktif untuk menghitung statistik keseluruhan
$allMenusQuery = mysqli_query($conn, "SELECT * FROM menu WHERE id_toko = $idToko AND deleted_at IS NULL");
$allMenus = mysqli_fetch_all($allMenusQuery, MYSQLI_ASSOC);

$totalItems = count($allMenus);
$stokAman = 0;
$stokMenipis = 0;
$stokHabis = 0;
$totalQtyStok = 0;

foreach ($allMenus as $m) {
    $stk = (int) $m['stok'];
    $totalQtyStok += $stk;
    if ($stk == 0) {
        $stokHabis++;
    } elseif ($stk <= 10) {
        $stokMenipis++;
    } else {
        $stokAman++;
    }
}

// Tangkap filter dari URL/GET request
$searchStok = trim($_GET['search_stok'] ?? '');
$kategoriStok = $_GET['kategori_stok'] ?? 'semua';
$statusStok = $_GET['status_stok'] ?? 'semua';

// Query filter untuk tabel
$sqlFilter = "SELECT * FROM menu WHERE id_toko = $idToko AND deleted_at IS NULL";

if ($searchStok !== '') {
    $searchEscaped = mysqli_real_escape_string($conn, $searchStok);
    $sqlFilter .= " AND nama_menu LIKE '%$searchEscaped%'";
}

if ($kategoriStok !== 'semua') {
    $katEscaped = mysqli_real_escape_string($conn, $kategoriStok);
    $sqlFilter .= " AND kategori = '$katEscaped'";
}

if ($statusStok === 'aman') {
    $sqlFilter .= " AND stok > 10";
} elseif ($statusStok === 'menipis') {
    $sqlFilter .= " AND stok <= 10 AND stok > 0";
} elseif ($statusStok === 'habis') {
    $sqlFilter .= " AND stok = 0";
}

$sqlFilter .= " ORDER BY nama_menu ASC";
$menusTabel = mysqli_fetch_all(mysqli_query($conn, $sqlFilter), MYSQLI_ASSOC);
?>

<style>
/* Styling for Stock Report Inline Controls */
.stock-control-group {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #f1f5f9;
    padding: 3px;
    border-radius: 8px;
    border: 1.5px solid #cbd5e1;
    vertical-align: middle;
}
.stock-btn {
    width: 26px;
    height: 26px;
    border-radius: 6px;
    border: none;
    background: #fff;
    color: #475569;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    transition: all 0.2s;
    padding: 0;
}
.stock-btn:hover {
    background: #cbd5e1;
    color: #1e293b;
}
.stock-btn:active {
    transform: scale(0.9);
}
.stock-input {
    width: 42px;
    text-align: center;
    border: none;
    background: transparent;
    font-weight: 700;
    color: #1e293b;
    font-size: 13.5px;
    outline: none;
    padding: 0;
}
.stock-input::-webkit-outer-spin-button,
.stock-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
.stock-input[type=number] {
    -moz-appearance: textfield;
}
.btn-quick-update {
    padding: 6px 10px;
    font-size: 11.5px;
    font-weight: 700;
    color: #fff;
    background: #5aab55;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 1px 3px rgba(90,171,85,0.2);
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-left: 6px;
    vertical-align: middle;
}
.btn-quick-update:hover {
    background: #4a9146;
}
.btn-quick-update:disabled {
    background: #94a3b8;
    cursor: not-allowed;
    box-shadow: none;
}
.badge-stock {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-weight: 700;
    font-size: 10.5px;
    padding: 4px 10px;
    border-radius: 20px;
    text-transform: uppercase;
}
.badge-stock.aman {
    background: #e6f4ea;
    color: #137333;
}
.badge-stock.menipis {
    background: #fef3c7;
    color: #d97706;
}
.badge-stock.habis {
    background: #fee2e2;
    color: #dc2626;
}

/* Toast styling for admin */
.admin-toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 99999;
    display: flex;
    flex-direction: column;
    gap: 10px;
    pointer-events: none;
}
.admin-toast {
    background: #fff;
    border-radius: 12px;
    padding: 14px 18px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 12px;
    border: 1px solid #e2e8f0;
    border-left: 5px solid #5aab55;
    pointer-events: auto;
    animation: toastSlideIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    max-width: 320px;
}
.admin-toast.error {
    border-left-color: #ef4444;
}
.admin-toast-icon {
    font-size: 18px;
    color: #5aab55;
}
.admin-toast.error .admin-toast-icon {
    color: #ef4444;
}
.admin-toast-body {
    flex-grow: 1;
}
.admin-toast-title {
    font-weight: 750;
    font-size: 13.5px;
    color: #1e293b;
    margin-bottom: 2px;
}
.admin-toast-message {
    font-size: 12px;
    color: #64748b;
    line-height: 1.4;
}
.admin-toast-close {
    background: none;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    padding: 4px;
    font-size: 14px;
}
.admin-toast-close:hover {
    color: #475569;
}
@keyframes toastSlideIn {
    from { transform: translateX(120%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
@keyframes toastSlideOut {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(120%); opacity: 0; }
}
</style>

<div class="stats-grid col4">
    <div class="stat-card">
        <div class="stat-label">Total Produk</div>
        <div class="stat-row">
            <div class="stat-value" style="color: #3b82f6;"><?= $totalItems ?></div>
            <i class="fa-solid fa-utensils stat-icon" style="color: #3b82f6;"></i>
        </div>
        <div class="stat-desc">Jumlah total jenis menu</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label">Stok Aman</div>
        <div class="stat-row">
            <div class="stat-value" style="color: #10b981;"><?= $stokAman ?></div>
            <i class="fa-solid fa-circle-check stat-icon" style="color: #10b981;"></i>
        </div>
        <div class="stat-desc">Stok produk di atas 10 unit</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Stok Menipis</div>
        <div class="stat-row">
            <div class="stat-value" style="color: #f59e0b;"><?= $stokMenipis ?></div>
            <i class="fa-solid fa-triangle-exclamation stat-icon" style="color: #f59e0b;"></i>
        </div>
        <div class="stat-desc">Stok produk antara 1 - 10 unit</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Stok Habis</div>
        <div class="stat-row">
            <div class="stat-value" style="color: #ef4444;"><?= $stokHabis ?></div>
            <i class="fa-solid fa-circle-xmark stat-icon" style="color: #ef4444;"></i>
        </div>
        <div class="stat-desc">Stok produk kosong (0)</div>
    </div>
</div>

<div class="table-card">
    <div class="table-card-header" style="flex-direction: row; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; border-bottom: none;">
        <h2>Daftar Stok Produk</h2>
        <div style="display: flex; gap: 8px;">
            <button class="btn-primary" onclick="cetakLaporanStok()" style="background: #3498db; box-shadow: 0 2px 8px rgba(52,152,219,0.3); border: none;">
                <i class="fa-solid fa-print"></i> Cetak Laporan
            </button>
        </div>
    </div>

    <form method="GET" action="index.php" class="menu-action-bar" style="padding: 0 24px 15px; margin-bottom: 0;" id="formFilterStok">
        <input type="hidden" name="section" value="laporan_stok">
        
        <div class="search-filter-group" style="width: 100%; flex-wrap: wrap;">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="search_stok" placeholder="Cari nama menu..." value="<?= htmlspecialchars($searchStok) ?>" onchange="document.getElementById('formFilterStok').submit()">
            </div>

            <select class="filter-select" name="kategori_stok" onchange="document.getElementById('formFilterStok').submit()">
                <option value="semua" <?= $kategoriStok === 'semua' ? 'selected' : '' ?>>Semua Kategori</option>
                <option value="makanan" <?= $kategoriStok === 'makanan' ? 'selected' : '' ?>>Makanan</option>
                <option value="minuman" <?= $kategoriStok === 'minuman' ? 'selected' : '' ?>>Minuman</option>
                <option value="snack" <?= $kategoriStok === 'snack' ? 'selected' : '' ?>>Snack</option>
            </select>

            <select class="filter-select" name="status_stok" onchange="document.getElementById('formFilterStok').submit()">
                <option value="semua" <?= $statusStok === 'semua' ? 'selected' : '' ?>>Semua Status Stok</option>
                <option value="aman" <?= $statusStok === 'aman' ? 'selected' : '' ?>>Stok Aman (> 10)</option>
                <option value="menipis" <?= $statusStok === 'menipis' ? 'selected' : '' ?>>Stok Menipis (1-10)</option>
                <option value="habis" <?= $statusStok === 'habis' ? 'selected' : '' ?>>Stok Habis (0)</option>
            </select>
            
            <?php if ($searchStok !== '' || $kategoriStok !== 'semua' || $statusStok !== 'semua'): ?>
                <a href="?section=laporan_stok" class="btn-primary" style="background: #94a3b8; text-decoration: none; padding: 10px 15px; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="fa-solid fa-rotate-left"></i> Reset Filter
                </a>
            <?php endif; ?>
        </div>
    </form>

    <div class="table-scroll">
        <table style="width: 100%;">
            <thead>
                <tr>
                    <th style="width: 60px; text-align: center;">No.</th>
                    <th style="width: 80px; text-align: center;">Gambar</th>
                    <th>Nama Menu</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Status Stok</th>
                    <th style="width: 230px;">Input / Update Stok</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($menusTabel)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 500;">
                            <i class="fa-solid fa-boxes-flat" style="font-size: 28px; display: block; margin-bottom: 8px; color: #cbd5e1;"></i>
                            Tidak ada produk menu yang sesuai dengan filter pencarian stok.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $no = 1;
                    foreach ($menusTabel as $m): 
                        $stk = (int) $m['stok'];
                        $statusClass = 'aman';
                        $statusLabel = 'Aman';
                        $statusIcon = 'fa-circle-check';
                        if ($stk == 0) {
                            $statusClass = 'habis';
                            $statusLabel = 'Habis';
                            $statusIcon = 'fa-circle-xmark';
                        } elseif ($stk <= 10) {
                            $statusClass = 'menipis';
                            $statusLabel = 'Menipis';
                            $statusIcon = 'fa-triangle-exclamation';
                        }
                    ?>
                        <tr id="menu-row-<?= $m['id_menu'] ?>">
                            <td style="text-align: center;"><?= $no++ ?>.</td>
                            <td style="text-align: center;">
                                <?php if (!empty($m['foto_menu'])): ?>
                                    <img src="../../../assets/img/menu/<?= htmlspecialchars($m['foto_menu']) ?>" style="width: 48px; height: 48px; border-radius: 8px; object-fit: cover; border: 1px solid #e2e8f0;">
                                <?php else: ?>
                                    <div style="width: 48px; height: 48px; border-radius: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #94a3b8; border: 1px solid #e2e8f0;">
                                        <i class="fa-solid fa-burger"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars($m['nama_menu']) ?></strong></td>
                            <td style="text-transform: capitalize;"><?= htmlspecialchars($m['kategori']) ?></td>
                            <td>
                                <?php if (isset($m['is_fleksibel']) && $m['is_fleksibel'] == 1): ?>
                                    <span style="color: #0ea5e9; font-size: 11.5px; font-weight: 700; background: #e0f2fe; padding: 2px 6px; border-radius: 4px;">Harga Fleksibel</span>
                                <?php else: ?>
                                    Rp <?= number_format($m['harga'], 0, ',', '.') ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge-stock <?= $statusClass ?>" id="badge-stok-<?= $m['id_menu'] ?>">
                                    <i class="fa-solid <?= $statusIcon ?>" style="margin-right: 4px;"></i>
                                    <span class="label-text"><?= $statusLabel ?></span>
                                </span>
                            </td>
                            <td>
                                <div class="stock-control-group">
                                    <button type="button" class="stock-btn" onclick="adjustStock(<?= $m['id_menu'] ?>, -1)"><i class="fa-solid fa-minus"></i></button>
                                    <input type="number" class="stock-input" id="input-stok-<?= $m['id_menu'] ?>" value="<?= $stk ?>" min="0" oninput="stockInputChanged(<?= $m['id_menu'] ?>)">
                                    <button type="button" class="stock-btn" onclick="adjustStock(<?= $m['id_menu'] ?>, 1)"><i class="fa-solid fa-plus"></i></button>
                                </div>
                                <button type="button" class="btn-quick-update" id="btn-update-<?= $m['id_menu'] ?>" onclick="updateStockAjax(<?= $m['id_menu'] ?>)" disabled>
                                    <i class="fa-solid fa-floppy-disk"></i> Update
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Container Toast Notif -->
<div id="toastContainerStok" class="admin-toast-container"></div>

<script>
function cetakLaporanStok() {
    const search = document.querySelector('input[name="search_stok"]').value;
    const kategori = document.querySelector('select[name="kategori_stok"]').value;
    const status = document.querySelector('select[name="status_stok"]').value;
    window.open('sections/print_stok.php?search_stok=' + encodeURIComponent(search) + '&kategori_stok=' + kategori + '&status_stok=' + status, '_blank');
}

function adjustStock(menuId, amount) {
    const input = document.getElementById('input-stok-' + menuId);
    if (!input) return;
    
    let current = parseInt(input.value) || 0;
    let newValue = Math.max(0, current + amount);
    input.value = newValue;
    
    stockInputChanged(menuId);
}

function stockInputChanged(menuId) {
    const input = document.getElementById('input-stok-' + menuId);
    const btn = document.getElementById('btn-update-' + menuId);
    if (!input || !btn) return;
    
    btn.disabled = false;
}

function showToastStok(title, message, type = 'success') {
    const container = document.getElementById('toastContainerStok');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `admin-toast ${type === 'success' ? '' : 'error'}`;

    const icon = type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';

    toast.innerHTML = `
        <i class="fa-solid ${icon} admin-toast-icon"></i>
        <div class="admin-toast-body">
            <div class="admin-toast-title">${title}</div>
            <div class="admin-toast-message">${message}</div>
        </div>
        <button type="button" class="admin-toast-close" onclick="this.parentElement.remove()"><i class="fa-solid fa-xmark"></i></button>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'toastSlideOut 0.3s cubic-bezier(0.4, 0, 1, 1) forwards';
        toast.addEventListener('animationend', () => {
            toast.remove();
        });
    }, 3500);
}

function updateStockAjax(menuId) {
    const input = document.getElementById('input-stok-' + menuId);
    const btn = document.getElementById('btn-update-' + menuId);
    const badge = document.getElementById('badge-stok-' + menuId);
    
    if (!input || !btn || !badge) return;
    
    const qty = parseInt(input.value) || 0;
    
    // Disable elements during request
    btn.disabled = true;
    const origText = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
    
    const formData = new FormData();
    formData.append('action', 'update_stok');
    formData.append('_ajax', '1');
    formData.append('id_menu', menuId);
    formData.append('stok', qty);
    formData.append('_section', 'laporan_stok');
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.innerHTML = origText;
        if (data.status === 'success') {
            showToastStok('Stok Diupdate', `Berhasil memperbarui stok menu menjadi ${qty}.`, 'success');
            
            // Update Badge status in UI dynamically
            badge.className = 'badge-stock';
            const iconEl = badge.querySelector('i');
            const labelEl = badge.querySelector('.label-text');
            
            if (qty === 0) {
                badge.classList.add('habis');
                iconEl.className = 'fa-solid fa-circle-xmark';
                labelEl.textContent = 'Habis';
            } else if (qty <= 10) {
                badge.classList.add('menipis');
                iconEl.className = 'fa-solid fa-triangle-exclamation';
                labelEl.textContent = 'Menipis';
            } else {
                badge.classList.add('aman');
                iconEl.className = 'fa-solid fa-circle-check';
                labelEl.textContent = 'Aman';
            }
        } else {
            showToastStok('Gagal', data.msg || 'Terjadi kesalahan sistem.', 'error');
            btn.disabled = false;
        }
    })
    .catch(err => {
        console.error(err);
        btn.innerHTML = origText;
        btn.disabled = false;
        showToastStok('Error', 'Gagal terhubung ke server.', 'error');
    });
}
</script>
