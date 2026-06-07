<!-- ═══════ SECTION: FAVORIT ═══════ -->
<div class="page-section <?= $active_tab === 'favorit' ? 'active' : '' ?>" id="section-favorit">
    <section class="section-block">
        <h2 class="section-title">Menu Favorit</h2>
        
        <!-- Filter Kategori Tabs untuk Favorit -->
        <div class="filter-tabs" id="favoritFilterTabs" style="display:none; margin-bottom: 20px; gap: 8px;">
            <button class="filter-tab active" onclick="filterFavoritKategori('semua', this)">
                <i class="fa-solid fa-border-all"></i> Semua
            </button>
            <button class="filter-tab" onclick="filterFavoritKategori('makanan', this)">
                <i class="fa-solid fa-bowl-rice"></i> Makanan
            </button>
            <button class="filter-tab" onclick="filterFavoritKategori('minuman', this)">
                <i class="fa-solid fa-mug-hot"></i> Minuman
            </button>
            <button class="filter-tab" onclick="filterFavoritKategori('snack', this)">
                <i class="fa-solid fa-cookie-bite"></i> Snack
            </button>
        </div>

        <div id="favoritGrid" class="all-menu-grid"></div>

        <!-- Empty State filter kategori kosong -->
        <div class="empty-state" id="favoritKategoriEmpty" style="display:none; margin-top:40px;">
            <i class="fa-solid fa-filter-circle-xmark" style="font-size: 48px; color: #cbd5e1; margin-bottom: 12px;"></i>
            <h3>Tidak Ada Favorit</h3>
            <p>Tidak ada menu favorit untuk kategori yang dipilih.</p>
        </div>

        <div class="empty-state" id="favoritEmpty">
            <i class="fa-solid fa-heart"></i>
            <h3>Belum Ada Favorit</h3>
            <p>Tandai menu favoritmu dengan menekan ikon like agar mudah ditemukan</p>
            <br>
            <button class="btn-promo-blank" onclick="switchNav('beranda')"
                style="font-size:13px;padding:10px 28px">Jelajahi Menu</button>
        </div>
    </section>
</div>
