<?php // about.php ?>
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

    .ab-team-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 12px;
        margin-bottom: 2rem;
    }

    .ab-team-card {
        background: #fff;
        border: 1px solid #eee;
        border-radius: 12px;
        padding: 1rem;
        text-align: center;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }

    .ab-team-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(121, 183, 117, 0.12);
        border-color: #79b775;
    }

    .ab-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 16px;
        margin: 0 auto 8px;
        font-family: 'Poppins', sans-serif;
    }

    .av-d {
        background: #E3E8FF;
        color: #79b775;
    }

    .av-p {
        background: #E8F5E9;
        color: #79b775;
    }

    .ab-name {
        font-size: 13px;
        font-weight: 600;
        color: #111;
        font-family: 'Poppins', sans-serif;
    }

    .ab-role {
        font-size: 11px;
        color: #777;
        margin-top: 4px;
        background: #f0f0f0;
        border-radius: 20px;
        padding: 2px 10px;
        display: inline-block;
        font-family: 'Poppins', sans-serif;
    }

    .ab-contact {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 2rem;
    }

    .ab-contact-btn {
        display: flex;
        align-items: center;
        gap: 6px;
        background: #f5f5f5;
        border: 1px solid #eee;
        border-radius: 8px;
        padding: 8px 16px;
        font-size: 13px;
        color: #444;
        cursor: pointer;
        text-decoration: none;
        font-family: 'Poppins', sans-serif;
        transition: all 0.2s ease;
    }

    .ab-contact-btn:hover {
        background: #79b775;
        color: #fff;
        border-color: #79b775;
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
// Ambil stats dari DB
$statKantin = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM toko"))['c'];
$statMenu = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM menu WHERE tersedia=1"))['c'];
?>

<div class="ab-hero">
    <h2>E-Kantin Esemkita</h2>
    <p>Platform digital pemesanan kantin sekolah yang memudahkan siswa memesan makanan tanpa antri panjang. Dibuat oleh
        tim ERROR 404.</p>
    <div class="ab-stats">
        <div class="ab-stat">
            <div class="ab-stat-num">
                <?= $statKantin ?>+
            </div>
            <div class="ab-stat-label">Kantin</div>
        </div>
        <div class="ab-stat">
            <div class="ab-stat-num">
                <?= $statMenu ?>+
            </div>
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
    <!-- TODO: ganti nama & inisial sesuai tim lo -->
    <div class="ab-team-card">
        <div class="ab-avatar av-d">D1</div>
        <div class="ab-name">Designer 1</div>
        <div class="ab-role">UI/UX Designer</div>
    </div>
    <div class="ab-team-card">
        <div class="ab-avatar av-d">D2</div>
        <div class="ab-name">Designer 2</div>
        <div class="ab-role">UI/UX Designer</div>
    </div>
    <div class="ab-team-card">
        <div class="ab-avatar av-d">D3</div>
        <div class="ab-name">Flowcharter</div>
        <div class="ab-role">Flowcharter</div>
    </div>
    <div class="ab-team-card">
        <div class="ab-avatar av-p">P1</div>
        <div class="ab-name">Programmer 1</div>
        <div class="ab-role">Frontend Dev</div>
    </div>
    <div class="ab-team-card">
        <div class="ab-avatar av-p">P2</div>
        <div class="ab-name">Programmer 2</div>
        <div class="ab-role">Backend Dev</div>
    </div>
    <div class="ab-team-card">
        <div class="ab-avatar av-p">P3</div>
        <div class="ab-name">Programmer 3</div>
        <div class="ab-role">Backend Dev</div>
    </div>
</div>

<div class="ab-section-title">Kontak</div>
<div class="ab-contact">
    <!-- TODO: ganti href dengan link asli -->
    <a class="ab-contact-btn" href="#">Instagram</a>
    <a class="ab-contact-btn" href="#">Email</a>
    <a class="ab-contact-btn" href="#">GitHub</a>
</div>

<hr class="ab-divider" />
<div class="ab-footer-row">
    <span>©2026 ERROR 404 Team</span>
    <span>SMKN 1 Boyolangu</span>
</div>