<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
$koneksi = $conn;

// Ambil foto profil pembeli dari session
$avatar_file = $_SESSION['user_foto'] ?? '';
$avatar_path = '../../assets/img/' . $avatar_file;
if (empty($avatar_file) || !file_exists(__DIR__ . '/../../assets/img/' . $avatar_file)) {
    $avatar_path = '../../assets/img/PPAril.jpeg';
}
$user_nama = $_SESSION['user_nama'] ?? 'Pembeli';
$user_role = $_SESSION['user_role'] ?? 'siswa';
$user_id   = $_SESSION['user_id'] ?? '';

// ── Ambil SEMUA menu tersedia (untuk section Beranda + Kantin) ──
$all_menus = [];
$q_all_menu = mysqli_query($koneksi, "SELECT menu.*, toko.nama_toko, toko.id_toko FROM menu 
                                      JOIN toko ON menu.id_toko = toko.id_toko 
                                      WHERE menu.tersedia = 1 AND menu.stok > 0 
                                      ORDER BY menu.id_menu DESC");
if ($q_all_menu) {
    while ($r = mysqli_fetch_assoc($q_all_menu)) $all_menus[] = $r;
}

// ── Ambil SEMUA toko ──
$all_tokos = [];
$q_all_toko = mysqli_query($koneksi, "SELECT * FROM toko WHERE deleted_at IS NULL ORDER BY FIELD(status, 'buka', 'tutup'), nama_toko ASC");
if ($q_all_toko) {
    while ($r = mysqli_fetch_assoc($q_all_toko)) $all_tokos[] = $r;
}

// Helper: resolve image path
function resolveMenuImg($foto) {
    if (!empty($foto)) {
        if (file_exists(__DIR__ . '/../../assets/img/menu/' . $foto)) return '../../assets/img/menu/' . $foto;
        if (file_exists(__DIR__ . '/../../assets/img/' . $foto)) return '../../assets/img/' . $foto;
    }
    return '../../assets/img/ayam.png';
}

function resolveTokoImg($foto, $nama) {
    if (!empty($foto)) {
        if (file_exists(__DIR__ . '/../../assets/img/kantin/' . $foto)) return '../../assets/img/kantin/' . $foto;
        if (file_exists(__DIR__ . '/../../assets/img/' . $foto)) return '../../assets/img/' . $foto;
    }
    $n = strtolower($nama);
    $map = ['tika'=>'kantin_bu_tika.jpeg','fajar'=>'kantin_pak_fajar.jpeg','agus'=>'kantin_pak_agus.jpeg',
            'mardika'=>'kantin_pak_mardika.jpeg','basuni'=>'kantin_pak_basuni.jpeg',
            'sahudi'=>'kantin_pak_sahudi.jpeg','sukamto'=>'kantin_pak_sukamto.jpeg',
            'angga'=>'kantin_pak_angga.jpeg','dian'=>'kantin_bu_dian.jpeg','kom'=>'kantin_bu_kom.jpeg'];
    foreach ($map as $key => $file) {
        if (str_contains($n, $key) && file_exists(__DIR__ . '/../../assets/img/' . $file)) return '../../assets/img/' . $file;
    }
    return '../../assets/img/ayam.png';
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
    <style>
        /* ── Floating Chat Button & Modal ── */
        .fab-chat{position:fixed;bottom:28px;right:28px;width:56px;height:56px;background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:22px;cursor:pointer;box-shadow:0 4px 20px rgba(34,197,94,.4);z-index:999;border:none;transition:transform .2s,box-shadow .2s}
        .fab-chat:hover{transform:scale(1.1);box-shadow:0 6px 24px rgba(34,197,94,.5)}
        .chat-modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center}
        .chat-modal-overlay.active{display:flex}
        .chat-modal-box{background:#fff;border-radius:16px;width:90%;max-width:860px;height:80vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.25);position:relative}
        .chat-modal-topbar{background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;padding:14px 20px;display:flex;align-items:center;justify-content:space-between;font-weight:700;font-size:15px}
        .chat-modal-topbar button{background:rgba(255,255,255,.2);border:none;color:#fff;width:32px;height:32px;border-radius:8px;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center}
        .chat-modal-content{flex:1;overflow:hidden;display:flex}
        .chat-modal-content .chat-wrapper{flex:1;border-radius:0;border:none;margin-top:0;height:100%!important;min-height:unset!important}

        /* ── Dropdown Menus ── */
        .dropdown-wrapper{position:relative}
        .dropdown-panel{display:none;position:absolute;top:calc(100% + 12px);right:0;background:#fff;border-radius:16px;box-shadow:0 12px 40px rgba(0,0,0,.12);min-width:300px;z-index:1100;overflow:hidden;border:1px solid #f1f5f9;animation:dropIn .2s ease}
        .dropdown-panel.show{display:block}
        @keyframes dropIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
        .dropdown-header{padding:16px 20px;border-bottom:1px solid #f1f5f9;font-weight:800;font-size:15px;color:#0f172a;display:flex;justify-content:space-between;align-items:center}
        .dropdown-body{max-height:320px;overflow-y:auto;padding:8px 0}
        .dropdown-body::-webkit-scrollbar{width:4px}
        .dropdown-body::-webkit-scrollbar-thumb{background:#cbd5e0;border-radius:4px}
        .dropdown-item{display:flex;align-items:center;gap:12px;padding:12px 20px;cursor:pointer;transition:background .15s}
        .dropdown-item:hover{background:#f8fafc}
        .dropdown-item img{width:40px;height:40px;border-radius:10px;object-fit:cover;flex-shrink:0}
        .dropdown-item .item-info{flex:1;min-width:0}
        .dropdown-item .item-info h4{font-size:13px;font-weight:700;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .dropdown-item .item-info p{font-size:11px;color:#64748b;margin-top:2px}
        .dropdown-item .item-price{font-size:13px;font-weight:800;color:#5cb85c;white-space:nowrap}
        .dropdown-item .item-qty{display:flex;align-items:center;gap:8px}
        .dropdown-item .item-qty button{width:26px;height:26px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;cursor:pointer;font-size:14px;font-weight:700;color:#1e293b;display:flex;align-items:center;justify-content:center;transition:all .15s}
        .dropdown-item .item-qty button:hover{background:#f0fdf4;border-color:#5cb85c;color:#5cb85c}
        .dropdown-item .item-qty span{font-size:14px;font-weight:700;min-width:18px;text-align:center}
        .dropdown-footer{padding:12px 20px;border-top:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center}
        .dropdown-footer .total-label{font-size:13px;color:#64748b;font-weight:600}
        .dropdown-footer .total-amount{font-size:16px;font-weight:800;color:#0f172a}
        .dropdown-footer .btn-checkout{background:#5cb85c;color:#fff;border:none;padding:10px 24px;border-radius:999px;font-weight:700;font-size:13px;cursor:pointer;box-shadow:0 4px 12px rgba(92,184,92,.25);transition:all .2s}
        .dropdown-footer .btn-checkout:hover{background:#4cae4c;transform:translateY(-1px)}
        .dropdown-empty{padding:40px 20px;text-align:center;color:#94a3b8;font-size:13px}
        .dropdown-empty i{font-size:32px;margin-bottom:10px;display:block;color:#cbd5e0}

        /* Notif item */
        .notif-dot{width:8px;height:8px;border-radius:50%;background:#5cb85c;flex-shrink:0}
        .notif-time{font-size:10px;color:#94a3b8;white-space:nowrap}

        /* Profile dropdown */
        .profile-dropdown{min-width:240px}
        .profile-header{display:flex;align-items:center;gap:12px;padding:20px;border-bottom:1px solid #f1f5f9}
        .profile-header img{width:48px;height:48px;border-radius:12px;object-fit:cover}
        .profile-header .profile-info h4{font-size:15px;font-weight:800;color:#0f172a}
        .profile-header .profile-info p{font-size:12px;color:#64748b;margin-top:2px;text-transform:capitalize}
        .profile-menu-item{display:flex;align-items:center;gap:12px;padding:12px 20px;cursor:pointer;transition:background .15s;font-size:14px;font-weight:600;color:#334155;text-decoration:none}
        .profile-menu-item:hover{background:#f8fafc;color:#5cb85c}
        .profile-menu-item i{width:20px;text-align:center;color:#64748b;font-size:16px}
        .profile-menu-item:hover i{color:#5cb85c}
        .profile-menu-item.danger{color:#ef4444}
        .profile-menu-item.danger i{color:#ef4444}
        .profile-menu-item.danger:hover{background:#fef2f2}

        /* ── Section Pages (Tab Navigation) ── */
        .page-section{display:none}
        .page-section.active{display:block}

        /* ── Toast ── */
        .toast-container{position:fixed;bottom:100px;left:50%;transform:translateX(-50%);z-index:9999;display:flex;flex-direction:column;gap:8px;align-items:center;pointer-events:none}
        .toast{background:#1e293b;color:#fff;padding:12px 24px;border-radius:12px;font-size:13px;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,.15);pointer-events:auto;animation:toastIn .3s ease,toastOut .3s ease 2.5s forwards}
        .toast.success{background:#16a34a}
        .toast.error{background:#ef4444}
        @keyframes toastIn{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
        @keyframes toastOut{from{opacity:1}to{opacity:0;transform:translateY(-10px)}}

        /* ── Pesanan Section ── */
        .empty-state{text-align:center;padding:60px 20px}
        .empty-state i{font-size:56px;color:#cbd5e0;margin-bottom:16px}
        .empty-state h3{font-size:18px;font-weight:800;color:#334155;margin-bottom:8px}
        .empty-state p{font-size:14px;color:#94a3b8;max-width:300px;margin:0 auto}

        /* ── Kantin Full Grid (tab Kantin) ── */
        .all-menu-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:16px}
        @media(min-width:768px){.all-menu-grid{grid-template-columns:repeat(3,1fr)}}
        @media(min-width:1024px){.all-menu-grid{grid-template-columns:repeat(4,1fr)}}
        .menu-card-full{background:#fff;border:1px solid #f1f5f9;border-radius:20px;padding:14px;text-align:center;box-shadow:0 8px 24px rgba(0,0,0,.02);transition:all .3s cubic-bezier(.4,0,.2,1);display:flex;flex-direction:column;justify-content:space-between}
        .menu-card-full:hover{transform:translateY(-4px);box-shadow:0 15px 35px rgba(22,62,43,.08);border-color:#bce4bc}
        .menu-card-full img{width:100%;aspect-ratio:1/1;object-fit:cover;border-radius:16px;border:2.5px solid #a3e635;padding:3px;background:#fff}
        .menu-card-full .mc-info{margin-top:10px;flex:1;display:flex;flex-direction:column;justify-content:space-between}
        .menu-card-full .mc-info h4{font-size:14px;font-weight:700;color:#0f172a;margin-bottom:4px;line-height:1.25;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
        .menu-card-full .mc-info .mc-toko{font-size:11px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px}
        .menu-card-full .mc-info .mc-price{font-size:13px;font-weight:800;color:#5cb85c;margin-bottom:10px}
        .btn-tambah-keranjang{background:#5cb85c;color:#fff;border:none;padding:8px 0;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;width:100%;box-shadow:0 4px 12px rgba(92,184,92,.2);transition:all .2s}
        .btn-tambah-keranjang:hover{background:#4cae4c;box-shadow:0 6px 16px rgba(92,184,92,.3)}

        /* ── Category filter active state ── */
        .category-item.active-cat .blank-circle{border-color:#5cb85c;box-shadow:0 4px 12px rgba(92,184,92,.2)}
        .category-item.active-cat span{color:#5cb85c;font-weight:800}

        /* ── Search results highlight ── */
        .search-results-section{margin-bottom:24px}
        .search-results-section .section-title{display:flex;align-items:center;gap:8px}
        .search-results-section .section-title .search-clear{font-size:12px;color:#ef4444;cursor:pointer;font-weight:600;text-decoration:underline}
    </style>
</head>
<body>

    <!-- ── TOP HEADER ── -->
    <header class="main-header">
        <div class="header-pattern-left"></div>
        <div class="header-pattern-right"></div>
        <div class="header-inner">
            <div class="top-bar">
                <div class="logo-area">
                    <img src="../../assets/img/logo-esemkita.png" class="school-logo" style="width:38px;height:38px;object-fit:contain;flex-shrink:0;border-radius:50%;background:#fff;padding:2px" alt="Logo">
                    <span class="brand-name">E-Kantin</span>
                </div>
                <div class="search-container">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" id="searchInput" placeholder="Cari menu atau kantin..." oninput="handleSearch(this.value)">
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
                            <div class="dropdown-header">Keranjang <span id="cartCount" style="color:#64748b;font-weight:600;font-size:13px"></span></div>
                            <div class="dropdown-body" id="cartBody"></div>
                            <div class="dropdown-footer" id="cartFooter" style="display:none">
                                <div><span class="total-label">Total</span><br><span class="total-amount" id="cartTotal">Rp 0</span></div>
                                <button class="btn-checkout" onclick="showToast('Fitur checkout segera hadir!','success')">Checkout</button>
                            </div>
                        </div>
                    </div>
                    <!-- Profil -->
                    <div class="dropdown-wrapper">
                        <img src="<?= $avatar_path; ?>" class="blank-avatar" alt="Profil" onclick="toggleDropdown('profileDrop')">
                        <div class="dropdown-panel profile-dropdown" id="profileDrop">
                            <div class="profile-header">
                                <img src="<?= $avatar_path; ?>" alt="Avatar">
                                <div class="profile-info">
                                    <h4><?= htmlspecialchars($user_nama); ?></h4>
                                    <p><?= htmlspecialchars($user_role); ?></p>
                                </div>
                            </div>
                            <a href="#" class="profile-menu-item" onclick="switchNav('beranda')"><i class="fa-solid fa-house"></i> Beranda</a>
                            <a href="#" class="profile-menu-item" onclick="switchNav('pesanan')"><i class="fa-solid fa-receipt"></i> Pesanan Saya</a>
                            <a href="#" class="profile-menu-item" onclick="switchNav('favorit')"><i class="fa-solid fa-heart"></i> Favorit</a>
                            <a href="#" class="profile-menu-item" onclick="bukaModalChat();closeAllDropdowns()"><i class="fa-solid fa-comment-dots"></i> Chat Kantin</a>
                            <div style="border-top:1px solid #f1f5f9;margin:4px 0"></div>
                            <a href="../../controllers/logout.php" class="profile-menu-item danger"><i class="fa-solid fa-right-from-bracket"></i> Keluar</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <nav class="nav-menu">
                <a href="#" class="nav-item active" data-nav="beranda" onclick="switchNav('beranda')">Beranda</a>
                <a href="#" class="nav-item" data-nav="pesanan" onclick="switchNav('pesanan')">Pesanan</a>
                <a href="#" class="nav-item" data-nav="favorit" onclick="switchNav('favorit')">Favorit</a>
                <a href="#" class="nav-item" data-nav="kantin" onclick="switchNav('kantin')">Kantin</a>
            </nav>
        </div>
    </header>

    <main class="content-container">
        <div class="content-inner">

            <!-- ═══════ SECTION: BERANDA ═══════ -->
            <div class="page-section active" id="section-beranda">

                <!-- Search Results (hidden by default) -->
                <section class="section-block search-results-section" id="searchResultsSection" style="display:none">
                    <h2 class="section-title">Hasil Pencarian: "<span id="searchQuery"></span>" <span class="search-clear" onclick="clearSearch()">✕ Hapus</span></h2>
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
                            <button class="btn-promo-blank" onclick="switchNav('kantin');showToast('Gunakan kode KANTINJOSS25 saat checkout!','success')">Pesan Sekarang</button>
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
                                <div class="menu-card" onclick="location.href='toko.php?id=<?= $menu['id_toko']; ?>'" style="cursor:pointer">
                                    <img src="<?= $img; ?>" class="menu-image-rect" alt="<?= htmlspecialchars($menu['nama_menu']); ?>">
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
                        <a href="#" class="category-item active-cat" data-kat="semua" onclick="filterKategori('semua',this)">
                            <div class="blank-circle icon-grid-center"><i class="fa-solid fa-table-cells-large"></i></div>
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
                            <div class="blank-circle"><img src="../../assets/img/ayam.png" style="filter:hue-rotate(130deg) saturate(1.5)" alt="Minuman"></div>
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
                            $status_teks  = $is_buka ? 'Buka' : 'Tutup';
                            $btn_disabled = !$is_buka ? 'style="background-color:#94a3b8;pointer-events:none;box-shadow:none"' : '';
                            $toko_img = resolveTokoImg($toko['foto_toko'] ?? '', $toko['nama_toko']);
                        ?>
                        <div class="kantin-card" data-nama="<?= strtolower($toko['nama_toko']); ?>">
                            <img src="<?= $toko_img; ?>" class="blank-image-square" alt="<?= htmlspecialchars($toko['nama_toko']); ?>">
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

            <!-- ═══════ SECTION: PESANAN ═══════ -->
            <div class="page-section" id="section-pesanan">
                <section class="section-block">
                    <h2 class="section-title">Pesanan Saya</h2>
                    <div class="empty-state" id="pesananEmpty">
                        <i class="fa-solid fa-receipt"></i>
                        <h3>Belum Ada Pesanan</h3>
                        <p>Pesanan yang kamu buat akan muncul di sini. Yuk mulai pesan dari kantin favoritmu!</p>
                        <br>
                        <button class="btn-promo-blank" onclick="switchNav('kantin')" style="font-size:13px;padding:10px 28px">Jelajahi Kantin</button>
                    </div>
                </section>
            </div>

            <!-- ═══════ SECTION: FAVORIT ═══════ -->
            <div class="page-section" id="section-favorit">
                <section class="section-block">
                    <h2 class="section-title">Menu Favorit</h2>
                    <div id="favoritGrid" class="all-menu-grid"></div>
                    <div class="empty-state" id="favoritEmpty">
                        <i class="fa-solid fa-heart"></i>
                        <h3>Belum Ada Favorit</h3>
                        <p>Tandai menu favoritmu dengan menekan ikon ❤️ agar mudah ditemukan nanti!</p>
                        <br>
                        <button class="btn-promo-blank" onclick="switchNav('beranda')" style="font-size:13px;padding:10px 28px">Jelajahi Menu</button>
                    </div>
                </section>
            </div>

            <!-- ═══════ SECTION: KANTIN (FULL MENU LIST) ═══════ -->
            <div class="page-section" id="section-kantin">
                <section class="section-block">
                    <h2 class="section-title">Semua Menu Tersedia</h2>
                    <!-- Category filter tabs -->
                    <div class="category-list" style="margin-bottom:20px">
                        <a href="#" class="category-item active-cat" data-kat2="semua" onclick="filterKantinMenu('semua',this)">
                            <div class="blank-circle icon-grid-center"><i class="fa-solid fa-table-cells-large"></i></div>
                            <span>Semua</span>
                        </a>
                        <a href="#" class="category-item" data-kat2="makanan" onclick="filterKantinMenu('makanan',this)">
                            <div class="blank-circle"><img src="../../assets/img/ayam.png" alt="Makanan"></div>
                            <span>Makanan</span>
                        </a>
                        <a href="#" class="category-item" data-kat2="snack" onclick="filterKantinMenu('snack',this)">
                            <div class="blank-circle"><img src="../../assets/img/soto.png" alt="Snack"></div>
                            <span>Snack</span>
                        </a>
                        <a href="#" class="category-item" data-kat2="minuman" onclick="filterKantinMenu('minuman',this)">
                            <div class="blank-circle"><img src="../../assets/img/ayam.png" style="filter:hue-rotate(130deg) saturate(1.5)" alt="Minuman"></div>
                            <span>Minuman</span>
                        </a>
                    </div>
                    <div class="all-menu-grid" id="allMenuGrid">
                        <?php foreach ($all_menus as $menu):
                            $img = resolveMenuImg($menu['foto_menu'] ?? '');
                            $kat = strtolower($menu['kategori'] ?? 'makanan');
                        ?>
                        <div class="menu-card-full" data-kategori="<?= $kat; ?>" data-nama="<?= strtolower($menu['nama_menu']); ?>" data-toko="<?= strtolower($menu['nama_toko']); ?>">
                            <img src="<?= $img; ?>" alt="<?= htmlspecialchars($menu['nama_menu']); ?>">
                            <div class="mc-info">
                                <h4><?= htmlspecialchars($menu['nama_menu']); ?></h4>
                                <p class="mc-toko"><?= htmlspecialchars($menu['nama_toko']); ?></p>
                                <p class="mc-price">Rp. <?= number_format($menu['harga'], 0, ',', '.'); ?></p>
                                <button class="btn-tambah-keranjang" onclick="addToCart(<?= $menu['id_menu']; ?>,'<?= htmlspecialchars(addslashes($menu['nama_menu']),ENT_QUOTES); ?>',<?= $menu['harga']; ?>,'<?= htmlspecialchars(addslashes($menu['foto_menu'] ?? ''),ENT_QUOTES); ?>','<?= htmlspecialchars(addslashes($menu['nama_toko']),ENT_QUOTES); ?>',<?= $menu['id_toko']; ?>)">
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

        </div>
    </main>

    <!-- Toast container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Floating Chat -->
    <button class="fab-chat" onclick="bukaModalChat()" title="Tanya Kantin">
        <i class="fa-solid fa-comment-dots"></i>
    </button>

    <div class="chat-modal-overlay" id="overlayModalChat" onclick="tutupModalChatKontainer(event)">
        <div class="chat-modal-box">
            <div class="chat-modal-topbar">
                <span>💬 Hubungi Kantin</span>
                <button onclick="tutupModalChat()">&times;</button>
            </div>
            <div class="chat-modal-content">
                <?php require __DIR__ . '/../chat.php'; ?>
            </div>
        </div>
    </div>

    <!-- Data menu JSON for JS -->
    <script>
    const ALL_MENUS = <?= json_encode(array_map(function($m) {
        return [
            'id_menu' => $m['id_menu'],
            'nama_menu' => $m['nama_menu'],
            'harga' => $m['harga'],
            'foto_menu' => $m['foto_menu'] ?? '',
            'kategori' => strtolower($m['kategori'] ?? 'makanan'),
            'nama_toko' => $m['nama_toko'],
            'id_toko' => $m['id_toko']
        ];
    }, $all_menus)); ?>;
    </script>

    <script>
    // ════════════════════════════════════════════
    //  NAVIGATION TABS
    // ════════════════════════════════════════════
    function switchNav(section) {
        event && event.preventDefault();
        // Update nav active states
        document.querySelectorAll('.nav-item').forEach(n => {
            n.classList.toggle('active', n.dataset.nav === section);
        });
        // Show/hide sections
        document.querySelectorAll('.page-section').forEach(s => {
            s.classList.toggle('active', s.id === 'section-' + section);
        });
        // Scroll to top
        window.scrollTo({top: 0, behavior: 'smooth'});
        closeAllDropdowns();
    }

    // ════════════════════════════════════════════
    //  DROPDOWNS (Notif, Cart, Profile)
    // ════════════════════════════════════════════
    function toggleDropdown(id) {
        event && event.stopPropagation();
        const panel = document.getElementById(id);
        const isOpen = panel.classList.contains('show');
        closeAllDropdowns();
        if (!isOpen) panel.classList.add('show');
    }

    function closeAllDropdowns() {
        document.querySelectorAll('.dropdown-panel').forEach(d => d.classList.remove('show'));
    }

    document.addEventListener('click', (e) => {
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
            cart.push({id_menu: id, nama_menu: nama, harga: harga, jumlah: 1, foto_menu: foto, nama_toko: toko, id_toko: idToko});
        }
        saveCart(cart);
        showToast('✅ ' + nama + ' ditambahkan ke keranjang!', 'success');
    }

    function updateCartQty(id, delta) {
        const cart = getCart();
        const item = cart.find(c => c.id_menu === id);
        if (item) {
            item.jumlah += delta;
            if (item.jumlah <= 0) {
                const idx = cart.indexOf(item);
                cart.splice(idx, 1);
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
            let imgSrc = '../../assets/img/ayam.png';
            if (item.foto_menu) imgSrc = '../../assets/img/menu/' + item.foto_menu;
            html += `
            <div class="dropdown-item">
                <img src="${imgSrc}" alt="${item.nama_menu}">
                <div class="item-info">
                    <h4>${item.nama_menu}</h4>
                    <p>${item.nama_toko}</p>
                </div>
                <div style="text-align:right">
                    <div class="item-price">Rp ${(item.harga * item.jumlah).toLocaleString('id-ID')}</div>
                    <div class="item-qty" style="margin-top:4px">
                        <button onclick="updateCartQty(${item.id_menu},-1)">−</button>
                        <span>${item.jumlah}</span>
                        <button onclick="updateCartQty(${item.id_menu},1)">+</button>
                    </div>
                </div>
            </div>`;
        });
        body.innerHTML = html;
    }

    // ════════════════════════════════════════════
    //  FAVORIT (localStorage)
    // ════════════════════════════════════════════
    function getFavorites() {
        try { return JSON.parse(localStorage.getItem('ekantin_fav') || '[]'); }
        catch { return []; }
    }

    function toggleFavorite(id) {
        let favs = getFavorites();
        const idx = favs.indexOf(id);
        if (idx > -1) {
            favs.splice(idx, 1);
            showToast('Dihapus dari favorit', '');
        } else {
            favs.push(id);
            showToast('❤️ Ditambahkan ke favorit!', 'success');
        }
        localStorage.setItem('ekantin_fav', JSON.stringify(favs));
        renderFavorites();
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

        const favMenus = ALL_MENUS.filter(m => favs.includes(m.id_menu));
        if (favMenus.length === 0) {
            grid.innerHTML = '';
            empty.style.display = 'block';
            return;
        }

        grid.innerHTML = favMenus.map(m => {
            let img = '../../assets/img/ayam.png';
            if (m.foto_menu) img = '../../assets/img/menu/' + m.foto_menu;
            return `
            <div class="menu-card-full">
                <img src="${img}" alt="${m.nama_menu}">
                <div class="mc-info">
                    <h4>${m.nama_menu}</h4>
                    <p class="mc-toko">${m.nama_toko}</p>
                    <p class="mc-price">Rp. ${Number(m.harga).toLocaleString('id-ID')}</p>
                    <div style="display:flex;gap:8px">
                        <button class="btn-tambah-keranjang" style="flex:1" onclick="addToCart(${m.id_menu},'${m.nama_menu.replace(/'/g,"\\'")}',${m.harga},'${(m.foto_menu||'').replace(/'/g,"\\'")}','${m.nama_toko.replace(/'/g,"\\'")}',${m.id_toko})">
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
                    let img = '../../assets/img/ayam.png';
                    if (m.foto_menu) img = '../../assets/img/menu/' + m.foto_menu;
                    return `
                    <div class="menu-card-full">
                        <img src="${img}" alt="${m.nama_menu}">
                        <div class="mc-info">
                            <h4>${m.nama_menu}</h4>
                            <p class="mc-toko">${m.nama_toko}</p>
                            <p class="mc-price">Rp. ${Number(m.harga).toLocaleString('id-ID')}</p>
                            <button class="btn-tambah-keranjang" onclick="addToCart(${m.id_menu},'${m.nama_menu.replace(/'/g,"\\'")}',${m.harga},'${(m.foto_menu||'').replace(/'/g,"\\'")}','${m.nama_toko.replace(/'/g,"\\'")}',${m.id_toko})">
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
    //  CHAT MODAL
    // ════════════════════════════════════════════
    function bukaModalChat() {
        closeAllDropdowns();
        document.getElementById('overlayModalChat').classList.add('active');
        if (typeof muatDaftarKontak === 'function') muatDaftarKontak('');
    }
    function tutupModalChat() {
        document.getElementById('overlayModalChat').classList.remove('active');
        if (typeof intervalPollingChat !== 'undefined' && intervalPollingChat) {
            clearInterval(intervalPollingChat);
            intervalPollingChat = null;
        }
        if (typeof ID_LAWAN_AKTIF !== 'undefined') ID_LAWAN_AKTIF = '';
    }
    function tutupModalChatKontainer(e) {
        if (e.target.id === 'overlayModalChat') tutupModalChat();
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