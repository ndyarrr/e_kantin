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
        <div class="btn-wrapper">
            <button onclick="window.location.href='./auth/login.php'" class="btn-login">Login</button>
        </div>
    </section>

    <section id="kantin" style="min-height: 100vh;">
        kantin content
    </section>

    <section id="leaderboard" style="min-height: 100vh;">
        <div class="test">Leaderboard Content</div>
    </section>

    <section id="about" style="min-height: 100vh;">
        about content
    </section>


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