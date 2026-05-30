<?php
//pembeli/toko.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

// ── Validasi parameter id toko ──
$id_toko = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_toko <= 0) {
    // Tampilkan halaman error jika id tidak valid
    $error_page = true;
    $error_message = 'ID toko tidak valid.';
} else {
    // ── Ambil data toko ──
    $stmt = mysqli_prepare($conn, "SELECT * FROM toko WHERE id_toko = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_toko);
    mysqli_stmt_execute($stmt);
    $result_toko = mysqli_stmt_get_result($stmt);
    $toko = mysqli_fetch_assoc($result_toko);
    mysqli_stmt_close($stmt);

    if (!$toko) {
        $error_page = true;
        $error_message = 'Toko tidak ditemukan.';
    } else {
        $error_page = false;

        // ── Tentukan gambar toko ──
        $foto_toko = $toko['foto_toko'] ?? '';
        $toko_img_src = '';

        if (!empty($foto_toko)) {
            if (file_exists(__DIR__ . '/../../assets/img/kantin/' . $foto_toko)) {
                $toko_img_src = '../../assets/img/kantin/' . $foto_toko;
            } elseif (file_exists(__DIR__ . '/../../assets/img/' . $foto_toko)) {
                $toko_img_src = '../../assets/img/' . $foto_toko;
            }
        }

        // Fallback berdasarkan nama toko
        if (empty($toko_img_src)) {
            $nama_kecil = strtolower($toko['nama_toko']);
            if (str_contains($nama_kecil, 'tika')) {
                $toko_img_src = '../../assets/img/kantin_bu_tika.jpeg';
            } elseif (str_contains($nama_kecil, 'fajar')) {
                $toko_img_src = '../../assets/img/kantin_pak_fajar.jpeg';
            } elseif (str_contains($nama_kecil, 'agus')) {
                $toko_img_src = '../../assets/img/kantin_pak_agus.jpeg';
            } elseif (str_contains($nama_kecil, 'mardika')) {
                $toko_img_src = '../../assets/img/kantin_pak_mardika.jpeg';
            } elseif (str_contains($nama_kecil, 'basuni')) {
                $toko_img_src = '../../assets/img/kantin_pak_basuni.jpeg';
            } else {
                $toko_img_src = '';
            }
        }

        // ── Status toko ──
        $is_buka = (strtolower($toko['status'] ?? '') === 'buka');
        $status_kelas = $is_buka ? 'online' : 'offline';
        $status_teks = $is_buka ? 'Buka' : 'Tutup';

        // ── Ambil menu toko ──
        $stmt_menu = mysqli_prepare($conn, "SELECT * FROM menu WHERE id_toko = ? ORDER BY kategori ASC, nama_menu ASC");
        mysqli_stmt_bind_param($stmt_menu, "i", $id_toko);
        mysqli_stmt_execute($stmt_menu);
        $result_menu = mysqli_stmt_get_result($stmt_menu);
        $menus = [];
        while ($row = mysqli_fetch_assoc($result_menu)) {
            $menus[] = $row;
        }
        mysqli_stmt_close($stmt_menu);

        // ── Hitung kategori unik ──
        $kategori_list = [];
        foreach ($menus as $m) {
            $kat = ucfirst(strtolower($m['kategori'] ?? 'Lainnya'));
            if (!in_array($kat, $kategori_list)) {
                $kategori_list[] = $kat;
            }
        }
    }
}

// Ambil avatar pembeli
$avatar_file = $_SESSION['user_foto'] ?? '';
$user_nama   = $_SESSION['user_nama'] ?? 'Pembeli';
$user_role   = $_SESSION['user_role'] ?? 'siswa';
$has_avatar  = !empty($avatar_file) && file_exists(__DIR__ . '/../../assets/img/' . $avatar_file);
$avatar_path = $has_avatar ? '../../assets/img/' . $avatar_file : '';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $error_page ? 'Toko Tidak Ditemukan' : htmlspecialchars($toko['nama_toko']) . ' - E-Kantin'; ?></title>
    <link rel="stylesheet" href="../../assets/css/pembeli.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Stylesheets consolidated in assets/css/pembeli.css -->
</head>

<body>

    <!-- SVG Symbols for Category Fallbacks (Performance Optimization) -->
    <svg style="display: none;">
        <symbol id="icon-minuman" viewBox="0 0 24 24">
            <path d="M3 2l2.01 18.23C5.13 21.23 5.97 22 7 22h10c1.03 0 1.87-.77 1.99-1.77L21 2H3zm9 17c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm1-9H8V8h5v2z" />
        </symbol>
        <symbol id="icon-snack" viewBox="0 0 24 24">
            <path d="M18.06 22.99h1.66c.84 0 1.53-.64 1.63-1.46L23 5.05h-5V1h-1.97v4.05h-4.97l.3 2.34c1.71.47 3.31 1.32 4.27 2.26 1.44 1.42 2.43 2.89 2.43 5.29v8.05zM1 21.99V21h15.03v.99c0 .55-.45 1-1.01 1H2.01c-.56 0-1.01-.45-1.01-1zm15.03-7c0-4.5-6.72-5-8.99-5-2.28 0-9.03.5-9.03 5h18.02z" />
        </symbol>
        <symbol id="icon-makanan" viewBox="0 0 24 24">
            <path d="M11 9H9V2H7v7H5V2H3v7c0 2.12 1.66 3.84 3.75 3.97V22h2.5v-9.03C11.34 12.84 13 11.12 13 9V2h-2v7zm5-3v8h2.5v8H21V2c-2.76 0-5 2.24-5 4z" />
        </symbol>
    </svg>

    <!-- ── TOP HEADER ── -->
    <header class="main-header">
        <div class="header-pattern-left"></div>
        <div class="header-pattern-right"></div>
        <div class="header-inner">
            <div class="top-bar">
                <div class="logo-area">
                    <img src="../../assets/img/logo-esemkita.png" class="school-logo"
                         style="width: 38px; height: 38px; object-fit: contain; flex-shrink: 0; border-radius: 50%; background-color: #ffffff; padding: 2px;"
                         alt="Logo Esemkita">
                    <span class="brand-name">E-Kantin</span>
                </div>
                <div class="search-container">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" placeholder="Cari menu..." id="searchInput" oninput="searchMenu(this.value)">
                </div>
                <div class="header-icons">
                    <div class="icon-badge" onclick="toggleCartDrawer()">
                        <i class="fa-solid fa-cart-shopping"></i>
                        <span class="badge" id="headerCartBadge">0</span>
                    </div>
                    <!-- Profil -->
                    <div class="dropdown-wrapper">
                        <?php if ($has_avatar): ?>
                            <img src="<?= $avatar_path; ?>" class="blank-avatar" alt="Profil"
                                onclick="toggleProfileDrop()" style="cursor:pointer;">
                        <?php else: ?>
                            <div class="avatar-initials size-sm" onclick="toggleProfileDrop()" style="cursor:pointer;"><?= strtoupper(substr($user_nama, 0, 1)); ?></div>
                        <?php endif; ?>
                        <div class="dropdown-panel profile-dropdown" id="tokoProfileDrop" style="display:none;">
                            <div class="profile-header">
                                <?php if ($has_avatar): ?>
                                    <img src="<?= $avatar_path; ?>" alt="Avatar">
                                <?php else: ?>
                                    <div class="avatar-initials size-md"><?= strtoupper(substr($user_nama, 0, 1)); ?></div>
                                <?php endif; ?>
                                <div class="profile-info">
                                    <h4><?= htmlspecialchars($user_nama); ?></h4>
                                    <p><?= htmlspecialchars($user_role); ?></p>
                                </div>
                            </div>
                            <a href="index.php" class="profile-menu-item"><i class="fa-solid fa-house"></i> Beranda</a>
                            <a href="../../auth/logout.php" class="profile-menu-item" style="color:#ef4444;" onclick="confirmLogout(event, this.href)"><i class="fa-solid fa-right-from-bracket"></i> Keluar</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- ── CONTENT ── -->
    <main class="content-container">
        <div class="content-inner">

            <?php if (!empty($error_page) && $error_page): ?>
                <!-- ── Error State ── -->
                <div class="error-container">
                    <div class="error-icon">
                        <i class="fa-solid fa-store-slash"></i>
                    </div>
                    <h2>Oops! Toko Tidak Ditemukan</h2>
                    <p><?= htmlspecialchars($error_message); ?></p>
                    <a href="index.php?tab=kantin" class="btn-kembali">
                        <i class="fa-solid fa-arrow-left"></i>
                        Kembali ke Kantin
                    </a>
                </div>
            <?php else: ?>

                <!-- ── Back Navigation ── -->
                <div class="toko-back-nav">
                    <a href="index.php?tab=kantin" class="btn-back" title="Kembali">
                        <i class="fa-solid fa-arrow-left"></i>
                    </a>
                    <span class="back-label">Kembali ke Kantin</span>
                </div>

                <!-- ── Store Hero ── -->
                <div class="toko-hero">
                    <div class="toko-hero-banner <?= empty($toko_img_src) ? 'toko-banner-placeholder' : ''; ?>">
                        <?php if (!empty($toko_img_src)): ?>
                            <img src="<?= $toko_img_src; ?>" alt="<?= htmlspecialchars($toko['nama_toko']); ?>"
                                onerror="this.style.display='none'; this.parentElement.classList.add('toko-banner-placeholder');">
                        <?php endif; ?>
                    </div>
                    <div class="toko-hero-info">
                        <?php if (!empty($toko_img_src)): ?>
                            <img src="<?= $toko_img_src; ?>" class="toko-avatar"
                                alt="<?= htmlspecialchars($toko['nama_toko']); ?>"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="toko-avatar-placeholder" style="display:none;">
                                <i class="fa-solid fa-store"></i>
                            </div>
                        <?php else: ?>
                            <div class="toko-avatar-placeholder">
                                <i class="fa-solid fa-store"></i>
                            </div>
                        <?php endif; ?>
                        <div class="toko-details">
                            <h1><?= htmlspecialchars($toko['nama_toko']); ?></h1>
                            <p class="toko-desc"><?= htmlspecialchars($toko['deskripsi'] ?? 'Makanan, Snack, & Minuman'); ?>
                            </p>
                            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                                <span class="toko-status-badge <?= $is_buka ? 'buka' : 'tutup'; ?>">
                                    <?= $status_teks; ?>
                                </span>
                                <div class="toko-meta">
                                    <span class="toko-meta-item">
                                        <i class="fa-solid fa-utensils"></i>
                                        <?= count($menus); ?> Menu
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Category Filter Tabs ── -->
                <div class="filter-tabs">
                    <button class="filter-tab active" onclick="filterKategori('semua', this)">
                        <i class="fa-solid fa-border-all"></i> Semua
                    </button>
                    <button class="filter-tab" onclick="filterKategori('makanan', this)">
                        <i class="fa-solid fa-bowl-rice"></i> Makanan
                    </button>
                    <button class="filter-tab" onclick="filterKategori('minuman', this)">
                        <i class="fa-solid fa-mug-hot"></i> Minuman
                    </button>
                    <button class="filter-tab" onclick="filterKategori('snack', this)">
                        <i class="fa-solid fa-cookie-bite"></i> Snack
                    </button>
                </div>

                <!-- ── Menu Grid ── -->
                <div class="menu-grid" id="menuGrid">
                    <?php if (count($menus) > 0): ?>
                        <?php foreach ($menus as $menu):
                            $foto_menu = $menu['foto_menu'] ?? '';
                            $kategori = strtolower($menu['kategori'] ?? 'makanan');
                            $stok = intval($menu['stok'] ?? 0);
                            $tersedia = intval($menu['tersedia'] ?? 0);
                            $is_available = ($tersedia && $stok > 0 && $is_buka);

                            // Cek foto ada atau tidak
                            $menu_has_foto = false;
                            $menu_img_src = '';
                            if (!empty($foto_menu)) {
                                if (file_exists(__DIR__ . '/../../assets/img/menu/' . $foto_menu)) {
                                    $menu_img_src = '../../assets/img/menu/' . $foto_menu;
                                    $menu_has_foto = true;
                                }
                            }
                            ?>
                            <div class="menu-grid-item" data-kategori="<?= htmlspecialchars($kategori); ?>"
                                 data-nama="<?= htmlspecialchars(strtolower($menu['nama_menu'])); ?>">
                                <div class="menu-item-img-wrap">
                                    <button class="btn-favorite-toko" data-id="<?= $menu['id_menu']; ?>" 
                                            onclick="toggleFavoriteToko(<?= $menu['id_menu']; ?>, this)" title="Tambah ke Favorit">
                                        <i class="fa-regular fa-heart"></i>
                                    </button>

                                    <?php if ($menu_has_foto): ?>
                                        <img src="<?= $menu_img_src ?>" alt="<?= htmlspecialchars($menu['nama_menu']) ?>" loading="lazy"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="menu-img-placeholder <?= $kategori ?>" style="display:none;">
                                            <svg><use href="#icon-<?= $kategori === 'minuman' || $kategori === 'snack' ? $kategori : 'makanan' ?>"></use></svg>
                                        </div>
                                    <?php else: ?>
                                        <div class="menu-img-placeholder <?= $kategori ?>">
                                            <svg><use href="#icon-<?= $kategori === 'minuman' || $kategori === 'snack' ? $kategori : 'makanan' ?>"></use></svg>
                                        </div>
                                    <?php endif; ?>

                                    <span class="menu-item-category <?= htmlspecialchars($kategori) ?>">
                                        <?= htmlspecialchars(ucfirst($kategori)) ?>
                                    </span>
                                    <?php if ($stok <= 0): ?>
                                        <span class="menu-item-stock out">Habis</span>
                                    <?php elseif ($stok <= 5): ?>
                                        <span class="menu-item-stock low">Sisa
                                            <?= $stok ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="menu-item-stock">Stok
                                            <?= $stok ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="menu-item-body">
                                    <div>
                                        <div class="menu-item-name"><?= htmlspecialchars($menu['nama_menu']); ?></div>
                                        <?php if (!empty($menu['deskripsi'])): ?>
                                            <div class="menu-item-desc"><?= htmlspecialchars($menu['deskripsi']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="menu-item-footer">
                                        <span class="menu-item-price">Rp <?= number_format($menu['harga'], 0, ',', '.'); ?></span>
                                        <button class="btn-tambah" <?= !$is_available ? 'disabled' : ''; ?>
                                            onclick="tambahKeKeranjang(<?= $menu['id_menu']; ?>, '<?= htmlspecialchars(addslashes($menu['nama_menu']), ENT_QUOTES); ?>', <?= $menu['harga']; ?>, '<?= htmlspecialchars(addslashes($foto_menu), ENT_QUOTES); ?>', '<?= htmlspecialchars(addslashes($toko['nama_toko']), ENT_QUOTES); ?>', <?= $toko['id_toko']; ?>, <?= $stok; ?>)">
                                            <i class="fa-solid fa-plus"></i>
                                            <?= $is_available ? 'Tambah' : ($stok <= 0 ? 'Habis' : 'Tutup'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-menu-state">
                            <i class="fa-solid fa-utensils"></i>
                            <h3>Belum Ada Menu</h3>
                            <p>Toko ini belum menambahkan menu. Silakan cek kembali nanti.</p>
                        </div>
                    <?php endif; ?>
                </div>

            <?php endif; ?>

        </div>
    </main>

    <?php if (empty($error_page) || !$error_page): ?>
        <!-- Toast container -->
        <div class="toast-container" id="toastContainer"></div>

        <!-- ── Floating Cart Button ── -->
        <button class="fab-cart" title="Lihat Keranjang">
            <i class="fa-solid fa-cart-shopping"></i>
            <span class="fab-cart-badge" id="fabCartBadge">0</span>
        </button>

        <!-- ── Cart Drawer Overlay ── -->
        <div class="cart-drawer-overlay" id="cartOverlay" onclick="toggleCartDrawer()"></div>

        <!-- ── Cart Drawer ── -->
        <div class="cart-drawer" id="cartDrawer">
            <div class="cart-drawer-header">
                <h3><i class="fa-solid fa-cart-shopping"></i> Keranjang Belanja</h3>
                <button class="cart-drawer-close" onclick="toggleCartDrawer()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="cart-drawer-body" id="cartDrawerBody">
                <!-- Cart items will be dynamically generated by JS -->
            </div>
            <div class="cart-drawer-footer" id="cartDrawerFooter">
                <div class="cart-drawer-total">
                    <span class="label">Total Belanja</span>
                    <span class="amount" id="cartDrawerTotal">Rp 0</span>
                </div>
                <button class="cart-drawer-btn" onclick="checkoutCart()">
                    <i class="fa-solid fa-cash-register"></i> Checkout Sekarang
                </button>
            </div>
        </div>

        <script>
            // ══════════════════════════════════════════════
            //  CART & FILTER LOGIC
            // ══════════════════════════════════════════════

            const CART_KEY = 'ekantin_cart';

            // ── Get cart from localStorage ──
            function getCart() {
                try {
                    const data = localStorage.getItem(CART_KEY);
                    return data ? JSON.parse(data) : [];
                } catch (e) {
                    return [];
                }
            }

            // ── Save cart to localStorage ──
            function saveCart(cart) {
                localStorage.setItem(CART_KEY, JSON.stringify(cart));
                updateBadges();
                if (document.getElementById('cartDrawer').classList.contains('show')) {
                    renderCartDrawer();
                }
            }

            // ── Update all badge counts ──
            function updateBadges() {
                const cart = getCart();
                const totalItems = cart.reduce((sum, item) => sum + (item.jumlah || 0), 0);

                const headerBadge = document.getElementById('headerCartBadge');
                const fabBadge = document.getElementById('fabCartBadge');

                if (headerBadge) {
                    headerBadge.textContent = totalItems;
                    headerBadge.style.display = totalItems > 0 ? 'flex' : 'none';
                }
                if (fabBadge) {
                    fabBadge.textContent = totalItems;
                    fabBadge.style.display = totalItems > 0 ? 'flex' : 'none';
                }
            }

            // ── Add to cart ──
            function tambahKeKeranjang(id_menu, nama_menu, harga, foto_menu, nama_toko, id_toko, stok) {
                let cart = getCart();

                const existingIndex = cart.findIndex(item => item.id_menu === id_menu);

                if (existingIndex !== -1) {
                    if (cart[existingIndex].jumlah >= stok) {
                        showToast('Stok tidak mencukupi! Maksimum stok: ' + stok, 'error');
                        return;
                    }
                    cart[existingIndex].jumlah += 1;
                    cart[existingIndex].selected = true; // Auto select if added again
                } else {
                    if (stok <= 0) {
                        showToast('Stok habis!', 'error');
                        return;
                    }
                    cart.push({
                        id_menu: id_menu,
                        nama_menu: nama_menu,
                        harga: harga,
                        jumlah: 1,
                        foto_menu: foto_menu,
                        nama_toko: nama_toko,
                        id_toko: id_toko,
                        selected: true,
                        stok: stok
                    });
                }

                saveCart(cart);
                showToast(nama_menu, 'success', { foto: foto_menu, toko: nama_toko });
            }

            // ── Show toast notification ──
            function showToast(message, type, meta) {
                const container = document.getElementById('toastContainer');
                if (!container) return;

                // Hapus toast lama agar tidak menumpuk di layar HP
                container.querySelectorAll('.toast').forEach(t => t.remove());

                const toast = document.createElement('div');
                toast.className = 'toast ' + (type || '');

                let titleText = 'Notifikasi';
                let actionHTML = '';

                if (type === 'success') {
                    titleText = 'Berhasil';
                } else if (type === 'error') {
                    titleText = 'Gagal';
                }

                if (meta && meta.foto) {
                    titleText = 'Ditambahkan ke Keranjang';
                }

                if (meta && meta.toko) {
                    actionHTML = `<button class="toast-action" onclick="toggleCartDrawer()">Lihat</button>`;
                }

                toast.innerHTML = `
                    <div class="toast-info">
                        <div class="toast-title">${titleText}</div>
                        <div class="toast-desc">${message}</div>
                    </div>
                    ${actionHTML}
                `;

                container.appendChild(toast);

                // Trigger anim keluar setelah 3.2 detik, lalu hapus elemen di detik ke-3.5
                setTimeout(() => {
                    toast.classList.add('fade-out');
                    setTimeout(() => toast.remove(), 300);
                }, 3200);
            }

            // ── Toggle Cart Drawer ──
            function toggleCartDrawer() {
                const drawer = document.getElementById('cartDrawer');
                const overlay = document.getElementById('cartOverlay');
                if (drawer && overlay) {
                    const isShown = drawer.classList.contains('show');
                    if (isShown) {
                        drawer.classList.remove('show');
                        overlay.classList.remove('show');
                        document.body.style.overflow = '';
                    } else {
                        renderCartDrawer();
                        drawer.classList.add('show');
                        overlay.classList.add('show');
                        document.body.style.overflow = 'hidden';
                    }
                }
            }

            // ── Toggle Profile Dropdown ──
            function toggleProfileDrop() {
                const drop = document.getElementById('tokoProfileDrop');
                if (!drop) return;
                const isVisible = drop.style.display === 'block';
                drop.style.display = isVisible ? 'none' : 'block';
            }
            // Close profile drop when clicking outside
            document.addEventListener('click', function(e) {
                const drop = document.getElementById('tokoProfileDrop');
                if (!drop) return;
                const wrapper = drop.closest('.dropdown-wrapper');
                if (wrapper && !wrapper.contains(e.target)) {
                    drop.style.display = 'none';
                }
            });

            // ── Render Cart Drawer Content ──
            function renderCartDrawer() {
                const cart = getCart();
                const body = document.getElementById('cartDrawerBody');
                const footer = document.getElementById('cartDrawerFooter');
                const totalEl = document.getElementById('cartDrawerTotal');

                if (!body) return;

                if (cart.length === 0) {
                    body.innerHTML = `
                        <div class="empty-state" style="padding: 40px 20px;">
                            <i class="fa-solid fa-cart-flatbed-suitcase" style="font-size: 48px; color: #cbd5e1; margin-bottom: 12px; display: block; text-align: center;"></i>
                            <h3 style="font-size: 16px; font-weight: 700; color: #475569; margin-bottom: 4px; text-align: center;">Keranjangmu Kosong</h3>
                            <p style="font-size: 13px; color: #94a3b8; text-align: center;">Yuk, tambahkan menu lezat ke keranjang belanjaanmu!</p>
                        </div>
                    `;
                    if (footer) footer.style.display = 'none';
                    return;
                }

                // Calculate selected items total
                const selectedItems = cart.filter(item => item.selected !== false);
                const totalPrice = selectedItems.reduce((sum, item) => sum + (item.harga * item.jumlah), 0);
                const allSelected = cart.every(item => item.selected !== false);

                if (footer) footer.style.display = 'block';
                if (totalEl) totalEl.textContent = 'Rp ' + totalPrice.toLocaleString('id-ID');

                // Select All UI at the top
                let html = `
                    <div class="cart-select-all-container">
                        <label class="cart-select-all-label">
                            <input type="checkbox" class="cart-item-checkbox" id="cartSelectAll" onchange="toggleSelectAllCart(this.checked)" ${allSelected ? 'checked' : ''}>
                            <span>Pilih Semua (${selectedItems.length}/${cart.length})</span>
                        </label>
                        <button class="btn-clear-cart" onclick="clearSelectedCart()"><i class="fa-regular fa-trash-can"></i> Hapus</button>
                    </div>
                `;

                cart.forEach(item => {
                    let imgHTML = '';
                    if (item.foto_menu) {
                        imgHTML = `<img src="../../assets/img/menu/${item.foto_menu}" alt="${item.nama_menu}" onerror="this.outerHTML='<div class=\\'cart-img-placeholder\\'><i class=\\'fa-solid fa-utensils\\'></i></div>';">`;
                    } else {
                        imgHTML = `<div class="cart-img-placeholder"><i class="fa-solid fa-utensils"></i></div>`;
                    }

                    const isSelected = item.selected !== false;

                    html += `
                        <div class="dropdown-item cart-item-row" style="padding: 12px 0; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                            <div style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0;">
                                <div class="cart-item-checkbox-wrap">
                                    <input type="checkbox" class="cart-item-checkbox" onchange="toggleCartItemSelection(${item.id_menu})" ${isSelected ? 'checked' : ''}>
                                </div>
                                <div style="width: 50px; height: 50px; border-radius: 8px; overflow: hidden; flex-shrink: 0;">
                                    ${imgHTML}
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <h4 style="margin: 0; font-size: 14px; font-weight: 700; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${item.nama_menu}</h4>
                                    <p style="margin: 2px 0 0 0; font-size: 11px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${item.nama_toko}</p>
                                    <div style="font-size: 13px; font-weight: 800; color: #5cb85c; margin-top: 4px;">Rp ${item.harga.toLocaleString('id-ID')}</div>
                                </div>
                            </div>
                            <div style="text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 6px; flex-shrink: 0;">
                                <div style="font-size: 14px; font-weight: 800; color: #1e293b;">Rp ${(item.harga * item.jumlah).toLocaleString('id-ID')}</div>
                                <div class="item-qty" style="display: inline-flex; align-items: center; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; background: #f8fafc;">
                                    <button onclick="updateCartQtyToko(${item.id_menu}, -1)" style="border: none; background: none; padding: 4px 10px; cursor: pointer; font-size: 14px; font-weight: 700; color: #64748b; transition: background 0.2s;">−</button>
                                    <span style="font-size: 13px; font-weight: 700; color: #1e293b; min-width: 20px; text-align: center;">${item.jumlah}</span>
                                    <button onclick="updateCartQtyToko(${item.id_menu}, 1)" style="border: none; background: none; padding: 4px 10px; cursor: pointer; font-size: 14px; font-weight: 700; color: #64748b; transition: background 0.2s;">+</button>
                                </div>
                            </div>
                        </div>
                    `;
                });
                body.innerHTML = html;
            }

            // ── Update Cart Item Quantity in Canteen page ──
            function updateCartQtyToko(id_menu, delta) {
                let cart = getCart();
                const existingIndex = cart.findIndex(item => item.id_menu === id_menu);
                if (existingIndex !== -1) {
                    if (delta > 0) {
                        const item = cart[existingIndex];
                        const stok = item.stok || 999;
                        if (item.jumlah >= stok) {
                            showToast('Stok tidak mencukupi! Maksimum stok: ' + stok, 'error');
                            return;
                        }
                    }
                    cart[existingIndex].jumlah += delta;
                    if (cart[existingIndex].jumlah <= 0) {
                        cart.splice(existingIndex, 1);
                    }
                    saveCart(cart);
                }
            }

            // ── Toggle Item Selection in Cart ──
            function toggleCartItemSelection(id) {
                const cart = getCart();
                const item = cart.find(c => c.id_menu === id);
                if (item) {
                    item.selected = item.selected === false ? true : false;
                }
                saveCart(cart);
            }

            // ── Toggle Select All Items in Cart ──
            function toggleSelectAllCart(isChecked) {
                const cart = getCart();
                cart.forEach(item => {
                    item.selected = isChecked;
                });
                saveCart(cart);
            }

            // ── Clear Selected Items in Cart ──
            function clearSelectedCart() {
                let cart = getCart();
                const initialLen = cart.length;
                cart = cart.filter(item => item.selected === false);
                if (cart.length === initialLen) {
                    showToast('Pilih item terlebih dahulu untuk dihapus', 'error');
                    return;
                }
                saveCart(cart);
                updateBadges();
                showToast('Item terpilih berhasil dihapus', 'success');
            }

            // ── Mock Checkout ──
            function checkoutCart() {
                const cart = getCart();
                const selectedItems = cart.filter(item => item.selected !== false);
                if (selectedItems.length === 0) {
                    showToast('Silakan pilih item yang ingin dibeli!', 'error');
                    return;
                }
                window.location.href = 'checkout.php';
            }

            // ── Favorites (DB-backed) ──
            const FAV_API = 'actions/favorit.php';
            let TOKO_FAVS = []; // diisi saat initFavorites()

            function getFavorites() {
                return TOKO_FAVS;
            }

            // ── Toggle favorite via DB ──
            function toggleFavoriteToko(id, btn) {
                id = Number(id);
                const icon = btn.querySelector('i');
                const idx = TOKO_FAVS.indexOf(id);
                const isLiked = idx === -1;

                // Optimistic UI
                if (isLiked) {
                    TOKO_FAVS.push(id);
                    btn.classList.add('active');
                    if (icon) icon.className = 'fa-solid fa-heart';
                    showToast('Ditambahkan ke favorit', 'success');
                } else {
                    TOKO_FAVS.splice(idx, 1);
                    btn.classList.remove('active');
                    if (icon) icon.className = 'fa-regular fa-heart';
                    showToast('Dihapus dari favorit', '');
                }

                // Sync ke DB
                const fd = new FormData();
                fd.append('action', 'toggle');
                fd.append('id_menu', id);
                fetch(FAV_API, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(data => {
                        if (data.error) {
                            // Rollback jika gagal
                            if (isLiked) TOKO_FAVS.splice(TOKO_FAVS.indexOf(id), 1);
                            else TOKO_FAVS.push(id);
                            btn.classList.toggle('active');
                            if (icon) icon.className = isLiked ? 'fa-regular fa-heart' : 'fa-solid fa-heart';
                            showToast('Gagal menyimpan favorit', 'error');
                        }
                    })
                    .catch(() => showToast('Koneksi gagal', 'error'));
            }

            // ── Load favorit dari DB lalu init tombol ──
            function initFavorites() {
                fetch(FAV_API + '?action=list')
                    .then(r => r.json())
                    .then(data => {
                        TOKO_FAVS = (data.favorites || []).map(Number);
                        document.querySelectorAll('.btn-favorite-toko').forEach(btn => {
                            const id = Number(btn.getAttribute('data-id'));
                            const icon = btn.querySelector('i');
                            if (TOKO_FAVS.includes(id)) {
                                btn.classList.add('active');
                                if (icon) icon.className = 'fa-solid fa-heart';
                            } else {
                                btn.classList.remove('active');
                                if (icon) icon.className = 'fa-regular fa-heart';
                            }
                        });
                    })
                    .catch(() => console.warn('Gagal load favorit dari server'));
            }

            // ── Filter by category ──
            function filterKategori(kategori, btnEl) {
                // Update active tab
                document.querySelectorAll('.filter-tab').forEach(tab => tab.classList.remove('active'));
                if (btnEl) btnEl.classList.add('active');

                // Filter menu items
                const items = document.querySelectorAll('.menu-grid-item');
                let visibleCount = 0;

                items.forEach(item => {
                    const itemKategori = item.getAttribute('data-kategori');
                    if (kategori === 'semua' || itemKategori === kategori) {
                        item.classList.remove('hidden');
                        visibleCount++;
                    } else {
                        item.classList.add('hidden');
                    }
                });

                // Show empty state if no items match
                let emptyState = document.getElementById('emptyFilterState');
                if (visibleCount === 0 && items.length > 0) {
                    if (!emptyState) {
                        emptyState = document.createElement('div');
                        emptyState.id = 'emptyFilterState';
                        emptyState.className = 'empty-menu-state';
                        emptyState.innerHTML = '<i class="fa-solid fa-filter-circle-xmark"></i><h3>Tidak ada menu</h3><p>Tidak ada menu untuk kategori ini.</p>';
                        document.getElementById('menuGrid').appendChild(emptyState);
                    }
                    emptyState.style.display = 'block';
                } else if (emptyState) {
                    emptyState.style.display = 'none';
                }
            }

            // ── Search menu ──
            function searchMenu(query) {
                const q = query.toLowerCase().trim();
                const items = document.querySelectorAll('.menu-grid-item');

                items.forEach(item => {
                    const nama = item.getAttribute('data-nama') || '';
                    if (q === '' || nama.includes(q)) {
                        item.classList.remove('hidden');
                    } else {
                        item.classList.add('hidden');
                    }
                });

                // Reset active filter tab
                if (q !== '') {
                    document.querySelectorAll('.filter-tab').forEach(tab => tab.classList.remove('active'));
                }
            }

            // ── Draggable FAB Cart Logic ──
            function makeCartDraggable() {
                const fab = document.querySelector('.fab-cart');
                if (!fab) return;

                let isDragging = false;
                let startX = 0, startY = 0;
                let initialLeft = 0, initialTop = 0;

                fab.style.touchAction = 'none';

                fab.addEventListener('pointerdown', (e) => {
                    if (e.button !== 0 && e.pointerType === 'mouse') return;
                    
                    isDragging = false;
                    startX = e.clientX;
                    startY = e.clientY;
                    
                    const rect = fab.getBoundingClientRect();
                    initialLeft = rect.left;
                    initialTop = rect.top;
                    
                    fab.setPointerCapture(e.pointerId);
                });

                fab.addEventListener('pointermove', (e) => {
                    if (!fab.hasPointerCapture(e.pointerId)) return;
                    
                    const dx = e.clientX - startX;
                    const dy = e.clientY - startY;
                    
                    if (Math.abs(dx) > 6 || Math.abs(dy) > 6) {
                        isDragging = true;
                    }
                    
                    if (isDragging) {
                        let targetLeft = initialLeft + dx;
                        let targetTop = initialTop + dy;
                        
                        const minX = 10;
                        const maxX = window.innerWidth - fab.offsetWidth - 10;
                        const minY = 10;
                        const maxY = window.innerHeight - fab.offsetHeight - 10;
                        
                        if (targetLeft < minX) targetLeft = minX;
                        if (targetLeft > maxX) targetLeft = maxX;
                        if (targetTop < minY) targetTop = minY;
                        if (targetTop > maxY) targetTop = maxY;
                        
                        fab.style.left = targetLeft + 'px';
                        fab.style.top = targetTop + 'px';
                        fab.style.right = 'auto';
                        fab.style.bottom = 'auto';
                    }
                });

                fab.addEventListener('pointerup', (e) => {
                    if (!fab.hasPointerCapture(e.pointerId)) return;
                    fab.releasePointerCapture(e.pointerId);
                    
                    if (!isDragging) {
                        toggleCartDrawer();
                    }
                });
            }

            function confirmLogout(event, logoutUrl) {
                if (event) event.preventDefault();
                
                let existingModal = document.getElementById('logoutConfirmModal');
                if (existingModal) existingModal.remove();
                
                const modal = document.createElement('div');
                modal.id = 'logoutConfirmModal';
                modal.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.4);
                    backdrop-filter: blur(8px);
                    -webkit-backdrop-filter: blur(8px);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 99999;
                    opacity: 0;
                    transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                `;
                
                const card = document.createElement('div');
                card.style.cssText = `
                    background: #ffffff;
                    padding: 30px 24px;
                    border-radius: 24px;
                    width: 90%;
                    max-width: 360px;
                    text-align: center;
                    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
                    transform: scale(0.9);
                    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                `;
                
                card.innerHTML = `
                    <div style="width: 56px; height: 56px; background: #fee2e2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                        <svg style="width: 28px; height: 28px; stroke: #ef4444;" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                    </div>
                    <h3 style="margin: 0 0 8px; font-family: 'Poppins', sans-serif; font-size: 18px; font-weight: 750; color: #1e293b;">Konfirmasi Keluar</h3>
                    <p style="margin: 0 0 24px; font-family: 'Poppins', sans-serif; font-size: 13.5px; color: #64748b; line-height: 1.5;">Apakah Anda yakin ingin keluar dari akun E-Kantin?</p>
                    <div style="display: flex; gap: 10px; justify-content: center;">
                        <button id="logoutCancelBtn" style="flex: 1; padding: 11px; border-radius: 12px; border: 1.5px solid #cbd5e1; background: #ffffff; color: #475569; font-family: 'Poppins', sans-serif; font-size: 13.5px; font-weight: 700; cursor: pointer; transition: all 0.2s;">Batal</button>
                        <button id="logoutConfirmBtn" style="flex: 1; padding: 11px; border-radius: 12px; border: none; background: #ef4444; color: #ffffff; font-family: 'Poppins', sans-serif; font-size: 13.5px; font-weight: 700; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);">Keluar</button>
                    </div>
                `;
                
                modal.appendChild(card);
                document.body.appendChild(modal);
                
                setTimeout(() => {
                    modal.style.opacity = '1';
                    card.style.transform = 'scale(1)';
                }, 10);
                
                function closeModal() {
                    modal.style.opacity = '0';
                    card.style.transform = 'scale(0.9)';
                    setTimeout(() => modal.remove(), 300);
                }
                
                document.getElementById('logoutCancelBtn').addEventListener('click', closeModal);
                document.getElementById('logoutConfirmBtn').addEventListener('click', () => {
                    window.location.href = logoutUrl;
                });
                
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) closeModal();
                });
            }

            // ── Initialize on page load ──
            document.addEventListener('DOMContentLoaded', function () {
                updateBadges();
                initFavorites();
                makeCartDraggable();
            });
        </script>
    <?php endif; ?>

</body>

</html>