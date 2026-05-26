<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
// Mapping koneksi database agar tidak terjadi error akibat perbedaan nama variabel
$koneksi = $conn;

// Jika user belum login, redirect ke login (opsional)
// if (empty($_SESSION['user_id'])) { header('Location: ../../auth/login.php'); exit; }

// Ambil foto profil pembeli dari session, jika tidak ada gunakan fallback default
$avatar_file = $_SESSION['user_foto'] ?? '';
$avatar_path = '../../assets/img/' . $avatar_file;
if (empty($avatar_file) || !file_exists(__DIR__ . '/../../assets/img/' . $avatar_file)) {
    $avatar_path = '../../assets/img/PPAril.jpeg'; // fallback avatar default
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
        /* ── Floating Chat Button & Modal Integration ── */
        .fab-chat {
            position: fixed;
            bottom: 28px;
            right: 28px;
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(34, 197, 94, 0.4);
            z-index: 999;
            border: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .fab-chat:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 24px rgba(34, 197, 94, 0.5);
        }
        .chat-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .chat-modal-overlay.active {
            display: flex;
        }
        .chat-modal-box {
            background: #fff;
            border-radius: 16px;
            width: 90%;
            max-width: 860px;
            height: 80vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.25);
            position: relative;
        }
        .chat-modal-topbar {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: #fff;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-weight: 700;
            font-size: 15px;
        }
        .chat-modal-topbar button {
            background: rgba(255,255,255,0.2);
            border: none;
            color: #fff;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .chat-modal-content {
            flex: 1;
            overflow: hidden;
            display: flex;
        }
        .chat-modal-content .chat-wrapper {
            flex: 1;
            border-radius: 0;
            border: none;
            margin-top: 0;
            height: 100% !important;
            min-height: unset !important;
        }
    </style>
</head>
<body>

    <!-- ── TOP HEADER (GLASSMORPHISM PRESET) ── -->
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
                    <input type="text" placeholder="Search">
                </div>
                <div class="header-icons">
                    <div class="icon-badge">
                        <i class="fa-regular fa-bell"></i>
                        <span class="badge">12</span>
                    </div>
                    <div class="icon-badge">
                        <i class="fa-solid fa-cart-shopping"></i>
                        <span class="badge">5</span>
                    </div>
                    <img src="<?= $avatar_path; ?>" class="blank-avatar" alt="Profil Pembeli">
                </div>
            </div>
            
            <nav class="nav-menu">
                <a href="#" class="nav-item active">Beranda</a>
                <a href="#" class="nav-item">Pesanan</a>
                <a href="#" class="nav-item">Favorit</a>
                <a href="#" class="nav-item">Kantin</a>
            </nav>
        </div>
    </header>

    <!-- ── CONTENT UTAMA ── -->
    <main class="content-container">
        <div class="content-inner">
            
            <!-- ── PROMO BANNER ── -->
            <section class="section-block">
                <h2 class="section-title">Promo Hari ini</h2>
                <div class="promo-banner-blank">
                    <div class="promo-text-placeholder">
                        <h3>DISKON 25%</h3>
                        <p>UNTUK MENU SPESIAL HARI INI!</p>
                    </div>
                    <div class="promo-action-area">
                        <span class="promo-code-right">KODE PROMO: <strong>KANTINJOSS25</strong></span>
                        <button class="btn-promo-blank">Pesan Sekarang</button>
                    </div>
                </div>
            </section>

            <!-- ── MENU TERLARIS ── -->
            <section class="section-block">
                <h2 class="section-title">Menu Terlaris</h2>
                <div class="horizontal-scroll">
                    <?php
                    // Query data menu terlaris gabung dengan toko
                    $query_menu = mysqli_query($koneksi, "SELECT menu.*, toko.nama_toko FROM menu 
                                                         JOIN toko ON menu.id_toko = toko.id_toko 
                                                         WHERE menu.tersedia = 1 AND menu.stok > 0 
                                                         LIMIT 5");

                    if ($query_menu && mysqli_num_rows($query_menu) > 0) {
                        while ($menu = mysqli_fetch_assoc($query_menu)) {
                            // Menangani visualisasi gambar menu
                            $foto_menu = $menu['foto_menu'];
                            $menu_img_src = '../../assets/img/ayam.png'; // default fallback

                            if (!empty($foto_menu)) {
                                if (file_exists(__DIR__ . '/../../assets/img/menu/' . $foto_menu)) {
                                    $menu_img_src = '../../assets/img/menu/' . $foto_menu;
                                } elseif (file_exists(__DIR__ . '/../../assets/img/' . $foto_menu)) {
                                    $menu_img_src = '../../assets/img/' . $foto_menu;
                                }
                            }
                            ?>
                            <div class="menu-card">
                                <img src="<?= $menu_img_src; ?>" class="menu-image-rect" alt="<?= htmlspecialchars($menu['nama_menu']); ?>">
                                <div class="menu-info">
                                    <h4><?= htmlspecialchars($menu['nama_menu']); ?></h4>
                                    <p class="seller-name"><?= htmlspecialchars($menu['nama_toko']); ?></p> 
                                    <span class="price-tag">Rp. <?= number_format($menu['harga'], 0, ',', '.'); ?></span>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        // Dummy visual data jika database kosong agar tampilan mengikuti desain di atas
                        $dummy_menus = [
                            ['nama_menu' => 'Nasi Ayam Geprek', 'nama_toko' => 'MAR DIKA', 'harga' => 8000],
                            ['nama_menu' => 'Nasi Ayam Geprek', 'nama_toko' => 'MAR DIKA', 'harga' => 8000],
                            ['nama_menu' => 'Nasi Ayam Geprek', 'nama_toko' => 'MAR DIKA', 'harga' => 8000]
                        ];
                        foreach ($dummy_menus as $menu) {
                            ?>
                            <div class="menu-card">
                                <img src="../../assets/img/ayam.png" class="menu-image-rect" alt="Nasi Ayam Geprek">
                                <div class="menu-info">
                                    <h4><?= htmlspecialchars($menu['nama_menu']); ?></h4>
                                    <p class="seller-name"><?= htmlspecialchars($menu['nama_toko']); ?></p> 
                                    <span class="price-tag">Rp. <?= number_format($menu['harga'], 0, ',', '.'); ?></span>
                                </div>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>
                
                <!-- Titik slider dengan kapsul aktif memanjang -->
                <div class="slider-dots">
                    <span class="dot active"></span>
                    <span class="dot"></span>
                    <span class="dot"></span>
                    <span class="dot"></span>
                </div>
            </section>

            <!-- ── KATEGORI CIRCULAR ── -->
            <section class="section-block">
                <h2 class="section-title">Kategori</h2>
                <div class="category-list">
                    <a href="#" class="category-item">
                        <div class="blank-circle icon-grid-center">
                            <i class="fa-solid fa-table-cells-large"></i>
                        </div>
                        <span>Semua</span>
                    </a>
                    <a href="#" class="category-item">
                        <div class="blank-circle">
                            <img src="../../assets/img/ayam.png" alt="Makanan">
                        </div>
                        <span>Makanan</span>
                    </a>
                    <a href="#" class="category-item">
                        <div class="blank-circle">
                            <img src="../../assets/img/soto.png" alt="Snack">
                        </div>
                        <span>Snack</span>
                    </a>
                    <a href="#" class="category-item">
                        <div class="blank-circle">
                            <!-- Minuman fallback estetik -->
                            <img src="../../assets/img/ayam.png" style="filter: hue-rotate(130deg) saturate(1.5);" alt="Minuman">
                        </div>
                        <span>Minuman</span>
                    </a>
                </div>
            </section>

            <!-- ── KANTIN GRID ── -->
            <section class="section-block">
                <h2 class="section-title">Kantin</h2>
                <div class="kantin-grid">
                    <?php
                    // Ambil data toko terdaftar dari database
                    $query_toko = mysqli_query($koneksi, "SELECT * FROM toko ORDER BY FIELD(status, 'buka', 'tutup'), nama_toko ASC");

                    if ($query_toko && mysqli_num_rows($query_toko) > 0) {
                        while ($toko = mysqli_fetch_assoc($query_toko)) {
                            // Cek status buka/tutup toko secara aman
                            $is_buka = (strtolower($toko['status'] ?? '') === 'buka');
                            $status_kelas = $is_buka ? 'online' : 'offline';
                            $status_teks  = $is_buka ? 'Buka' : 'Tutup';
                            
                            // Nonaktifkan tombol jika tutup
                            $btn_disabled = !$is_buka ? 'style="background-color:#94a3b8; pointer-events:none; box-shadow:none;"' : '';
                            
                            // Menangani visualisasi gambar toko
                            $foto_toko = $toko['foto_toko'] ?? '';
                            $toko_img_src = '';

                            if (!empty($foto_toko)) {
                                if (file_exists(__DIR__ . '/../../assets/img/kantin/' . $foto_toko)) {
                                    $toko_img_src = '../../assets/img/kantin/' . $foto_toko;
                                } elseif (file_exists(__DIR__ . '/../../assets/img/' . $foto_toko)) {
                                    $toko_img_src = '../../assets/img/' . $foto_toko;
                                }
                            }
                            
                            // Jika path kosong atau gambar tidak ditemukan, cari fallback ilustrasi asli
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
                                    $toko_img_src = '../../assets/img/ayam.png'; // default fallback
                                }
                            }
                            ?>
                            
                            <div class="kantin-card">
                                <img src="<?= $toko_img_src; ?>" class="blank-image-square" alt="<?= htmlspecialchars($toko['nama_toko']); ?>">
                                <div class="kantin-info">
                                    <h3><?= htmlspecialchars($toko['nama_toko']); ?></h3>
                                    <p><?= htmlspecialchars($toko['deskripsi'] ?? 'Makanan, Snack, & Minuman'); ?></p>
                                    <span class="status-indicator <?= $status_kelas; ?>"><?= $status_teks; ?></span>
                                    
                                    <a href="toko.php?id=<?= $toko['id_toko']; ?>" class="btn-lihat-menu" <?= $btn_disabled; ?>>
                                        <?= $is_buka ? 'Lihat Menu' : 'Sedang Tutup'; ?>
                                    </a>
                                </div>
                            </div>

                            <?php 
                        }
                    } else {
                        // Dummy visual data kantin jika database kosong agar mengikuti mockup desain
                        $dummy_tokos = [
                            ['nama' => 'Kantin Bu Tika', 'deskripsi' => 'Makanan,Snack,& Minuman', 'status' => 'buka', 'img' => 'kantin_bu_tika.jpeg'],
                            ['nama' => 'Kantin Pak Fajar', 'deskripsi' => 'Makanan,Snack,& Minuman', 'status' => 'buka', 'img' => 'kantin_pak_fajar.jpeg'],
                            ['nama' => 'Kantin Pak Agus', 'deskripsi' => 'Makanan,Snack,& Minuman', 'status' => 'buka', 'img' => 'kantin_pak_agus.jpeg'],
                            ['nama' => 'Kantin Pak Mardika', 'deskripsi' => 'Makanan,Snack,& Minuman', 'status' => 'buka', 'img' => 'kantin_pak_mardika.jpeg'],
                            ['nama' => 'Kantin Pak Basuni', 'deskripsi' => 'Makanan,Snack,& Minuman', 'status' => 'tutup', 'img' => 'kantin_pak_basuni.jpeg']
                        ];
                        foreach ($dummy_tokos as $t) {
                            $is_buka = ($t['status'] === 'buka');
                            $status_kelas = $is_buka ? 'online' : 'offline';
                            $status_teks  = $is_buka ? 'Buka' : 'Tutup';
                            $btn_disabled = !$is_buka ? 'style="background-color:#94a3b8; pointer-events:none; box-shadow:none;"' : '';
                            ?>
                            <div class="kantin-card">
                                <img src="../../assets/img/<?= $t['img']; ?>" class="blank-image-square" alt="<?= $t['nama']; ?>">
                                <div class="kantin-info">
                                    <h3><?= $t['nama']; ?></h3>
                                    <p><?= $t['deskripsi']; ?></p>
                                    <span class="status-indicator <?= $status_kelas; ?>"><?= $status_teks; ?></span>
                                    
                                    <a href="#" class="btn-lihat-menu" <?= $btn_disabled; ?>>
                                        <?= $is_buka ? 'Lihat Menu' : 'Sedang Tutup'; ?>
                                    </a>
                                </div>
                            </div>
                            <?php
                        }
                    }
                    ?>

                    <!-- Kartu Khusus tombol 'SEMUA' di akhir baris grid kantin -->
                    <div class="category-item-all-box" onclick="location.href='#';">
                        <div class="blank-square-icon">
                            <i class="fa-solid fa-table-cells-large"></i>
                        </div>
                        <span class="all-text-label">SEMUA</span>
                    </div>
                </div>
            </section>

        </div>
    </main>

    <!-- ══════════════════════════════════
         FLOATING CHAT BUTTON + MODAL
    ══════════════════════════════════ -->
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

    <script>
    function bukaModalChat() {
        document.getElementById('overlayModalChat').classList.add('active');
        if (typeof muatDaftarKontak === 'function') {
            muatDaftarKontak('');
        }
    }
    function tutupModalChat() {
        document.getElementById('overlayModalChat').classList.remove('active');
        if (typeof intervalPollingChat !== 'undefined' && intervalPollingChat) {
            clearInterval(intervalPollingChat);
            intervalPollingChat = null;
        }
        ID_LAWAN_AKTIF = '';
    }
    function tutupModalChatKontainer(e) {
        if (e.target.id === 'overlayModalChat') {
            tutupModalChat();
        }
    }
    </script>
</body>
</html>