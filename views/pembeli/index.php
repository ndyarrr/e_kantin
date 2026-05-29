<?php
//pembeli.index.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
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
$q_all_menu = mysqli_query($koneksi, "SELECT menu.*, toko.nama_toko, toko.id_toko FROM menu 
                                      JOIN toko ON menu.id_toko = toko.id_toko 
                                      WHERE menu.tersedia = 1 AND menu.stok > 0 
                                      ORDER BY menu.id_menu DESC");
if ($q_all_menu) {
    while ($r = mysqli_fetch_assoc($q_all_menu))
        $all_menus[] = $r;
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

function resolveTokoImg($foto, $nama)
{
    if (!empty($foto)) {
        if (file_exists(__DIR__ . '/../../assets/img/kantin/' . $foto))
            return '../../assets/img/kantin/' . $foto;
        if (file_exists(__DIR__ . '/../../assets/img/' . $foto))
            return '../../assets/img/' . $foto;
    }
    $n = strtolower($nama);
    $map = [
        'tika' => 'kantin_bu_tika.jpeg',
        'fajar' => 'kantin_pak_fajar.jpeg',
        'agus' => 'kantin_pak_agus.jpeg',
        'mardika' => 'kantin_pak_mardika.jpeg',
        'basuni' => 'kantin_pak_basuni.jpeg',
        'sahudi' => 'kantin_pak_sahudi.jpeg',
        'sukamto' => 'kantin_pak_sukamto.jpeg',
        'angga' => 'kantin_pak_angga.jpeg',
        'dian' => 'kantin_bu_dian.jpeg',
        'kom' => 'kantin_bu_kom.jpeg'
    ];
    foreach ($map as $key => $file) {
        if (str_contains($n, $key)) {
            if (file_exists(__DIR__ . '/../../assets/img/kantin/' . $file))
                return '../../assets/img/kantin/' . $file;
            if (file_exists(__DIR__ . '/../../assets/img/' . $file))
                return '../../assets/img/' . $file;
        }
    }
    return '';
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
                <img src="../../assets/img/logo-esemkita.png" class="school-logo" alt="Logo">
                <span class="brand-name">E-Kantin</span>
            </div>
            
            <div class="search-container">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="searchInput" placeholder="Cari menu atau kantin..." oninput="handleSearch(this.value)">
            </div>
            
            <div class="header-icons">
                
                <div class="dropdown-wrapper">
                    <div class="icon-badge" onclick="toggleDropdown('notifDrop')">
                        <i class="fa-regular fa-bell"></i>
                        <?php if ($unread_notif_count > 0): ?>
                            <span class="badge" id="notifBadge"><?= $unread_notif_count ?></span>
                        <?php else: ?>
                            <span class="badge" id="notifBadge" style="display: none;">0</span>
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
                        <span class="badge" id="headerCartBadge">0</span>
                    </div>
                </div>

                <div class="dropdown-wrapper">
                    <?php if ($has_avatar): ?>
                        <img src="<?= $avatar_path; ?>" class="blank-avatar" alt="Profil" onclick="toggleDropdown('profileDrop')">
                    <?php else: ?>
                        <div class="avatar-initials size-sm" onclick="toggleDropdown('profileDrop')">
                            <?= strtoupper(substr($user_nama, 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="dropdown-panel profile-dropdown" id="profileDrop">
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
                        <a href="#" class="profile-menu-item" onclick="switchNav('beranda')"><i class="fa-solid fa-house"></i> Beranda</a>
                        <a href="#" class="profile-menu-item" onclick="switchNav('pesanan')"><i class="fa-solid fa-receipt"></i> Pesanan Saya</a>
                        <div style="border-top:1px solid #f1f5f9;margin:4px 0"></div>
                        <a href="../../auth/logout.php" class="profile-menu-item danger"><i class="fa-solid fa-right-from-bracket"></i> Keluar</a>
                    </div>
                </div>
                
            </div>
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
    <main class="content-container">
        <div class="content-inner">
            <?php require __DIR__ . '/sections/beranda.php'; ?>
            <?php require __DIR__ . '/sections/pesanan.php'; ?>
            <?php require __DIR__ . '/sections/favorit.php'; ?>
            <?php require __DIR__ . '/sections/kantin.php'; ?>
            <?php require __DIR__ . '/sections/chat.php'; ?>
        </div>
    </main>
    <button class="fab-cart" title="Lihat Keranjang">
        <i class="fa-solid fa-cart-shopping"></i>
        <span class="fab-cart-badge" id="fabCartBadge">0</span>
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
                'harga' => $m['harga'],
                'foto_menu' => $m['foto_menu'] ?? '',
                'kategori' => strtolower($m['kategori'] ?? 'makanan'),
                'nama_toko' => $m['nama_toko'],
                'id_toko' => (int) $m['id_toko']
            ];
        }, $all_menus)); ?>;

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
        //  CART (localStorage) - VERSI DRAWER FULLSCREEN
        // ════════════════════════════════════════════
        function getCart() {
            try { return JSON.parse(localStorage.getItem('ekantin_cart') || '[]'); }
            catch { return []; }
        }

        function saveCart(cart) {
            localStorage.setItem('ekantin_cart', JSON.stringify(cart));
            updateBadges();
            if (document.getElementById('cartDrawer').classList.contains('show')) {
                renderCartDrawer();
            }
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

        function addToCart(id, nama, harga, foto, toko, idToko) {
            const cart = getCart();
            const existing = cart.find(c => c.id_menu === id);
            if (existing) {
                existing.jumlah++;
                existing.selected = true; // Auto select if added again
            } else {
                cart.push({ id_menu: id, nama_menu: nama, harga: harga, jumlah: 1, foto_menu: foto, nama_toko: toko, id_toko: idToko, selected: true });
            }
            saveCart(cart);
            showToast(nama, 'success', { foto: foto, toko: toko });
        }

        function updateCartQty(id, delta, event) {
            if(event) event.stopPropagation();
            const cart = getCart();
            const item = cart.find(c => c.id_menu === id);
            if (item) {
                item.jumlah += delta;
                if (item.jumlah <= 0) {
                    cart.splice(cart.indexOf(item), 1);
                }
            }
            saveCart(cart);
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

        function toggleCartItemSelection(id) {
            const cart = getCart();
            const item = cart.find(c => c.id_menu === id);
            if (item) {
                item.selected = item.selected === false ? true : false;
            }
            saveCart(cart);
        }

        function toggleSelectAllCart(isChecked) {
            const cart = getCart();
            cart.forEach(item => {
                item.selected = isChecked;
            });
            saveCart(cart);
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
                                <button onclick="updateCartQty(${item.id_menu}, -1, event)" style="border: none; background: none; padding: 4px 10px; cursor: pointer; font-size: 14px; font-weight: 700; color: #64748b; transition: background 0.2s;">−</button>
                                <span style="font-size: 13px; font-weight: 700; color: #1e293b; min-width: 20px; text-align: center;">${item.jumlah}</span>
                                <button onclick="updateCartQty(${item.id_menu}, 1, event)" style="border: none; background: none; padding: 4px 10px; cursor: pointer; font-size: 14px; font-weight: 700; color: #64748b; transition: background 0.2s;">+</button>
                            </div>
                        </div>
                    </div>`;
            });
            body.innerHTML = html;
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
        function renderMenuImageHTML(foto_menu, kategori, nama_menu) {
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
                <div class="menu-card-full-img-wrap">
                    <img src="${img_src}" alt="${nama_menu}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="menu-img-placeholder ${kat}" style="display:none;">
                        ${svgContent}
                    </div>
                </div>`;
            } else {
                return `
                <div class="menu-card-full-img-wrap">
                    <div class="menu-img-placeholder ${kat}">
                        ${svgContent}
                    </div>
                </div>`;
            }
        }

        function renderFavorites() {
            const favs = getFavorites();
            const grid = document.getElementById('favoritGrid');
            const empty = document.getElementById('favoritEmpty');

            if (favs.length === 0) {
                grid.innerHTML = '';
                empty.style.display = 'block';
                return;
            }
            empty.style.display = 'none';

            const favMenus = ALL_MENUS.filter(m => favs.includes(Number(m.id_menu)));
            if (favMenus.length === 0) {
                grid.innerHTML = '';
                empty.style.display = 'block';
                return;
            }

            grid.innerHTML = favMenus.map(m => {
                const imgWrapHTML = renderMenuImageHTML(m.foto_menu, m.kategori, m.nama_menu);
                return `
            <div class="menu-card-full">
                ${imgWrapHTML}
                <div class="mc-info">
                    <h4>${m.nama_menu}</h4>
                    <p class="mc-toko">${m.nama_toko}</p>
                    <p class="mc-price">Rp. ${Number(m.harga).toLocaleString('id-ID')}</p>
                    <div style="display:flex;gap:8px">
                        <button class="btn-tambah-keranjang" style="flex:1" onclick="addToCart(${m.id_menu},'${m.nama_menu.replace(/'/g, "\\'")}',${m.harga},'${(m.foto_menu || '').replace(/'/g, "\\'")}','${m.nama_toko.replace(/'/g, "\\'")}',${m.id_toko})">
                            <i class="fa-solid fa-cart-plus"></i> Tambah
                        </button>
                        <button class="btn-tambah-keranjang" style="flex:0;padding:8px 12px;background:#ef4444;box-shadow:0 4px 12px rgba(239,68,68,.2)" onclick="toggleFavorite(${m.id_menu})">
                            <i class="fa-solid fa-heart-crack"></i>
                        </button>
                    </div>
                </div>
            </div>`;
            }).join('');
        }

        // ════════════════════════════════════════════
        //  SEARCH
        // ════════════════════════════════════════════
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

                // Also filter kantin cards
                document.querySelectorAll('#kantinGrid .kantin-card').forEach(card => {
                    const nama = card.dataset.nama || '';
                    card.style.display = nama.includes(q) ? '' : 'none';
                });

                if (results.length === 0) {
                    grid.innerHTML = '<div class="empty-state" style="grid-column:1/-1"><i class="fa-solid fa-magnifying-glass"></i><h3>Tidak ditemukan</h3><p>Coba kata kunci lain</p></div>';
                } else {
                    grid.innerHTML = results.map(m => {
                        const imgWrapHTML = renderMenuImageHTML(m.foto_menu, m.kategori, m.nama_menu);
                        return `
                    <div class="menu-card-full">
                        ${imgWrapHTML}
                        <div class="mc-info">
                            <h4>${m.nama_menu}</h4>
                            <p class="mc-toko">${m.nama_toko}</p>
                            <p class="mc-price">Rp. ${Number(m.harga).toLocaleString('id-ID')}</p>
                            <button class="btn-tambah-keranjang" onclick="addToCart(${m.id_menu},'${m.nama_menu.replace(/'/g, "\\'")}',${m.harga},'${(m.foto_menu || '').replace(/'/g, "\\'")}','${m.nama_toko.replace(/'/g, "\\'")}',${m.id_toko})">
                                <i class="fa-solid fa-cart-plus"></i> Tambah
                            </button>
                        </div>
                    </div>`;
                    }).join('');
                }

                resultSection.style.display = '';
                promoSection.style.display = 'none';
                menuSection.style.display = 'none';
                kategoriSection.style.display = 'none';
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
                    const imgWrapHTML = renderMenuImageHTML(m.foto_menu, m.kategori, m.nama_menu);
                    return `
                    <div class="menu-card-full">
                        ${imgWrapHTML}
                        <div class="mc-info">
                            <h4>${m.nama_menu}</h4>
                            <p class="mc-toko">${m.nama_toko}</p>
                            <p class="mc-price">Rp. ${Number(m.harga).toLocaleString('id-ID')}</p>
                            <button class="btn-tambah-keranjang" onclick="addToCart(${m.id_menu},'${m.nama_menu.replace(/'/g, "\\'")}',${m.harga},'${(m.foto_menu || '').replace(/'/g, "\\'")}','${m.nama_toko.replace(/'/g, "\\'")}',${m.id_toko})">
                                <i class="fa-solid fa-cart-plus"></i> Tambah
                            </button>
                        </div>
                    </div>`;
                }).join('');
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

        function showPromoSlide(index) {
            const slides = document.querySelectorAll('.promo-slide');
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
            const slides = document.querySelectorAll('.promo-slide');
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
        //  INIT
        // ════════════════════════════════════════════
        document.addEventListener('DOMContentLoaded', () => {
            updateBadges(); // Ganti ini agar sesuai dengan format drawer
            renderFavorites();
            initMenuTerlarisSlider();
            makeCartDraggable();

            // Start promo slideshow autoplay
            startPromoInterval();

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
</body>

</html>