<?php require_once 'config/database.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./assets/css/styles.css?v=5">
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

                <div
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

        // Menutup pop-up kalau user ngeklik area luar kotak putih
        window.onclick = function (event) {
            const modal = document.getElementById('modalKantin');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>

</html>