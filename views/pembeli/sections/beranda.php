<!-- ═══════ SECTION: BERANDA ═══════ -->
<div class="page-section active" id="section-beranda">

    <!-- Search Results (hidden by default) -->
    <section class="section-block search-results-section" id="searchResultsSection" style="display:none">
        <div class="search-header-row">
            <h3 class="search-title-text">
                <span>Hasil Pencarian untuk:</span>
                <span class="search-query-highlight" id="searchQuery"></span>
            </h3>
            <button class="btn-clear-search" onclick="clearSearch()" type="button">
                <i class="fa-solid fa-xmark"></i> Bersihkan
            </button>
        </div>
        <div class="all-menu-grid" id="searchResultsGrid"></div>
    </section>

    <!-- Promo -->
    <section class="section-block" id="promoSection">
        <h2 class="section-title">Promo Hari ini</h2>
        
        <?php if (empty($promo_banners)): ?>
            <!-- Fallback Empty State -->
            <div class="promo-banner-empty" style="width: 100%; min-height: 120px; border-radius: 20px; border: 2px dashed #cbd5e1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; background: #f8fafc; color: #64748b; padding: 20px; text-align: center; box-sizing: border-box; margin-bottom: 20px;">
                <i class="fa-solid fa-rectangle-ad" style="font-size: 28px; color: #cbd5e1;"></i>
                <p style="margin: 0; font-size: 13px; font-weight: 700;">Belum ada banner sama sekali di sini</p>
            </div>
        <?php else: ?>
            <div class="promo-slider-layout">
                <div class="promo-slider-column">
                    <div class="promo-slider-container promo-slider-primary">
                        <div class="promo-slider-wrapper" id="promoSliderWrapper">
                            <?php renderPromoSlides($promo_banners, 0); ?>
                        </div>
                    </div>
                    <div class="promo-banner-owner" id="promoBannerOwner"></div>
                </div>
                <?php if (count($promo_banners) > 1): ?>
                    <div class="promo-slider-column promo-slider-column-secondary">
                        <div class="promo-slider-container promo-slider-secondary">
                            <div class="promo-slider-wrapper" id="promoSliderWrapperSecondary">
                                <?php renderPromoSlides($promo_banners, 1); ?>
                            </div>
                        </div>
                        <div class="promo-banner-owner" id="promoBannerOwnerSecondary"></div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (count($promo_banners) > 1): ?>
                <div class="promo-slider-controls-bar">
                    <button class="promo-control-btn" onclick="movePromoSlide(-1)" aria-label="Previous">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <div class="promo-slider-dots">
                        <?php foreach ($promo_banners as $index => $banner): ?>
                            <span class="promo-dot <?= $index === 0 ? 'active' : '' ?>"
                                onclick="setPromoSlide(<?= $index ?>)"></span>
                        <?php endforeach; ?>
                    </div>
                    <button class="promo-control-btn" onclick="movePromoSlide(1)" aria-label="Next">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <!-- Menu Terlaris -->
    <section class="section-block" id="menuSection">
        <h2 class="section-title">Menu Terlaris</h2>
        <div class="horizontal-scroll">
            <?php
            $top_menus = $terlaris_menus ?? [];
            if (!empty($top_menus)) {
                foreach ($top_menus as $menu) {
                    $img = resolveMenuImg($menu['foto_menu'] ?? '');
                    $kat = strtolower($menu['kategori'] ?? 'makanan');
                    $has_stock = ((int)($menu['stok'] ?? 0) > 0);
                    ?>
                    <div class="menu-card" onclick="bukaDetailMenu(<?= (int) $menu['id_menu'] ?>)" style="cursor:pointer">
                        <div class="menu-img-wrap-beranda" style="<?= !$has_stock ? 'position: relative; opacity: 0.65;' : '' ?>">
                            <?php if (!$has_stock): ?>
                                <div style="position: absolute; top: 8px; right: 8px; background: #dc2626; color: #ffffff; font-size: 9px; font-weight: 800; padding: 3px 8px; border-radius: 9999px; text-transform: uppercase; z-index: 2; box-shadow: 0 2px 4px rgba(220,38,38,0.2);">Habis</div>
                            <?php endif; ?>
                            <?php if (!empty($img)): ?>
                                <img src="<?= $img; ?>" alt="<?= htmlspecialchars($menu['nama_menu']); ?>"
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
                        </div>
                        <div class="menu-info">
                            <h4><?= htmlspecialchars($menu['nama_menu']); ?></h4>
                            <p class="seller-name"><?= htmlspecialchars($menu['nama_toko']); ?></p>
                            <?php if (isset($menu['is_fleksibel']) && $menu['is_fleksibel'] == 1): ?>
                                <span class="price-tag flex-price-tag" style="background: rgba(14, 165, 233, 0.1); color: #0ea5e9; padding: 4px 8px; border-radius: 6px; font-weight: 750; font-size: 11px; display: inline-flex; align-items: center; gap: 4px;"><i class="fa-solid fa-arrows-left-right-to-line"></i> Harga Fleksibel</span>
                            <?php else: ?>
                                <span class="price-tag">Rp. <?= number_format($menu['harga'], 0, ',', '.'); ?></span>
                            <?php endif; ?>
                            <?php 
                            $is_toko_buka = (strtolower($menu['status_toko'] ?? '') === 'buka');
                            if ($is_toko_buka && $has_stock): 
                            ?>
                                <button class="btn-tambah-keranjang"
                                    style="width:100%;margin-top:8px;font-size:12px;padding:7px 10px" onclick="event.stopPropagation(); bukaDetailMenu(<?= (int) $menu['id_menu'] ?>)">
                                    <i class="fa-solid fa-cart-plus"></i> Tambah
                                </button>
                            <?php elseif (!$has_stock): ?>
                                <button class="btn-tambah-keranjang"
                                    style="width:100%;margin-top:8px;font-size:12px;padding:7px 10px;background-color:#ef4444;color:#ffffff;cursor:pointer;box-shadow:none" onclick="event.stopPropagation(); bukaDetailMenu(<?= (int) $menu['id_menu'] ?>)">
                                    Stok Habis
                                </button>
                            <?php else: ?>
                                <button class="btn-tambah-keranjang"
                                    style="width:100%;margin-top:8px;font-size:12px;padding:7px 10px;background-color:#94a3b8;pointer-events:none;box-shadow:none" disabled>
                                    Toko Tutup
                                </button>
                            <?php endif; ?>
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
        
        <div class="category-list-modern">
            <a href="#" class="cat-modern-item" onclick="event.preventDefault(); bukaDetailKategori('semua')">
                <div class="cat-modern-circle semua">
                    <i class="fa-solid fa-table-cells-large"></i>
                </div>
                <span>Semua</span>
            </a>
            
            <a href="#" class="cat-modern-item" onclick="event.preventDefault(); bukaDetailKategori('makanan')">
                <div class="cat-modern-circle">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width: 32px; height: 32px; fill: #ff7a45; filter: drop-shadow(0 2px 4px rgba(255, 122, 69, 0.2));">
                        <path d="M11 9H9V2H7v7H5V2H3v7c0 2.12 1.66 3.84 3.75 3.97V22h2.5v-9.03C11.34 12.84 13 11.12 13 9V2h-2v7zm5-3v8h2.5v8H21V2c-2.76 0-5 2.24-5 4z" />
                    </svg>
                </div>
                <span>Makanan</span>
            </a>
            
            <a href="#" class="cat-modern-item" onclick="event.preventDefault(); bukaDetailKategori('snack')">
                <div class="cat-modern-circle">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width: 32px; height: 32px; fill: #9254de; filter: drop-shadow(0 2px 4px rgba(146, 84, 222, 0.2));">
                        <path d="M18.06 22.99h1.66c.84 0 1.53-.64 1.63-1.46L23 5.05h-5V1h-1.97v4.05h-4.97l.3 2.34c1.71.47 3.31 1.32 4.27 2.26 1.44 1.42 2.43 2.89 2.43 5.29v8.05zM1 21.99V21h15.03v.99c0 .55-.45 1-1.01 1H2.01c-.56 0-1.01-.45-1.01-1zm15.03-7c0-4.5-6.72-5-8.99-5-2.28 0-9.03.5-9.03 5h18.02z" />
                    </svg>
                </div>
                <span>Snack</span>
            </a>
            
            <a href="#" class="cat-modern-item" onclick="event.preventDefault(); bukaDetailKategori('minuman')">
                <div class="cat-modern-circle">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width: 32px; height: 32px; fill: #1890ff; filter: drop-shadow(0 2px 4px rgba(24, 144, 255, 0.2));">
                        <path d="M3 2l2.01 18.23C5.13 21.23 5.97 22 7 22h10c1.03 0 1.87-.77 1.99-1.77L21 2H3zm9 17c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm1-9H8V8h5v2z" />
                    </svg>
                </div>
                <span>Minuman</span>
            </a>
        </div>
    </section>

    <!-- Kantin Grid -->
    <section class="section-block" id="kantinSection">
        <h2 class="section-title">Kantin</h2>
        <div class="kantin-grid kantin-grid-beranda" id="kantinGridBeranda">
            <?php 
            $home_tokos = array_slice($all_tokos, 0, 3);
            foreach ($home_tokos as $toko):
                $is_buka = (strtolower($toko['status'] ?? '') === 'buka');
                $status_kelas = $is_buka ? 'online' : 'offline';
                $status_teks = $is_buka ? 'Buka' : 'Tutup';
                $btn_disabled = !$is_buka ? 'style="background-color:#94a3b8;pointer-events:none;box-shadow:none"' : '';
                $toko_img = resolveTokoImg($toko['foto_toko'] ?? '');
                ?>
                <div class="kantin-card" data-nama="<?= strtolower($toko['nama_toko']); ?>">
                    <?php if (!empty($toko_img)): ?>
                        <img src="<?= $toko_img; ?>" class="blank-image-square"
                            alt="<?= htmlspecialchars($toko['nama_toko']); ?>"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="toko-img-placeholder" style="display:none;">
                            <i class="fa-solid fa-store"></i>
                            <span><?= htmlspecialchars($toko['nama_toko']); ?></span>
                        </div>
                    <?php else: ?>
                        <div class="toko-img-placeholder">
                            <i class="fa-solid fa-store"></i>
                            <span><?= htmlspecialchars($toko['nama_toko']); ?></span>
                        </div>
                    <?php endif; ?>
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