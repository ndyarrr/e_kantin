<?php
// hero.php
// Cara pakai: <?php include 'components/hero.php';
?>

<style>
    .hero-header {
        background: #79b775;
        border-radius: 0 0 24px 24px;
        /* padding-top gede biar konten ga ketutupan navbar fixed */
        padding: 90px 32px 40px;
        position: relative;
        overflow: hidden;
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
</style>

<div class="hero-header">
    <!-- Logo pojok kiri (hapus kalau ga punya) -->
    <div class="hero-logo">
        <img src="./assets/img/logo-esemkita.png" alt="Logo" />
    </div>
    
    <h1 class="hero-title">Welcome To</h1>
    <p class="hero-subtitle">E-Kantin</p>
    <p class="hero-desc">Buat pesananmu di kantin jadi lebih cepat</p>

    <div class="hero-search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
            stroke-linejoin="round">
            <circle cx="11" cy="11" r="8" />
            <line x1="21" y1="21" x2="16.65" y2="16.65" />
        </svg>
        <input type="text" placeholder="Cari menu..." />
    </div>
</div>