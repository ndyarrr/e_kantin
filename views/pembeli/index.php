<?php
//pembeli.index.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/banner_canvas.php';
$koneksi = $conn;

// Ambil foto profil pembeli dari session
$avatar_file = $_SESSION['user_foto'] ?? '';
$has_avatar = !empty($avatar_file) && file_exists(__DIR__ . '/../../assets/img/' . $avatar_file);
$avatar_path = $has_avatar ? '../../assets/img/' . $avatar_file : '';
$user_nama = $_SESSION['user_nama'] ?? 'Pembeli';
$user_role = $_SESSION['user_role'] ?? 'siswa';
$user_id = $_SESSION['user_id'] ?? '';

// ── Ambil 5 pesanan terbaru untuk notifikasi ──
$notifications = [];
$unread_notif_count = 0;
if (!empty($user_id)) {
    $col_pembeli = ($user_role === 'siswa') ? 'nisn_pembeli' : 'nuptk_pembeli';
    $q_notif = mysqli_query($koneksi, "
        SELECT p.id_pesanan, p.status, p.waktu_pesan, t.nama_toko 
        FROM pesanan p
        JOIN toko t ON p.id_toko = t.id_toko
        WHERE p.$col_pembeli = '$user_id'
        ORDER BY p.waktu_pesan DESC
        LIMIT 5
    ");
    if ($q_notif) {
        while ($r = mysqli_fetch_assoc($q_notif)) {
            $notifications[] = $r;
            if (in_array($r['status'], ['menunggu', 'diproses', 'siap'])) {
                $unread_notif_count++;
            }
        }
    }
}

// ── Ambil SEMUA menu tersedia (untuk section Beranda + Kantin) ──
$all_menus = [];
$q_all_menu = mysqli_query($koneksi, "SELECT menu.*, toko.nama_toko, toko.id_toko, toko.status AS status_toko FROM menu 
                                      JOIN toko ON menu.id_toko = toko.id_toko 
                                      WHERE menu.tersedia = 1 AND menu.stok > 0 
                                      AND menu.deleted_at IS NULL AND toko.deleted_at IS NULL
                                      ORDER BY menu.id_menu DESC");
if ($q_all_menu) {
    while ($r = mysqli_fetch_assoc($q_all_menu))
        $all_menus[] = $r;
}

// ── Ambil 6 menu terlaris untuk section Beranda ──
$terlaris_menus = [];
$q_terlaris = mysqli_query($koneksi, "SELECT menu.*, toko.nama_toko, toko.id_toko, toko.status AS status_toko FROM menu 
                                      JOIN toko ON menu.id_toko = toko.id_toko 
                                      WHERE menu.tersedia = 1 
                                      AND menu.deleted_at IS NULL AND toko.deleted_at IS NULL
                                      ORDER BY menu.terjual DESC, menu.id_menu DESC 
                                      LIMIT 6");
if ($q_terlaris) {
    while ($r = mysqli_fetch_assoc($q_terlaris)) {
        $terlaris_menus[] = $r;
    }
}

// ── Ambil favorit dari DB ──
$user_favs = [];
if (!empty($user_id)) {
    $stmt_fav = mysqli_prepare($koneksi, "SELECT id_menu FROM favorit WHERE user_id = ? AND user_role = ?");
    mysqli_stmt_bind_param($stmt_fav, 'ss', $user_id, $user_role);
    mysqli_stmt_execute($stmt_fav);
    $res_fav = mysqli_stmt_get_result($stmt_fav);
    while ($r = mysqli_fetch_assoc($res_fav))
        $user_favs[] = (int) $r['id_menu'];
    mysqli_stmt_close($stmt_fav);
}

// ── Ambil SEMUA toko ──
$all_tokos = [];
$q_all_toko = mysqli_query($koneksi, "SELECT * FROM toko WHERE deleted_at IS NULL ORDER BY FIELD(status, 'buka', 'tutup'), nama_toko ASC");
if ($q_all_toko) {
    while ($r = mysqli_fetch_assoc($q_all_toko))
        $all_tokos[] = $r;
}

// ── Ambil data banner promo aktif ──
$promo_banners = [];
$q_banners = mysqli_query($koneksi, "SELECT bp.*, t.nama_toko FROM banner_promo bp 
                                      JOIN toko t ON bp.id_toko = t.id_toko 
                                      WHERE bp.aktif = 1 
                                      AND bp.deleted_at IS NULL 
                                      AND bp.berlaku_hingga >= CURDATE() 
                                      ORDER BY bp.id_banner DESC");
if ($q_banners) {
    while ($r = mysqli_fetch_assoc($q_banners)) {
        $promo_banners[] = $r;
    }
}

// Helper: resolve image path
function resolveMenuImg($foto)
{
    if (!empty($foto)) {
        if (file_exists(__DIR__ . '/../../assets/img/menu/' . $foto))
            return '../../assets/img/menu/' . $foto;
        if (file_exists(__DIR__ . '/../../assets/img/' . $foto))
            return '../../assets/img/' . $foto;
    }
    return '';
}

function resolveTokoImg($foto)
{
    if (!empty($foto)) {
        if (file_exists(__DIR__ . '/../../assets/img/kantin/' . $foto))
            return '../../assets/img/kantin/' . $foto;
        if (file_exists(__DIR__ . '/../../assets/img/' . $foto))
            return '../../assets/img/' . $foto;
    }
    return '';
}

require_once __DIR__ . '/../../config/banner_canvas.php';

function renderPromoSlides(array $banners, int $activeIndex = 0): void
{
    foreach ($banners as $index => $banner) {
        ?>
        <div class="promo-slide <?= $index === $activeIndex ? 'active' : '' ?>"
            data-toko-name="<?= htmlspecialchars($banner['nama_toko']) ?>">
            <a href="toko.php?id=<?= (int) $banner['id_toko'] ?>" class="promo-slide-link banner-canvas-viewport" <?= bannerCanvasDataAttrs($banner['canvas_config'] ?? '') ?>>
                <img src="../../assets/img/banner/<?= htmlspecialchars($banner['gambar']) ?>"
                    alt="Banner <?= htmlspecialchars($banner['nama_toko']) ?>">
            </a>
        </div>
        <?php
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Kantin - Beranda Pembeli</title>
    <link rel="stylesheet" href="../../assets/css/pembeli.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Stylesheets consolidated in assets/css/pembeli.css -->
</head>

<body>

    <!-- ── TOP HEADER ── -->
    <header class="main-header">
    <div class="header-inner">
        <div class="top-bar">
            <div class="logo-area">
                <img src="../../assets/img/logo_ekantin_hijau.png" class="school-logo" alt="Logo">
                <div class="greeting-area">
                    <span class="greeting-name">Halo, <?= htmlspecialchars($user_nama); ?>!</span>
                    <span class="greeting-sub">Selamat datang di E-Kantin!</span>
                </div>
            </div>
            
            <div class="header-icons">
                
                <div class="dropdown-wrapper">
                    <div class="icon-badge" onclick="toggleDropdown('notifDrop')">
                        <i class="fa-regular fa-bell"></i>
                        <?php if ($unread_notif_count > 0): ?>
                            <span class="badge" id="notifBadge"><?= $unread_notif_count ?></span>
                        <?php else: ?>
                            <span class="badge" id="notifBadge" style="display: none;"></span>
                        <?php endif; ?>
                    </div>
                    <div class="dropdown-panel" id="notifDrop">
                        <div class="dropdown-header" style="border-bottom: 1px solid #f1f5f9; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
                            <h3 style="margin:0; font-size: 15px; font-weight: 800; color: #1e293b;">Notifikasi</h3>
                            <span style="font-size: 12px; color: #5cb85c; font-weight: 700; cursor: pointer;" onclick="tandaiSemuaNotifDibaca()">Tandai semua dibaca</span>
                        </div>
                        <div class="dropdown-body" id="notifBody" style="padding: 0; max-height: 400px; overflow-y: auto;">
                            <?php if (empty($notifications)): ?>
                                <div style="padding: 40px 20px; text-align: center; color: #94a3b8;">
                                    <i class="fa-regular fa-bell-slash" style="font-size: 32px; margin-bottom: 10px; display: block; color: #cbd5e1;"></i>
                                    <span style="font-size: 13px; font-weight: 600;">Belum ada notifikasi</span>
                                </div>
                            <?php else: ?>
                                <?php foreach ($notifications as $n): 
                                    $status = $n['status'];
                                    $toko = htmlspecialchars($n['nama_toko']);
                                    $id_pesanan = $n['id_pesanan'];
                                    
                                    // Tentukan ikon, warna latar, judul, dan keterangan
                                    $icon = 'fa-receipt';
                                    $bg_color = 'bg-blue';
                                    $title = 'Status Pesanan';
                                    $desc = '';
                                    $is_unread = in_array($status, ['menunggu', 'diproses', 'siap']);
                                    
                                    if ($status === 'menunggu') {
                                        $icon = 'fa-clock';
                                        $bg_color = 'bg-orange';
                                        $title = 'Pesanan Menunggu Konfirmasi';
                                        $desc = "Pesananmu #$id_pesanan di $toko sedang menunggu konfirmasi.";
                                    } elseif ($status === 'diproses') {
                                        $icon = 'fa-fire-burner';
                                        $bg_color = 'bg-blue';
                                        $title = 'Pesanan Sedang Disiapkan';
                                        $desc = "Kantin $toko sedang menyiapkan pesananmu #$id_pesanan.";
                                    } elseif ($status === 'siap') {
                                        $icon = 'fa-circle-check';
                                        $bg_color = 'bg-green';
                                        $title = 'Pesanan Siap Diambil';
                                        $desc = "Hore! Pesananmu #$id_pesanan di $toko sudah siap diambil.";
                                    } elseif ($status === 'selesai') {
                                        $icon = 'fa-circle-check';
                                        $bg_color = 'bg-teal';
                                        $title = 'Pesanan Selesai';
                                        $desc = "Terima kasih! Pesananmu #$id_pesanan di $toko telah selesai.";
                                    } elseif ($status === 'dibatalkan') {
                                        $icon = 'fa-circle-xmark';
                                        $bg_color = 'bg-red';
                                        $title = 'Pesanan Dibatalkan';
                                        $desc = "Maaf, pesananmu #$id_pesanan di $toko dibatalkan oleh kantin.";
                                    }
                                    
                                    // Hitung waktu relatif sederhana
                                    $waktu_pesan = strtotime($n['waktu_pesan']);
                                    $selisih = time() - $waktu_pesan;
                                    if ($selisih < 60) {
                                        $waktu_str = 'Baru saja';
                                    } elseif ($selisih < 3600) {
                                        $waktu_str = round($selisih / 60) . ' menit yang lalu';
                                    } elseif ($selisih < 86400) {
                                        $waktu_str = round($selisih / 3600) . ' jam yang lalu';
                                    } else {
                                        $waktu_str = round($selisih / 86400) . ' hari yang lalu';
                                    }
                                ?>
                                    <div class="notif-item-modern <?= $is_unread ? 'unread' : '' ?>" onclick="switchNav('pesanan')" style="cursor: pointer;">
                                        <div class="notif-icon-wrap <?= $bg_color ?>"><i class="fa-solid <?= $icon ?>"></i></div>
                                        <div class="notif-content">
                                            <h4><?= $title ?></h4>
                                            <p><?= $desc ?></p>
                                            <span class="notif-time"><?= $waktu_str ?></span>
                                        </div>
                                        <?php if ($is_unread): ?>
                                            <div class="unread-indicator"></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="dropdown-wrapper">
                    <div class="icon-badge" onclick="toggleCartDrawer()">
                        <i class="fa-solid fa-cart-shopping"></i>
                        <span class="badge" id="headerCartBadge" style="display: none;"></span>
                    </div>
                </div>

                <div class="dropdown-wrapper">
                    <?php if ($has_avatar): ?>
                        <img src="<?= $avatar_path; ?>" class="blank-avatar" id="topbarAvatarImg" alt="Profil" onclick="switchNav('profil')" style="cursor: pointer;">
                    <?php else: ?>
                        <div class="avatar-initials size-sm" id="topbarAvatarInit" onclick="switchNav('profil')" style="cursor: pointer;">
                            <?= strtoupper(substr($user_nama, 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>

        <div class="search-container">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input type="text" id="searchInput" placeholder="Cari menu atau kantin..." oninput="handleSearch(this.value)" onkeydown="handleSearchKeydown(event)">
        </div>

        <nav class="nav-menu-wrapper">
            <div class="nav-menu-container">
                <a href="#" class="nav-item active" data-nav="beranda" onclick="switchNav('beranda')">
                    <i class="fa-solid fa-house"></i> <span>Beranda</span>
                </a>
                <a href="#" class="nav-item" data-nav="pesanan" onclick="switchNav('pesanan')">
                    <i class="fa-solid fa-receipt"></i> <span>Pesanan</span>
                </a>
                <a href="#" class="nav-item" data-nav="favorit" onclick="switchNav('favorit')">
                    <i class="fa-solid fa-heart"></i> <span>Favorit</span>
                </a>
                <a href="#" class="nav-item" data-nav="kantin" onclick="switchNav('kantin')">
                    <i class="fa-solid fa-store"></i> <span>Kantin</span>
                </a>
                <a href="#" class="nav-item" data-nav="chat" onclick="switchNav('chat')">
                    <i class="fa-solid fa-comment-dots"></i> <span>Chat</span>
                    <span class="badge" id="chatNotifBadge" style="display: none;"></span>
                </a>
            </div>
        </nav>
    </div>
</header>

    <div id="section-kategori-detail" class="kategori-overlay-page">
        <div class="kategori-detail-header">
            <button class="btn-back-kategori" onclick="tutupDetailKategori()">
                <i class="fa-solid fa-arrow-left"></i>
            </button>
            <h2 class="kategori-detail-title" id="kategoriDetailTitle">Kategori</h2>
        </div>
        <div class="kategori-detail-content">
            <div class="all-menu-grid" id="kategoriDetailGrid">
                </div>
        </div>
    </div>

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
    <main class="content-container">
        <div class="content-inner">
            <?php require __DIR__ . '/sections/beranda.php'; ?>
            <?php require __DIR__ . '/sections/pesanan.php'; ?>
            <?php require __DIR__ . '/sections/favorit.php'; ?>
            <?php require __DIR__ . '/sections/kantin.php'; ?>
            <?php require __DIR__ . '/sections/chat.php'; ?>
            <?php require __DIR__ . '/sections/profil.php'; ?>
        </div>
    </main>
    <button class="fab-cart" title="Lihat Keranjang">
        <i class="fa-solid fa-cart-shopping"></i>
        <span class="fab-cart-badge" id="fabCartBadge" style="display: none;"></span>
    </button>

    <div class="cart-drawer-overlay" id="cartOverlay" onclick="toggleCartDrawer()"></div>

    <div class="cart-drawer" id="cartDrawer">
        <div class="cart-drawer-header">
            <h3><i class="fa-solid fa-cart-shopping"></i> Keranjang Belanja</h3>
            <button class="cart-drawer-close" onclick="toggleCartDrawer()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="cart-drawer-body" id="cartDrawerBody">
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

    <!-- Toast container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Mobile dropdown panel backdrop overlay -->
    <div id="dropdownBackdrop" class="dropdown-panel-backdrop" onclick="closeAllDropdowns()"></div>

    <!-- Data menu JSON for JS -->
    <script>
        const ALL_MENUS = <?= json_encode(array_map(function ($m) {
            return [
                'id_menu' => (int) $m['id_menu'],
                'nama_menu' => $m['nama_menu'],
                'deskripsi' => $m['deskripsi'] ?? '',
                'harga' => (int) $m['harga'],
                'foto_menu' => $m['foto_menu'] ?? '',
                'kategori' => strtolower($m['kategori'] ?? 'makanan'),
                'nama_toko' => $m['nama_toko'],
                'id_toko' => (int) $m['id_toko'],
                'stok' => (int) $m['stok'],
                'status_toko' => strtolower($m['status_toko'] ?? 'tutup'),
                'is_fleksibel' => (int) ($m['is_fleksibel'] ?? 0)
            ];
        }, $all_menus)); ?>;

        const ALL_TOKOS = <?= json_encode(array_map(function ($t) {
            return [
                'id_toko' => (int) $t['id_toko'],
                'nama_toko' => $t['nama_toko'],
                'deskripsi' => $t['deskripsi'] ?? '',
                'status' => strtolower($t['status'] ?? 'tutup'),
                'foto_toko' => resolveTokoImg($t['foto_toko'] ?? ''),
            ];
        }, $all_tokos)); ?>;

        // Favorit awal dari DB (sync lintas device)
        let USER_FAVS = <?= json_encode($user_favs) ?>;
        const FAV_API = 'actions/favorit.php';
    </script>

    <script>
        // ════════════════════════════════════════════
        //  NAVIGATION TABS
        // ════════════════════════════════════════════
        function switchNav(section) {
            if (typeof event !== 'undefined' && event && typeof event.preventDefault === 'function') {
                event.preventDefault();
            }
            closeAllDropdowns();

            // Hentikan/jalankan polling chat sesuai section aktif
            if (section !== 'chat') {
                if (typeof intervalPollingChat !== 'undefined' && intervalPollingChat) {
                    clearInterval(intervalPollingChat);
                    intervalPollingChat = null;
                }
                if (typeof ID_LAWAN_AKTIF !== 'undefined') ID_LAWAN_AKTIF = '';
            } else {
                // Pastikan sidebar kontak tidak tersembunyi (collapsed) saat awal dibuka
                const sidebar = document.getElementById('sidebarKontak');
                if (sidebar) {
                    sidebar.classList.remove('collapsed');
                }
                const ikon = document.getElementById('ikonToggleSidebar');
                if (ikon) {
                    ikon.className = 'fa-solid fa-angles-left';
                }
                if (typeof muatDaftarKontak === 'function') muatDaftarKontak('');
            }

            // Update nav active states
            document.querySelectorAll('.nav-item').forEach(n => {
                n.classList.toggle('active', n.dataset.nav === section);
            });
            // Show/hide sections
            document.querySelectorAll('.page-section').forEach(s => {
                s.classList.toggle('active', s.id === 'section-' + section);
            });

            // Update unread chat badge
            if (typeof checkUnreadChats === 'function') {
                checkUnreadChats();
            }

            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // ════════════════════════════════════════════
        //  DROPDOWNS (Notif, Cart, Profile)
        // ════════════════════════════════════════════
        function toggleDropdown(id) {
            event && event.stopPropagation();
            const panel = document.getElementById(id);
            const isOpen = panel.classList.contains('show');
            closeAllDropdowns();
            if (!isOpen) {
                panel.classList.add('show');
                const bd = document.getElementById('dropdownBackdrop');
                if (bd) bd.classList.add('show');
                const header = document.querySelector('.main-header');
                if (header) header.classList.add('has-open-dropdown');
            }
        }

        function closeAllDropdowns() {
            document.querySelectorAll('.dropdown-panel').forEach(d => d.classList.remove('show'));
            const bd = document.getElementById('dropdownBackdrop');
            if (bd) bd.classList.remove('show');
            const header = document.querySelector('.main-header');
            if (header) header.classList.remove('has-open-dropdown');
        }

        document.addEventListener('click', (e) => {
            if (e.target.closest('.dropdown-panel')) return;
            if (!e.target.closest('.dropdown-wrapper')) closeAllDropdowns();
        });

        // ════════════════════════════════════════════
        //  CART (Database Sync + localStorage Cache) - VERSI DRAWER FULLSCREEN
        // ════════════════════════════════════════════
        function getCart() {
            try { return JSON.parse(localStorage.getItem('ekantin_cart') || '[]'); }
            catch { return []; }
        }

        function saveCart(cart, skipRender = false) {
            localStorage.setItem('ekantin_cart', JSON.stringify(cart));
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
                    localStorage.setItem('ekantin_cart', JSON.stringify(data.cart));
                    updateBadges();
                    if (document.getElementById('cartDrawer') && document.getElementById('cartDrawer').classList.contains('show')) {
                        renderCartDrawer();
                    }
                }
            })
            .catch(err => console.error('Gagal mengambil keranjang dari database:', err));
        }

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

        function renderAddToCartButton(m, styleExtra = '') {
            const isBuka = (m.status_toko === 'buka');
            if (!isBuka) {
                const styleDisabled = styleExtra ? `style="${styleExtra};background-color:#94a3b8;pointer-events:none;box-shadow:none"` : 'style="background-color:#94a3b8;pointer-events:none;box-shadow:none"';
                return `<button class="btn-tambah-keranjang" ${styleDisabled} disabled>
                    Toko Tutup
                </button>`;
            }
            if (m.stok <= 0) {
                const styleOut = styleExtra ? `style="${styleExtra};background-color:#ef4444;color:#ffffff;cursor:pointer;box-shadow:none"` : 'style="background-color:#ef4444;color:#ffffff;cursor:pointer;box-shadow:none"';
                return `<button class="btn-tambah-keranjang" ${styleOut} onclick="event.stopPropagation(); bukaDetailMenu(${m.id_menu})">
                    Stok Habis
                </button>`;
            }
            const styleAttr = styleExtra ? `style="${styleExtra}"` : '';
            return `<button class="btn-tambah-keranjang" ${styleAttr} onclick="event.stopPropagation(); bukaDetailMenu(${m.id_menu})">
                <i class="fa-solid fa-cart-plus"></i> Tambah
            </button>`;
        }

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

        let activeDetailMenu = null;
        let activeDetailPrice = 0;

        function bukaDetailMenu(id) {
            const menu = ALL_MENUS.find(m => m.id_menu === id);
            if (!menu) return;
            
            activeDetailMenu = menu;
            
            const overlay = document.getElementById('section-menu-detail');
            const container = document.getElementById('menuDetailContainer');
            const navMenu = document.querySelector('.nav-menu');
            
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
                            <input type="number" id="detailFlexPriceInput" placeholder="Masukkan harga..." min="1000" oninput="updateDetailFlexPriceFromInput(this)" style="
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
            
            // Show overlay page
            if (navMenu) navMenu.style.display = 'none';
            document.body.style.overflow = 'hidden'; // Lock scrolling
            overlay.classList.add('active');
            
            // Set default price variable
            if (menu.is_fleksibel !== 1) {
                activeDetailPrice = menu.harga;
            } else {
                activeDetailPrice = 0; // needs input
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
            const navMenu = document.querySelector('.nav-menu');
            
            if (overlay) overlay.classList.remove('active');
            document.body.style.overflow = ''; // Restore scroll
            
            setTimeout(() => {
                if (navMenu) navMenu.style.display = 'flex';
            }, 300);
            
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
            
            if (price >= 1000) {
                input.style.borderColor = '#16a34a';
            } else {
                input.style.borderColor = '#cbd5e1';
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
                    if (errDiv) errDiv.style.display = 'block';
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
            
            const success = addToCartWithQty(
                activeDetailMenu.id_menu,
                activeDetailMenu.nama_menu,
                hargaToAdd,
                activeDetailMenu.foto_menu,
                activeDetailMenu.nama_toko,
                activeDetailMenu.id_toko,
                qty,
                customHarga
            );
            
            if (success) {
                tutupDetailMenu();
            }
        }

        function addToCartWithQty(id, nama, harga, foto, toko, idToko, qty = 1, customHarga = null) {
            const cart = getCart();
            const menuItem = ALL_MENUS.find(m => Number(m.id_menu) === Number(id));
            if (menuItem && menuItem.status_toko !== 'buka') {
                showToast('Kantin sedang tutup!', 'error');
                return false;
            }
            const activeHarga = Number(customHarga !== null ? customHarga : harga);
            const stock = menuItem ? menuItem.stok : 999;
            const existing = cart.find(c => Number(c.id_menu) === Number(id) && Number(c.harga) === Number(activeHarga));
            
            if (existing) {
                if (existing.jumlah + qty > stock) {
                    showToast('Stok tidak mencukupi! Maksimum stok: ' + stock, 'error');
                    return false;
                }
                existing.jumlah += qty;
                existing.selected = true;
            } else {
                if (stock <= 0) {
                    showToast('Stok habis!', 'error');
                    return false;
                }
                if (qty > stock) {
                    showToast('Stok tidak mencukupi! Maksimum stok: ' + stock, 'error');
                    return false;
                }
                cart.push({ id_menu: id, nama_menu: nama, harga: activeHarga, jumlah: qty, foto_menu: foto, nama_toko: toko, id_toko: idToko, selected: true, catatan: '', stok: stock });
            }
            saveCart(cart);
            showToast(nama + ' x' + qty + ' (Rp ' + (activeHarga * qty).toLocaleString('id-ID') + ')', 'success', { foto: foto, toko: toko });
            return true;
        }

        function addToCart(id, nama, harga, foto, toko, idToko, customHarga = null) {
            const cart = getCart();
            const menuItem = ALL_MENUS.find(m => Number(m.id_menu) === Number(id));
            if (menuItem && menuItem.status_toko !== 'buka') {
                showToast('Kantin sedang tutup!', 'error');
                return;
            }
            if (menuItem && menuItem.is_fleksibel === 1 && customHarga === null) {
                openPriceInputModal(menuItem, (price) => {
                    addToCart(id, nama, price, foto, toko, idToko, price);
                });
                return;
            }
            const activeHarga = Number(customHarga !== null ? customHarga : harga);
            const stock = menuItem ? menuItem.stok : 999;
            const existing = cart.find(c => Number(c.id_menu) === Number(id) && Number(c.harga) === Number(activeHarga));
            if (existing) {
                if (existing.jumlah >= stock) {
                    showToast('Stok tidak mencukupi! Maksimum stok: ' + stock, 'error');
                    return;
                }
                existing.jumlah++;
                existing.selected = true;
            } else {
                if (stock <= 0) {
                    showToast('Stok habis!', 'error');
                    return;
                }
                cart.push({ id_menu: id, nama_menu: nama, harga: activeHarga, jumlah: 1, foto_menu: foto, nama_toko: toko, id_toko: idToko, selected: true, catatan: '', stok: stock });
            }
            saveCart(cart);
            showToast(nama + ' (Rp ' + activeHarga.toLocaleString('id-ID') + ')', 'success', { foto: foto, toko: toko });
        }

        function updateCartSummaryUI() {
            const cart = getCart();
            const selectedItems = cart.filter(item => item.selected !== false);
            const totalPrice = selectedItems.reduce((sum, item) => sum + (item.harga * item.jumlah), 0);
            const allSelected = cart.every(item => item.selected !== false);

            const totalEl = document.getElementById('cartDrawerTotal');
            if (totalEl) totalEl.textContent = 'Rp ' + totalPrice.toLocaleString('id-ID');

            const selectAllCheck = document.getElementById('cartSelectAll');
            if (selectAllCheck) selectAllCheck.checked = allSelected;

            const selectAllLabel = document.querySelector('.cart-select-all-label span');
            if (selectAllLabel) {
                selectAllLabel.textContent = `Pilih Semua (${selectedItems.length}/${cart.length})`;
            }
        }

        function updateCartItemRowUI(id, harga, qty) {
            const row = document.querySelector(`.cart-item-row[data-id="${id}"][data-harga="${harga}"]`);
            if (row) {
                const input = row.querySelector('.item-qty-input');
                if (input) input.value = qty;

                const subtotalEl = row.querySelector('.item-subtotal');
                if (subtotalEl) {
                    const cartItem = getCart().find(c => c.id_menu == id && c.harga == harga);
                    if (cartItem) {
                        subtotalEl.textContent = 'Rp ' + (cartItem.harga * qty).toLocaleString('id-ID');
                    }
                }
            }
            updateCartSummaryUI();
        }

        function updateCartQty(id, harga, delta, event) {
            if(event) event.stopPropagation();
            const cart = getCart();
            const item = cart.find(c => Number(c.id_menu) === Number(id) && Number(c.harga) === Number(harga));
            if (item) {
                if (delta > 0) {
                    const menuItem = ALL_MENUS.find(m => m.id_menu == id);
                    const stock = menuItem ? menuItem.stok : (item.stok || 999);
                    if (item.jumlah >= stock) {
                        showToast('Stok tidak mencukupi! Maksimum stok: ' + stock, 'error');
                        return;
                    }
                }
                item.jumlah += delta;
                if (item.jumlah <= 0) {
                    cart.splice(cart.indexOf(item), 1);
                    saveCart(cart);
                } else {
                    saveCart(cart, true);
                    updateCartItemRowUI(id, harga, item.jumlah);
                }
            }
        }

        function manualUpdateCartQty(id, harga, value, maxStock) {
            let qty = parseInt(value);
            const cart = getCart();
            const item = cart.find(c => Number(c.id_menu) === Number(id) && Number(c.harga) === Number(harga));
            if (!item) return;

            if (isNaN(qty) || qty < 0) {
                qty = 1;
            }

            if (qty === 0) {
                cart.splice(cart.indexOf(item), 1);
                saveCart(cart); // Full render since item was removed
            } else {
                if (qty > maxStock) {
                    showToast('Stok tidak mencukupi! Maksimum stok: ' + maxStock, 'error');
                    qty = maxStock;
                }
                item.jumlah = qty;
                saveCart(cart, true); // Skip full render
                updateCartItemRowUI(id, harga, qty);
            }
        }

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

        function toggleCartItemSelection(id, harga) {
            const cart = getCart();
            const item = cart.find(c => Number(c.id_menu) === Number(id) && Number(c.harga) === Number(harga));
            if (item) {
                item.selected = !item.selected;
            }
            saveCart(cart, true); // Skip full render
            updateCartSummaryUI();
        }

        function toggleSelectAllCart(isChecked) {
            const cart = getCart();
            cart.forEach(item => {
                item.selected = isChecked;
            });
            saveCart(cart, true); // Skip full render
            
            const checkboxes = document.querySelectorAll('.cart-item-checkbox');
            checkboxes.forEach(cb => {
                if (cb.id !== 'cartSelectAll') {
                    cb.checked = isChecked;
                }
            });
            updateCartSummaryUI();
        }

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

        function checkoutCart() {
            const cart = getCart();
            const selectedItems = cart.filter(item => item.selected !== false);
            if (selectedItems.length === 0) {
                showToast('Silakan pilih item yang ingin dibeli!', 'error');
                return;
            }
            window.location.href = 'checkout.php';
        }

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
                    </div>`;
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
                                    <button onclick="updateCartQty(${item.id_menu}, ${item.harga}, -1, event)" style="border: none; background: none; padding: 4px 10px; cursor: pointer; font-size: 14px; font-weight: 700; color: #64748b; transition: background 0.2s;">−</button>
                                    <input type="number" class="item-qty-input" value="${item.jumlah}" min="0" max="${item.stok || 999}" onchange="manualUpdateCartQty(${item.id_menu}, ${item.harga}, this.value, ${item.stok || 999})" onkeydown="if(event.key === 'Enter') this.blur();" onclick="event.stopPropagation()">
                                    <button onclick="updateCartQty(${item.id_menu}, ${item.harga}, 1, event)" style="border: none; background: none; padding: 4px 10px; cursor: pointer; font-size: 14px; font-weight: 700; color: #64748b; transition: background 0.2s;">+</button>
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 6px; padding-left: 28px; width: 100%; box-sizing: border-box;">
                            <i class="fa-regular fa-comment-dots" style="color: #94a3b8; font-size: 12px;"></i>
                            <input type="text" class="cart-item-note-input" value="${item.catatan || ''}" placeholder="Tambah catatan..." onchange="updateCartItemNote(${item.id_menu}, ${item.harga}, this.value)" style="flex: 1; border: 1px solid #f1f5f9; border-radius: 6px; padding: 4px 8px; font-size: 11px; color: #64748b; outline: none; background: #f8fafc; transition: all 0.2s;" onfocus="this.style.borderColor='#5cb85c'; this.style.background='#ffffff'" onblur="this.style.borderColor='#f1f5f9'; this.style.background='#f8fafc'">
                        </div>
                    </div>`;
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

        // ════════════════════════════════════════════
        //  FAVORIT (database-backed via AJAX)
        // ════════════════════════════════════════════
        function getFavorites() {
            return USER_FAVS; // already loaded from DB on page load
        }

        function toggleFavorite(id) {
            id = Number(id);
            // Optimistic UI update
            const idx = USER_FAVS.indexOf(id);
            const isLiked = idx === -1;
            if (isLiked) {
                USER_FAVS.push(id);
                showToast('Ditambahkan ke favorit', 'success');
            } else {
                USER_FAVS.splice(idx, 1);
                showToast('Dihapus dari favorit', '');
            }
            renderFavorites();
            updateAllHeartButtons();

            // Sync ke DB
            const fd = new FormData();
            fd.append('action', 'toggle');
            fd.append('id_menu', id);
            fetch(FAV_API, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        // Rollback jika gagal
                        if (isLiked) USER_FAVS.splice(USER_FAVS.indexOf(id), 1);
                        else USER_FAVS.push(id);
                        renderFavorites();
                        updateAllHeartButtons();
                        showToast('Gagal menyimpan favorit', 'error');
                    }
                })
                .catch(() => {
                    showToast('Koneksi gagal', 'error');
                });
        }

        // Update semua tombol heart di halaman berdasarkan USER_FAVS
        function updateAllHeartButtons() {
            document.querySelectorAll('[data-fav-id]').forEach(btn => {
                const id = Number(btn.getAttribute('data-fav-id'));
                const icon = btn.querySelector('i');
                if (USER_FAVS.includes(id)) {
                    btn.classList.add('active');
                    if (icon) icon.className = 'fa-solid fa-heart';
                } else {
                    btn.classList.remove('active');
                    if (icon) icon.className = 'fa-regular fa-heart';
                }
            });
        }

        // Helper function to render a beautiful category-specific placeholder menu card
        function renderMenuImageHTML(foto_menu, kategori, nama_menu, id_menu = null, stok = null) {
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

            let heartHTML = '';
            if (id_menu !== null) {
                heartHTML = `<button class="btn-favorite-toko" data-fav-id="${id_menu}" onclick="toggleFavorite(${id_menu}); event.stopPropagation();"><i class="fa-regular fa-heart"></i></button>`;
            }

            let stockHTML = '';
            if (stok !== null) {
                const stockVal = Number(stok);
                if (stockVal <= 0) {
                    stockHTML = `<span class="menu-item-stock out">Habis</span>`;
                } else if (stockVal <= 5) {
                    stockHTML = `<span class="menu-item-stock low">Sisa ${stockVal}</span>`;
                } else {
                    stockHTML = `<span class="menu-item-stock">Stok ${stockVal}</span>`;
                }
            }

            if (img_src) {
                return `
                <div class="menu-card-full-img-wrap">
                    <img src="${img_src}" alt="${nama_menu}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="menu-img-placeholder ${kat}" style="display:none;">
                        ${svgContent}
                    </div>
                    ${heartHTML}
                    ${stockHTML}
                </div>`;
            } else {
                return `
                <div class="menu-card-full-img-wrap">
                    <div class="menu-img-placeholder ${kat}">
                        ${svgContent}
                    </div>
                    ${heartHTML}
                    ${stockHTML}
                </div>`;
            }
        }

        let activeFavoritKategori = 'semua';

        function filterFavoritKategori(kategori, btnEl) {
            activeFavoritKategori = kategori;
            
            // Perbarui kelas active pada tab tombol filter favorit
            const tabs = document.querySelectorAll('#favoritFilterTabs .filter-tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            if (btnEl) btnEl.classList.add('active');
            
            renderFavorites();
        }

        function renderFavorites() {
            const favs = getFavorites();
            const grid = document.getElementById('favoritGrid');
            const empty = document.getElementById('favoritEmpty');
            const filterTabs = document.getElementById('favoritFilterTabs');
            const katEmpty = document.getElementById('favoritKategoriEmpty');

            if (favs.length === 0) {
                grid.innerHTML = '';
                if (filterTabs) filterTabs.style.display = 'none';
                if (katEmpty) katEmpty.style.display = 'none';
                empty.style.display = 'block';
                return;
            }
            empty.style.display = 'none';
            if (filterTabs) filterTabs.style.display = 'flex';

            let favMenus = ALL_MENUS.filter(m => favs.includes(Number(m.id_menu)));
            
            // Filter berdasarkan kategori yang dipilih
            if (activeFavoritKategori !== 'semua') {
                favMenus = favMenus.filter(m => m.kategori && m.kategori.toLowerCase() === activeFavoritKategori);
            }

            if (favMenus.length === 0) {
                grid.innerHTML = '';
                if (katEmpty) katEmpty.style.display = 'block';
                return;
            }
            if (katEmpty) katEmpty.style.display = 'none';

            grid.innerHTML = favMenus.map(m => {
                const imgWrapHTML = renderMenuImageHTML(m.foto_menu, m.kategori, m.nama_menu, null, m.stok);
                const btnHTML = renderAddToCartButton(m, 'flex:1');
                return `
            <div class="menu-card-full" data-kategori="${(m.kategori || '').toLowerCase()}" onclick="bukaDetailMenu(${m.id_menu})" style="cursor: pointer;">
                ${imgWrapHTML}
                <div class="mc-info">
                    <h4>${m.nama_menu}</h4>
                    <p class="mc-toko">${m.nama_toko}</p>
                    <p class="mc-price">${m.is_fleksibel === 1 ? '<span style="background: rgba(14, 165, 233, 0.1); color: #0ea5e9; padding: 2px 8px; border-radius: 8px; font-weight: 750; font-size: 11.5px; display: inline-flex; align-items: center; gap: 4px;"><i class="fa-solid fa-arrows-left-right-to-line"></i> Harga Fleksibel</span>' : 'Rp. ' + Number(m.harga).toLocaleString('id-ID')}</p>
                    <div style="display:flex;gap:8px">
                        ${btnHTML}
                        <button class="btn-tambah-keranjang" style="flex:0;padding:8px 12px;background:#ef4444;box-shadow:0 4px 12px rgba(239,68,68,.2)" onclick="event.stopPropagation(); toggleFavorite(${m.id_menu})">
                            <i class="fa-solid fa-heart-crack"></i>
                        </button>
                    </div>
                </div>
            </div>`;
            }).join('');
        }

        function renderKantinCardHTML(t) {
            const isBuka = t.status === 'buka';
            const statusClass = isBuka ? 'online' : 'offline';
            const statusText = isBuka ? 'Buka' : 'Tutup';
            const btnDisabled = !isBuka ? 'style="background-color:#94a3b8;pointer-events:none;box-shadow:none"' : '';
            const btnText = isBuka ? 'Lihat Menu' : 'Sedang Tutup';
            const desk = t.deskripsi || 'Makanan, Snack, & Minuman';

            let imgHTML;
            if (t.foto_toko) {
                imgHTML = `<img src="${t.foto_toko}" class="blank-image-square"
                    alt="${t.nama_toko}"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="toko-img-placeholder" style="display:none;">
                    <i class="fa-solid fa-store"></i>
                    <span>${t.nama_toko}</span>
                </div>`;
            } else {
                imgHTML = `<div class="toko-img-placeholder">
                    <i class="fa-solid fa-store"></i>
                    <span>${t.nama_toko}</span>
                </div>`;
            }

            return `<div class="kantin-card" data-nama="${t.nama_toko.toLowerCase()}">
                ${imgHTML}
                <div class="kantin-info">
                    <h3>${t.nama_toko}</h3>
                    <p>${desk}</p>
                    <span class="status-indicator ${statusClass}">${statusText}</span>
                    <a href="toko.php?id=${t.id_toko}" class="btn-lihat-menu" ${btnDisabled}>
                        ${btnText}
                    </a>
                </div>
            </div>`;
        }

        function renderSemuaKantinBoxHTML() {
            return `<div class="category-item-all-box" onclick="switchNav('kantin')">
                <div class="blank-square-icon"><i class="fa-solid fa-table-cells-large"></i></div>
                <span class="all-text-label">SEMUA</span>
            </div>`;
        }

        function renderBerandaKantinDefault() {
            const grid = document.getElementById('kantinGridBeranda');
            if (!grid) return;

            const homeTokos = ALL_TOKOS.slice(0, 3);
            grid.innerHTML = homeTokos.map(renderKantinCardHTML).join('') + renderSemuaKantinBoxHTML();
        }

        function restoreKantinGridBeranda() {
            renderBerandaKantinDefault();
        }

        function renderBerandaKantinSearch(q, matchingTokoIds) {
            const grid = document.getElementById('kantinGridBeranda');
            if (!grid) return;

            const kantinResults = ALL_TOKOS.filter(t =>
                t.nama_toko.toLowerCase().includes(q) ||
                (t.deskripsi && t.deskripsi.toLowerCase().includes(q)) ||
                matchingTokoIds.has(t.id_toko)
            );

            if (kantinResults.length === 0) {
                grid.innerHTML = `<div class="empty-state" style="grid-column:1/-1"><i class="fa-solid fa-store-slash"></i><h3>Tidak ada kantin ditemukan</h3><p>Coba kata kunci lain</p></div>${renderSemuaKantinBoxHTML()}`;
            } else {
                grid.innerHTML = kantinResults.map(renderKantinCardHTML).join('') + renderSemuaKantinBoxHTML();
            }
        }

        // ════════════════════════════════════════════
        //  SEARCH
        // ════════════════════════════════════════════
        function handleSearchKeydown(e) {
            if (e.key === 'Enter') {
                const val = e.target.value.trim().toLowerCase();
                if (val.length >= 2) {
                    const results = ALL_MENUS.filter(m =>
                        m.nama_menu.toLowerCase().includes(val) ||
                        m.nama_toko.toLowerCase().includes(val) ||
                        m.kategori.toLowerCase().includes(val)
                    );
                    if (results.length > 0) {
                        bukaDetailMenu(results[0].id_menu);
                        e.target.blur();
                    }
                }
            }
        }

        let searchTimeout;
        function handleSearch(val) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const q = val.trim().toLowerCase();
                const resultSection = document.getElementById('searchResultsSection');
                const grid = document.getElementById('searchResultsGrid');
                const promoSection = document.getElementById('promoSection');
                const menuSection = document.getElementById('menuSection');
                const kategoriSection = document.getElementById('kategoriSection');
                const kantinSection = document.getElementById('kantinSection');

                if (q.length < 2) {
                    resultSection.style.display = 'none';
                    promoSection.style.display = '';
                    menuSection.style.display = '';
                    kategoriSection.style.display = '';
                    kantinSection.style.display = '';
                    restoreKantinGridBeranda();
                    return;
                }

                // Make sure we're on beranda
                switchNav('beranda');

                // Filter menus
                const results = ALL_MENUS.filter(m =>
                    m.nama_menu.toLowerCase().includes(q) ||
                    m.nama_toko.toLowerCase().includes(q) ||
                    m.kategori.toLowerCase().includes(q)
                );

                document.getElementById('searchQuery').textContent = val.trim();

                const matchingTokoIds = new Set(results.map(m => m.id_toko));
                renderBerandaKantinSearch(q, matchingTokoIds);

                if (results.length === 0) {
                    grid.innerHTML = '<div class="empty-state" style="grid-column:1/-1"><i class="fa-solid fa-magnifying-glass"></i><h3>Tidak ditemukan</h3><p>Coba kata kunci lain</p></div>';
                } else {
                    grid.innerHTML = results.map(m => {
                        const imgWrapHTML = renderMenuImageHTML(m.foto_menu, m.kategori, m.nama_menu, m.id_menu, m.stok);
                        const btnHTML = renderAddToCartButton(m);
                        return `
                    <div class="menu-card-full" onclick="bukaDetailMenu(${m.id_menu})" style="cursor: pointer;">
                        ${imgWrapHTML}
                        <div class="mc-info">
                            <h4>${m.nama_menu}</h4>
                            <p class="mc-toko">${m.nama_toko}</p>
                            <p class="mc-price">${m.is_fleksibel === 1 ? '<span style="background: rgba(14, 165, 233, 0.1); color: #0ea5e9; padding: 2px 8px; border-radius: 8px; font-weight: 750; font-size: 11.5px; display: inline-flex; align-items: center; gap: 4px;"><i class="fa-solid fa-arrows-left-right-to-line"></i> Harga Fleksibel</span>' : 'Rp. ' + Number(m.harga).toLocaleString('id-ID')}</p>
                            ${btnHTML}
                        </div>
                    </div>`;
                    }).join('');
                    updateAllHeartButtons();
                }

                resultSection.style.display = '';
                promoSection.style.display = 'none';
                menuSection.style.display = 'none';
                kategoriSection.style.display = 'none';
                kantinSection.style.display = '';
            }, 300);
        }

        function clearSearch() {
            document.getElementById('searchInput').value = '';
            handleSearch('');
        }

        // ════════════════════════════════════════════
        //  CATEGORY FILTER (Beranda kantin grid)
        // ════════════════════════════════════════════
        function filterKategori(kat, el) {
            event && event.preventDefault();
            document.querySelectorAll('#kategoriSection .category-item').forEach(c => c.classList.remove('active-cat'));
            el.classList.add('active-cat');

            // Filter menu terlaris
            const cards = document.querySelectorAll('.horizontal-scroll .menu-card');
            // We can't filter the PHP-rendered cards by category easily, so just navigate to Kantin tab with filter
            switchNav('kantin');
            filterKantinMenu(kat, document.querySelector(`[data-kat2="${kat}"]`));
        }

        // ════════════════════════════════════════════
        //  CATEGORY FILTER (Kantin tab full menu)
        // ════════════════════════════════════════════
        function filterKantinMenu(kat, el) {
            event && event.preventDefault();
            document.querySelectorAll('#section-kantin .category-item').forEach(c => c.classList.remove('active-cat'));
            if (el) el.classList.add('active-cat');

            document.querySelectorAll('#allMenuGrid .menu-card-full').forEach(card => {
                if (kat === 'semua' || card.dataset.kategori === kat) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        // ════════════════════════════════════════════
        //  LAYAR DETAIL KATEGORI (FULL PAGE)
        // ════════════════════════════════════════════
        function bukaDetailKategori(kat) {
            const detailSec = document.getElementById('section-kategori-detail');
            const grid = document.getElementById('kategoriDetailGrid');
            const title = document.getElementById('kategoriDetailTitle');
            const navMenu = document.querySelector('.nav-menu');

            // Ganti Judul
            title.textContent = kat === 'semua' ? 'Semua Kategori' : kat;

            // Filter data dari ALL_MENUS
            const results = ALL_MENUS.filter(m => kat === 'semua' || m.kategori === kat);

            if (results.length === 0) {
                grid.innerHTML = `
                    <div class="empty-state" style="grid-column:1/-1; margin-top:40px;">
                        <i class="fa-solid fa-utensils"></i>
                        <h3>Kosong</h3>
                        <p>Belum ada menu di kategori ini.</p>
                    </div>`;
            } else {
                grid.innerHTML = results.map(m => {
                    const imgWrapHTML = renderMenuImageHTML(m.foto_menu, m.kategori, m.nama_menu, m.id_menu, m.stok);
                    const btnHTML = renderAddToCartButton(m);
                    return `
                    <div class="menu-card-full" onclick="bukaDetailMenu(${m.id_menu})" style="cursor: pointer;">
                        ${imgWrapHTML}
                        <div class="mc-info">
                            <h4>${m.nama_menu}</h4>
                            <p class="mc-toko">${m.nama_toko}</p>
                            <p class="mc-price">${m.is_fleksibel === 1 ? '<span style="background: rgba(14, 165, 233, 0.1); color: #0ea5e9; padding: 2px 8px; border-radius: 8px; font-weight: 750; font-size: 11.5px; display: inline-flex; align-items: center; gap: 4px;"><i class="fa-solid fa-arrows-left-right-to-line"></i> Harga Fleksibel</span>' : 'Rp. ' + Number(m.harga).toLocaleString('id-ID')}</p>
                            ${btnHTML}
                        </div>
                    </div>`;
                }).join('');
                updateAllHeartButtons();
            }

            // Sembunyikan navbar bawah dan tampilkan layar overlay
            if (navMenu) navMenu.style.display = 'none';
            document.body.style.overflow = 'hidden'; // Kunci scroll di belakang
            detailSec.classList.add('active');
        }

        function tutupDetailKategori() {
            const detailSec = document.getElementById('section-kategori-detail');
            const navMenu = document.querySelector('.nav-menu');

            detailSec.classList.remove('active');
            document.body.style.overflow = ''; // Lepas kunci scroll
            
            // Tunggu animasi meluncur selesai (0.3s) baru munculkan navbar lagi
            setTimeout(() => {
                if (navMenu) navMenu.style.display = 'flex';
            }, 300);
        }

        // ════════════════════════════════════════════
        //  TOAST NOTIFICATION
        // ════════════════════════════════════════════
        function showToast(msg, type, meta) {
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
                    <div class="toast-desc">${msg}</div>
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

        // ══════════════════════════════════════════════
        //  MARK ALL NOTIFICATIONS AS READ LOGIC
        // ══════════════════════════════════════════════
        function tandaiSemuaNotifDibaca() {
            const badge = document.getElementById('notifBadge');
            if (badge) {
                badge.style.display = 'none';
                badge.textContent = '0';
            }
            
            document.querySelectorAll('.notif-item-modern.unread').forEach(item => {
                item.classList.remove('unread');
                const indicator = item.querySelector('.unread-indicator');
                if (indicator) indicator.remove();
            });
            
            showToast('Semua notifikasi ditandai dibaca', 'success');
        }



        // ══════════════════════════════════════════════
        //  MENU TERLARIS SLIDER LOGIC
        // ══════════════════════════════════════════════
        function initMenuTerlarisSlider() {
            const container = document.querySelector('#menuSection .horizontal-scroll');
            const dots = document.querySelectorAll('#menuSection .dot');
            if (!container || dots.length === 0) return;

            // Update dots when scrolling
            container.addEventListener('scroll', () => {
                const maxScroll = container.scrollWidth - container.clientWidth;
                if (maxScroll <= 0) return;
                
                const scrollPercent = container.scrollLeft / maxScroll;
                const activeIndex = Math.min(
                    Math.round(scrollPercent * (dots.length - 1)),
                    dots.length - 1
                );
                
                dots.forEach((dot, idx) => {
                    dot.classList.toggle('active', idx === activeIndex);
                });
            });

            // Make dots clickable
            dots.forEach((dot, index) => {
                dot.addEventListener('click', () => {
                    const maxScroll = container.scrollWidth - container.clientWidth;
                    if (maxScroll <= 0) return;
                    
                    const targetScroll = (index / (dots.length - 1)) * maxScroll;
                    container.scrollTo({
                        left: targetScroll,
                        behavior: 'smooth'
                    });
                });
                dot.style.cursor = 'pointer';
            });
        }

        // ══════════════════════════════════════════════
        //  DRAGGABLE FAB CART LOGIC
        // ══════════════════════════════════════════════
        function makeCartDraggable() {
            const fab = document.querySelector('.fab-cart');
            if (!fab) return;

            let isDragging = false;
            let startX = 0, startY = 0;
            let initialLeft = 0, initialTop = 0;

            // touch-action none prevents page scrolling while dragging on touch devices
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
                
                // Distinguish drag from click
                if (Math.abs(dx) > 6 || Math.abs(dy) > 6) {
                    isDragging = true;
                }
                
                if (isDragging) {
                    let targetLeft = initialLeft + dx;
                    let targetTop = initialTop + dy;
                    
                    // Boundary constraint
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

        // ══════════════════════════════════════════════
        //  PROMO BANNER SLIDER LOGIC
        // ══════════════════════════════════════════════
        let currentPromoSlide = 0;
        let promoSlideInterval = null;

        function getPromoSlides() {
            return document.querySelectorAll('#promoSliderWrapper .promo-slide');
        }

        function getPromoSecondarySlides() {
            return document.querySelectorAll('#promoSliderWrapperSecondary .promo-slide');
        }

        function renderPromoOwner(el, slide) {
            if (!el || !slide) return;
            const tokoName = slide.getAttribute('data-toko-name') || '';
            el.innerHTML = tokoName
                ? `<i class="fa-solid fa-store" style="color: #5cb85c; margin-right: 4px;"></i><strong>${tokoName}</strong>`
                : '';
        }

        function syncPromoSecondaryPane(primaryIndex, totalSlides) {
            const secondarySlides = getPromoSecondarySlides();
            if (secondarySlides.length === 0 || totalSlides <= 1) return;

            const secondaryIndex = (primaryIndex + 1) % totalSlides;
            secondarySlides.forEach((slide, idx) => {
                slide.classList.toggle('active', idx === secondaryIndex);
            });

            renderPromoOwner(
                document.getElementById('promoBannerOwnerSecondary'),
                secondarySlides[secondaryIndex]
            );
        }

        function showPromoSlide(index) {
            const slides = getPromoSlides();
            const dots = document.querySelectorAll('.promo-dot');
            if (slides.length === 0) return;

            if (index >= slides.length) currentPromoSlide = 0;
            else if (index < 0) currentPromoSlide = slides.length - 1;
            else currentPromoSlide = index;

            slides.forEach((slide, idx) => {
                slide.classList.toggle('active', idx === currentPromoSlide);
            });

            dots.forEach((dot, idx) => {
                dot.classList.toggle('active', idx === currentPromoSlide);
            });

            syncPromoSecondaryPane(currentPromoSlide, slides.length);

            renderPromoOwner(document.getElementById('promoBannerOwner'), slides[currentPromoSlide]);
        }

        function movePromoSlide(direction) {
            resetPromoInterval();
            showPromoSlide(currentPromoSlide + direction);
        }

        function setPromoSlide(index) {
            resetPromoInterval();
            showPromoSlide(index);
        }

        function startPromoInterval() {
            const slides = getPromoSlides();
            if (slides.length > 1) {
                promoSlideInterval = setInterval(() => {
                    showPromoSlide(currentPromoSlide + 1);
                }, 4000);
            }
        }

        function resetPromoInterval() {
            if (promoSlideInterval) {
                clearInterval(promoSlideInterval);
                startPromoInterval();
            }
        }

        // ════════════════════════════════════════════
        //  CHAT UNREAD BADGE & LOGOUT CONFIRMATION
        // ════════════════════════════════════════════
        function checkUnreadChats() {
            fetch('../../backend/ambil_unread_chat.php')
                .then(res => res.json())
                .then(data => {
                    const badge = document.getElementById('chatNotifBadge');
                    if (badge) {
                        const count = parseInt(data.unread_count) || 0;
                        if (count > 0) {
                            badge.textContent = count;
                            badge.style.display = 'flex';
                        } else {
                            badge.textContent = '';
                            badge.style.display = 'none';
                        }
                    }
                })
                .catch(() => {
                    const badge = document.getElementById('chatNotifBadge');
                    if (badge) {
                        badge.textContent = '';
                        badge.style.display = 'none';
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
                z-index: 9999999;
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

        // ── GANTI NAMA MODAL ──
        function openChangeNameModal() {
            closeAllDropdowns();
            let existingModal = document.getElementById('changeNameModal');
            if (existingModal) existingModal.remove();
            
            const modal = document.createElement('div');
            modal.id = 'changeNameModal';
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
                z-index: 9999999;
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
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
                transform: scale(0.9);
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            `;
            
            const currentNama = document.querySelector('.profile-info h4') ? document.querySelector('.profile-info h4').textContent.trim() : '';
            
            card.innerHTML = `
                <h3 style="margin: 0 0 16px; font-family: 'Poppins', sans-serif; font-size: 18px; font-weight: 750; color: #1e293b; text-align: center;">Ganti Nama</h3>
                <form id="changeNameForm" onsubmit="submitChangeName(event)">
                    <div style="margin-bottom: 20px; text-align: left;">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Nama Lengkap Baru</label>
                        <input type="text" id="newNameInput" name="nama" value="${currentNama.replace(/"/g, '&quot;')}" required style="width: 100%; padding: 11px 16px; border-radius: 12px; border: 1.5px solid #cbd5e1; outline: none; font-size: 13.5px; box-sizing: border-box; font-family: 'Poppins', sans-serif;">
                    </div>
                    <div style="display: flex; gap: 10px; justify-content: center;">
                        <button type="button" id="changeNameCancelBtn" style="flex: 1; padding: 11px; border-radius: 12px; border: 1.5px solid #cbd5e1; background: #ffffff; color: #475569; font-family: 'Poppins', sans-serif; font-size: 13.5px; font-weight: 700; cursor: pointer; transition: all 0.2s;">Batal</button>
                        <button type="submit" style="flex: 1; padding: 11px; border-radius: 12px; border: none; background: #5cb85c; color: #ffffff; font-family: 'Poppins', sans-serif; font-size: 13.5px; font-weight: 700; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 12px rgba(92, 184, 92, 0.25);">Simpan</button>
                    </div>
                </form>
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
            
            document.getElementById('changeNameCancelBtn').addEventListener('click', closeModal);
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });
        }

        function submitChangeName(e) {
            e.preventDefault();
            const input = document.getElementById('newNameInput');
            const nama = input.value.trim();
            if (!nama) return;

            const formData = new FormData();
            formData.append('action', 'ganti_nama');
            formData.append('nama', nama);

            fetch('actions/proses_profil.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast('🎉 Nama berhasil diperbarui!', 'success');
                    
                    // Update UI
                    document.querySelectorAll('.profile-info h4').forEach(el => el.textContent = nama);
                    
                    // Remove modal
                    document.getElementById('changeNameModal').style.opacity = '0';
                    setTimeout(() => document.getElementById('changeNameModal').remove(), 300);
                } else {
                    showToast('Gagal: ' + data.message, 'error');
                }
            })
            .catch(() => {
                showToast('Koneksi gagal!', 'error');
            });
        }

        // ── GANTI PASSWORD MODAL ──
        function openChangePasswordModal() {
            closeAllDropdowns();
            let existingModal = document.getElementById('changePasswordModal');
            if (existingModal) existingModal.remove();
            
            const modal = document.createElement('div');
            modal.id = 'changePasswordModal';
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
                z-index: 9999999;
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
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
                transform: scale(0.9);
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            `;
            
            card.innerHTML = `
                <h3 style="margin: 0 0 16px; font-family: 'Poppins', sans-serif; font-size: 18px; font-weight: 750; color: #1e293b; text-align: center;">Ganti Password</h3>
                <form id="changePasswordForm" onsubmit="submitChangePassword(event)">
                    <div style="margin-bottom: 12px; text-align: left;">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 4px;">Password Lama</label>
                        <input type="password" id="pwLamaInput" name="password_lama" required style="width: 100%; padding: 11px 16px; border-radius: 12px; border: 1.5px solid #cbd5e1; outline: none; font-size: 13.5px; box-sizing: border-box; font-family: 'Poppins', sans-serif;">
                    </div>
                    <div style="margin-bottom: 12px; text-align: left;">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 4px;">Password Baru</label>
                        <input type="password" id="pwBaruInput" name="password_baru" minlength="6" required style="width: 100%; padding: 11px 16px; border-radius: 12px; border: 1.5px solid #cbd5e1; outline: none; font-size: 13.5px; box-sizing: border-box; font-family: 'Poppins', sans-serif;">
                        <small style="font-size: 10px; color: #64748b; display: block; margin-top: 4px;">Minimal 6 karakter</small>
                    </div>
                    <div style="margin-bottom: 20px; text-align: left;">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 4px;">Konfirmasi Password Baru</label>
                        <input type="password" id="pwKonfirmInput" name="password_konfirm" required style="width: 100%; padding: 11px 16px; border-radius: 12px; border: 1.5px solid #cbd5e1; outline: none; font-size: 13.5px; box-sizing: border-box; font-family: 'Poppins', sans-serif;">
                    </div>
                    <div style="display: flex; gap: 10px; justify-content: center;">
                        <button type="button" id="changePasswordCancelBtn" style="flex: 1; padding: 11px; border-radius: 12px; border: 1.5px solid #cbd5e1; background: #ffffff; color: #475569; font-family: 'Poppins', sans-serif; font-size: 13.5px; font-weight: 700; cursor: pointer; transition: all 0.2s;">Batal</button>
                        <button type="submit" style="flex: 1; padding: 11px; border-radius: 12px; border: none; background: #5cb85c; color: #ffffff; font-family: 'Poppins', sans-serif; font-size: 13.5px; font-weight: 700; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 12px rgba(92, 184, 92, 0.25);">Simpan</button>
                    </div>
                </form>
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
            
            document.getElementById('changePasswordCancelBtn').addEventListener('click', closeModal);
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });
        }

        function submitChangePassword(e) {
            e.preventDefault();
            const pwLama = document.getElementById('pwLamaInput').value;
            const pwBaru = document.getElementById('pwBaruInput').value;
            const pwKonfirm = document.getElementById('pwKonfirmInput').value;

            if (pwBaru !== pwKonfirm) {
                showToast('Konfirmasi password tidak cocok!', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'ganti_password');
            formData.append('password_lama', pwLama);
            formData.append('password_baru', pwBaru);
            formData.append('password_konfirm', pwKonfirm);

            fetch('actions/proses_profil.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast('Password berhasil diperbarui!', 'success');
                    
                    // Remove modal
                    document.getElementById('changePasswordModal').style.opacity = '0';
                    setTimeout(() => document.getElementById('changePasswordModal').remove(), 300);
                } else {
                    showToast('Gagal: ' + data.message, 'error');
                }
            })
            .catch(() => {
                showToast('Koneksi gagal!', 'error');
            });
        }

        // ── GANTI FOTO PROFIL MODAL ──
        function openChangeAvatarModal() {
            closeAllDropdowns();
            let existingModal = document.getElementById('changeAvatarModal');
            if (existingModal) existingModal.remove();
            
            const modal = document.createElement('div');
            modal.id = 'changeAvatarModal';
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
                z-index: 9999999;
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
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
                transform: scale(0.9);
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            `;
            
            card.innerHTML = `
                <h3 style="margin: 0 0 16px; font-family: 'Poppins', sans-serif; font-size: 18px; font-weight: 750; color: #1e293b; text-align: center;">Ganti Foto Profil</h3>
                <form id="changeAvatarForm" onsubmit="submitChangeAvatar(event)">
                    <div style="margin-bottom: 20px; text-align: center;">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 12px; text-align: left;">Pilih File Foto</label>
                        <input type="file" id="newAvatarInput" name="foto_profil" accept="image/*" required style="width: 100%; outline: none; font-size: 13px; font-family: 'Poppins', sans-serif;">
                    </div>
                    <div style="display: flex; gap: 10px; justify-content: center;">
                        <button type="button" id="changeAvatarCancelBtn" style="flex: 1; padding: 11px; border-radius: 12px; border: 1.5px solid #cbd5e1; background: #ffffff; color: #475569; font-family: 'Poppins', sans-serif; font-size: 13.5px; font-weight: 700; cursor: pointer; transition: all 0.2s;">Batal</button>
                        <button type="submit" style="flex: 1; padding: 11px; border-radius: 12px; border: none; background: #5cb85c; color: #ffffff; font-family: 'Poppins', sans-serif; font-size: 13.5px; font-weight: 700; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 12px rgba(92, 184, 92, 0.25);">Unggah</button>
                    </div>
                </form>
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
            
            document.getElementById('changeAvatarCancelBtn').addEventListener('click', closeModal);
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });
        }

        function submitChangeAvatar(e) {
            e.preventDefault();
            const fileInput = document.getElementById('newAvatarInput');
            if (fileInput.files.length === 0) return;

            const formData = new FormData();
            formData.append('action', 'ganti_foto');
            formData.append('foto_profil', fileInput.files[0]);

            fetch('actions/proses_profil.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast('Foto profil berhasil diperbarui!', 'success');
                    
                    // Update UI avatars on page
                    const newPath = data.foto_path;
                    document.querySelectorAll('.blank-avatar, .profile-dropdown img').forEach(el => {
                        el.src = newPath;
                    });
                    
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast('Gagal: ' + data.message, 'error');
                }
            })
            .catch(() => {
                showToast('Koneksi gagal!', 'error');
            });
        }

        // ════════════════════════════════════════════
        //  INIT
        // ════════════════════════════════════════════
        document.addEventListener('DOMContentLoaded', () => {
            fetchDBCart(); // Sinkronkan keranjang dari DB
            updateBadges(); // Ganti ini agar sesuai dengan format drawer
            renderFavorites();
            initMenuTerlarisSlider();
            makeCartDraggable();

            // Start promo slideshow autoplay
            showPromoSlide(0);
            startPromoInterval();

            // Check unread chats immediately and periodically
            checkUnreadChats();
            setInterval(checkUnreadChats, 5000);

            // Cek parameter tab di URL
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            if (tabParam) {
                switchNav(tabParam);
                // Bersihkan query parameter dari URL agar jika di-refresh kembali ke beranda
                const newUrl = window.location.pathname;
                window.history.replaceState({}, document.title, newUrl);
            } else if (window.location.hash) {
                const hashTab = window.location.hash.substring(1);
                if (document.getElementById('section-' + hashTab)) {
                    switchNav(hashTab);
                    // Bersihkan hash dari URL
                    const newUrl = window.location.pathname + window.location.search;
                    window.history.replaceState({}, document.title, newUrl);
                }
            }
        });
    </script>
    <script src="../../assets/js/banner-canvas.js?v=<?= time(); ?>"></script>
</body>

</html>