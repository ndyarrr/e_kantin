<?php 
/** @var array $daftarMenu */
?>

<form method="GET" action="" class="menu-action-bar" id="formFilterMenu">
    <input type="hidden" name="section" value="menu">
    
    <div class="search-filter-group">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="search" placeholder="Cari menu..." 
                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" 
                   onchange="document.getElementById('formFilterMenu').submit()">
        </div>
        
        <select class="filter-select" name="kategori" onchange="document.getElementById('formFilterMenu').submit()">
            <option value="semua" <?= ($_GET['kategori'] ?? '') === 'semua' ? 'selected' : '' ?>>Semua Kategori</option>
            <option value="makanan" <?= ($_GET['kategori'] ?? '') === 'makanan' ? 'selected' : '' ?>>Makanan</option>
            <option value="minuman" <?= ($_GET['kategori'] ?? '') === 'minuman' ? 'selected' : '' ?>>Minuman</option>
        </select>
    </div>
    
    <button type="button" class="btn-primary" style="background-color: #5aab55; color: white; border: none; padding: 10px 20px; border-radius: 10px; font-weight: bold; cursor: pointer;" onclick="toggleFormTambah()">
        <i class="fa-solid fa-plus"></i> New Item
    </button>
</form>

<h2 style="margin: 20px 0; font-size: 22px; color: #1f2937;">Menu Listing</h2>

<div class="menu-grid">
    <?php if (empty($daftarMenu)): ?>
        <div style="grid-column: 1/-1; text-align: center; color: #9ca3af; padding: 40px 0;">
            <i class="fa-solid fa-utensils" style="font-size: 40px; margin-bottom: 10px; display: block;"></i>
            Belum ada produk di menu ini.
        </div>
    <?php else: foreach ($daftarMenu as $m): 
        $namaMenuLower = strtolower($m['nama_menu']);
        $isMinuman = (strpos($namaMenuLower, 'teh') !== false || strpos($namaMenuLower, 'es') !== false || strpos($namaMenuLower, 'minum') !== false || strpos($namaMenuLower, 'jus') !== false);
        
        $stok = $m['stok'] ?? 1; 
        $isTersedia = $stok > 0;
    ?>
    <div class="menu-card">
        <div class="menu-img-wrap">
            <?php if (!empty($m['foto'])): ?>
                <img src="../../assets/img/menu/<?= htmlspecialchars($m['foto']) ?>" alt="Foto Menu" onerror="this.src='../../assets/img/ayam.png'">
            <?php else: ?>
                <img src="../../assets/img/ayam.png" alt="Foto Default">
            <?php endif; ?>
        </div>
        
        <div class="menu-info">
            <h3 class="menu-title" title="<?= htmlspecialchars($m['nama_menu']) ?>">
                <?= htmlspecialchars($m['nama_menu']) ?>
            </h3>
            <div class="menu-row-flex">
                <span class="menu-price">Rp <?= number_format($m['harga'], 0, ',', '.') ?></span>
                <span class="badge-status-tersedia" style="<?= !$isTersedia ? 'background-color:#fde8e8; color:#9b1c1c;' : '' ?>">
                    <?= $isTersedia ? 'Tersedia' : 'Habis' ?>
                </span>
            </div>
        </div>
        
        <div class="menu-actions">
            <button type="button" class="btn-outline-edit" onclick="alert('Edit ID: <?= $m['id_menu'] ?>')"><i class="fa-solid fa-pen"></i> Edit</button>
            <button type="button" class="btn-outline-delete" onclick="confirm('Hapus menu ini?') ? alert('Proses Hapus ID: <?= $m['id_menu'] ?>') : ''"><i class="fa-solid fa-trash"></i> Hapus</button>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div>

<div id="containerTambahMenu" class="form-tambah-container toggle-form">
    <div class="form-tambah-header">
        <i class="fa-solid fa-circle-plus" style="color: #5aab55; font-size: 20px;"></i>
        <h3>Form Tambah Menu Baru</h3>
    </div>
    
    <form action="" method="POST" enctype="multipart/form-data" class="static-form">
        <input type="hidden" name="_section" value="menu">
        <input type="hidden" name="action" value="tambah_menu">
        
        <div class="form-grid-layout">
            <div class="form-group">
                <label for="nama_menu">Nama Menu</label>
                <input type="text" id="nama_menu" name="nama_menu" placeholder="Contoh: Nasi Goreng Gila" required>
            </div>
            
            <div class="form-group">
                <label for="harga_menu">Harga (Rp)</label>
                <input type="number" id="harga_menu" name="harga" placeholder="Contoh: 12000" min="0" max="99999" required>
            </div>
            
            <div class="form-group">
                <label for="stok_menu">Stok Awal</label>
                <input type="number" id="stok_menu" name="stok" placeholder="Contoh: 50" min="0" value="50" required>
            </div>
            
            <div class="form-group">
                <label for="foto_menu">Foto Menu</label>
                <input type="file" id="foto_menu" name="foto" accept="image/*">
            </div>
        </div>
        
        <div class="form-tambah-footer">
            <button type="button" class="btn-reset" onclick="toggleFormTambah()">Batal</button>
            <button type="submit" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> Simpan Menu</button>
        </div>
    </form>
</div>

<script>
function toggleFormTambah() {
    const formBox = document.getElementById('containerTambahMenu');
    
    // Toggle class 'show' untuk memunculkan/menyembunyikan
    formBox.classList.toggle('show');
    
    if (formBox.classList.contains('show')) {
        // Efek scroll otomatis ke arah form biar user tau letak inputannya
        formBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        document.getElementById('nama_menu').focus();
    } else {
        // Reset isi form jika ditutup/batal
        formBox.querySelector('form').reset();
    }
}
</script>