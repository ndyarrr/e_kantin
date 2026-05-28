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
        if (str_contains($n, $key) && file_exists(__DIR__ . '/../../assets/img/' . $file))
            return '../../assets/img/' . $file;
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
        <div class="header-pattern-left"></div>
        <div class="header-pattern-right"></div>
        <div class="header-inner">
            <div class="top-bar">
                <div class="logo-area">
                    <img src="../../assets/img/logo-esemkita.png" class="school-logo"
                        style="width:38px;height:38px;object-fit:contain;flex-shrink:0;border-radius:50%;background:#fff;padding:2px"
                        alt="Logo">
                    <span class="brand-name">E-Kantin</span>
                </div>
                <div class="search-container">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" id="searchInput" placeholder="Cari menu atau kantin..."
                        oninput="handleSearch(this.value)">
                </div>
                <div class="header-icons">
                    <!-- Notifikasi -->
                    <div class="dropdown-wrapper">
                        <div class="icon-badge" onclick="toggleDropdown('notifDrop')">
                            <i class="fa-regular fa-bell"></i>
                            <span class="badge" id="notifBadge">3</span>
                        </div>
                        <div class="dropdown-panel" id="notifDrop">
                            <div class="dropdown-header">Notifikasi</div>
                            <div class="dropdown-body" id="notifBody">
                                <div class="dropdown-item">
                                    <div class="notif-dot"></div>
                                    <div class="item-info">
                                        <h4>Pesanan #001 sedang diproses</h4>
                                        <p>Kantin Bu Tika sedang menyiapkan pesananmu</p>
                                    </div>
                                    <span class="notif-time">2m</span>
                                </div>
                                <div class="dropdown-item">
                                    <div class="notif-dot"></div>
                                    <div class="item-info">
                                        <h4>Promo baru tersedia!</h4>
                                        <p>Diskon 25% untuk semua menu hari ini</p>
                                    </div>
                                    <span class="notif-time">15m</span>
                                </div>
                                <div class="dropdown-item">
                                    <div class="notif-dot"></div>
                                    <div class="item-info">
                                        <h4>Selamat datang di E-Kantin!</h4>
                                        <p>Jelajahi menu favorit dari kantin sekolahmu</p>
                                    </div>
                                    <span class="notif-time">1j</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Keranjang -->
                    <div class="dropdown-wrapper">
                        <div class="icon-badge" onclick="toggleDropdown('cartDrop')">
                            <i class="fa-solid fa-cart-shopping"></i>
                            <span class="badge" id="cartBadge">0</span>
                        </div>
                        <div class="dropdown-panel" id="cartDrop" style="min-width:340px">
                            <div class="dropdown-header">Keranjang <span id="cartCount"
                                    style="color:#64748b;font-weight:600;font-size:13px"></span></div>
                            <div class="dropdown-body" id="cartBody"></div>
                            <div class="dropdown-footer" id="cartFooter" style="display:none">
                                <div><span class="total-label">Total</span><br><span class="total-amount"
                                        id="cartTotal">Rp 0</span></div>
                                <button class="btn-checkout"
                                    onclick="event.stopPropagation(); showToast('Fitur checkout segera hadir!','success')">Checkout</button>
                            </div>
                        </div>
                    </div>
                    <!-- Profil -->
                    <div class="dropdown-wrapper">
                        <?php if ($has_avatar): ?>
                            <img src="<?= $avatar_path; ?>" class="blank-avatar" alt="Profil"
                                onclick="toggleDropdown('profileDrop')">
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
                            <a href="#" class="profile-menu-item" onclick="switchNav('beranda')"><i
                                    class="fa-solid fa-house"></i> Beranda</a>
                            <a href="#" class="profile-menu-item" onclick="switchNav('pesanan')"><i
                                    class="fa-solid fa-receipt"></i> Pesanan Saya</a>
                            <a href="#" class="profile-menu-item" onclick="switchNav('favorit')"><i
                                    class="fa-solid fa-heart"></i> Favorit</a>
                            <a href="#" class="profile-menu-item" onclick="switchNav('chat')"><i
                                    class="fa-solid fa-comment-dots"></i> Chat Kantin</a>
                            <div style="border-top:1px solid #f1f5f9;margin:4px 0"></div>
                            <a href="../../auth/logout.php" class="profile-menu-item danger"><i
                                    class="fa-solid fa-right-from-bracket"></i> Keluar</a>
                        </div>
                    </div>
                </div>
            </div>

            <nav class="nav-menu">
                <a class="nav-item active" data-nav="beranda" onclick="switchNav('beranda')">
                    <i class="fa-solid fa-house"></i>
                    <span>Beranda</span>
                </a>
                <a class="nav-item" data-nav="pesanan" onclick="switchNav('pesanan')">
                    <i class="fa-solid fa-receipt"></i>
                    <span>Pesanan</span>
                </a>
                <a class="nav-item" data-nav="favorit" onclick="switchNav('favorit')">
                    <i class="fa-solid fa-heart"></i>
                    <span>Favorit</span>
                </a>
                <a class="nav-item" data-nav="kantin" onclick="switchNav('kantin')">
                    <i class="fa-solid fa-store"></i>
                    <span>Kantin</span>
                </a>
                <a class="nav-item" data-nav="chat" onclick="switchNav('chat')">
                    <i class="fa-solid fa-comment-dots"></i>
                    <span>Chat</span>
                </a>
            </nav>
        </div>
    </header>

    <main class="content-container">
        <div class="content-inner">
            <?php require __DIR__ . '/sections/beranda.php'; ?>
            <?php require __DIR__ . '/sections/pesanan.php'; ?>
            <?php require __DIR__ . '/sections/favorit.php'; ?>
            <?php require __DIR__ . '/sections/kantin.php'; ?>
            <?php require __DIR__ . '/sections/chat.php'; ?>
        </div>
    </main>

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
            event && event.preventDefault();
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
        //  CART (localStorage)
        // ════════════════════════════════════════════
        function getCart() {
            try { return JSON.parse(localStorage.getItem('ekantin_cart') || '[]'); }
            catch { return []; }
        }

        function saveCart(cart) {
            localStorage.setItem('ekantin_cart', JSON.stringify(cart));
            renderCart();
        }

        function addToCart(id, nama, harga, foto, toko, idToko) {
            const cart = getCart();
            const existing = cart.find(c => c.id_menu === id);
            if (existing) {
                existing.jumlah++;
            } else {
                cart.push({ id_menu: id, nama_menu: nama, harga: harga, jumlah: 1, foto_menu: foto, nama_toko: toko, id_toko: idToko });
            }
            saveCart(cart);
            showToast('✅ ' + nama + ' ditambahkan ke keranjang!', 'success');
        }

        function updateCartQty(id, delta, event) {
            event && event.stopPropagation(); // ← tambah ini
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

        function renderCart() {
            const cart = getCart();
            const body = document.getElementById('cartBody');
            const footer = document.getElementById('cartFooter');
            const badge = document.getElementById('cartBadge');
            const count = document.getElementById('cartCount');
            const totalEl = document.getElementById('cartTotal');

            const totalItems = cart.reduce((s, c) => s + c.jumlah, 0);
            const totalPrice = cart.reduce((s, c) => s + (c.harga * c.jumlah), 0);

            badge.textContent = totalItems;
            badge.style.display = totalItems > 0 ? 'flex' : 'none';
            count.textContent = totalItems > 0 ? '(' + totalItems + ' item)' : '';

            if (cart.length === 0) {
                body.innerHTML = '<div class="dropdown-empty"><i class="fa-solid fa-cart-shopping"></i>Keranjang masih kosong</div>';
                footer.style.display = 'none';
                return;
            }

            footer.style.display = 'flex';
            totalEl.textContent = 'Rp ' + totalPrice.toLocaleString('id-ID');

            let html = '';
            cart.forEach(item => {
                let imgHTML = '';
                if (item.foto_menu) {
                    imgHTML = `<img src="../../assets/img/menu/${item.foto_menu}" alt="${item.nama_menu}" onerror="this.outerHTML='<div class=\\'cart-img-placeholder\\'><i class=\\'fa-solid fa-utensils\\'></i></div>';">`;
                } else {
                    imgHTML = `<div class="cart-img-placeholder"><i class="fa-solid fa-utensils"></i></div>`;
                }
                html += `
            <div class="dropdown-item">
                ${imgHTML}
                <div class="item-info">
                    <h4>${item.nama_menu}</h4>
                    <p>${item.nama_toko}</p>
                </div>
                <div style="text-align:right">
                    <div class="item-price">Rp ${(item.harga * item.jumlah).toLocaleString('id-ID')}</div>
                    <div class="item-qty" style="margin-top:4px">
                        <button onclick="updateCartQty(${item.id_menu},-1,event)">−</button>
                        <span>${item.jumlah}</span>
                        <button onclick="updateCartQty(${item.id_menu},1,event)">+</button>
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
                showToast('❤️ Ditambahkan ke favorit!', 'success');
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
        //  TOAST NOTIFICATION
        // ════════════════════════════════════════════
        function showToast(msg, type) {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = 'toast ' + (type || '');
            toast.textContent = msg;
            container.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }



        // ════════════════════════════════════════════
        //  INIT
        // ════════════════════════════════════════════
        document.addEventListener('DOMContentLoaded', () => {
            renderCart();
            renderFavorites();
        });
    </script>
</body>

</html>