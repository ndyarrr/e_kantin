<?php // about.php ?>
<script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>

<style>
    #about {
        padding: 48px 32px;
        background: #fff;
    }

    .ab-hero {
        background: #79b775;
        border-radius: 16px;
        padding: 2rem;
        color: white;
        margin-bottom: 2rem;
    }

    .ab-hero h2 {
        font-size: 22px;
        font-weight: 700;
        margin-bottom: 8px;
        font-family: 'Poppins', sans-serif;
    }

    .ab-hero p {
        font-size: 13px;
        opacity: 0.85;
        line-height: 1.7;
        max-width: 500px;
        font-family: 'Poppins', sans-serif;
    }

    .ab-stats {
        display: flex;
        gap: 16px;
        margin-top: 1.5rem;
        flex-wrap: wrap;
    }

    .ab-stat {
        background: rgba(255, 255, 255, 0.15);
        border-radius: 10px;
        padding: 12px 20px;
        text-align: center;
    }

    .ab-stat-num {
        font-size: 20px;
        font-weight: 700;
        font-family: 'Poppins', sans-serif;
    }

    .ab-stat-label {
        font-size: 11px;
        opacity: 0.8;
        margin-top: 2px;
        font-family: 'Poppins', sans-serif;
    }

    .ab-section-title {
        font-size: 15px;
        font-weight: 700;
        color: #111;
        margin-bottom: 1rem;
        font-family: 'Poppins', sans-serif;
    }

    /* Grid Layout Tim Pengembang (Responsif) */
    .ab-team-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 16px;
        margin-bottom: 2rem;
    }

    /* Desain Kartu Tim (Menggunakan tag <a>) */
    .ab-team-card {
        background: #fff;
        border: 1.5px solid #eee;
        border-radius: 16px;
        padding: 20px 12px;
        text-align: center;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-decoration: none; /* Menghilangkan garis bawah link default */
        color: inherit; /* Menjaga warna teks agar tidak berubah biru */
    }

    /* Efek Hover Kartu Terangkat */
    .ab-team-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 10px 20px rgba(121, 183, 117, 0.15);
        border-color: #79b775;
    }

    /* Desain Foto Profil */
    .ab-avatar {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        object-fit: cover;
        margin: 0 auto 12px;
        border: 3px solid transparent;
        background: #f4f9f3;
        transition: all 0.3s ease;
    }

    /* Efek Ring Hijau pada Foto saat Kartu di-hover */
    .ab-team-card:hover .ab-avatar {
        border-color: #79b775;
        padding: 2px;
    }

    .ab-name {
        font-size: 14px;
        font-weight: 600;
        color: #111;
        font-family: 'Poppins', sans-serif;
        margin-bottom: 4px;
    }

    .ab-role {
        font-size: 11px;
        color: #777;
        background: #f5f5f5;
        border-radius: 20px;
        padding: 4px 12px;
        display: inline-block;
        font-family: 'Poppins', sans-serif;
        font-weight: 500;
    }

    /* Area Tombol Kontak */
    .ab-contact {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 2rem;
    }

    /* Tombol Kontak Utama */
    .ab-contact-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #fff;
        border: 1.5px solid #eee;
        border-radius: 12px;
        padding: 10px 20px;
        font-size: 13px;
        font-weight: 600;
        color: #555;
        cursor: pointer;
        text-decoration: none;
        font-family: 'Poppins', sans-serif;
        transition: all 0.2s ease;
    }

    .ab-contact-btn iconify-icon {
        font-size: 20px;
        color: #888;
        transition: all 0.2s ease;
    }

    /* Efek Hover Tombol Kontak */
    .ab-contact-btn:hover {
        background: #79b775;
        color: #fff;
        border-color: #79b775;
        transform: translateY(-2px);
    }

    .ab-contact-btn:hover iconify-icon {
        color: #fff;
    }

    .ab-divider {
        border: none;
        border-top: 1px solid #eee;
        margin: 1.5rem 0;
    }

    .ab-footer-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 12px;
        color: #999;
        flex-wrap: wrap;
        gap: 8px;
        font-family: 'Poppins', sans-serif;
    }

    /* Responsivitas Layar HP */
    @media (max-width: 768px) {
        #about {
            padding: 32px 16px;
        }

        .ab-team-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .ab-stats {
            gap: 10px;
        }
    }
</style>

<?php
// Ambil data statistik dinamis dari Database
$statKantin = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM toko WHERE deleted_at IS NULL"))['c'];
$statMenu = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM menu m JOIN toko t ON m.id_toko = t.id_toko WHERE m.tersedia=1 AND m.deleted_at IS NULL AND t.deleted_at IS NULL"))['c'];
?>

<div class="ab-hero">
    <h2>E-Kantin Esemkita</h2>
    <p>Platform digital pemesanan kantin sekolah yang memudahkan siswa memesan makanan tanpa antri panjang. Dibuat oleh tim ERROR 404.</p>
    <div class="ab-stats">
        <div class="ab-stat">
            <div class="ab-stat-num"><?= $statKantin ?></div>
            <div class="ab-stat-label">Kantin</div>
        </div>
        <div class="ab-stat">
            <div class="ab-stat-num"><?= $statMenu ?></div>
            <div class="ab-stat-label">Menu</div>
        </div>
        <div class="ab-stat">
            <div class="ab-stat-num">2026</div>
            <div class="ab-stat-label">Tahun dibuat</div>
        </div>
    </div>
</div>

<div class="ab-section-title">Tim Pengembang</div>
<div class="ab-team-grid">
    
    <a class="ab-team-card" href="https://www.instagram.com/dns.tm_?igsh=MTBoMTd2M2RyOXYyMA==" target="_blank">
        <img class="ab-avatar" src="./assets/img/PPDanes.jpeg" alt="Project Manager">
        <div class="ab-name">Daneswara P.H.G</div>
        <div class="ab-role">Project Manager</div>
    </a>
    
    <a class="ab-team-card" href="https://www.instagram.com/_ayzzrril?igsh=M3pwYjd1ZHkyY253" target="_blank">
        <img class="ab-avatar" src="./assets/img/PPAcin.jpeg" alt="UI/UX Designer">
        <div class="ab-name">Ahmad Yazril R.F</div>
        <div class="ab-role">UI/UX Designer</div>
    </a>
    
    <a class="ab-team-card" href="https://www.instagram.com/dedineckhurt?igsh=MWtpanQ5cGRyYjdhMg==" target="_blank">
        <img class="ab-avatar" src="./assets/img/PPDedi.jpeg" alt="Flowcharter">
        <div class="ab-name">Dedi Permana</div>
        <div class="ab-role">Flowcharter</div>
    </a>
    
    <a class="ab-team-card" href="https://www.instagram.com/alvafloww?igsh=MThtb2pmeHBrZHUxNg==" target="_blank">
        <img class="ab-avatar" src="./assets/img/PPVaro.jpeg" alt="Tester">
        <div class="ab-name">Alvaro Algozhali</div>
        <div class="ab-role">Tester</div>
    </a>
    
    <a class="ab-team-card" href="https://www.instagram.com/ndydod?igsh=eDF6Y281OTBqbzR2" target="_blank">
        <img class="ab-avatar" src="./assets/img/PPFandy.png" alt="Backend Dev">
        <div class="ab-name">Fandy Ahmad R.</div>
        <div class="ab-role">Backend Dev</div>
    </a>
    
    <a class="ab-team-card" href="https://www.instagram.com/ariel_wijaya88?igsh=eHY4Nmpyd2MyaDM1" target="_blank">
        <img class="ab-avatar" src="./assets/img/PPAril.jpeg" alt="Frontend Dev">
        <div class="ab-name">Aril Wijaya S.</div>
        <div class="ab-role">Frontend Dev</div>
    </a>
</div>

<div class="ab-section-title">Kontak</div>
<div class="ab-contact">
    <a class="ab-contact-btn" href="https://www.instagram.com/erro.r404team?igsh=MTdjeThjYmJuNjV5NQ==" target="_blank">
        <iconify-icon icon="fa6-brands:instagram"></iconify-icon> Instagram
    </a>
    <a class="ab-contact-btn" href="https://www.tiktok.com/@error404team6?is_from_webapp=1&sender_device=pc" target="_blank">
        <iconify-icon icon="ri:tiktok-fill"></iconify-icon> TikTok
    </a>
    <a class="ab-contact-btn" href="mailto:ekantin404@gmail.com">
        <iconify-icon icon="ri:mail-send-line"></iconify-icon> Email
    </a>
    <a class="ab-contact-btn" href="https://github.com/ndyarrr/e_kantin.git" target="_blank">
        <iconify-icon icon="fa6-brands:github"></iconify-icon> GitHub
    </a>
</div>

<hr class="ab-divider" />
<div class="ab-footer-row">
    <span>©2026 ERROR 404 Team</span>
    <span>SMKN 1 Boyolangu</span>
</div>