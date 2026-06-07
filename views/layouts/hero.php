<?php
$semuaMenu = mysqli_fetch_all(mysqli_query(
    $conn,
    "SELECT m.id_menu, m.nama_menu, m.deskripsi, m.harga, m.foto_menu, t.nama_toko, t.id_toko, m.deleted_at, m.is_fleksibel
    FROM menu m
    JOIN toko t ON t.id_toko = m.id_toko
    WHERE m.deleted_at IS NULL
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
        font-family: 'Poppins', sans-serif;
        position: relative;
        z-index: 2;
    }

    .hero-subtitle {
        font-size: 15px;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.9);
        margin: 0 0 6px;
        font-family: 'Poppins', sans-serif;
        position: relative;
        z-index: 2;
    }

    .hero-desc {
        font-size: 14px;
        color: rgba(255, 255, 255, 0.8);
        margin: 0 0 20px;
        font-family: 'Poppins', sans-serif;
        position: relative;
        z-index: 2;
    }

    .hero-cta-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #ffffff;
        color: #79b775;
        font-weight: 700;
        font-size: 13.5px;
        padding: 9px 24px;
        border-radius: 100px;
        text-decoration: none;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
        transition: all 0.25s ease;
        font-family: 'Poppins', sans-serif;
        position: relative;
        z-index: 2;
        margin-bottom: 20px;
        cursor: pointer;
        border: none;
    }

    .hero-cta-btn:hover {
        background: #f8fafc;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
        color: #16a34a;
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
        font-family: 'Poppins', sans-serif;
        background: transparent;
    }

    .hero-search input::placeholder {
        color: #bbb;
    }

    .search-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        margin-top: 8px;
        width: 100%;
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

    <div class="hero-header" style="overflow:visible;">
        <div class="hero-logo">
            <img src="./assets/img/logo_ekantin_hijau.png" alt="Logo" />
        </div>
        <h1 class="hero-title">Welcome To</h1>
        <p class="hero-subtitle">E-Kantin</p>
        <p class="hero-desc">Buat pesananmu di kantin jadi lebih cepat</p>
        <a href="#kantin" class="hero-cta-btn" onclick="document.getElementById('kantin').scrollIntoView({behavior: 'smooth'}); return false;">
            <i class="fa-solid fa-store"></i> Jelajahi Kantin
        </a>

        <!-- TAMBAH BALIK INI -->
        <div class="hero-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <input type="text" placeholder="Cari menu..." id="heroSearchInput" autocomplete="off" />
            <div id="searchDropdown" class="search-dropdown" style="display:none"></div>
        </div>
    </div>
</div>


<script>
    const menuData = <?= json_encode($semuaMenu) ?>;

    const input = document.getElementById('heroSearchInput');
    const dropdown = document.getElementById('searchDropdown');

    input.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase()
        const searchRect = input.getBoundingClientRect();


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
            <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 12px; padding: 12px;">
                ${hasil.slice(0, 8).map(m => `
                    <a class="search-item" href="javascript:void(0)" onclick="bukaModalMenu(${m.id_menu})" style="display: flex; flex-direction: column; align-items: flex-start; padding: 0; border-radius: 12px; border: 1.5px solid #eee; overflow: hidden; text-decoration: none; width: 220px; box-sizing: border-box;">
                        ${m.foto_menu
                    ? `<img style="width:100%;height:140px;object-fit:cover;display:block;" 
           src="./assets/img/menu/${m.foto_menu}" 
           data-nama="${m.nama_menu.replace(/"/g, '&quot;')}"
           onerror="gantiKeFallback(this)">`
                    : fallbackSVG(m.nama_menu)
                }
        
                        <div style="padding: 10px 12px 12px; width: 100%; box-sizing: border-box;">
                            <div class="search-item-nama">${m.nama_menu}</div>
                            <div style="font-size: 12px; color: #555; font-family: Poppins, sans-serif; margin: 2px 0 6px;">
                                ${parseInt(m.is_fleksibel) === 1 
                                    ? `<span style="background: rgba(14, 165, 233, 0.1); color: #0ea5e9; padding: 2px 6px; border-radius: 6px; font-weight: 750; font-size: 11px; display: inline-flex; align-items: center; gap: 4px;"><i class="fa-solid fa-arrows-left-right-to-line"></i> Harga Fleksibel</span>` 
                                    : 'Rp ' + parseInt(m.harga).toLocaleString('id-ID')
                                }
                            </div>
                            <span style="background: #e8f5e9; color: #79b775; font-size: 10px; font-weight: 700; padding: 2px 10px; border-radius: 20px; font-family: Poppins, sans-serif;">
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

    function getKategoriIcon(nama) {
        const n = nama.toLowerCase();
        if (n.includes('minum') || n.includes('es') || n.includes('jus') || n.includes('teh') || n.includes('kopi')) {
            return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width:32px;height:32px;fill:#1890ff;filter:drop-shadow(0 2px 4px rgba(24,144,255,0.2));">
            <path d="M3 2l2.01 18.23C5.13 21.23 5.97 22 7 22h10c1.03 0 1.87-.77 1.99-1.77L21 2H3zm9 17c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm1-9H8V8h5v2z"/>
        </svg>`;
        } else if (n.includes('snack') || n.includes('gorengan') || n.includes('keripik') || n.includes('cemilan')) {
            return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width:32px;height:32px;fill:#9254de;filter:drop-shadow(0 2px 4px rgba(146,84,222,0.2));">
            <path d="M18.06 22.99h1.66c.84 0 1.53-.64 1.63-1.46L23 5.05h-5V1h-1.97v4.05h-4.97l.3 2.34c1.71.47 3.31 1.32 4.27 2.26 1.44 1.42 2.43 2.89 2.43 5.29v8.05zM1 21.99V21h15.03v.99c0 .55-.45 1-1.01 1H2.01c-.56 0-1.01-.45-1.01-1zm15.03-7c0-4.5-6.72-5-8.99-5-2.28 0-9.03.5-9.03 5h18.02z"/>
        </svg>`;
        } else {
            return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width:32px;height:32px;fill:#ff7a45;filter:drop-shadow(0 2px 4px rgba(255,122,69,0.2));">
            <path d="M11 9H9V2H7v7H5V2H3v7c0 2.12 1.66 3.84 3.75 3.97V22h2.5v-9.03C11.34 12.84 13 11.12 13 9V2h-2v7zm5-3v8h2.5v8H21V2c-2.76 0-5 2.24-5 4z"/>
        </svg>`;
        }
    }

    function fallbackSVG(nama) {
        const n = nama.toLowerCase();
        let bg, border;
        if (n.includes('minum') || n.includes('es') || n.includes('jus') || n.includes('teh') || n.includes('kopi')) {
            bg = '#eff6ff'; border = '#bfdbfe';
        } else if (n.includes('snack') || n.includes('gorengan') || n.includes('keripik') || n.includes('cemilan')) {
            bg = '#f5f3ff'; border = '#ddd6fe';
        } else {
            bg = '#fff2e8'; border = '#fed7aa';
        }
        return `<div style="width:100%;height:140px;background:${bg};border-bottom:2px solid ${border};display:flex;align-items:center;justify-content:center;">${getKategoriIcon(nama)}</div>`;
    }

    // Tutup dropdown kalau klik di luar
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.hero-search') && !e.target.closest('#searchDropdown')) {
            dropdown.style.display = 'none';
        }
    });
    function gantiKeFallback(el) {
        const nama = el.getAttribute('data-nama') || '';
        const div = document.createElement('div');
        div.innerHTML = fallbackSVG(nama);
        el.parentNode.replaceChild(div.firstChild, el);
    }
</script>