<!-- ═══════ SECTION: BERANDA ═══════ -->
<div class="page-section active" id="section-beranda">

    <!-- Search Results (hidden by default) -->
    <section class="section-block search-results-section" id="searchResultsSection" style="display:none">
        <h2 class="section-title">Hasil Pencarian: "<span id="searchQuery"></span>" <span
                class="search-clear" onclick="clearSearch()">✕ Hapus</span></h2>
        <div class="all-menu-grid" id="searchResultsGrid"></div>
    </section>

    <!-- Promo -->
    <section class="section-block" id="promoSection">
        <h2 class="section-title">Promo Hari ini</h2>
        <div class="promo-banner-blank">
            <div class="promo-text-placeholder">
                <h3>DISKON 25%</h3>
                <p>UNTUK MENU SPESIAL HARI INI!</p>
            </div>
            <div class="promo-action-area">
                <span class="promo-code-right">KODE PROMO: <strong>KANTINJOSS25</strong></span>
                <button class="btn-promo-blank"
                    onclick="switchNav('kantin');showToast('Gunakan kode KANTINJOSS25 saat checkout!','success')">Pesan
                    Sekarang</button>
            </div>
        </div>
    </section>

    <!-- Menu Terlaris -->
    <section class="section-block" id="menuSection">
        <h2 class="section-title">Menu Terlaris</h2>
        <div class="horizontal-scroll">
            <?php
            $top_menus = array_slice($all_menus, 0, 6);
            if (!empty($top_menus)) {
                foreach ($top_menus as $menu) {
                    $img = resolveMenuImg($menu['foto_menu'] ?? '');
                    ?>
                    <div class="menu-card" onclick="location.href='toko.php?id=<?= $menu['id_toko']; ?>'"
                        style="cursor:pointer">
                        <img src="<?= $img; ?>" class="menu-image-rect"
                            alt="<?= htmlspecialchars($menu['nama_menu']); ?>">
                        <div class="menu-info">
                            <h4><?= htmlspecialchars($menu['nama_menu']); ?></h4>
                            <p class="seller-name"><?= htmlspecialchars($menu['nama_toko']); ?></p>
                            <span class="price-tag">Rp. <?= number_format($menu['harga'], 0, ',', '.'); ?></span>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo "<p style='color:#94a3b8;font-size:13px;padding:20px 0'>Belum ada menu tersedia saat ini.</p>";
            }
            ?>
        </div>
        <div class="slider-dots">
            <span class="dot active"></span><span class="dot"></span><span class="dot"></span>
        </div>
    </section>

    <!-- Kategori -->
    <section class="section-block" id="kategoriSection">
        <h2 class="section-title">Kategori</h2>
        <div class="category-list">
            <a href="#" class="category-item active-cat" data-kat="semua"
                onclick="filterKategori('semua',this)">
                <div class="blank-circle icon-grid-center"><i class="fa-solid fa-table-cells-large"></i>
                </div>
                <span>Semua</span>
            </a>
            <a href="#" class="category-item" data-kat="makanan" onclick="filterKategori('makanan',this)">
                <div class="blank-circle"><img src="../../assets/img/ayam.png" alt="Makanan"></div>
                <span>Makanan</span>
            </a>
            <a href="#" class="category-item" data-kat="snack" onclick="filterKategori('snack',this)">
                <div class="blank-circle"><img src="../../assets/img/soto.png" alt="Snack"></div>
                <span>Snack</span>
            </a>
            <a href="#" class="category-item" data-kat="minuman" onclick="filterKategori('minuman',this)">
                <div class="blank-circle"><img src="../../assets/img/ayam.png"
                        style="filter:hue-rotate(130deg) saturate(1.5)" alt="Minuman"></div>
                <span>Minuman</span>
            </a>
        </div>
    </section>

    <!-- Kantin Grid -->
    <section class="section-block" id="kantinSection">
        <h2 class="section-title">Kantin</h2>
        <div class="kantin-grid" id="kantinGrid">
            <?php foreach ($all_tokos as $toko):
                $is_buka = (strtolower($toko['status'] ?? '') === 'buka');
                $status_kelas = $is_buka ? 'online' : 'offline';
                $status_teks = $is_buka ? 'Buka' : 'Tutup';
                $btn_disabled = !$is_buka ? 'style="background-color:#94a3b8;pointer-events:none;box-shadow:none"' : '';
                $toko_img = resolveTokoImg($toko['foto_toko'] ?? '', $toko['nama_toko']);
                ?>
                <div class="kantin-card" data-nama="<?= strtolower($toko['nama_toko']); ?>">
                    <img src="<?= $toko_img; ?>" class="blank-image-square"
                        alt="<?= htmlspecialchars($toko['nama_toko']); ?>">
                    <div class="kantin-info">
                        <h3><?= htmlspecialchars($toko['nama_toko']); ?></h3>
                        <p><?= htmlspecialchars($toko['deskripsi'] ?? 'Makanan, Snack, & Minuman'); ?></p>
                        <span class="status-indicator <?= $status_kelas; ?>"><?= $status_teks; ?></span>
                        <a href="toko.php?id=<?= $toko['id_toko']; ?>" class="btn-lihat-menu" <?= $btn_disabled; ?>>
                            <?= $is_buka ? 'Lihat Menu' : 'Sedang Tutup'; ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="category-item-all-box" onclick="switchNav('kantin')">
                <div class="blank-square-icon"><i class="fa-solid fa-table-cells-large"></i></div>
                <span class="all-text-label">SEMUA</span>
            </div>
        </div>
    </section>
</div>
