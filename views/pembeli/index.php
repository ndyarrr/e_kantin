<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

// Jika user belum login, redirect ke login (opsional — aktifkan kalau pembeli perlu login)
// if (empty($_SESSION['user_id'])) { header('Location: ../../auth/login.php'); exit; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Kantin - Beranda</title>
    <link rel="stylesheet" href="../../assets/css/pembeli.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ── Floating Chat Button ── */
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
        /* ── Modal Overlay Chat ── */
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

    <header class="main-header">
        <div class="header-inner">
            <div class="top-bar">
                <div class="logo-area">
                    <div class="blank-logo"></div>
                    <span class="brand-name">E-Kantin</span>
                </div>
                <div class="search-container">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" placeholder="Search">
                </div>
                <div class="header-icons">
                    <div class="icon-badge">
                        <i class="fa-regular fa-bell"></i>
                        <span class="badge red">12</span>
                    </div>
                    <div class="icon-badge">
                        <i class="fa-solid fa-cart-shopping"></i>
                        <span class="badge red">5</span>
                    </div>
                    <div class="blank-avatar"></div>
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

    <main class="content-container">
        <div class="content-inner">
            
            <section class="section-block">
                <h2 class="section-title">Promo Hari ini</h2>
                <div class="promo-banner-blank">
                    <div class="promo-text-placeholder">
                        <h3>DISKON 25%</h3>
                        <p>KODE PROMO: <strong>KANTINJOSS25</strong></p>
                    </div>
                    <button class="btn-promo-blank">Pesan Sekarang</button>
                </div>
            </section>

            <section class="section-block">
                <h2 class="section-title">Menu Terlaris</h2>
                <div class="horizontal-scroll">
                        <?php
                        $query_menu = mysqli_query($koneksi, "SELECT menu.*, toko.nama_toko FROM menu 
                                                            JOIN toko ON menu.id_toko = toko.id_toko 
                                                            WHERE menu.tersedia = 1 AND menu.stok > 0 
                                                            LIMIT 5");

                        if ($query_menu && mysqli_num_rows($query_menu) > 0) {
                            while ($menu = mysqli_fetch_assoc($query_menu)) {
                                ?>
                                <div class="menu-card">
                                    
                                    <?php if (!empty($menu['foto_menu'])): ?>
                                        <img src="../../assets/img/<?= $menu['foto_menu']; ?>" class="menu-image-rect" alt="Foto Menu">
                                    <?php else: ?>
                                        <img src="../../assets/img/ayam.png" class="menu-image-rect" alt="Default Menu">
                                    <?php endif; ?>
                                    <div class="menu-info">
                                        <h4><?= htmlspecialchars($menu['nama_menu']); ?></h4>
                                        <p class="seller-name"><?= htmlspecialchars($menu['nama_toko']); ?></p> 
                                        <span class="price-tag">Rp. <?= number_format($menu['harga'], 0, ',', '.'); ?></span>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo "<p style='color:#888; font-size:13px; padding:10px 0;'>Belum ada menu tersedia saat ini.</p>";
                        }
                        ?>
                    </div>
                <div class="slider-dots">
                    <span class="dot active"></span>
                    <span class="dot"></span>
                    <span class="dot"></span>
                </div>
            </section>

            <section class="section-block">
                <h2 class="section-title">Kategori</h2>
                <div class="category-list">
                    <div class="category-item">
                        <div class="blank-circle icon-grid-center">
                            <i class="fa-solid fa-table-cells-large"></i>
                        </div>
                        <span>Semua</span>
                    </div>
                    <div class="category-item">
                        <div class="blank-circle"></div>
                        <span>Makanan</span>
                    </div>
                    <div class="category-item">
                        <div class="blank-circle"></div>
                        <span>Snack</span>
                    </div>
                    <div class="category-item">
                        <div class="blank-circle"></div>
                        <span>Minuman</span>
                    </div>
                </div>
            </section>

            <section class="section-block">
                <h2 class="section-title">Kantin</h2>
                <div class="kantin-grid">
                    <?php
                    // Ambil data toko asli dari tabel database e_kantin Anda
                    $query_toko = mysqli_query($koneksi, "SELECT * FROM toko ORDER BY status ASC, nama_toko ASC");

                    if ($query_toko && mysqli_num_rows($query_toko) > 0) {
                        while ($toko = mysqli_fetch_assoc($query_toko)) {
                            // Cek status toko untuk kelas CSS
                            $status_kelas = ($toko['status'] == 'buka') ? 'online' : 'offline';
                            $status_teks  = ($toko['status'] == 'buka') ? 'Buka' : 'Tutup';
                            
                            // Kunci tombol jika status toko tutup
                            $btn_disabled = ($toko['status'] == 'tutup') ? 'style="background-color:#95a5a6; pointer-events:none;"' : '';
                            ?>
                            
                            <div class="kantin-card">
                                <div class="blank-image-square border-green"></div>
                                <div class="kantin-info">
                                    <h3><?= htmlspecialchars($toko['nama_toko']); ?></h3>
                                    <p><?= htmlspecialchars($toko['deskripsi'] ?? 'Makanan & Minuman'); ?></p>
                                    <span class="status-indicator <?= $status_kelas; ?>"><?= $status_teks; ?></span>
                                    
                                    <a href="toko.php?id=<?= $toko['id_toko']; ?>" class="btn-lihat-menu" <?= $btn_disabled; ?>>
                                        <?= ($toko['status'] == 'buka') ? 'Lihat Menu' : 'Sedang Tutup'; ?>
                                    </a>
                                </div>
                            </div>

                            <?php 
                        }
                    } else {
                        echo "<p style='color:#888; font-size:13px; padding:10px 0;'>Belum ada data kantin yang terdaftar.</p>";
                    }
                    ?>

                    <div class="category-item-all-box">
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