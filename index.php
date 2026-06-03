<?php require_once 'config/database.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./assets/css/styles.css?v=6">
    <title>E-Kantin</title>

    <style>
        /* 1. Kunci body agar tidak bisa di-scroll ke samping (kanan/kiri) */
        html,
        body {
            overflow-x: hidden !important;
            width: 100% !important;
            margin: 0;
            padding: 0;
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

    <div id="modalKantin" class="modal-overlay" style="display:none;">
        <div class="modal-content"
            style="padding:0; overflow:hidden; max-height:85vh; overflow-y:auto; position:relative; overflow-x:hidden; word-break:break-word;">

            <button class="modal-close" onclick="tutupModal()"
                style="position:absolute; top:12px; right:12px; z-index:10; width:32px; height:32px; border-radius:50%; background:rgba(0,0,0,0.35); border:none; color:#fff; font-size:20px; cursor:pointer; display:flex; align-items:center; justify-content:center;">×</button>

            <!-- Foto kantin full-width di atas -->
            <div id="modalFotoWrap"
                style="width:100%; background:#f3f4f6; display:flex; align-items:center; justify-content:center; min-height:160px; max-height:300px; overflow:hidden; position:relative;">
                <img id="modalFoto" src="" alt=""
                    style="width:100%; max-height:300px; object-fit:contain; background:#1a1a1a; display:none; border-radius:0;">
                <svg id="modalFotoPlaceholder" xmlns="http://www.w3.org/2000/svg" width="80" height="80"
                    viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M3 9l1-5h16l1 5" />
                    <path
                        d="M3 9a2 2 0 0 0 2 2 2 2 0 0 0 2-2 2 2 0 0 0 2 2 2 2 0 0 0 2-2 2 2 0 0 0 2 2 2 2 0 0 0 2-2" />
                    <path d="M5 11v8a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-8" />
                    <line x1="10" y1="15" x2="14" y2="15" />
                </svg>
            </div>

            <!-- Konten teks -->
            <div style="padding:20px 20px 8px;">
                <h2 id="modalNama" class="modal-title" style="margin:0 0 4px;">Nama Kantin</h2>
                <p id="modalDesc" class="modal-desc" style="font-size:13px; color:#6b7280; margin:0 0 14px;"></p>

                <!-- Deskripsi panjang -->
                <div id="modalDescPanjangWrap"
                    style="border-top:1px solid #f0f0f0; padding-top:14px; margin-bottom:14px;">
                    <p id="modalDescPanjang"
                        style="margin:0; font-size:13px; color:#6b7280; line-height:1.7; word-break:break-word; overflow-wrap:break-word; white-space:normal;">
                    </p>
                </div>

                <div id="modalKantinInfoPembayaran"
                    style="background:#fff3cd; border:1px solid #ffeeba; border-radius:8px; padding:10px 14px; margin-bottom:16px; font-size:12px; color:#856404;">
                    ⚠️ <b>Perhatian:</b> Saat ini pembayaran hanya dapat dilakukan secara tunai di kasir.
                </div>
            </div>

            <!-- Daftar menu -->
            <div style="padding:0 20px 20px;">
                <h3
                    style="font-size:13px; font-weight:600; color:#9ca3af; text-transform:uppercase; letter-spacing:0.05em; margin:0 0 12px;">
                    Daftar Menu</h3>
                <div id="modalIsiMenu" class="modal-menu-list"></div>
            </div>

        </div>
    </div>

    <!-- Modal Detail Menu -->
    <div id="modalMenu" class="modal-overlay" style="display:none;">
        <div class="modal-content"
            style="padding:0; overflow:hidden; max-height:85vh; overflow-y:auto; position:relative; overflow-x:hidden; word-break:break-word;">

            <button class="modal-close" onclick="tutupModalMenu()"
                style="position:absolute; top:12px; right:12px; z-index:10; width:32px; height:32px; border-radius:50%; background:rgba(0,0,0,0.35); border:none; color:#fff; font-size:20px; cursor:pointer; display:flex; align-items:center; justify-content:center;">×</button>

            <!-- Foto menu -->
            <div id="modalMenuFotoWrap"
                style="width:100%; background:#f3f4f6; display:flex; align-items:center; justify-content:center; height:260px; overflow:hidden; position:relative;">
                <img id="modalMenuFoto" src="" alt=""
                    style="width:100%; height:100%; object-fit:cover; display:none;">
                <div id="modalMenuFallback" style="width:100%; height:100%; display:none;"></div>
            </div>

            <!-- Detail info menu -->
            <div style="padding:20px;">
                <span id="modalMenuKantinBadge" style="background:#e8f5e9; color:#79b775; font-size:11px; font-weight:700; padding:4px 12px; border-radius:20px; display:inline-block; margin-bottom:10px; font-family:'Poppins', sans-serif;">
                    Nama Kantin
                </span>
                
                <h2 id="modalMenuNama" class="modal-title" style="margin:0 0 6px; font-family:'Poppins', sans-serif; font-weight:700; font-size:22px;">Nama Menu</h2>
                
                <div id="modalMenuHarga" style="font-size:20px; font-weight:700; color:#79b775; margin-bottom:16px; font-family:'Poppins', sans-serif;">
                    Rp 0
                </div>

                <div style="border-top:1px solid #f0f0f0; padding-top:14px; margin-bottom:20px;">
                    <h4 style="font-size:12px; font-weight:600; color:#9ca3af; text-transform:uppercase; letter-spacing:0.05em; margin:0 0 6px;">Deskripsi</h4>
                    <p id="modalMenuDesc" style="margin:0; font-size:13px; color:#6b7280; line-height:1.6; font-family:'Poppins', sans-serif;">
                        Deskripsi menu...
                    </p>
                </div>

                <!-- Action buttons -->
                <div style="display:flex; flex-direction:column; gap:10px; margin-top:20px;">
                    <button id="btnLihatKantin" onclick="" style="width:100%; background:#fff; color:#79b775; border:1.5px solid #79b775; font-size:14px; font-weight:600; padding:12px; border-radius:12px; cursor:pointer; font-family:'Poppins', sans-serif; transition:all 0.2s ease; display:flex; align-items:center; justify-content:center; gap:8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-store"><path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/><path d="M22 7v3a2 2 0 0 1-2 2v0a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 16 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 12 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 8 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 4 12v0a2 2 0 0 1-2-2V7"/></svg>
                        Lihat Kantin Penjual
                    </button>
                    <button onclick="window.location.href='./auth/login.php'" style="width:100%; background:#79b775; color:#fff; border:none; font-size:14px; font-weight:600; padding:12px; border-radius:12px; cursor:pointer; font-family:'Poppins', sans-serif; transition:all 0.2s ease; display:flex; align-items:center; justify-content:center; gap:8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-in"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" x2="3" y1="12" y2="12"/></svg>
                        Login untuk Memesan
                    </button>
                </div>
            </div>
        </div>
    </div>

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

        // Fungsi untuk memanggil data dan membuka Pop-up
        async function bukaModal(id_toko) {
            const modal = document.getElementById('modalKantin');
            const containerMenu = document.getElementById('modalIsiMenu');
            const foto = document.getElementById('modalFoto');
            const fotoPlaceholder = document.getElementById('modalFotoPlaceholder');

            modal.style.display = 'flex';
            document.getElementById('modalNama').textContent = "Memuat data...";
            document.getElementById('modalDesc').textContent = "";
            foto.style.display = 'none';
            fotoPlaceholder.style.display = 'block';
            containerMenu.innerHTML = '<p style="text-align:center; color:#888;">Sedang mengambil menu...</p>';

            try {
                const merespon = await fetch(`get_detail.php?id=${id_toko}`);
                const data = await merespon.json();

                document.getElementById('modalNama').textContent = data.toko.nama_toko;
                document.getElementById('modalDesc').textContent = data.toko.deskripsi || '';

                // Update info pembayaran
                const infoPembayaran = document.getElementById('modalKantinInfoPembayaran');
                if (infoPembayaran) {
                    if (data.toko.qris_image && data.toko.qris_image.trim() !== '') {
                        infoPembayaran.style.background = '#e8f5e9';
                        infoPembayaran.style.border = '1px solid #c8e6c9';
                        infoPembayaran.style.color = '#2e7d32';
                        infoPembayaran.innerHTML = '✅ <b>QRIS Tersedia:</b> Kantin ini mendukung pembayaran cashless via QRIS & Tunai.';
                    } else {
                        infoPembayaran.style.background = '#fff3cd';
                        infoPembayaran.style.border = '1px solid #ffeeba';
                        infoPembayaran.style.color = '#856404';
                        infoPembayaran.innerHTML = '⚠️ <b>Perhatian:</b> Saat ini pembayaran hanya dapat dilakukan secara tunai di kasir.';
                    }
                }


                // Deskripsi panjang
                const descPanjangWrap = document.getElementById('modalDescPanjangWrap');
                const descPanjang = document.getElementById('modalDescPanjang');
                if (data.toko.deskripsi_panjang) {
                    descPanjang.textContent = data.toko.deskripsi_panjang;
                    descPanjangWrap.style.display = 'block';
                } else {
                    descPanjangWrap.style.display = 'none';
                }

                // Foto kantin
                if (data.toko.foto_toko) {
                    foto.src = './assets/img/kantin/' + data.toko.foto_toko;
                    foto.style.display = 'block';
                    fotoPlaceholder.style.display = 'none';
                    foto.onerror = () => {
                        foto.style.display = 'none';
                        fotoPlaceholder.style.display = 'block';
                    };
                }

                let htmlMenu = '';
                if (data.menus && data.menus.length > 0) {
                    data.menus.forEach(m => {
                        htmlMenu += `
                <div class="menu-item-modal">
                    <div class="menu-item-nama">${m.nama_menu}</div>
                    <div class="menu-item-harga">Rp ${parseInt(m.harga).toLocaleString('id-ID')}</div>
                </div>`;
                    });
                } else {
                    htmlMenu = '<p style="font-size:13px; color:#888;">Belum ada menu di kantin ini.</p>';
                }
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

        // Fungsi membuka pop-up detail menu dari search
        function bukaModalMenu(id_menu) {
            // menuData didefinisikan di views/layouts/hero.php
            const menu = menuData.find(m => parseInt(m.id_menu) === parseInt(id_menu));
            if (!menu) return;

            // Tutup search dropdown jika terbuka
            const dropdown = document.getElementById('searchDropdown');
            if (dropdown) dropdown.style.display = 'none';

            const modal = document.getElementById('modalMenu');
            const foto = document.getElementById('modalMenuFoto');
            const fallback = document.getElementById('modalMenuFallback');

            document.getElementById('modalMenuNama').textContent = menu.nama_menu;
            document.getElementById('modalMenuKantinBadge').textContent = menu.nama_toko;
            document.getElementById('modalMenuHarga').textContent = 'Rp ' + parseInt(menu.harga).toLocaleString('id-ID');
            document.getElementById('modalMenuDesc').textContent = menu.deskripsi || 'Tidak ada deskripsi untuk menu ini.';

            // Render photo or fallback
            if (menu.foto_menu) {
                foto.src = './assets/img/menu/' + menu.foto_menu;
                foto.style.display = 'block';
                fallback.style.display = 'none';
                foto.onerror = () => {
                    foto.style.display = 'none';
                    if (typeof fallbackSVG === 'function') {
                        fallback.innerHTML = fallbackSVG(menu.nama_menu);
                        const innerDiv = fallback.querySelector('div');
                        if (innerDiv) {
                            innerDiv.style.height = '100%';
                            innerDiv.style.borderBottom = 'none';
                        }
                        const svgEl = fallback.querySelector('svg');
                        if (svgEl) {
                            svgEl.style.width = '64px';
                            svgEl.style.height = '64px';
                        }
                    } else {
                        fallback.innerHTML = '<div style="width:100%;height:100%;background:#eff6ff;display:flex;align-items:center;justify-content:center;font-size:32px;">🍽️</div>';
                    }
                    fallback.style.display = 'block';
                };
            } else {
                foto.style.display = 'none';
                if (typeof fallbackSVG === 'function') {
                    fallback.innerHTML = fallbackSVG(menu.nama_menu);
                    const innerDiv = fallback.querySelector('div');
                    if (innerDiv) {
                        innerDiv.style.height = '100%';
                        innerDiv.style.borderBottom = 'none';
                    }
                    const svgEl = fallback.querySelector('svg');
                    if (svgEl) {
                        svgEl.style.width = '64px';
                        svgEl.style.height = '64px';
                    }
                } else {
                    fallback.innerHTML = '<div style="width:100%;height:100%;background:#eff6ff;display:flex;align-items:center;justify-content:center;font-size:32px;">🍽️</div>';
                }
                fallback.style.display = 'block';
            }

            // Set button Lihat Kantin onclick action
            const btnLihat = document.getElementById('btnLihatKantin');
            if (btnLihat) {
                btnLihat.onclick = () => {
                    tutupModalMenu();
                    bukaModal(menu.id_toko);
                };
            }

            modal.style.display = 'flex';
        }

        function tutupModalMenu() {
            document.getElementById('modalMenu').style.display = 'none';
        }

        // Menutup pop-up kalau user ngeklik area luar kotak putih
        window.onclick = function (event) {
            const modalKantin = document.getElementById('modalKantin');
            const modalMenu = document.getElementById('modalMenu');
            if (event.target == modalKantin) {
                modalKantin.style.display = "none";
            }
            if (event.target == modalMenu) {
                modalMenu.style.display = "none";
            }
        }
    </script>
</body>

</html>