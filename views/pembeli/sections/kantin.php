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
                <div class="blank-circle makanan">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width: 28px; height: 28px; fill: #ff7a45; filter: drop-shadow(0 2px 4px rgba(255, 122, 69, 0.2));">
                        <path d="M11 9H9V2H7v7H5V2H3v7c0 2.12 1.66 3.84 3.75 3.97V22h2.5v-9.03C11.34 12.84 13 11.12 13 9V2h-2v7zm5-3v8h2.5v8H21V2c-2.76 0-5 2.24-5 4z" />
                    </svg>
                </div>
                <span>Makanan</span>
            </a>
            <a href="#" class="category-item" data-kat2="snack" onclick="filterKantinMenu('snack',this)">
                <div class="blank-circle snack">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width: 28px; height: 28px; fill: #9254de; filter: drop-shadow(0 2px 4px rgba(146, 84, 222, 0.2));">
                        <path d="M18.06 22.99h1.66c.84 0 1.53-.64 1.63-1.46L23 5.05h-5V1h-1.97v4.05h-4.97l.3 2.34c1.71.47 3.31 1.32 4.27 2.26 1.44 1.42 2.43 2.89 2.43 5.29v8.05zM1 21.99V21h15.03v.99c0 .55-.45 1-1.01 1H2.01c-.56 0-1.01-.45-1.01-1zm15.03-7c0-4.5-6.72-5-8.99-5-2.28 0-9.03.5-9.03 5h18.02z" />
                    </svg>
                </div>
                <span>Snack</span>
            </a>
            <a href="#" class="category-item" data-kat2="minuman"
                onclick="filterKantinMenu('minuman',this)">
                <div class="blank-circle minuman">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width: 28px; height: 28px; fill: #1890ff; filter: drop-shadow(0 2px 4px rgba(24, 144, 255, 0.2));">
                        <path d="M3 2l2.01 18.23C5.13 21.23 5.97 22 7 22h10c1.03 0 1.87-.77 1.99-1.77L21 2H3zm9 17c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm1-9H8V8h5v2z" />
                    </svg>
                </div>
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
                    <div class="menu-card-full-img-wrap">
                        <?php if (!empty($img)): ?>
                            <img src="<?= $img; ?>" alt="<?= htmlspecialchars($menu['nama_menu']); ?>"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="menu-img-placeholder <?= $kat ?>" style="display:none;">
                                <?php if ($kat === 'minuman'): ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                        <path d="M3 2l2.01 18.23C5.13 21.23 5.97 22 7 22h10c1.03 0 1.87-.77 1.99-1.77L21 2H3zm9 17c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm1-9H8V8h5v2z" />
                                    </svg>
                                <?php elseif ($kat === 'snack'): ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                        <path d="M18.06 22.99h1.66c.84 0 1.53-.64 1.63-1.46L23 5.05h-5V1h-1.97v4.05h-4.97l.3 2.34c1.71.47 3.31 1.32 4.27 2.26 1.44 1.42 2.43 2.89 2.43 5.29v8.05zM1 21.99V21h15.03v.99c0 .55-.45 1-1.01 1H2.01c-.56 0-1.01-.45-1.01-1zm15.03-7c0-4.5-6.72-5-8.99-5-2.28 0-9.03.5-9.03 5h18.02z" />
                                    </svg>
                                <?php else: ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                        <path d="M11 9H9V2H7v7H5V2H3v7c0 2.12 1.66 3.84 3.75 3.97V22h2.5v-9.03C11.34 12.84 13 11.12 13 9V2h-2v7zm5-3v8h2.5v8H21V2c-2.76 0-5 2.24-5 4z" />
                                    </svg>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="menu-img-placeholder <?= $kat ?>">
                                <?php if ($kat === 'minuman'): ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                        <path d="M3 2l2.01 18.23C5.13 21.23 5.97 22 7 22h10c1.03 0 1.87-.77 1.99-1.77L21 2H3zm9 17c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm1-9H8V8h5v2z" />
                                    </svg>
                                <?php elseif ($kat === 'snack'): ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                        <path d="M18.06 22.99h1.66c.84 0 1.53-.64 1.63-1.46L23 5.05h-5V1h-1.97v4.05h-4.97l.3 2.34c1.71.47 3.31 1.32 4.27 2.26 1.44 1.42 2.43 2.89 2.43 5.29v8.05zM1 21.99V21h15.03v.99c0 .55-.45 1-1.01 1H2.01c-.56 0-1.01-.45-1.01-1zm15.03-7c0-4.5-6.72-5-8.99-5-2.28 0-9.03.5-9.03 5h18.02z" />
                                    </svg>
                                <?php else: ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                        <path d="M11 9H9V2H7v7H5V2H3v7c0 2.12 1.66 3.84 3.75 3.97V22h2.5v-9.03C11.34 12.84 13 11.12 13 9V2h-2v7zm5-3v8h2.5v8H21V2c-2.76 0-5 2.24-5 4z" />
                                    </svg>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
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
