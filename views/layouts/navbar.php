<?php // navbar.php ?>
<style>
    .site-navbar {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        transform: none;
        z-index: 999;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        background: rgba(121, 183, 117, 0.75);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: none;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 0;
        padding: 9px 18px;
        box-shadow: 0 4px 24px rgba(91, 79, 207, 0.2);
        min-width: unset;
    }

    .navbar-logo {
        display: flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        flex-shrink: 0;
    }

    .navbar-logo img {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        object-fit: cover;
    }

    .navbar-logo-name {
        font-size: 13px;
        font-weight: 700;
        color: #fff;
        font-family: 'Poppins', sans-serif;
        white-space: nowrap;
    }

    .nav-links {
        display: flex;
        align-items: center;
        gap: 6px;
        flex: 1;
        justify-content: center;
    }

    .nav-pill {
        padding: 6px 18px;
        border-radius: 100px;
        border: 1.5px solid rgba(255, 255, 255, 0.5);
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
        background: rgba(255, 255, 255, 0.1);
    }

    .nav-pill.active {
        color: #fff !important;
        border-color: #fff !important;
        background: rgba(255, 255, 255, 0.15);
    }

    .navbar-btn-login {
        display: flex;
        align-items: center;
        gap: 6px;
        background: #fff;
        color: #79b775;
        font-size: 13px;
        font-weight: 700;
        font-family: 'Poppins', sans-serif;
        padding: 7px 18px;
        border-radius: 100px;
        border: none;
        cursor: pointer;
        white-space: nowrap;
        flex-shrink: 0;
        transition: all 0.2s ease;
        text-decoration: none;
    }

    .navbar-btn-login:hover {
        background: #f0f0f0;
        transform: translateY(-1px);
    }

    /* hamburger */
    .navbar-hamburger {
        display: none;
        flex-direction: column;
        gap: 5px;
        cursor: pointer;
        padding: 4px;
        flex-shrink: 0;
    }

    .navbar-hamburger span {
        display: block;
        width: 24px;
        height: 2px;
        background: #fff;
        border-radius: 2px;
        transition: all 0.3s ease;
    }

    .navbar-hamburger.open span:nth-child(1) {
        transform: translateY(7px) rotate(45deg);
    }

    .navbar-hamburger.open span:nth-child(2) {
        opacity: 0;
    }

    .navbar-hamburger.open span:nth-child(3) {
        transform: translateY(-7px) rotate(-45deg);
    }

    /* dropdown mobile */
    .nav-dropdown {
        display: none;
        position: fixed;
        top: 62px;
        /* sedikit jarak dari navbar */
        left: 12px;
        right: 12px;
        /* ga full width, kasih margin kiri kanan */
        background: rgba(121, 183, 117, 0.75);
        /* lebih transparan biar keliatan blur */
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        flex-direction: column;
        padding: 12px;
        gap: 8px;
        z-index: 998;
        border-radius: 16px;
        /* rounded */
        border: 1px solid rgba(255, 255, 255, 0.25);
        box-shadow: 0 8px 32px rgba(91, 79, 207, 0.25);
    }

    .nav-dropdown.open {
        display: flex;
    }

    .nav-dropdown .nav-pill {
        text-align: center;
        padding: 10px 18px;
    }

    .nav-dropdown .navbar-btn-login {
        justify-content: center;
        margin-top: 4px;
    }

    @media (max-width: 768px) {
        .site-navbar {
            padding: 12px 16px;
        }

        .nav-links {
            display: none;
        }

        .navbar-btn-login {
            display: none;
        }

        .navbar-hamburger {
            display: flex;
        }
    }
</style>

<nav class="site-navbar">
    <a class="navbar-logo" href="#">
        <img src="./assets/img/logo_ekantin_putih.png" alt="Logo" />
        <span class="navbar-logo-name">E-Kantin</span>
    </a>

    <div class="nav-links">
        <a class="nav-pill" data-target="home">HOME</a>
        <a class="nav-pill" data-target="kantin">KANTIN</a>
        <a class="nav-pill" data-target="leaderboard">LEADERBOARD</a>
        <a class="nav-pill" data-target="about">ABOUT</a>
    </div>

    <a class="navbar-btn-login" href="./auth/login.php">Login</a>

    <div class="navbar-hamburger" id="hamburger" onclick="toggleHamburger()">
        <span></span>
        <span></span>
        <span></span>
    </div>
</nav>

<!-- dropdown mobile -->
<div class="nav-dropdown" id="navDropdown">
    <a class="nav-pill" data-target="home" onclick="closeDropdown()">HOME</a>
    <a class="nav-pill" data-target="kantin" onclick="closeDropdown()">KANTIN</a>
    <a class="nav-pill" data-target="leaderboard" onclick="closeDropdown()">LEADERBOARD</a>
    <a class="nav-pill" data-target="about" onclick="closeDropdown()">ABOUT</a>
    <a class="navbar-btn-login" href="./auth/login.php">Login</a>
</div>

<script>
    function toggleHamburger() {
        document.getElementById('hamburger').classList.toggle('open');
        document.getElementById('navDropdown').classList.toggle('open');
    }

    function closeDropdown() {
        document.getElementById('hamburger').classList.remove('open');
        document.getElementById('navDropdown').classList.remove('open');
    }

    document.addEventListener('click', function (e) {
        const hamburger = document.getElementById('hamburger');
        const dropdown = document.getElementById('navDropdown');
        if (!hamburger.contains(e.target) && !dropdown.contains(e.target)) {
            closeDropdown();
        }
    });
</script>