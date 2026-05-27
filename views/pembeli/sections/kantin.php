<!-- ═══════ SECTION: KANTIN (FULL MENU LIST) ═══════ -->
<div class="page-section" id="section-kantin">
    <section class="section-block">
        <h2 class="section-title">Semua Menu Tersedia</h2>
        <!-- Category filter tabs -->
        <div class="category-list" style="margin-bottom:20px">
            <a href="#" class="category-item active-cat" data-kat2="semua"
                onclick="filterKantinMenu('semua',this)">
                <div class="blank-circle icon-grid-center"><i class="fa-solid fa-table-cells-large"></i>
                </div>
                <span>Semua</span>
            </a>
            <a href="#" class="category-item" data-kat2="makanan"
                onclick="filterKantinMenu('makanan',this)">
                <div class="blank-circle"><img src="../../assets/img/ayam.png" alt="Makanan"></div>
                <span>Makanan</span>
            </a>
            <a href="#" class="category-item" data-kat2="snack" onclick="filterKantinMenu('snack',this)">
                <div class="blank-circle"><img src="../../assets/img/soto.png" alt="Snack"></div>
                <span>Snack</span>
            </a>
            <a href="#" class="category-item" data-kat2="minuman"
                onclick="filterKantinMenu('minuman',this)">
                <div class="blank-circle"><img src="../../assets/img/ayam.png"
                        style="filter:hue-rotate(130deg) saturate(1.5)" alt="Minuman"></div>
                <span>Minuman</span>
            </a>
        </div>
        <div class="all-menu-grid" id="allMenuGrid">
            <?php foreach ($all_menus as $menu):
                $img = resolveMenuImg($menu['foto_menu'] ?? '');
                $kat = strtolower($menu['kategori'] ?? 'makanan');
                ?>
                <div class="menu-card-full" data-kategori="<?= $kat; ?>"
                    data-nama="<?= strtolower($menu['nama_menu']); ?>"
                    data-toko="<?= strtolower($menu['nama_toko']); ?>">
                    <img src="<?= $img; ?>" alt="<?= htmlspecialchars($menu['nama_menu']); ?>">
                    <div class="mc-info">
                        <h4><?= htmlspecialchars($menu['nama_menu']); ?></h4>
                        <p class="mc-toko"><?= htmlspecialchars($menu['nama_toko']); ?></p>
                        <p class="mc-price">Rp. <?= number_format($menu['harga'], 0, ',', '.'); ?></p>
                        <button class="btn-tambah-keranjang"
                            onclick="addToCart(<?= $menu['id_menu']; ?>,'<?= htmlspecialchars(addslashes($menu['nama_menu']), ENT_QUOTES); ?>',<?= $menu['harga']; ?>,'<?= htmlspecialchars(addslashes($menu['foto_menu'] ?? ''), ENT_QUOTES); ?>','<?= htmlspecialchars(addslashes($menu['nama_toko']), ENT_QUOTES); ?>',<?= $menu['id_toko']; ?>)">
                            <i class="fa-solid fa-cart-plus"></i> Tambah
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (empty($all_menus)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-store-slash"></i>
                <h3>Belum Ada Menu</h3>
                <p>Kantin belum menambahkan menu. Coba lagi nanti ya!</p>
            </div>
        <?php endif; ?>
    </section>
</div>
