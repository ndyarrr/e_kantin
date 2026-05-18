<?php
$semuaMenu = mysqli_fetch_all(mysqli_query(
    $conn,
    "SELECT m.nama_menu, m.deskripsi, m.harga, m.foto_menu, t.nama_toko, t.id_toko
    FROM menu m
    JOIN toko t ON t.id_toko = m.id_toko
    WHERE m.tersedia = 1 AND t.status = 'buka'
    ORDER BY m.nama_menu ASC"
), MYSQLI_ASSOC);
?>

<style>
    .hero-header {
        background: #79b775;
        border-radius: 0 0 24px 24px;
        /* padding-top gede biar konten ga ketutupan navbar fixed */
        padding: 90px 32px 40px;
        position: relative;
        overflow: visible;
        text-align: center;
    }

    .hero-header::before,
    .hero-header::after {
        content: '';
        position: absolute;
        width: 180px;
        height: 180px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.07);
        top: 50%;
        transform: translateY(-50%);
    }

    .hero-header::before {
        left: -40px;
    }

    .hero-header::after {
        right: -40px;
    }

    .hero-logo {
        position: absolute;
        top: 16px;
        left: 24px;
        z-index: 2;
    }

    .hero-logo img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }

    .hero-title {
        font-size: 32px;
        font-weight: 700;
        color: #fff;
        margin: 0 0 4px;
        font-family: sans-serif;
        position: relative;
        z-index: 2;
    }

    .hero-subtitle {
        font-size: 15px;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.9);
        margin: 0 0 6px;
        font-family: sans-serif;
        position: relative;
        z-index: 2;
    }

    .hero-desc {
        font-size: 14px;
        color: rgba(255, 255, 255, 0.8);
        margin: 0 0 20px;
        font-family: sans-serif;
        position: relative;
        z-index: 2;
    }

    .hero-search {
        display: flex;
        align-items: center;
        background: #fff;
        border-radius: 12px;
        padding: 10px 16px;
        max-width: 480px;
        margin: 0 auto;
        gap: 10px;
        position: relative;
        z-index: 2;
    }

    .hero-search svg {
        width: 18px;
        height: 18px;
        color: #888;
        flex-shrink: 0;
    }

    .hero-search input {
        border: none;
        outline: none;
        font-size: 14px;
        width: 100%;
        color: #333;
        font-family: sans-serif;
        background: transparent;
    }

    .hero-search input::placeholder {
        color: #bbb;
    }

    .search-dropdown {
        position: fixed;
        left: 50%;
        transform: translateX(-50%);
        width: min(480px, 90vw);
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        max-height: 480px;
        overflow-y: auto;
        z-index: 9999;
    }

    .search-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 16px;
        cursor: pointer;
        border-bottom: 1px solid #f5f5f5;
        transition: background 0.15s;
        text-decoration: none;
    }

    .search-item:hover {
        background: #f9f9f9;
    }

    .search-item:last-child {
        border-bottom: none;
    }

    .search-item-img {
        width: 44px;
        height: 44px;
        border-radius: 8px;
        object-fit: cover;
        background: #eee;
        flex-shrink: 0;
    }

    .search-item-info {
        flex: 1;
        min-width: 0;
    }

    .search-item-nama {
        font-size: 13px;
        font-weight: 600;
        color: #111;
        font-family: 'Poppins', sans-serif;
    }

    .search-item-desc {
        font-size: 11px;
        color: #888;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-family: 'Poppins', sans-serif;
    }

    .search-item-harga {
        font-size: 12px;
        font-weight: 700;
        color: #79b775;
        white-space: nowrap;
        font-family: 'Poppins', sans-serif;
    }

    .search-empty {
        padding: 20px;
        text-align: center;
        color: #aaa;
        font-size: 13px;
        font-family: 'Poppins', sans-serif;
    }

    .search-item-kantin {
        font-size: 10px;
        color: #79b775;
        font-weight: 600;
        font-family: 'Poppins', sans-serif;
        margin-bottom: 2px;
    }
</style>

<!-- Tambah div wrapper di luar hero-header -->
<div style="position:relative">

    <div class="hero-header" style="overflow:hidden;">
        <div class="hero-logo">
            <img src="./assets/img/logo-esemkita.png" alt="Logo" />
        </div>
        <h1 class="hero-title">Welcome To</h1>
        <p class="hero-subtitle">E-Kantin</p>
        <p class="hero-desc">Buat pesananmu di kantin jadi lebih cepat</p>

        <!-- TAMBAH BALIK INI -->
        <div class="hero-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <input type="text" placeholder="Cari menu..." id="heroSearchInput" autocomplete="off" />
        </div>
    </div>

    <!-- Dropdown di LUAR hero-header tapi masih di dalam wrapper -->

</div>

<div id="searchDropdown" class="search-dropdown" style="display:none"></div>



<script>
    const menuData = <?= json_encode($semuaMenu) ?>;

    const input = document.getElementById('heroSearchInput');
    const dropdown = document.getElementById('searchDropdown');

    input.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        const searchRect = input.getBoundingClientRect();
        dropdown.style.top = (searchRect.bottom + 8) + 'px';


        if (q.length < 2) {
            dropdown.style.display = 'none';
            return;
        }

        const hasil = menuData.filter(m =>
            m.nama_menu.toLowerCase().includes(q) ||
            (m.deskripsi && m.deskripsi.toLowerCase().includes(q))
        );

        if (hasil.length === 0) {
            dropdown.innerHTML = '<div class="search-empty">Menu tidak ditemukan</div>';
        } else {
            dropdown.innerHTML = `
            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px;padding:12px">
                ${hasil.slice(0, 8).map(m => `
                    <a class="search-item" href="./kantin/detail.php?id=${m.id_toko}" style="flex-direction:column;align-items:flex-start;padding:0;border-radius:12px;border:1.5px solid #eee;overflow:hidden;text-decoration:none">
                        <img style="width:100%;height:140px;object-fit:cover;display:block;background:#eee"
                            src="${m.foto_menu ? './assets/img/menu/' + m.foto_menu : ''}"
                            onerror="this.style.background='#eee';this.style.minHeight='140px'">
                        <div style="padding:10px 12px 12px">
                            <div class="search-item-nama">${m.nama_menu}</div>
                            <div style="font-size:12px;color:#555;font-family:Poppins,sans-serif;margin:2px 0 6px">Rp ${parseInt(m.harga).toLocaleString('id-ID')}</div>
                            <span style="background:#e8f5e9;color:#79b775;font-size:10px;font-weight:700;padding:2px 10px;border-radius:20px;font-family:Poppins,sans-serif">
                                ${m.nama_toko}
                            </span>
                        </div>
                    </a>
                `).join('')}
            </div>
        `;
        }

        dropdown.style.display = 'block';
    });

    // Tutup dropdown kalau klik di luar
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.hero-search') && !e.target.closest('#searchDropdown')) {
            dropdown.style.display = 'none';
        }
    });
</script>