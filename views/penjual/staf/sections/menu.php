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
        
        <!-- FIX 4: value konsisten kapital sesuai ENUM -->
        <select class="filter-select" name="kategori" onchange="document.getElementById('formFilterMenu').submit()">
            <option value="semua"   <?= ($_GET['kategori'] ?? 'semua') === 'semua'   ? 'selected' : '' ?>>Semua Kategori</option>
            <option value="Makanan" <?= ($_GET['kategori'] ?? '')       === 'Makanan' ? 'selected' : '' ?>>Makanan</option>
            <option value="Minuman" <?= ($_GET['kategori'] ?? '')       === 'Minuman' ? 'selected' : '' ?>>Minuman</option>
            <option value="Snack"   <?= ($_GET['kategori'] ?? '')       === 'Snack'   ? 'selected' : '' ?>>Snack</option>
        </select>
    </div>
    
    <button type="button" class="btn-primary" 
            style="background-color:#5aab55;color:white;border:none;padding:10px 20px;border-radius:10px;font-weight:bold;cursor:pointer;" 
            onclick="toggleFormTambah()">
        <i class="fa-solid fa-plus"></i> New Item
    </button>
</form>

<h2 style="margin:20px 0;font-size:22px;color:#1f2937;">Menu Listing</h2>

<div class="menu-grid">
    <?php if (empty($daftarMenu)): ?>
        <div style="grid-column:1/-1;text-align:center;color:#9ca3af;padding:40px 0;">
            <i class="fa-solid fa-utensils" style="font-size:40px;margin-bottom:10px;display:block;"></i>
            Belum ada produk di menu ini.
        </div>
    <?php else: foreach ($daftarMenu as $m):
        $stok      = $m['stok'] ?? 0;
        $isTersedia = $stok > 0;

        $badgeMap = [
            'Makanan' => ['color' => '#dcfce7', 'text' => '#166534'],
            'Minuman' => ['color' => '#dbeafe', 'text' => '#1e40af'],
            'Snack'   => ['color' => '#fef9c3', 'text' => '#854d0e'],
        ];
        $kat = ucfirst($m['kategori'] ?? 'Makanan');
        $b   = $badgeMap[$kat] ?? $badgeMap['Makanan'];
    ?>
    <div class="menu-card">
        <div class="menu-img-wrap">
            <?php if (!empty($m['foto_menu'])): ?>
                <img src="../../assets/img/menu/<?= htmlspecialchars($m['foto_menu']) ?>" 
                     alt="Foto Menu" onerror="this.src='../../assets/img/ayam.png'">
            <?php else: ?>
                <img src="../../assets/img/ayam.png" alt="Foto Default">
            <?php endif; ?>
        </div>
        
        <!-- FIX 1: satu blok menu-info saja, sudah include badge kategori -->
        <div class="menu-info">
            <h3 class="menu-title" title="<?= htmlspecialchars($m['nama_menu']) ?>">
                <?= htmlspecialchars($m['nama_menu']) ?>
            </h3>
            <div class="menu-row-flex">
                <span class="menu-price">Rp <?= number_format($m['harga'], 0, ',', '.') ?></span>
                <span class="badge-status-tersedia" style="<?= !$isTersedia ? 'background-color:#fde8e8;color:#9b1c1c;' : '' ?>">
                    <?= $isTersedia ? 'Tersedia' : 'Habis' ?>
                </span>
            </div>
            <span style="display:inline-block;margin-top:4px;padding:2px 10px;border-radius:12px;font-size:11px;
                         background:<?= $b['color'] ?>;color:<?= $b['text'] ?>;">
                <?= $kat ?>
            </span>
        </div>

        <div class="menu-actions">
            <!-- FIX 3: tambah parameter kategori + proteksi karakter khusus (petik) -->
            <button type="button" class="btn-outline-edit" 
                onclick="bukaFormEdit(
                    <?= $m['id_menu'] ?>, 
                    '<?= htmlspecialchars(addslashes($m['nama_menu']), ENT_QUOTES, 'UTF-8') ?>', 
                    <?= $m['harga'] ?>, 
                    <?= $m['stok'] ?>,
                    '<?= htmlspecialchars(addslashes($kat), ENT_QUOTES, 'UTF-8') ?>'
                )">
                <i class="fa-solid fa-pen"></i> Edit
            </button>
            <form method="POST" style="display:inline" 
                  onsubmit="return confirm('Hapus menu <?= htmlspecialchars(addslashes($m['nama_menu'])) ?>?')">
                <input type="hidden" name="_section" value="menu">
                <input type="hidden" name="action" value="hapus_menu">
                <input type="hidden" name="id_menu" value="<?= $m['id_menu'] ?>">
                <button type="submit" class="btn-outline-delete">
                    <i class="fa-solid fa-trash"></i> Hapus
                </button>
            </form>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div>

<!-- Form Tambah Menu -->
<div id="containerTambahMenu" class="form-tambah-container toggle-form">
    <div class="form-tambah-header">
        <i class="fa-solid fa-circle-plus" style="color:#5aab55;font-size:20px;"></i>
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
            <!-- FIX 2: field kategori di form tambah -->
            <div class="form-group">
                <label for="kategori_tambah">Kategori</label>
                <select id="kategori_tambah" name="kategori" class="filter-select" required>
                    <option value="Makanan">Makanan</option>
                    <option value="Minuman">Minuman</option>
                    <option value="Snack">Snack</option>
                </select>
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

<!-- Form Edit Menu -->
<div id="containerEditMenu" class="form-tambah-container toggle-form">
    <div class="form-tambah-header">
        <i class="fa-solid fa-pen" style="color:#5aab55;font-size:20px"></i>
        <h3>Form Edit Menu</h3>
    </div>
    <form action="" method="POST" enctype="multipart/form-data" class="static-form">
        <input type="hidden" name="_section" value="menu">
        <input type="hidden" name="action" value="edit_menu">
        <input type="hidden" name="id_menu" id="editIdMenu">
        <div class="form-grid-layout">
            <div class="form-group">
                <label>Nama Menu</label>
                <input type="text" name="nama_menu" id="editNamaMenu" required>
            </div>
            <div class="form-group">
                <label>Harga (Rp)</label>
                <input type="number" name="harga" id="editHarga" min="0" max="99999" required>
            </div>
            <div class="form-group">
                <label>Stok</label>
                <input type="number" name="stok" id="editStok" min="0" required>
            </div>
            <div class="form-group">
                <label>Kategori</label>
                <select name="kategori" id="editKategori" class="filter-select" required>
                    <option value="Makanan">Makanan</option>
                    <option value="Minuman">Minuman</option>
                    <option value="Snack">Snack</option>
                </select>
            </div>
            <div class="form-group">
                <label>Foto Baru (opsional)</label>
                <input type="file" name="foto" accept="image/*">
            </div>
        </div>
        <div class="form-tambah-footer">
            <button type="button" class="btn-reset" onclick="tutupFormEdit()">Batal</button>
            <button type="submit" class="btn-save">
                <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
            </button>
        </div>
    </form>
</div>

<script>
function toggleFormTambah() {
    const formBox = document.getElementById('containerTambahMenu');
    tutupFormEdit();
    formBox.classList.toggle('show');
    if (formBox.classList.contains('show')) {
        formBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        document.getElementById('nama_menu').focus();
    } else {
        formBox.querySelector('form').reset();
    }
}

// FIX 3: terima parameter kategori, set ke select
function bukaFormEdit(id, nama, harga, stok, kategori) {
    const formBox = document.getElementById('containerEditMenu');
    document.getElementById('containerTambahMenu').classList.remove('show');
    document.getElementById('editIdMenu').value    = id;
    document.getElementById('editNamaMenu').value  = nama;
    document.getElementById('editHarga').value     = harga;
    document.getElementById('editStok').value      = stok;
    document.getElementById('editKategori').value  = kategori;
    formBox.classList.add('show');
    formBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function tutupFormEdit() {
    const formBox = document.getElementById('containerEditMenu');
    formBox.classList.remove('show');
    formBox.querySelector('form').reset();
}
</script>