<?php
//pembeli/toko.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/banner_canvas.php';

// ── Validasi parameter id toko ──
$id_toko = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_toko <= 0) {
    // Tampilkan halaman error jika id tidak valid
    $error_page = true;
    $error_message = 'ID toko tidak valid.';
} else {
    // ── Ambil data toko ──
    $stmt = mysqli_prepare($conn, "
        SELECT t.*, s.nomor AS nomor_lapak
        FROM toko t
        LEFT JOIN slot_stand_kantin s ON s.id_toko = t.id_toko
        WHERE t.id_toko = ?
    ");
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

        // ── Query foto latar belakang untuk slideshow ──
        $q_latar = mysqli_query($conn, "SELECT * FROM `foto_latar_belakang` WHERE `id_toko` = $id_toko ORDER BY `urutan` ASC");
        $latar_photos = [];
        if ($q_latar) {
            while ($row = mysqli_fetch_assoc($q_latar)) {
                $latar_photos[] = $row;
            }
        }


        // ── Status toko ──
        $is_buka = (strtolower($toko['status'] ?? '') === 'buka');
        $status_kelas = $is_buka ? 'online' : 'offline';
        $status_teks = $is_buka ? 'Buka' : 'Tutup';

        $nomor_lapak = (int) ($toko['nomor_lapak'] ?? 0);
        if ($nomor_lapak < 1) {
            $nomor_lapak = (int) ($toko['urutan'] ?? 0) + 1;
        }

        // ── Ambil menu toko ──
        $stmt_menu = mysqli_prepare($conn, "SELECT * FROM menu WHERE id_toko = ? AND deleted_at IS NULL ORDER BY kategori ASC, nama_menu ASC");
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
    <script src="../../assets/js/banner-canvas.js?v=<?= time(); ?>"></script>
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
                    <img src="../../assets/img/logo_ekantin_hijau.png" class="school-logo"
                         style="width: 38px; height: 38px; object-fit: contain; flex-shrink: 0; border-radius: 50%; background-color: #ffffff; padding: 2px;"
                         alt="Logo Esemkita">
                    <span class="brand-name">E-Kantin</span>
                </div>
                <div class="search-container">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" placeholder="Cari menu..." id="searchInput" oninput="searchMenu(this.value)" onkeydown="handleSearchKeydown(event)">
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
                    <div class="toko-hero-banner <?= (empty($toko_img_src) && empty($latar_photos)) ? 'toko-banner-placeholder' : ''; ?>">
                        <?php if (!empty($latar_photos)): ?>
                            <div class="hero-slideshow-container" style="position: relative; width: 100%; height: 100%; overflow: hidden;">
                                <?php foreach ($latar_photos as $index => $photo): 
                                    $latarCanvasData = bannerCanvasDataAttrs($photo['canvas_config'] ?? '');
                                ?>
                                    <div class="hero-slide <?= $index === 0 ? 'active' : ''; ?>" data-slide-index="<?= $index ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; transition: opacity 0.8s ease-in-out; z-index: 1;">
                                        <div class="hero-slide-viewport banner-canvas-viewport" <?= $latarCanvasData ?> style="width:100%; height:100%; position: relative; overflow: hidden;">
                                            <img src="../../assets/img/latar_belakang/<?= htmlspecialchars($photo['gambar']); ?>" alt="<?= htmlspecialchars($toko['nama_toko']); ?>" style="display: block; position: absolute; max-width: none; max-height: none;">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (count($latar_photos) > 1): ?>
                                <div class="hero-slideshow-dots" style="position: absolute; bottom: 15px; left: 50%; transform: translateX(-50%); display: flex; gap: 8px; z-index: 10;">
                                    <?php foreach ($latar_photos as $index => $photo): ?>
                                        <span class="hero-dot <?= $index === 0 ? 'active' : ''; ?>" onclick="currentHeroSlide(<?= $index ?>)" style="width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,0.4); cursor: pointer; transition: all 0.3s ease;"></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="toko-banner-placeholder-svg">
                                <div class="banner-pattern-overlay"></div>
                                <div class="toko-banner-fallback-icon-container">
                                    <div class="toko-banner-fallback-icon-wrap">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #ffffff;">
                                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                                        </svg>
                                    </div>
                                    <span class="toko-banner-fallback-text"><?= htmlspecialchars($toko['nama_toko']); ?></span>
                                </div>
                            </div>
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
                            <p class="toko-desc"><?= htmlspecialchars($toko['deskripsi'] ?? 'Belum ada deskripsi.'); ?>
                            </p>
                            <span class="lapak-badge">lapak no.<?= $nomor_lapak ?></span>
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
                                 data-nama="<?= htmlspecialchars(strtolower($menu['nama_menu'])); ?>"
                                 onclick="handleMenuCardClick(<?= (int) $menu['id_menu'] ?>)" style="cursor: pointer;">
                                <div class="menu-item-img-wrap">
                                    <button class="btn-favorite-toko" data-id="<?= $menu['id_menu']; ?>" 
                                            onclick="toggleFavoriteToko(<?= $menu['id_menu']; ?>, this); event.stopPropagation();" title="Tambah ke Favorit">
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
                                        <span class="menu-item-category <?= htmlspecialchars($kategori) ?>">
                                            <?= htmlspecialchars(ucfirst($kategori)) ?>
                                        </span>
                                        <div class="menu-item-name"><?= htmlspecialchars($menu['nama_menu']); ?></div>
                                        <?php if (!empty($menu['deskripsi'])): ?>
                                            <div class="menu-item-desc"><?= htmlspecialchars($menu['deskripsi']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="menu-item-footer">
                                        <?php if (isset($menu['is_fleksibel']) && $menu['is_fleksibel'] == 1): ?>
                                            <span class="menu-item-price flex-price-tag" style="background: rgba(14, 165, 233, 0.1); color: #0ea5e9; padding: 4px 8px; border-radius: 6px; font-weight: 750; font-size: 11.5px; display: inline-flex; align-items: center; gap: 4px;"><i class="fa-solid fa-arrows-left-right-to-line"></i> Harga Fleksibel</span>
                                        <?php else: ?>
                                            <span class="menu-item-price">Rp <?= number_format($menu['harga'], 0, ',', '.'); ?></span>
                                        <?php endif; ?>
                                         <?php if (!$is_buka): ?>
                                             <button class="btn-tambah" style="background-color:#94a3b8; pointer-events:none; box-shadow:none" disabled>
                                                 Tutup
                                             </button>
                                         <?php elseif ($stok <= 0 || !$tersedia): ?>
                                             <button class="btn-tambah" style="background-color:#ef4444; color:#ffffff; box-shadow:none; cursor:pointer;"
                                                 onclick="bukaDetailMenu(<?= $menu['id_menu']; ?>); event.stopPropagation();">
                                                 Habis
                                             </button>
                                         <?php else: ?>
                                             <button class="btn-tambah"
                                                 onclick="event.stopPropagation(); tambahKeKeranjang(<?= (int)$menu['id_menu']; ?>, <?= htmlspecialchars(json_encode($menu['nama_menu']), ENT_QUOTES, 'UTF-8'); ?>, <?= (int)$menu['harga']; ?>, <?= htmlspecialchars(json_encode($menu['foto_menu'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>, <?= htmlspecialchars(json_encode($toko['nama_toko']), ENT_QUOTES, 'UTF-8'); ?>, <?= (int)$toko['id_toko']; ?>, <?= (int)$menu['stok']; ?>, <?= (int)($menu['is_fleksibel'] ?? 0); ?>);">
                                                 <i class="fa-solid fa-plus"></i> Tambah
                                             </button>
                                         <?php endif; ?>
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

        <!-- ── OVERLAY DETAIL MENU ── -->
        <div id="section-menu-detail" class="kategori-overlay-page">
            <div class="kategori-detail-header">
                <button class="btn-back-kategori" onclick="tutupDetailMenu()">
                    <i class="fa-solid fa-arrow-left"></i>
                </button>
                <h2 class="kategori-detail-title" id="menuDetailTitle">Detail Menu</h2>
            </div>
            <div class="kategori-detail-content" style="background: #ffffff;">
                <div id="menuDetailContainer" class="menu-detail-content-wrapper">
                    <!-- Dynamically populated via JS -->
                </div>
            </div>
        </div>

        <!-- ── Floating Cart Button ── -->
        <button class="fab-cart" title="Lihat Keranjang">
            <i class="fa-solid fa-cart-shopping"></i>
            <span class="fab-cart-badge" id="fabCartBadge">0</span>
        </button>
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

            const ALL_MENUS = <?= json_encode(array_map(function ($m) use ($toko) {
                return [
                    'id_menu' => (int) $m['id_menu'],
                    'nama_menu' => $m['nama_menu'],
                    'deskripsi' => $m['deskripsi'] ?? '',
                    'harga' => (int) $m['harga'],
                    'foto_menu' => $m['foto_menu'] ?? '',
                    'kategori' => strtolower($m['kategori'] ?? 'makanan'),
                    'nama_toko' => $toko['nama_toko'],
                    'id_toko' => (int) $toko['id_toko'],
                    'stok' => (int) $m['stok'],
                    'status_toko' => strtolower($toko['status'] ?? 'tutup'),
                    'is_fleksibel' => (int) ($m['is_fleksibel'] ?? 0)
                ];
            }, $menus)); ?>;

            let activeDetailMenu = null;
            let activeDetailPrice = 0;

            function handleMenuCardClick(id) {
                bukaDetailMenu(id);
            }

            function bukaDetailMenu(id) {
                const menu = ALL_MENUS.find(m => m.id_menu === id);
                if (!menu) return;
                
                activeDetailMenu = menu;
                
                const overlay = document.getElementById('section-menu-detail');
                const container = document.getElementById('menuDetailContainer');
                
                // Format Image
                const imgWrapHTML = renderDetailMenuImageHTML(menu.foto_menu, menu.kategori, menu.nama_menu);
                
                // Pricing layout
                let pricingHTML = '';
                if (menu.is_fleksibel === 1) {
                    const suggestions = [2000, 5000, 10000, 15000, 20000, 25000];
                    const chipsHtml = suggestions.map(price => `
                        <button type="button" class="price-chip-btn-detail" data-val="${price}" onclick="setDetailFlexPrice(${price}, this)">
                            Rp ${price.toLocaleString('id-ID')}
                        </button>
                    `).join('');
                    
                    pricingHTML = `
                        <div class="menu-detail-divider"></div>
                        <div class="menu-detail-section-title">Harga Fleksibel</div>
                        <div style="font-size: 13px; color: #64748b; margin-bottom: 10px;">Menu ini memiliki harga fleksibel. Masukkan harga yang Anda inginkan:</div>
                        <div class="form-group" style="margin-bottom: 16px;">
                            <div style="position: relative; display: flex; align-items: center;">
                                <span style="position: absolute; left: 16px; font-family: 'Poppins', sans-serif; font-size: 18px; font-weight: 700; color: #64748b;">Rp</span>
                                <input type="number" id="detailFlexPriceInput" placeholder="Masukkan harga..." min="1000" max="50000" step="500" oninput="updateDetailFlexPriceFromInput(this)" style="
                                    width: 100%;
                                    padding: 14px 16px 14px 44px;
                                    border: 2px solid #cbd5e1;
                                    border-radius: 16px;
                                    font-family: 'Poppins', sans-serif;
                                    font-size: 18px;
                                    font-weight: 700;
                                    color: #1e293b;
                                    outline: none;
                                    box-sizing: border-box;
                                    transition: border-color 0.2s;
                                ">
                            </div>
                            <div id="detailPriceValidationError" style="color: #ef4444; font-size: 12px; font-weight: 600; margin-top: 6px; display: none;">Minimal harga pembelian Rp 1.000</div>
                        </div>
                        <div style="margin-bottom: 16px;">
                            <div style="font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Rekomendasi Harga</div>
                            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                ${chipsHtml}
                            </div>
                        </div>
                    `;
                } else {
                    pricingHTML = `
                        <div class="menu-detail-divider"></div>
                        <div class="menu-detail-section-title">Harga</div>
                        <div class="menu-detail-price" id="menuDetailPriceLabel">
                            Rp ${Number(menu.harga).toLocaleString('id-ID')}
                        </div>
                    `;
                }

                // Description HTML
                const desc = menu.deskripsi || '';
                const descHTML = `
                    <div class="menu-detail-section-title">Deskripsi Menu</div>
                    <div class="menu-detail-desc" style="margin-bottom: 16px;">
                        ${desc ? desc : '<span style="color:#94a3b8; font-style: italic;">Tidak ada deskripsi untuk menu ini.</span>'}
                    </div>
                `;
                
                // Stock display
                let stockBadgeHTML = '';
                if (menu.stok <= 0) {
                    stockBadgeHTML = `<span class="menu-item-stock-badge out">Stok Habis</span>`;
                } else if (menu.stok <= 5) {
                    stockBadgeHTML = `<span class="menu-item-stock-badge low">Sisa ${menu.stok}</span>`;
                } else {
                    stockBadgeHTML = `<span class="menu-item-stock-badge">Stok Tersedia (${menu.stok})</span>`;
                }
                
                // Canteen status display
                const isTokoBuka = menu.status_toko === 'buka';
                const tokoBadgeHTML = isTokoBuka 
                    ? `<span class="toko-status-badge open">Kantin Buka</span>`
                    : `<span class="toko-status-badge closed">Kantin Tutup</span>`;

                container.innerHTML = `
                    <div class="menu-detail-card">
                        ${imgWrapHTML}
                        
                        <div class="menu-detail-badge-row" style="display: flex; gap: 8px; margin-top: 16px; margin-bottom: 8px; flex-wrap: wrap;">
                            <span class="menu-item-category ${menu.kategori}">${menu.kategori.charAt(0).toUpperCase() + menu.kategori.slice(1)}</span>
                            ${stockBadgeHTML}
                            ${tokoBadgeHTML}
                        </div>
                        
                        <h2 class="menu-detail-name">${menu.nama_menu}</h2>
                        <div class="menu-detail-toko">
                            <i class="fa-solid fa-store" style="color:#16a34a"></i>
                            <span>${menu.nama_toko}</span>
                        </div>
                        
                        <div class="menu-detail-divider"></div>
                        ${descHTML}
                        
                        ${pricingHTML}
                        
                        <div class="menu-detail-divider"></div>
                        <div class="menu-detail-section-title">Jumlah yang Ingin Dibeli</div>
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; background: #f8fafc; padding: 12px 16px; border-radius: 16px; border: 1.5px solid #f1f5f9;">
                            <span style="font-size: 14px; font-weight: 600; color: #475569;">Kuantitas</span>
                            <div class="qty-counter-container" style="display: flex; align-items: center; gap: 12px; background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 12px; padding: 4px 8px;">
                                <button type="button" class="qty-btn-detail" onclick="adjustDetailQtyBtn(-1)" style="background:none; border:none; color:#16a34a; font-size:16px; font-weight:bold; cursor:pointer; width:28px; height:28px; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-minus"></i></button>
                                <input type="number" id="detailQtyInput" value="1" min="1" max="${menu.stok}" oninput="validateDetailQtyInput(this)" style="width: 40px; text-align: center; font-family: 'Poppins', sans-serif; font-size: 16px; font-weight: 700; border: none; outline: none; -moz-appearance: textfield;">
                                <button type="button" class="qty-btn-detail" onclick="adjustDetailQtyBtn(1)" style="background:none; border:none; color:#16a34a; font-size:16px; font-weight:bold; cursor:pointer; width:28px; height:28px; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-plus"></i></button>
                            </div>
                        </div>
                        
                        <button class="btn-add-to-cart-large" id="btnAddToCartLarge" onclick="tambahKeKeranjangDariDetail()" style="
                            width: 100%;
                            padding: 16px;
                            border: none;
                            border-radius: 16px;
                            background: ${isTokoBuka && menu.stok > 0 ? 'linear-gradient(135deg, #16a34a, #15803d)' : '#cbd5e1'};
                            color: #ffffff;
                            font-family: 'Poppins', sans-serif;
                            font-size: 16px;
                            font-weight: 700;
                            cursor: ${isTokoBuka && menu.stok > 0 ? 'pointer' : 'not-allowed'};
                            transition: all 0.2s;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            gap: 10px;
                            box-shadow: ${isTokoBuka && menu.stok > 0 ? '0 4px 12px rgba(22, 163, 74, 0.2)' : 'none'};
                        " ${isTokoBuka && menu.stok > 0 ? '' : 'disabled'}>
                            <i class="fa-solid fa-cart-plus"></i>
                            <span id="btnAddToCartLargeText">
                                ${!isTokoBuka ? 'Kantin Tutup' : (menu.stok <= 0 ? 'Stok Habis' : 'Tambah ke Keranjang (Rp ' + Number(menu.harga).toLocaleString('id-ID') + ')')}
                            </span>
                        </button>
                    </div>
                `;
                
                document.body.style.overflow = 'hidden'; // Lock scrolling
                overlay.classList.add('active');
                
                if (menu.is_fleksibel !== 1) {
                    activeDetailPrice = menu.harga;
                } else {
                    activeDetailPrice = 0;
                }
            }
            
            function renderDetailMenuImageHTML(foto_menu, kategori, nama_menu) {
                const kat = (kategori || 'makanan').toLowerCase();
                let img_src = '';
                if (foto_menu) {
                    img_src = '../../assets/img/menu/' + foto_menu;
                }

                let svgContent = '';
                if (kat === 'minuman') {
                    svgContent = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M3 2l2.01 18.23C5.13 21.23 5.97 22 7 22h10c1.03 0 1.87-.77 1.99-1.77L21 2H3zm9 17c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm1-9H8V8h5v2z" /></svg>`;
                } else if (kat === 'snack') {
                    svgContent = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.06 22.99h1.66c.84 0 1.53-.64 1.63-1.46L23 5.05h-5V1h-1.97v4.05h-4.97l.3 2.34c1.71.47 3.31 1.32 4.27 2.26 1.44 1.42 2.43 2.89 2.43 5.29v8.05zM1 21.99V21h15.03v.99c0 .55-.45 1-1.01 1H2.01c-.56 0-1.01-.45-1.01-1zm15.03-7c0-4.5-6.72-5-8.99-5-2.28 0-9.03.5-9.03 5h18.02z" /></svg>`;
                } else {
                    svgContent = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M11 9H9V2H7v7H5V2H3v7c0 2.12 1.66 3.84 3.75 3.97V22h2.5v-9.03C11.34 12.84 13 11.12 13 9V2h-2v7zm5-3v8h2.5v8H21V2c-2.76 0-5 2.24-5 4z" /></svg>`;
                }

                if (img_src) {
                    return `
                    <div class="menu-detail-img-wrap">
                        <img src="${img_src}" alt="${nama_menu}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="menu-img-placeholder ${kat}" style="display:none; width: 100%; height: 100%; min-height: 220px; align-items: center; justify-content: center; background: #e2e8f0;">
                            ${svgContent}
                        </div>
                    </div>`;
                } else {
                    return `
                    <div class="menu-detail-img-wrap">
                        <div class="menu-img-placeholder ${kat}" style="display:flex; width: 100%; height: 100%; min-height: 220px; align-items: center; justify-content: center; background: #e2e8f0;">
                            ${svgContent}
                        </div>
                    </div>`;
                }
            }

            function tutupDetailMenu() {
                const overlay = document.getElementById('section-menu-detail');
                if (overlay) overlay.classList.remove('active');
                document.body.style.overflow = ''; // Restore scroll
                
                activeDetailMenu = null;
                activeDetailPrice = 0;
            }

            function setDetailFlexPrice(price, el) {
                const input = document.getElementById('detailFlexPriceInput');
                if (input) input.value = price;
                
                document.querySelectorAll('.price-chip-btn-detail').forEach(b => {
                    b.classList.remove('active');
                });
                if (el) el.classList.add('active');
                
                activeDetailPrice = price;
                const errDiv = document.getElementById('detailPriceValidationError');
                if (errDiv) errDiv.style.display = 'none';
                if (input) input.style.borderColor = '#cbd5e1';
                
                updateDetailTotalText();
            }

            function updateDetailFlexPriceFromInput(input) {
                const price = parseInt(input.value) || 0;
                activeDetailPrice = price;
                
                document.querySelectorAll('.price-chip-btn-detail').forEach(b => {
                    b.classList.remove('active');
                });
                
                const errDiv = document.getElementById('detailPriceValidationError');
                if (errDiv) errDiv.style.display = 'none';
                
                if (price >= 1000 && price <= 50000 && price % 500 === 0) {
                    input.style.borderColor = '#16a34a';
                } else if (price > 50000) {
                    input.style.borderColor = '#ef4444';
                    const errDiv = document.getElementById('detailPriceValidationError');
                    if (errDiv) {
                        errDiv.textContent = 'Maksimal harga Rp 50.000';
                        errDiv.style.display = 'block';
                    }
                }
                
                updateDetailTotalText();
            }

            function adjustDetailQtyBtn(delta) {
                const input = document.getElementById('detailQtyInput');
                if (!input || !activeDetailMenu) return;
                
                let val = parseInt(input.value) || 1;
                val += delta;
                
                const max = activeDetailMenu.stok;
                if (val < 1) val = 1;
                if (val > max) {
                    showToast('Stok tidak mencukupi! Maksimum stok: ' + max, 'error');
                    val = max;
                }
                
                input.value = val;
                updateDetailTotalText();
            }

            function validateDetailQtyInput(input) {
                if (!activeDetailMenu) return;
                let val = parseInt(input.value);
                const max = activeDetailMenu.stok;
                
                if (isNaN(val) || val < 1) {
                    return;
                }
                if (val > max) {
                    showToast('Stok tidak mencukupi! Maksimum stok: ' + max, 'error');
                    val = max;
                    input.value = val;
                }
                updateDetailTotalText();
            }

            function updateDetailTotalText() {
                const btnText = document.getElementById('btnAddToCartLargeText');
                if (!btnText || !activeDetailMenu) return;
                
                const qtyInput = document.getElementById('detailQtyInput');
                const qty = parseInt(qtyInput ? qtyInput.value : 1) || 1;
                
                if (activeDetailMenu.status_toko !== 'buka') {
                    btnText.textContent = 'Kantin Tutup';
                    return;
                }
                if (activeDetailMenu.stok <= 0) {
                    btnText.textContent = 'Stok Habis';
                    return;
                }
                
                if (activeDetailMenu.is_fleksibel === 1) {
                    if (activeDetailPrice < 1000) {
                        btnText.textContent = 'Tentukan Harga Dahulu';
                    } else {
                        btnText.textContent = `Tambah ke Keranjang (Rp ${(activeDetailPrice * qty).toLocaleString('id-ID')})`;
                    }
                } else {
                    btnText.textContent = `Tambah ke Keranjang (Rp ${(activeDetailMenu.harga * qty).toLocaleString('id-ID')})`;
                }
            }

            function tambahKeKeranjangDariDetail() {
                if (!activeDetailMenu) return;
                
                if (activeDetailMenu.status_toko !== 'buka') {
                    showToast('Kantin sedang tutup!', 'error');
                    return;
                }
                
                const qtyInput = document.getElementById('detailQtyInput');
                let qty = parseInt(qtyInput ? qtyInput.value : 1);
                if (isNaN(qty) || qty < 1) qty = 1;
                
                const max = activeDetailMenu.stok;
                if (qty > max) {
                    showToast('Stok tidak mencukupi! Maksimum stok: ' + max, 'error');
                    return;
                }
                
                let customHarga = null;
                if (activeDetailMenu.is_fleksibel === 1) {
                    const price = activeDetailPrice;
                    if (price < 1000) {
                        const errDiv = document.getElementById('detailPriceValidationError');
                        if (errDiv) {
                            errDiv.textContent = 'Minimal harga pembelian Rp 1.000';
                            errDiv.style.display = 'block';
                        }
                        const input = document.getElementById('detailFlexPriceInput');
                        if (input) {
                            input.style.borderColor = '#ef4444';
                            input.focus();
                        }
                        return;
                    }
                    if (price > 50000) {
                        const errDiv = document.getElementById('detailPriceValidationError');
                        if (errDiv) {
                            errDiv.textContent = 'Maksimal harga Rp 50.000';
                            errDiv.style.display = 'block';
                        }
                        const input = document.getElementById('detailFlexPriceInput');
                        if (input) {
                            input.style.borderColor = '#ef4444';
                            input.focus();
                        }
                        return;
                    }
                    if (price % 500 !== 0) {
                        const errDiv = document.getElementById('detailPriceValidationError');
                        if (errDiv) {
                            errDiv.textContent = 'Harga harus kelipatan Rp 500 (contoh: 1.000, 1.500, 2.000)';
                            errDiv.style.display = 'block';
                        }
                        const input = document.getElementById('detailFlexPriceInput');
                        if (input) {
                            input.style.borderColor = '#ef4444';
                            input.focus();
                        }
                        return;
                    }
                    customHarga = price;
                }
                
                const hargaToAdd = customHarga !== null ? customHarga : activeDetailMenu.harga;
                
                const success = tambahKeKeranjangWithQty(
                    activeDetailMenu.id_menu,
                    activeDetailMenu.nama_menu,
                    hargaToAdd,
                    activeDetailMenu.foto_menu,
                    activeDetailMenu.nama_toko,
                    activeDetailMenu.id_toko,
                    activeDetailMenu.stok,
                    qty,
                    activeDetailMenu.is_fleksibel,
                    customHarga
                );
                
                if (success) {
                    tutupDetailMenu();
                }
            }

            function tambahKeKeranjangWithQty(id_menu, nama_menu, harga, foto_menu, nama_toko, id_toko, stok, qty = 1, is_fleksibel = 0, customHarga = null) {
                let cart = getCart();
                const activeHarga = Number(customHarga !== null ? customHarga : harga);
                const existingIndex = cart.findIndex(item => Number(item.id_menu) === Number(id_menu) && Number(item.harga) === Number(activeHarga));

                if (existingIndex !== -1) {
                    if (cart[existingIndex].jumlah + qty > stok) {
                        showToast('Stok tidak mencukupi! Maksimum stok: ' + stok, 'error');
                        return false;
                    }
                    cart[existingIndex].jumlah += qty;
                    cart[existingIndex].selected = true;
                } else {
                    if (stok <= 0) {
                        showToast('Stok habis!', 'error');
                        return false;
                    }
                    if (qty > stok) {
                        showToast('Stok tidak mencukupi! Maksimum stok: ' + stok, 'error');
                        return false;
                    }
                    cart.push({
                        id_menu: id_menu,
                        nama_menu: nama_menu,
                        harga: activeHarga,
                        jumlah: qty,
                        foto_menu: foto_menu,
                        nama_toko: nama_toko,
                        id_toko: id_toko,
                        selected: true,
                        catatan: '',
                        stok: stok
                    });
                }
                saveCart(cart);
                showToast(nama_menu + ' x' + qty + ' (Rp ' + (activeHarga * qty).toLocaleString('id-ID') + ')', 'success', { foto: foto_menu, toko: nama_toko });
                return true;
            }

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
            function saveCart(cart, skipRender = false) {
                localStorage.setItem(CART_KEY, JSON.stringify(cart));
                updateBadges();
                if (!skipRender && document.getElementById('cartDrawer').classList.contains('show')) {
                    renderCartDrawer();
                }
                
                // Sinkronisasi asinkron ke database
                fetch('actions/keranjang.php?action=sync', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(cart)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.error) console.error('Gagal sinkronisasi keranjang ke database:', data.error);
                })
                .catch(err => console.error('Koneksi sinkronisasi gagal:', err));
            }

            function fetchDBCart() {
                fetch('actions/keranjang.php?action=list')
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success' && data.cart) {
                        localStorage.setItem(CART_KEY, JSON.stringify(data.cart));
                        updateBadges();
                        if (document.getElementById('cartDrawer') && document.getElementById('cartDrawer').classList.contains('show')) {
                            renderCartDrawer();
                        }
                    }
                })
                .catch(err => console.error('Gagal mengambil keranjang dari database:', err));
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
            function openPriceInputModal(item, onConfirm) {
                const existing = document.getElementById('priceInputModal');
                if (existing) existing.remove();
                
                const modal = document.createElement('div');
                modal.id = 'priceInputModal';
                modal.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.45);
                    backdrop-filter: blur(10px);
                    -webkit-backdrop-filter: blur(10px);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 999999;
                    opacity: 0;
                    transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                `;
                
                const card = document.createElement('div');
                card.style.cssText = `
                    background: #ffffff;
                    padding: 28px 24px;
                    border-radius: 24px;
                    width: 90%;
                    max-width: 380px;
                    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
                    transform: scale(0.9);
                    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                    box-sizing: border-box;
                `;
                
                const suggestions = [2000, 5000, 10000, 15000, 20000, 25000];
                let chipsHtml = suggestions.map(price => `
                    <button type="button" class="price-chip-btn" data-val="${price}" style="
                        padding: 8px 14px;
                        background: #f1f5f9;
                        border: 1.5px solid #e2e8f0;
                        border-radius: 12px;
                        font-family: 'Poppins', sans-serif;
                        font-size: 13px;
                        font-weight: 600;
                        color: #475569;
                        cursor: pointer;
                        transition: all 0.2s;
                    ">Rp ${price.toLocaleString('id-ID')}</button>
                `).join('');
                
                card.innerHTML = `
                    <div style="text-align: center; margin-bottom: 20px;">
                        <div style="width: 56px; height: 56px; background: rgba(92, 184, 92, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;">
                            <i class="fa-solid fa-arrows-left-right-to-line" style="color: #5cb85c; font-size: 24px;"></i>
                        </div>
                        <h3 style="margin: 0; font-family: 'Poppins', sans-serif; font-size: 18px; font-weight: 800; color: #1e293b;">Tentukan Harga</h3>
                        <p style="margin: 4px 0 0; font-family: 'Poppins', sans-serif; font-size: 13px; color: #64748b;">
                            Tentukan harga pembelian untuk <strong>${item.nama_menu}</strong>
                        </p>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <div style="position: relative; display: flex; align-items: center;">
                            <span style="position: absolute; left: 16px; font-family: 'Poppins', sans-serif; font-size: 18px; font-weight: 700; color: #64748b;">Rp</span>
                            <input type="number" id="customPriceInput" placeholder="0" min="1000" style="
                                width: 100%;
                                padding: 14px 16px 14px 44px;
                                border: 2px solid #cbd5e1;
                                border-radius: 16px;
                                font-family: 'Poppins', sans-serif;
                                font-size: 18px;
                                font-weight: 700;
                                color: #1e293b;
                                outline: none;
                                box-sizing: border-box;
                                transition: border-color 0.2s;
                            ">
                        </div>
                        <div id="priceValidationError" style="color: #ef4444; font-size: 12px; font-weight: 600; margin-top: 6px; display: none;">Minimal harga pembelian Rp 1.000</div>
                    </div>
                    
                    <div style="margin-bottom: 24px;">
                        <div style="font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;">Rekomendasi</div>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                            ${chipsHtml}
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 12px;">
                        <button id="cancelPriceBtn" style="
                            flex: 1;
                            padding: 12px;
                            border: 2px solid #cbd5e1;
                            border-radius: 14px;
                            background: #ffffff;
                            color: #475569;
                            font-family: 'Poppins', sans-serif;
                            font-size: 14px;
                            font-weight: 700;
                            cursor: pointer;
                            transition: all 0.2s;
                        ">Batal</button>
                        <button id="confirmPriceBtn" style="
                            flex: 1;
                            padding: 12px;
                            border: none;
                            border-radius: 14px;
                            background: linear-gradient(135deg, #5cb85c, #4cae4c);
                            color: #ffffff;
                            font-family: 'Poppins', sans-serif;
                            font-size: 14px;
                            font-weight: 700;
                            cursor: pointer;
                            transition: all 0.2s;
                            box-shadow: 0 4px 12px rgba(92, 184, 92, 0.2);
                        ">Konfirmasi</button>
                    </div>
                `;
                
                modal.appendChild(card);
                document.body.appendChild(modal);
                
                const input = card.querySelector('#customPriceInput');
                const confirmBtn = card.querySelector('#confirmPriceBtn');
                const cancelBtn = card.querySelector('#cancelPriceBtn');
                const errDiv = card.querySelector('#priceValidationError');
                
                setTimeout(() => {
                    modal.style.opacity = '1';
                    card.style.transform = 'scale(1)';
                    input.focus();
                }, 10);
                
                function closeModal() {
                    modal.style.opacity = '0';
                    card.style.transform = 'scale(0.9)';
                    setTimeout(() => modal.remove(), 300);
                }
                
                card.querySelectorAll('.price-chip-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        input.value = btn.getAttribute('data-val');
                        errDiv.style.display = 'none';
                        input.style.borderColor = '#cbd5e1';
                        
                        card.querySelectorAll('.price-chip-btn').forEach(b => {
                            b.style.background = '#f1f5f9';
                            b.style.borderColor = '#e2e8f0';
                            b.style.color = '#475569';
                        });
                        btn.style.background = 'rgba(92, 184, 92, 0.1)';
                        btn.style.borderColor = '#5cb85c';
                        btn.style.color = '#5cb85c';
                    });
                });
                
                input.addEventListener('input', () => {
                    errDiv.style.display = 'none';
                    input.style.borderColor = '#5cb85c';
                    
                    card.querySelectorAll('.price-chip-btn').forEach(b => {
                        b.style.background = '#f1f5f9';
                        b.style.borderColor = '#e2e8f0';
                        b.style.color = '#475569';
                    });
                });
                
                confirmBtn.addEventListener('click', () => {
                    const val = parseInt(input.value);
                    if (isNaN(val) || val < 1000) {
                        errDiv.style.display = 'block';
                        input.style.borderColor = '#ef4444';
                        input.focus();
                        return;
                    }
                    closeModal();
                    if (onConfirm) onConfirm(val);
                });
                
                cancelBtn.addEventListener('click', closeModal);
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) closeModal();
                });
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') confirmBtn.click();
                });
            }

            function tambahKeKeranjang(id_menu, nama_menu, harga, foto_menu, nama_toko, id_toko, stok, is_fleksibel = 0, customHarga = null) {
                let cart = getCart();

                if (is_fleksibel === 1 && customHarga === null) {
                    openPriceInputModal({nama_menu: nama_menu, id_menu: id_menu}, (price) => {
                        tambahKeKeranjang(id_menu, nama_menu, price, foto_menu, nama_toko, id_toko, stok, is_fleksibel, price);
                    });
                    return;
                }

                const activeHarga = Number(customHarga !== null ? customHarga : harga);
                const existingIndex = cart.findIndex(item => Number(item.id_menu) === Number(id_menu) && Number(item.harga) === Number(activeHarga));

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
                        harga: activeHarga,
                        jumlah: 1,
                        foto_menu: foto_menu,
                        nama_toko: nama_toko,
                        id_toko: id_toko,
                        selected: true,
                        catatan: '',
                        stok: stok
                    });
                }

                saveCart(cart);
                showToast(nama_menu + ' (Rp ' + activeHarga.toLocaleString('id-ID') + ')', 'success', { foto: foto_menu, toko: nama_toko });
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
                        imgHTML = `<img src="../../assets/img/menu/${item.foto_menu}" alt="${item.nama_menu}" onerror="this.outerHTML='<div class=\\'cart-img-placeholder\\'><i class=\\'fa-solid fa-utensils\\'></i></div>';">` ;
                    } else {
                        imgHTML = `<div class="cart-img-placeholder"><i class="fa-solid fa-utensils"></i></div>`;
                    }

                    const isSelected = item.selected !== false;

                    html += `
                        <div class="dropdown-item cart-item-row" data-id="${item.id_menu}" data-harga="${item.harga}" style="padding: 12px 0; border-bottom: 1px solid #f1f5f9; display: flex; flex-direction: column; gap: 8px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; width: 100%;">
                                <div style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0;">
                                    <div class="cart-item-checkbox-wrap">
                                        <input type="checkbox" class="cart-item-checkbox" onchange="toggleCartItemSelection(${item.id_menu}, ${item.harga})" ${isSelected ? 'checked' : ''}>
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
                                    <div class="item-subtotal" style="font-size: 14px; font-weight: 800; color: #1e293b;">Rp ${(item.harga * item.jumlah).toLocaleString('id-ID')}</div>
                                    <div class="item-qty" style="display: inline-flex; align-items: center; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; background: #f8fafc;">
                                        <button onclick="updateCartQtyToko(${item.id_menu}, ${item.harga}, -1, event)" style="border: none; background: none; padding: 4px 10px; cursor: pointer; font-size: 14px; font-weight: 700; color: #64748b; transition: background 0.2s;">−</button>
                                        <input type="number" class="item-qty-input" value="${item.jumlah}" min="0" max="${item.stok || 999}" onchange="manualUpdateCartQtyToko(${item.id_menu}, ${item.harga}, this.value, ${item.stok || 999})" onkeydown="if(event.key === 'Enter') this.blur();" onclick="event.stopPropagation()">
                                        <button onclick="updateCartQtyToko(${item.id_menu}, ${item.harga}, 1, event)" style="border: none; background: none; padding: 4px 10px; cursor: pointer; font-size: 14px; font-weight: 700; color: #64748b; transition: background 0.2s;">+</button>
                                    </div>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 6px; padding-left: 28px; width: 100%; box-sizing: border-box;">
                                <i class="fa-regular fa-comment-dots" style="color: #94a3b8; font-size: 12px;"></i>
                                <input type="text" class="cart-item-note-input" value="${item.catatan || ''}" placeholder="Tambah catatan..." onchange="updateCartItemNote(${item.id_menu}, ${item.harga}, this.value)" style="flex: 1; border: 1px solid #f1f5f9; border-radius: 6px; padding: 4px 8px; font-size: 11px; color: #64748b; outline: none; background: #f8fafc; transition: all 0.2s;" onfocus="this.style.borderColor='#5cb85c'; this.style.background='#ffffff'" onblur="this.style.borderColor='#f1f5f9'; this.style.background='#f8fafc'">
                            </div>
                        </div>
                    `;
                });
                body.innerHTML = html;
            }

            function updateCartItemNote(id, harga, val) {
                const cart = getCart();
                const item = cart.find(c => Number(c.id_menu) === Number(id) && Number(c.harga) === Number(harga));
                if (item) {
                    item.catatan = val.trim();
                    saveCart(cart);
                }
            }

            function updateCartQtyToko(id_menu, harga, delta) {
                let cart = getCart();
                const existingIndex = cart.findIndex(item => Number(item.id_menu) === Number(id_menu) && Number(item.harga) === Number(harga));
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

            function manualUpdateCartQtyToko(id_menu, harga, value, maxStock) {
                let qty = parseInt(value);
                let cart = getCart();
                const existingIndex = cart.findIndex(item => Number(item.id_menu) === Number(id_menu) && Number(item.harga) === Number(harga));
                if (existingIndex !== -1) {
                    if (isNaN(qty) || qty < 0) {
                        qty = 1; // Default fallback for invalid/empty inputs
                    }

                    if (qty === 0) {
                        cart.splice(existingIndex, 1);
                    } else {
                        if (qty > maxStock) {
                            showToast('Stok tidak mencukupi! Maksimum stok: ' + maxStock, 'error');
                            qty = maxStock;
                        }
                        cart[existingIndex].jumlah = qty;
                    }
                    saveCart(cart);
                }
            }

            // ── Toggle Item Selection in Cart ──
            function toggleCartItemSelection(id, harga) {
                const cart = getCart();
                const item = cart.find(c => Number(c.id_menu) === Number(id) && Number(c.harga) === Number(harga));
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
                
                // Ambil semua input catatan di keranjang untuk memastikan catatan terbaru tersimpan
                document.querySelectorAll('.cart-item-note-input').forEach(input => {
                    const row = input.closest('.cart-item-row');
                    if (row) {
                        const id = Number(row.getAttribute('data-id'));
                        const harga = Number(row.getAttribute('data-harga'));
                        const val = input.value.trim();
                        
                        const item = cart.find(c => Number(c.id_menu) === id && Number(c.harga) === harga);
                        if (item) {
                            item.catatan = val;
                        }
                    }
                });
                
                // Simpan ke localStorage
                localStorage.setItem('ekantin_cart', JSON.stringify(cart));
                updateBadges();

                const selectedItems = cart.filter(item => item.selected !== false);
                if (selectedItems.length === 0) {
                    showToast('Silakan pilih item yang ingin dibeli!', 'error');
                    return;
                }

                const btn = document.querySelector('.cart-drawer-btn');
                let originalHTML = '';
                if (btn) {
                    originalHTML = btn.innerHTML;
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyiapkan...';
                }

                // Sinkronisasi ke database dulu baru redirect
                fetch('actions/keranjang.php?action=sync', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(cart)
                })
                .then(res => res.json())
                .then(data => {
                    window.location.href = 'checkout.php';
                })
                .catch(err => {
                    console.error('Koneksi sinkronisasi gagal:', err);
                    window.location.href = 'checkout.php';
                });
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

            function handleSearchKeydown(e) {
                if (e.key === 'Enter') {
                    const val = e.target.value.trim().toLowerCase();
                    if (val.length >= 2) {
                        const menu = ALL_MENUS.find(m => m.nama_menu.toLowerCase().includes(val));
                        if (menu) {
                            handleMenuCardClick(menu.id_menu);
                            e.target.blur();
                        }
                    }
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

            // ── Slideshow Latar Belakang Toko ──
            let currentHeroSlideIndex = 0;
            let heroSlideshowInterval = null;

            window.currentHeroSlide = function(index) {
                showHeroSlide(index);
                resetHeroSlideshowTimer();
            };

            function showHeroSlide(index) {
                const slides = document.querySelectorAll('.hero-slide');
                const dots = document.querySelectorAll('.hero-dot');
                if (slides.length === 0) return;

                if (index >= slides.length) {
                    currentHeroSlideIndex = 0;
                } else if (index < 0) {
                    currentHeroSlideIndex = slides.length - 1;
                } else {
                    currentHeroSlideIndex = index;
                }

                slides.forEach((slide, idx) => {
                    // Reset inline styles applied during dragging
                    slide.style.transform = '';
                    slide.style.transition = '';
                    if (idx === currentHeroSlideIndex) {
                        slide.classList.add('active');
                        slide.style.opacity = '1';
                        slide.style.zIndex = '2';
                    } else {
                        slide.classList.remove('active');
                        slide.style.opacity = '0';
                        slide.style.zIndex = '1';
                    }
                });

                dots.forEach((dot, idx) => {
                    if (idx === currentHeroSlideIndex) {
                        dot.classList.add('active');
                    } else {
                        dot.classList.remove('active');
                    }
                });
            }

            function startHeroSlideshow() {
                const slides = document.querySelectorAll('.hero-slide');
                if (slides.length <= 1) return;
                heroSlideshowInterval = setInterval(() => {
                    showHeroSlide(currentHeroSlideIndex + 1);
                }, 5000);
            }

            function resetHeroSlideshowTimer() {
                if (heroSlideshowInterval) {
                    clearInterval(heroSlideshowInterval);
                }
                startHeroSlideshow();
            }

            // ── Swipe & Drag Touch support for Slideshow ──
            function initSlideshowSwipe() {
                const container = document.querySelector('.hero-slideshow-container');
                if (!container) return;

                const slides = container.querySelectorAll('.hero-slide');
                if (slides.length <= 1) return;

                let startX = 0;
                let startY = 0;
                let isDragging = false;
                let diffX = 0;
                const threshold = 50; // min distance in px to trigger slide change

                // Grab cursor for desktop feel
                container.style.cursor = 'grab';

                // Prevent default image drag ghosting
                container.addEventListener('dragstart', (e) => {
                    if (e.target.tagName === 'IMG') {
                        e.preventDefault();
                    }
                });

                function getEventX(e) {
                    return e.touches && e.touches.length > 0 ? e.touches[0].clientX : e.clientX;
                }

                function getEventY(e) {
                    return e.touches && e.touches.length > 0 ? e.touches[0].clientY : e.clientY;
                }

                function handleStart(e) {
                    // Stop auto-slide timer temporarily during interaction
                    if (heroSlideshowInterval) {
                        clearInterval(heroSlideshowInterval);
                    }

                    startX = getEventX(e);
                    startY = getEventY(e);
                    isDragging = true;
                    diffX = 0;

                    if (!e.touches) {
                        container.style.cursor = 'grabbing';
                    }

                    const activeSlide = container.querySelector('.hero-slide.active');
                    if (activeSlide) {
                        activeSlide.style.transition = 'none';
                    }
                }

                function handleMove(e) {
                    if (!isDragging) return;

                    const currentX = getEventX(e);
                    const currentY = getEventY(e);
                    diffX = currentX - startX;
                    const diffY = currentY - startY;

                    // If user is swiping horizontally, prevent page scroll
                    if (Math.abs(diffX) > Math.abs(diffY)) {
                        if (e.cancelable) e.preventDefault();

                        // Apply visual drag offset with resistance
                        const activeSlide = container.querySelector('.hero-slide.active');
                        if (activeSlide) {
                            activeSlide.style.transform = `translateX(${diffX * 0.4}px)`;
                        }
                    }
                }

                function handleEnd() {
                    if (!isDragging) return;
                    isDragging = false;
                    container.style.cursor = 'grab';

                    const activeSlide = container.querySelector('.hero-slide.active');
                    if (activeSlide) {
                        // Smoothly animate back to center or let slide change handle it
                        activeSlide.style.transition = 'opacity 0.8s ease-in-out, transform 0.3s ease-out';
                        activeSlide.style.transform = 'translateX(0)';
                    }

                    if (Math.abs(diffX) >= threshold) {
                        if (diffX > 0) {
                            // Swipe Right -> Go to Previous Slide
                            showHeroSlide(currentHeroSlideIndex - 1);
                        } else {
                            // Swipe Left -> Go to Next Slide
                            showHeroSlide(currentHeroSlideIndex + 1);
                        }
                    }

                    // Resume auto-slide
                    resetHeroSlideshowTimer();
                }

                // Touch support (mobile)
                container.addEventListener('touchstart', handleStart, { passive: true });
                container.addEventListener('touchmove', handleMove, { passive: false });
                container.addEventListener('touchend', handleEnd);

                // Mouse drag support (laptop/desktop)
                container.addEventListener('mousedown', handleStart);
                
                // Add move/up to window so drag behaves correctly even if cursor leaves banner
                window.addEventListener('mousemove', (e) => {
                    if (isDragging) handleMove(e);
                });
                window.addEventListener('mouseup', () => {
                    if (isDragging) handleEnd();
                });
            }

            // ── Initialize on page load ──
            document.addEventListener('DOMContentLoaded', function () {
                fetchDBCart();
                updateBadges();
                initFavorites();
                makeCartDraggable();
                
                // Initialize background banner canvases
                if (typeof BannerCanvas !== 'undefined') {
                    BannerCanvas.initAll(document.querySelector('.toko-hero-banner') || document);
                }
                // Start background slideshow
                startHeroSlideshow();
                // Initialize touch/drag swipe support
                initSlideshowSwipe();
            });
        </script>
    <?php endif; ?>

</body>

</html>