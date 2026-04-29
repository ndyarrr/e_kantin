<?php // navbar.php ?>
<style>
    .site-navbar {
        position: fixed;
        top: 16px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 999;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        background: rgba(92, 107, 192, 0.85);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-radius: 100px;
        padding: 8px 12px;
    }

    .nav-pill {
        padding: 6px 18px;
        border-radius: 100px;
        border: 2px solid rgba(255, 255, 255, 0.6);
        color: rgba(255, 255, 255, 0.85);
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        font-family: sans-serif;
        letter-spacing: 0.02em;
        transition: all 0.2s ease;
        background: transparent;
        white-space: nowrap;
        cursor: pointer;
    }

    .nav-pill:hover {
        color: #fff;
        border-color: rgba(255, 255, 255, 0.9);
    }

    .nav-pill.active {
        color: #fff !important;
        border-color: #fff !important;
    }
</style>

<nav class="site-navbar">
    <a class="nav-pill" data-target="home">HOME</a>
    <a class="nav-pill" data-target="kantin">KANTIN</a>
    <a class="nav-pill" data-target="leaderboard">LEADERBOARD</a>
    <a class="nav-pill" data-target="about">About</a>
</nav>

