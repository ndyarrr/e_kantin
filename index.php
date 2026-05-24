<?php require_once 'config/database.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./assets/css/styles.css?v=4">
    <title>E-Kantin</title>
    
    <style>
        /* 1. Kunci body agar tidak bisa di-scroll ke samping (kanan/kiri) */
        html, body {
            overflow-x: hidden !important;
            width: 100% !important;
            margin: 0;
            padding: 0;
        }

        /* 2. Pastikan semua elemen menghitung padding sebagai bagian dari lebar utama */
        * {
            box-sizing: border-box;
        }

        @media (max-width: 768px) {
            /* ... (kode lain seperti .btn-ps dan .collage-grid biarkan saja) ... */

            /* 1. Beri ruang atas dan bawah pada bungkus galeri agar gambar melayang tidak kepotong */
            .collage-wrapper {
                height: auto !important;
                min-height: auto !important;
                padding: 40px 15px 40px 15px !important; /* Ruang atas bawah diperbesar */
                position: relative !important;
                overflow: visible !important; /* PENTING: Ubah jadi visible agar bayangan tidak terpotong kotak */
            }

            /* 2. Gambar Ayam Geprek (Kiri Atas) */
            .col-food-left {
                display: block !important;
                width: 140px !important; /* Ukuran pas */
                left: 5px !important; /* Masukkan ke dalam layar, jangan minus */
                top: -15px !important; /* Numpang estetik di atas pojok kiri galeri */
                z-index: 20 !important;
                filter: drop-shadow(8px 15px 15px rgba(0,0,0,0.4)) !important; /* Bayangan lebih nyata dan tebal */
            }

            /* 3. Gambar Soto (Kanan Bawah) */
            .col-food-right {
                display: block !important;
                width: 130px !important;
                right: 5px !important; /* Masukkan ke dalam layar */
                bottom: 25px !important; /* Numpang estetik di atas pojok kanan galeri */
                z-index: 20 !important;
                filter: drop-shadow(-8px 15px 15px rgba(0,0,0,0.4)) !important;
            }
        }
        /* =========================================
   UI/UX ENHANCEMENTS: FOTO MAKANAN MENGAMBANG
   ========================================= */

/* 1. Animasi Mengambang (Floating Effect) */
@keyframes floatFood {
    0% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-12px) rotate(3deg); }
    100% { transform: translateY(0px) rotate(0deg); }
}

/* 2. Styling Dasar & Efek 3D */
.col-food {
    position: absolute !important;
    z-index: 10 !important;
    /* Drop shadow lembut untuk kedalaman visual */
    filter: drop-shadow(0px 15px 25px rgba(0, 0, 0, 0.35)) !important; 
    animation: floatFood 4s ease-in-out infinite !important;
    pointer-events: none !important; /* Agar gambar tidak menghalangi klik pada tombol/layout di bawahnya */
}

/* 3. Posisi Estetik di Desktop */
.col-food-left {
    width: 250px !important;
    left: -40px !important;
    top: 5% !important;
}

.col-food-right {
    width: 220px !important;
    right: -30px !important;
    bottom: 5% !important;
    animation-delay: 1.5s !important; /* Animasi dibuat tidak sinkron agar lebih natural */
}

/* 4. Posisi Estetik di HP (Mobile Responsive) */
@media (max-width: 768px) {
    /* Pastikan .col-food di-set ke block, jika sebelumnya kamu pakai display: none */
    .col-food {
        display: block !important;
    }
    
    .col-food-left {
        width: 120px !important;
        left: -20px !important;
        top: -40px !important; /* Digeser ke pojok kiri atas ruang kosong */
    }
    
    .col-food-right {
        width: 110px !important;
        right: -15px !important;
        bottom: -20px !important; /* Digeser ke pojok kanan bawah */
    }
}
    </style>
</head>

<body>
    <?php include 'views/layouts/navbar.php'; ?>
    <section id="home">
        <?php include 'views/layouts/hero.php'; ?>
        <div class="btn-ps">
            <p>Masuk untuk memulai</p>
            <div class="btn-wrapper">
                <button onclick="window.location.href='./auth/login.php'" class="btn-login">Login</button>
            </div>
        </div>
        <div class="collage-wrapper">
            <!-- Foto makanan mengambang -->
            <img class="col-food col-food-left" src="./assets/img/ayam.png" alt="" />
            <img class="col-food col-food-right" src="./assets/img/soto.png" alt="" />
            <div class="collage-grid">
                <img class="col-img col-1" src="./assets/img/gb1.jpeg" alt="" />
                <img class="col-img col-2" src="./assets/img/gb2.jpeg" alt="" />
                <img class="col-img col-3" src="./assets/img/gb3.jpeg" alt="" />
                <img class="col-img col-4" src="./assets/img/gb4.png" alt="" />
                <img class="col-img col-5" src="./assets/img/gb5.png" alt="" />
            </div>
        </div>

        <div class="deskripsi">
            <h2>Apa itu E-Kantin?</h2>
            <p>E-Kantin adalah platform kantin digital sekolah yang dirancang untuk mempermudah proses pemesanan makanan
                dan minuman secara online dengan sistem yang cepat, aman, dan efisien. Melalui E-Kantin, siswa dan guru
                dapat melihat menu, melakukan pemesanan tanpa antre, serta memantau transaksi secara real-time. Selain
                memudahkan pembeli, platform ini juga membantu penjual dan pihak sekolah dalam mengelola data transaksi,
                menu, dan operasional kantin dengan lebih modern dan terorganisir.</p>
        </div>
    </section>

    <section id="kantin">
        <?php include 'views/layouts/kantin.php'; ?>
    </section>

    <section id="leaderboard">
        <?php include 'views/layouts/leaderboard.php'; ?>
    </section>

    <section id="about">
        <?php include 'views/layouts/about.php'; ?>
    </section>

    <?php include 'views/layouts/footer.php'; ?>


    <script>
        const pills = document.querySelectorAll('.nav-pill');
        const sections = document.querySelectorAll('section[id]');

        pills.forEach(pill => {
            pill.addEventListener('click', () => {
                const target = document.getElementById(pill.dataset.target);
                if (target) target.scrollIntoView({ behavior: 'smooth' });
            });
        });

        function updateActive() {
            let current = sections[0].id;
            sections.forEach(sec => {
                if (sec.getBoundingClientRect().top <= 100) current = sec.id;
            });
            pills.forEach(pill => {
                pill.classList.remove('active');
                if (pill.dataset.target === current) pill.classList.add('active');
            });
        }

        document.querySelector('.hero-search input').addEventListener('input', function () {
            const q = this.value.toLowerCase();
            document.querySelectorAll('.kantin-card').forEach(card => {
                const nama = card.querySelector('.kantin-card-badge').textContent.toLowerCase();
                const desc = card.querySelector('.kantin-card-desc').textContent.toLowerCase();
                card.style.display = (nama.includes(q) || desc.includes(q)) ? '' : 'none';
            });
        });

        window.addEventListener('scroll', updateActive);
        updateActive();
    </script>
    <div id="modalKantin" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <button class="modal-close" onclick="tutupModal()">×</button>
        
        <h2 id="modalNama" class="modal-title">Nama Kantin</h2>
        <p id="modalDesc" class="modal-desc">Deskripsi kantin</p>
        
        <div class="info-tunai" style="margin-bottom: 16px; font-size: 12px; background: #fff3cd; color: #856404; padding: 8px 12px; border-radius: 6px; border: 1px solid #ffeeba;">
            ⚠️ <b>Perhatian:</b> Saat ini pembayaran hanya dapat dilakukan secara tunai di kasir.
        </div>

        <h3 style="font-size: 15px; margin-bottom: 10px; font-family: 'Poppins', sans-serif;">Daftar Menu</h3>
        <div id="modalIsiMenu" class="modal-menu-list">
            </div>
    </div>
</div>
<script>
    // Fungsi untuk memanggil data dan membuka Pop-up
async function bukaModal(id_toko) {
    const modal = document.getElementById('modalKantin');
    const containerMenu = document.getElementById('modalIsiMenu');
    
    // Tampilkan modal dengan status loading
    modal.style.display = 'flex';
    document.getElementById('modalNama').textContent = "Memuat data...";
    document.getElementById('modalDesc').textContent = "Tunggu sebentar...";
    containerMenu.innerHTML = '<p style="text-align:center; color:#888;">Sedang mengambil menu...</p>';

    try {
        // Minta data ke PHP
        const merespon = await fetch(`get_detail.php?id=${id_toko}`);
        const data = await merespon.json();

        // Ganti teks judul dan deskripsi
        document.getElementById('modalNama').textContent = data.toko.nama_toko;
        document.getElementById('modalDesc').textContent = data.toko.deskripsi || '-';

        // Susun HTML untuk menu
        let htmlMenu = '';
        if (data.menus && data.menus.length > 0) {
            data.menus.forEach(m => {
                htmlMenu += `
                <div class="menu-item-modal">
                    <div>
                        <div class="menu-item-nama">${m.nama_menu}</div>
                    </div>
                    <div class="menu-item-harga">Rp ${parseInt(m.harga).toLocaleString('id-ID')}</div>
                </div>`;
            });
        } else {
            htmlMenu = '<p style="font-size:13px; color:#888;">Belum ada menu di kantin ini.</p>';
        }
        
        // Tampilkan menunya
        containerMenu.innerHTML = htmlMenu;

    } catch (error) {
        console.error(error);
        containerMenu.innerHTML = '<p style="color:red; font-size:13px;">Gagal memuat data menu.</p>';
    }
}

// Fungsi menutup pop-up
function tutupModal() {
    document.getElementById('modalKantin').style.display = 'none';
}

// Menutup pop-up kalau user ngeklik area luar kotak putih
window.onclick = function(event) {
    const modal = document.getElementById('modalKantin');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
</script>
</body>
</html>