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
    <style>
        /* ══════════════════════════════════════════════
           TOKO DETAIL PAGE - INLINE STYLES
        ══════════════════════════════════════════════ */

        /* ── Back Navigation ── */
        .toko-back-nav {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }
        .btn-back {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            color: #1e293b;
            font-size: 18px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .btn-back:hover {
            background: #f0fdf4;
            border-color: #5cb85c;
            color: #5cb85c;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(92, 184, 92, 0.15);
        }
        .back-label {
            font-size: 15px;
            font-weight: 700;
            color: #475569;
        }

        /* ── Store Hero Header ── */
        .toko-hero {
            background: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            border: 1px solid #f1f5f9;
            box-shadow: 0 8px 32px rgba(0,0,0,0.04);
            margin-bottom: 32px;
        }
        .toko-hero-banner {
            position: relative;
            width: 100%;
            height: 220px;
            overflow: hidden;
        }
        .toko-hero-banner img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .toko-hero-banner::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 80px;
            background: linear-gradient(transparent, rgba(255,255,255,0.9));
        }
        .toko-hero-info {
            padding: 20px 24px 24px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-top: -50px;
            position: relative;
            z-index: 2;
        }
        .toko-avatar {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            object-fit: cover;
            border: 3px solid #ffffff;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            flex-shrink: 0;
            background: #ffffff;
        }
        .toko-details {
            flex: 1;
            padding-top: 26px;
        }
        .toko-details h1 {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 6px;
            line-height: 1.2;
        }
        .toko-details .toko-desc {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
            margin-bottom: 10px;
            line-height: 1.5;
        }
        .toko-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }
        .toko-status-badge.buka {
            background: #dcfce7;
            color: #16a34a;
        }
        .toko-status-badge.tutup {
            background: #f1f5f9;
            color: #94a3b8;
        }
        .toko-status-badge::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
        }
        .toko-meta {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-top: 8px;
            flex-wrap: wrap;
        }
        .toko-meta-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
        }
        .toko-meta-item i {
            color: #5cb85c;
            font-size: 14px;
        }

        /* ── Category Filter Tabs ── */
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
            overflow-x: auto;
            padding: 4px 0;
            scrollbar-width: none;
        }
        .filter-tabs::-webkit-scrollbar {
            display: none;
        }
        .filter-tab {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 22px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            border: 1.5px solid #e2e8f0;
            background: #ffffff;
            color: #475569;
            white-space: nowrap;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
        }
        .filter-tab:hover {
            border-color: #5cb85c;
            color: #5cb85c;
            background: #f0fdf4;
        }
        .filter-tab.active {
            background: linear-gradient(135deg, #5cb85c, #4cae4c);
            color: #ffffff;
            border-color: transparent;
            box-shadow: 0 4px 12px rgba(92, 184, 92, 0.3);
        }
        .filter-tab i {
            font-size: 13px;
        }

        /* ── Menu Grid ── */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .menu-grid-item {
            background: #ffffff;
            border: 1px solid #f1f5f9;
            border-radius: 20px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 16px rgba(0,0,0,0.02);
            position: relative;
        }
        .menu-grid-item:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 40px rgba(22, 62, 43, 0.08);
            border-color: #bce4bc;
        }
        .menu-grid-item.hidden {
            display: none;
        }
        .menu-item-img-wrap {
            position: relative;
            width: 100%;
            aspect-ratio: 1.15 / 1;
            overflow: hidden;
        }
        .menu-item-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }
        .menu-grid-item:hover .menu-item-img-wrap img {
            transform: scale(1.08);
        }
        .menu-item-category {
            position: absolute;
            top: 10px;
            left: 10px;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        .menu-item-category.makanan { background: rgba(239, 68, 68, 0.85); color: #fff; }
        .menu-item-category.minuman { background: rgba(59, 130, 246, 0.85); color: #fff; }
        .menu-item-category.snack { background: rgba(245, 158, 11, 0.85); color: #fff; }
        .menu-item-category.lainnya { background: rgba(107, 114, 128, 0.85); color: #fff; }

        .menu-item-stock {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 4px 8px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 700;
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(4px);
            color: #475569;
        }
        .menu-item-stock.low { color: #ef4444; }
        .menu-item-stock.out { background: rgba(239,68,68,0.9); color: #fff; }

        .menu-item-body {
            padding: 14px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .menu-item-name {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.3;
            margin-bottom: 4px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .menu-item-desc {
            font-size: 11px;
            color: #94a3b8;
            font-weight: 500;
            line-height: 1.4;
            margin-bottom: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .menu-item-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }
        .menu-item-price {
            font-size: 15px;
            font-weight: 800;
            color: #5cb85c;
        }
        .btn-tambah {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            padding: 8px 14px;
            border-radius: 12px;
            background: linear-gradient(135deg, #5cb85c, #4cae4c);
            color: #ffffff;
            border: none;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 3px 10px rgba(92, 184, 92, 0.25);
            white-space: nowrap;
        }
        .btn-tambah:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(92, 184, 92, 0.4);
        }
        .btn-tambah:active {
            transform: scale(0.95);
        }
        .btn-tambah:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            box-shadow: none;
        }
        .btn-tambah:disabled:hover {
            transform: none;
        }

        /* ── Empty State ── */
        .empty-menu-state {
            text-align: center;
            padding: 60px 20px;
            grid-column: 1 / -1;
        }
        .empty-menu-state i {
            font-size: 48px;
            color: #cbd5e0;
            margin-bottom: 16px;
        }
        .empty-menu-state h3 {
            font-size: 18px;
            font-weight: 700;
            color: #475569;
            margin-bottom: 8px;
        }
        .empty-menu-state p {
            font-size: 14px;
            color: #94a3b8;
        }

        /* ── Toast Notification ── */
        .toast-notification {
            position: fixed;
            bottom: 100px;
            left: 50%;
            transform: translateX(-50%) translateY(20px);
            background: linear-gradient(135deg, #1e293b, #0f172a);
            color: #ffffff;
            padding: 14px 24px;
            border-radius: 16px;
            font-size: 13px;
            font-weight: 700;
            box-shadow: 0 10px 40px rgba(0,0,0,0.25);
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 10px;
            white-space: nowrap;
        }
        .toast-notification.show {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) translateY(0);
        }
        .toast-notification i {
            color: #4ade80;
            font-size: 16px;
        }

        /* ── Floating Cart Button ── */
        .fab-cart {
            position: fixed;
            bottom: 28px;
            right: 28px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #5cb85c, #4cae4c);
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            cursor: pointer;
            box-shadow: 0 6px 24px rgba(92, 184, 92, 0.4);
            z-index: 999;
            border: none;
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .fab-cart:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 30px rgba(92, 184, 92, 0.5);
        }
        .fab-cart-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            font-size: 10px;
            background: #ef4444;
            color: #ffffff;
            padding: 2px 7px;
            border-radius: 999px;
            font-weight: 800;
            border: 2px solid #ffffff;
            min-width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ── Error Page ── */
        .error-container {
            text-align: center;
            padding: 80px 20px;
        }
        .error-container .error-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: #fef2f2;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
        }
        .error-container .error-icon i {
            font-size: 40px;
            color: #ef4444;
        }
        .error-container h2 {
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 12px;
        }
        .error-container p {
            font-size: 15px;
            color: #64748b;
            margin-bottom: 24px;
        }
        .btn-kembali {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 28px;
            border-radius: 14px;
            background: linear-gradient(135deg, #5cb85c, #4cae4c);
            color: #ffffff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            box-shadow: 0 4px 16px rgba(92, 184, 92, 0.3);
            transition: all 0.25s ease;
        }
        .btn-kembali:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(92, 184, 92, 0.4);
        }

        /* ── Responsive ── */
        @media (min-width: 768px) {
            .menu-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 20px;
            }
            .toko-hero-banner {
                height: 280px;
            }
            .toko-avatar {
                width: 96px;
                height: 96px;
            }
            .toko-details h1 {
                font-size: 26px;
            }
            .toko-hero-info {
                padding: 24px 32px 28px;
            }
        }

        @media (min-width: 1024px) {
            .menu-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 24px;
            }
            .toko-hero-banner {
                height: 320px;
            }
        }

        @media (max-width: 400px) {
            .menu-item-footer {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            .btn-tambah {
                width: 100%;
                justify-content: center;
            }
            .toko-avatar {
                width: 64px;
                height: 64px;
            }
            .toko-details h1 {
                font-size: 18px;
            }
        }
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
