<?php require_once 'config/database.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./assets/css/styles.css">
    <title>E-Kantin</title>
    <!-- <?php //include 'views/layouts/header.php'; ?> -->
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
        <div class="collage-wrapper" draggable="false">
            <div class="collage-grid">
                <img class="col-img col-1" src="./assets/img/gb1.jpeg" alt="" />
                <img class="col-img col-2" src="./assets/img/gb2.jpeg" alt="" />
                <img class="col-img col-3" src="./assets/img/gb3.jpeg" alt="" />
                <img class="col-img col-4" src="./assets/img/gb4.jpeg" alt="" />
                <img class="col-img col-5" src="./assets/img/gb5.jpeg" alt="" />
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
        history.scrollRestoration = 'manual';
        window.scrollTo(0, 0);

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

        window.addEventListener('scroll', updateActive);
        updateActive();
    </script>
</body>

</html>