<?php
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
                $toko_img_src = '../../assets/img/ayam.png';
            }
        }

        // ── Status toko ──
        $is_buka = (strtolower($toko['status'] ?? '') === 'buka');
        $status_kelas = $is_buka ? 'online' : 'offline';
        $status_teks  = $is_buka ? 'Buka' : 'Tutup';

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
$avatar_path = '../../assets/img/' . $avatar_file;
if (empty($avatar_file) || !file_exists(__DIR__ . '/../../assets/img/' . $avatar_file)) {
    $avatar_path = '../../assets/img/PPAril.jpeg';
}
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

    <!-- ── TOP HEADER ── -->
    <header class="main-header">
        <div class="header-pattern-left"></div>
        <div class="header-pattern-right"></div>
        <div class="header-inner">
            <div class="top-bar">
                <div class="logo-area">
                    <img src="../../assets/img/logo-esemkita.png" class="school-logo" style="width: 38px; height: 38px; object-fit: contain; flex-shrink: 0; border-radius: 50%; background-color: #ffffff; padding: 2px;" alt="Logo Esemkita">
                    <span class="brand-name">E-Kantin</span>
                </div>
                <div class="search-container">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" placeholder="Cari menu..." id="searchInput" oninput="searchMenu(this.value)">
                </div>
                <div class="header-icons">
                    <div class="icon-badge" onclick="location.href='index.php'">
                        <i class="fa-solid fa-cart-shopping"></i>
                        <span class="badge" id="headerCartBadge">0</span>
                    </div>
                    <img src="<?= $avatar_path; ?>" class="blank-avatar" alt="Profil Pembeli">
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
                    <a href="index.php" class="btn-kembali">
                        <i class="fa-solid fa-arrow-left"></i>
                        Kembali ke Beranda
                    </a>
                </div>
            <?php else: ?>

                <!-- ── Back Navigation ── -->
                <div class="toko-back-nav">
                    <a href="index.php" class="btn-back" title="Kembali">
                        <i class="fa-solid fa-arrow-left"></i>
                    </a>
                    <span class="back-label">Kembali ke Beranda</span>
                </div>

                <!-- ── Store Hero ── -->
                <div class="toko-hero">
                    <div class="toko-hero-banner">
                        <img src="<?= $toko_img_src; ?>" alt="<?= htmlspecialchars($toko['nama_toko']); ?>">
                    </div>
                    <div class="toko-hero-info">
                        <img src="<?= $toko_img_src; ?>" class="toko-avatar" alt="<?= htmlspecialchars($toko['nama_toko']); ?>">
                        <div class="toko-details">
                            <h1><?= htmlspecialchars($toko['nama_toko']); ?></h1>
                            <p class="toko-desc"><?= htmlspecialchars($toko['deskripsi'] ?? 'Makanan, Snack, & Minuman'); ?></p>
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
                            // Gambar menu
                            $foto_menu = $menu['foto_menu'] ?? '';
                            $menu_img_src = '../../assets/img/ayam.png';
                            if (!empty($foto_menu)) {
                                if (file_exists(__DIR__ . '/../../assets/img/menu/' . $foto_menu)) {
                                    $menu_img_src = '../../assets/img/menu/' . $foto_menu;
                                } elseif (file_exists(__DIR__ . '/../../assets/img/' . $foto_menu)) {
                                    $menu_img_src = '../../assets/img/' . $foto_menu;
                                }
                            }

                            $kategori = strtolower($menu['kategori'] ?? 'lainnya');
                            $stok = intval($menu['stok'] ?? 0);
                            $tersedia = intval($menu['tersedia'] ?? 0);
                            $is_available = ($tersedia && $stok > 0 && $is_buka);
                        ?>
                        <div class="menu-grid-item" data-kategori="<?= htmlspecialchars($kategori); ?>" data-nama="<?= htmlspecialchars(strtolower($menu['nama_menu'])); ?>">
                            <div class="menu-item-img-wrap">
                                <img src="<?= $menu_img_src; ?>" alt="<?= htmlspecialchars($menu['nama_menu']); ?>" loading="lazy">
                                <span class="menu-item-category <?= htmlspecialchars($kategori); ?>">
                                    <?= htmlspecialchars(ucfirst($kategori)); ?>
                                </span>
                                <?php if ($stok <= 0): ?>
                                    <span class="menu-item-stock out">Habis</span>
                                <?php elseif ($stok <= 5): ?>
                                    <span class="menu-item-stock low">Sisa <?= $stok; ?></span>
                                <?php else: ?>
                                    <span class="menu-item-stock">Stok <?= $stok; ?></span>
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
                                    <button class="btn-tambah"
                                        <?= !$is_available ? 'disabled' : ''; ?>
                                        onclick="tambahKeKeranjang(<?= $menu['id_menu']; ?>, '<?= htmlspecialchars(addslashes($menu['nama_menu']), ENT_QUOTES); ?>', <?= $menu['harga']; ?>, '<?= htmlspecialchars(addslashes($menu_img_src), ENT_QUOTES); ?>', '<?= htmlspecialchars(addslashes($toko['nama_toko']), ENT_QUOTES); ?>', <?= $toko['id_toko']; ?>)">
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
    <!-- ── Toast Notification ── -->
    <div class="toast-notification" id="toastNotif">
        <i class="fa-solid fa-circle-check"></i>
        <span id="toastMessage">Item ditambahkan ke keranjang!</span>
    </div>

    <!-- ── Floating Cart Button ── -->
    <a href="index.php" class="fab-cart" title="Lihat Keranjang">
        <i class="fa-solid fa-cart-shopping"></i>
        <span class="fab-cart-badge" id="fabCartBadge">0</span>
    </a>

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
    function tambahKeKeranjang(id_menu, nama_menu, harga, foto_menu, nama_toko, id_toko) {
        let cart = getCart();

        const existingIndex = cart.findIndex(item => item.id_menu === id_menu);

        if (existingIndex !== -1) {
            cart[existingIndex].jumlah += 1;
        } else {
            cart.push({
                id_menu: id_menu,
                nama_menu: nama_menu,
                harga: harga,
                jumlah: 1,
                foto_menu: foto_menu,
                nama_toko: nama_toko,
                id_toko: id_toko
            });
        }

        saveCart(cart);
        updateBadges();
        showToast('Item ditambahkan ke keranjang!');
    }

    // ── Show toast notification ──
    function showToast(message) {
        const toast = document.getElementById('toastNotif');
        const toastMsg = document.getElementById('toastMessage');
        if (toastMsg) toastMsg.textContent = message;

        toast.classList.add('show');

        setTimeout(() => {
            toast.classList.remove('show');
        }, 2500);
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

    // ── Initialize on page load ──
    document.addEventListener('DOMContentLoaded', function() {
        updateBadges();
    });
    </script>
    <?php endif; ?>

</body>
</html>
