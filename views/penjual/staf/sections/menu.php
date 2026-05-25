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
            <option value="snack" <?= ($_GET['kategori'] ?? '') === 'snack' ? 'selected' : '' ?>>Snack</option>
        </select>
    </div>

    <button type="button" class="btn-primary"
        style="background-color: #5aab55; color: white; border: none; padding: 10px 20px; border-radius: 10px; font-weight: bold; cursor: pointer;"
        onclick="toggleFormTambah()">
        <i class="fa-solid fa-plus"></i> New Item
    </button>
</form>

<h2 style="margin: 20px 0; font-size: 22px; color: #1f2937;">Menu Listing</h2>

<div class="menu-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px;">
    <?php if (empty($daftarMenu)): ?>
        <div style="grid-column: 1/-1; text-align: center; color: #9ca3af; padding: 40px 0;">
            <i class="fa-solid fa-utensils" style="font-size: 40px; margin-bottom: 10px; display: block;"></i>
            Belum ada produk di menu ini.
        </div>
    <?php else:
        foreach ($daftarMenu as $m):
            $stok = $m['stok'] ?? 1;
            $isTersedia = $stok > 0;
            $kat = strtolower($m['kategori'] ?? 'makanan');
            ?>
            <div class="menu-card"
                style="border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; background: #fff; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">

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

                <div class="menu-info"
                    style="padding: 15px; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <h3 class="menu-title" title="<?= htmlspecialchars($m['nama_menu']) ?>"
                            style="margin: 0 0 10px 0; font-size: 16px; font-weight: bold; color: #1f2937;">
                            <?= htmlspecialchars($m['nama_menu']) ?>
                        </h3>

                        <span
                            style="font-size: 12px; color: #6b7280; text-transform: capitalize; background: #f3f4f6; padding: 2px 6px; border-radius: 4px;">
                            <?php if ($kat === 'minuman'): ?>
                                <i class="fa-solid fa-glass-water"></i>
                            <?php elseif ($kat === 'snack'): ?>
                                <i class="fa-solid fa-cookie-bite"></i>
                            <?php else: ?>
                                <i class="fa-solid fa-bowl-food"></i>
                            <?php endif; ?>
                            <?= htmlspecialchars($kat) ?>
                        </span>
                    </div>

                    <div class="menu-row-flex"
                        style="display: flex; align-items: center; justify-content: space-between; margin-top: 10px; margin-bottom: 15px;">
                        <span class="menu-price" style="font-weight: bold; color: #5aab55; font-size: 15px;">Rp
                            <?= number_format($m['harga'], 0, ',', '.') ?></span>

                        <span class="badge-status-tersedia"
                            style="padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: 600; white-space: nowrap; <?= !$isTersedia ? 'background-color:#fde8e8; color:#9b1c1c;' : 'background-color:#e1f5fe; color:#0288d1;' ?>">
                            <?= $isTersedia ? 'Tersedia' : 'Habis' ?>
                        </span>
                    </div>
                </div>

                <div class="menu-actions" style="display: flex; border-top: 1px solid #e5e7eb;">
                    <button type="button" class="btn-outline-edit"
                        style="flex: 1; padding: 10px; background: none; border: none; border-right: 1px solid #e5e7eb; color: #2563eb; cursor: pointer; font-weight: 600;"
                        onclick="bukaFormEdit(<?= htmlspecialchars(json_encode($m)) ?>)">
                        <i class="fa-solid fa-pen"></i> Edit
                    </button>
                    <form method="POST" style="display:inline; flex:1;"
                        onsubmit="return confirm('Hapus menu <?= htmlspecialchars($m['nama_menu']) ?>?')">
                        <input type="hidden" name="_section" value="menu">
                        <input type="hidden" name="action" value="hapus_menu">
                        <input type="hidden" name="id_menu" value="<?= $m['id_menu'] ?>">
                        <button type="submit" class="btn-outline-delete"
                            style="width: 100%; padding: 10px; background: none; border: none; color: #dc2626; cursor: pointer; font-weight: 600;">
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

    <form action="" method="POST" enctype="multipart/form-data" class="static-form">
        <input type="hidden" name="_section" value="menu">
        <input type="hidden" name="action" value="tambah_menu">

        <div class="form-grid-layout">
            <div class="form-group">
                <label for="nama_menu">Nama Menu</label>
                <input type="text" id="nama_menu" name="nama_menu" placeholder="Contoh: Nasi Goreng Gila" required>
            </div>

            <div class="form-group">
                <label for="kategori_menu">Kategori</label>
                <select id="kategori_menu" name="kategori"
                    style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 8px;" required>
                    <option value="makanan">Makanan</option>
                    <option value="minuman">Minuman</option>
                    <option value="snack">Snack</option>
                </select>
            </div>

            <div class="form-group">
                <label for="harga_menu">Harga (Rp)</label>
                <input type="number" id="harga_menu" name="harga" placeholder="Contoh: 12000" min="0" max="99999"
                    required>
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

<div id="containerEditMenu" class="form-tambah-container toggle-form" style="border-top: 4px solid #2563eb;">
    <div class="form-tambah-header">
        <i class="fa-solid fa-pen-to-square" style="color: #2563eb; font-size: 20px;"></i>
        <h3>Form Edit Menu: <span id="judul_edit_menu" style="color:#1f2937;"></span></h3>
    </div>

    <form action="" method="POST" enctype="multipart/form-data" class="static-form">
        <input type="hidden" name="_section" value="menu">
        <input type="hidden" name="action" value="edit_menu">
        <input type="hidden" id="edit_id_menu" name="id_menu">

        <div class="form-grid-layout">
            <div class="form-group">
                <label for="edit_nama_menu">Nama Menu</label>
                <input type="text" id="edit_nama_menu" name="nama_menu" required>
            </div>

            <div class="form-group">
                <label for="edit_kategori_menu">Kategori</label>
                <select id="edit_kategori_menu" name="kategori"
                    style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 8px;" required>
                    <option value="makanan">Makanan</option>
                    <option value="minuman">Minuman</option>
                    <option value="snack">Snack</option>
                </select>
            </div>

            <div class="form-group">
                <label for="edit_harga_menu">Harga (Rp)</label>
                <input type="number" id="edit_harga_menu" name="harga" min="0" max="99999" required>
            </div>

            <div class="form-group">
                <label for="edit_stok_menu">Stok</label>
                <input type="number" id="edit_stok_menu" name="stok" min="0" required>
            </div>

            <div class="form-group">
                <label for="edit_foto_menu">Ganti Foto Menu <small style="color:gray;">(Kosongkan jika tidak
                        diubah)</small></label>
                <input type="file" id="edit_foto_menu" name="foto" accept="image/*">
            </div>
        </div>

        <div class="form-tambah-footer">
            <button type="button" class="btn-reset" onclick="tutupFormEdit()">Batal</button>
            <button type="submit" class="btn-save" style="background-color: #2563eb;"><i
                    class="fa-solid fa-floppy-disk"></i> Simpan Perubahan</button>
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

    function tutupFormEdit() {
        const formEdit = document.getElementById('containerEditMenu');
        formEdit.classList.remove('show');
        formEdit.querySelector('form').reset();
    }
</script>