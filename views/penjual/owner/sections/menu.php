<?php
/** @var array $daftarMenu */
?>

<form method="GET" action="index.php" class="menu-action-bar" id="formFilterMenu">
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
            <option value="snack" <?= ($_GET['kategori'] ?? '') === 'snack' ? 'selected' : '' ?>>Snack</option>
        </select>
    </div>

    <button type="button" class="btn-primary" onclick="toggleFormTambah()">
        <i class="fa-solid fa-plus"></i> New Item
    </button>
</form>

<h2 class="menu-listing-title">Menu Listing</h2>

<div class="menu-grid">
    <?php if (empty($daftarMenu)): ?>
        <div class="menu-empty-state">
            <i class="fa-solid fa-utensils"></i>
            <p>Belum ada produk di menu ini.</p>
        </div>
    <?php else:
        foreach ($daftarMenu as $m):
            $stok = (int) ($m['stok'] ?? 0);
            $isTersedia = $stok > 0;
            $kat = strtolower($m['kategori'] ?? 'makanan');
            ?>
            <div class="menu-card <?= !$isTersedia ? 'card-habis' : '' ?>">

                <div class="menu-img-wrap">
                    <?php if (!empty($m['foto_menu'])): ?>
                        <img src="../../../assets/img/menu/<?= htmlspecialchars($m['foto_menu']) ?>" alt="Foto Menu"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="menu-img-placeholder <?= $kat ?>" style="display:none;">
                            <?php if ($kat === 'minuman'): ?>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                    <path
                                        d="M3 2l2.01 18.23C5.13 21.23 5.97 22 7 22h10c1.03 0 1.87-.77 1.99-1.77L21 2H3zm9 17c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm1-9H8V8h5v2z" />
                                </svg>
                            <?php elseif ($kat === 'snack'): ?>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                    <path
                                        d="M18.06 22.99h1.66c.84 0 1.53-.64 1.63-1.46L23 5.05h-5V1h-1.97v4.05h-4.97l.3 2.34c1.71.47 3.31 1.32 4.27 2.26 1.44 1.42 2.43 2.89 2.43 5.29v8.05zM1 21.99V21h15.03v.99c0 .55-.45 1-1.01 1H2.01c-.56 0-1.01-.45-1.01-1zm15.03-7c0-4.5-6.72-5-8.99-5-2.28 0-9.03.5-9.03 5h18.02z" />
                                </svg>
                            <?php else: ?>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                    <path
                                        d="M11 9H9V2H7v7H5V2H3v7c0 2.12 1.66 3.84 3.75 3.97V22h2.5v-9.03C11.34 12.84 13 11.12 13 9V2h-2v7zm5-3v8h2.5v8H21V2c-2.76 0-5 2.24-5 4z" />
                                </svg>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="menu-img-placeholder <?= $kat ?>">
                            <?php if ($kat === 'minuman'): ?>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                    <path
                                        d="M3 2l2.01 18.23C5.13 21.23 5.97 22 7 22h10c1.03 0 1.87-.77 1.99-1.77L21 2H3zm9 17c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm1-9H8V8h5v2z" />
                                </svg>
                            <?php elseif ($kat === 'snack'): ?>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                    <path
                                        d="M18.06 22.99h1.66c.84 0 1.53-.64 1.63-1.46L23 5.05h-5V1h-1.97v4.05h-4.97l.3 2.34c1.71.47 3.31 1.32 4.27 2.26 1.44 1.42 2.43 2.89 2.43 5.29v8.05zM1 21.99V21h15.03v.99c0 .55-.45 1-1.01 1H2.01c-.56 0-1.01-.45-1.01-1zm15.03-7c0-4.5-6.72-5-8.99-5-2.28 0-9.03.5-9.03 5h18.02z" />
                                </svg>
                            <?php else: ?>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                    <path
                                        d="M11 9H9V2H7v7H5V2H3v7c0 2.12 1.66 3.84 3.75 3.97V22h2.5v-9.03C11.34 12.84 13 11.12 13 9V2h-2v7zm5-3v8h2.5v8H21V2c-2.76 0-5 2.24-5 4z" />
                                </svg>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <span class="badge-status <?= $isTersedia ? 'bg-green' : 'bg-red' ?>">
                        <?= $isTersedia ? 'Tersedia' : 'Habis' ?>
                    </span>
                </div>

                <div class="menu-info">
                    <span class="menu-kategori">
                        <?php if ($kat === 'minuman'): ?>
                            <i class="fa-solid fa-glass-water"></i>
                        <?php elseif ($kat === 'snack'): ?>
                            <i class="fa-solid fa-cookie-bite"></i>
                        <?php else: ?>
                            <i class="fa-solid fa-bowl-food"></i>
                        <?php endif; ?>
                        <?= htmlspecialchars($kat) ?>
                    </span>

                    <h3 class="menu-title" title="<?= htmlspecialchars($m['nama_menu']) ?>">
                        <?= htmlspecialchars($m['nama_menu']) ?>
                    </h3>

                    <div class="menu-price">Rp <?= number_format($m['harga'], 0, ',', '.') ?></div>

                    <div class="menu-stock">
                        <i class="fa-solid fa-boxes-stacked"></i> Stok: <strong><?= $stok ?></strong> unit
                    </div>
                </div>

                <div class="menu-actions">
                    <button type="button" class="btn-outline-edit"
                        onclick="bukaFormEdit(<?= htmlspecialchars(json_encode($m)) ?>)">
                        <i class="fa-solid fa-pen"></i> Edit
                    </button>
                    <form action="index.php?section=menu" method="POST"
                        onsubmit="return confirm('Apakah anda yakin ingin menghapus menu ini?')" style="flex: 1; margin: 0;">
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

<div id="containerTambahMenu" class="form-tambah-container toggle-form">
    <div class="form-tambah-header">
        <i class="fa-solid fa-circle-plus" style="color: #5aab55; font-size: 20px;"></i>
        <h3>Form Tambah Menu Baru</h3>
    </div>

    <form action="index.php?section=menu" method="POST" enctype="multipart/form-data" class="static-form">
        <input type="hidden" name="action" value="tambah_menu">

        <div class="form-grid-layout">
            <div class="form-group">
                <label for="nama_menu">Nama Menu</label>
                <input type="text" id="nama_menu" name="nama_menu" placeholder="Contoh: Nasi Goreng Gila" required>
            </div>

            <div class="form-group">
                <label for="kategori_menu">Kategori</label>
                <select id="kategori_menu" name="kategori" required>
                    <option value="makanan">Makanan</option>
                    <option value="minuman">Minuman</option>
                    <option value="snack">Snack</option>
                </select>
            </div>

            <div class="form-group">
                <label for="harga_menu">Harga (Rp)</label>
                <input type="number" id="harga_menu" name="harga" placeholder="Contoh: 12000" min="0" required>
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

<div id="containerEditMenu" class="form-tambah-container toggle-form" style="border-top: 4px solid #3b82f6;">
    <div class="form-tambah-header">
        <i class="fa-solid fa-pen-to-square" style="color: #3b82f6; font-size: 20px;"></i>
        <h3>Form Edit Menu: <span id="judul_edit_menu"></span></h3>
    </div>

    <form action="index.php?section=menu" method="POST" enctype="multipart/form-data" class="static-form">
        <input type="hidden" name="action" value="edit_menu">
        <input type="hidden" id="edit_id_menu" name="id_menu">

        <div class="form-grid-layout">
            <div class="form-group">
                <label for="edit_nama_menu">Nama Menu</label>
                <input type="text" id="edit_nama_menu" name="nama_menu" required>
            </div>

            <div class="form-group">
                <label for="edit_kategori_menu">Kategori</label>
                <select id="edit_kategori_menu" name="kategori" required>
                    <option value="makanan">Makanan</option>
                    <option value="minuman">Minuman</option>
                    <option value="snack">Snack</option>
                </select>
            </div>

            <div class="form-group">
                <label for="edit_harga_menu">Harga (Rp)</label>
                <input type="number" id="edit_harga_menu" name="harga" min="0" required>
            </div>

            <div class="form-group">
                <label for="edit_stok_menu">Stok</label>
                <input type="number" id="edit_stok_menu" name="stok" min="0" required>
            </div>

            <div class="form-group">
                <label for="edit_foto_menu">Ganti Foto Menu <small>(Kosongkan jika tidak diubah)</small></label>
                <input type="file" id="edit_foto_menu" name="foto" accept="image/*">
            </div>
        </div>

        <div class="form-tambah-footer">
            <button type="button" class="btn-reset" onclick="tutupFormEdit()">Batal</button>
            <button type="submit" class="btn-save" style="background: #3b82f6;"><i class="fa-solid fa-floppy-disk"></i>
                Simpan Perubahan</button>
        </div>
    </form>
</div>

<script>
    function toggleFormTambah() {
        tutupFormEdit();
        const formBox = document.getElementById('containerTambahMenu');
        formBox.classList.toggle('show');
        if (formBox.classList.contains('show')) {
            formBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
            document.getElementById('nama_menu').focus();
        } else {
            formBox.querySelector('form').reset();
        }
    }

    function bukaFormEdit(menu) {
        document.getElementById('containerTambahMenu').classList.remove('show');
        const formEdit = document.getElementById('containerEditMenu');

        document.getElementById('judul_edit_menu').textContent = menu.nama_menu;
        document.getElementById('edit_id_menu').value = menu.id_menu;
        document.getElementById('edit_nama_menu').value = menu.nama_menu;
        document.getElementById('edit_kategori_menu').value = menu.kategori ? menu.kategori.toLowerCase() : 'makanan';
        document.getElementById('edit_harga_menu').value = menu.harga;
        document.getElementById('edit_stok_menu').value = menu.stok;

        formEdit.classList.add('show');
        formEdit.scrollIntoView({ behavior: 'smooth', block: 'center' });
        document.getElementById('edit_nama_menu').focus();
    }

    // Perbaikan bug reset form agar tombol filter data GET tidak terganggu
    function tutupFormEdit() {
        const formEdit = document.getElementById('containerEditMenu');
        formEdit.classList.remove('show');
    }
</script>