<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);

require_once __DIR__ . '/../../config/database.php';
$daftarToko = mysqli_fetch_all(mysqli_query(
    $conn,
    "SELECT id_toko, nama_toko FROM toko ORDER BY nama_toko ASC"
), MYSQLI_ASSOC);

$roles = [
    [
        'key' => 'pembeli',
        'label' => 'Pembeli',
        'field_label' => 'NISN',
        'field_name' => 'identifier',
        'placeholder' => 'Masukkan NISN (10 digit)',
        'img' => '../../assets/img/role_pembeli.jpg',
        'color' => '#4CAF50',
        'has_activation' => false,
        'has_toko' => false,
        'has_toggle' => true,
    ],
    [
        'key' => 'penjual',
        'label' => 'Penjual',
        'field_label' => 'Username',
        'field_name' => 'username',
        'placeholder' => 'Masukkan username',
        'img' => '../../assets/img/role_penjual.jpg',
        'color' => '#FF9800',
        'has_activation' => false,
        'has_toko' => true,
        'has_toggle' => false,
    ],
    [
        'key' => 'admin',
        'label' => 'Admin',
        'field_label' => 'Username',
        'field_name' => 'username',
        'placeholder' => 'Masukkan username admin',
        'img' => '../../assets/img/role_admin.jpg',
        'color' => '#9C27B0',
        'has_activation' => true,
        'has_toko' => false,
        'has_toggle' => false,
    ],
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - E-Kantin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/login.css">
</head>

<body>

    <div class="bg-deco">
        <span>🍚</span><span>🍜</span><span>🥤</span><span>🍗</span><span>🥘</span>
        <span>🍱</span><span>🍛</span><span>🧃</span><span>🥗</span><span>🍲</span>
    </div>

    <div class="login-wrapper">

        <div class="login-brand">
            <img src="../../assets/img/logo-esemkita.png" alt="Logo" onerror="this.style.display='none'">
        </div>

        <div class="login-card">

            <!-- Panel kiri — role -->
            <div class="role-panel" id="rolePanel">
                <button class="arrow-btn arrow-up" onclick="prevRole()" title="Role sebelumnya">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                        stroke-linejoin="round">
                        <polyline points="18 15 12 9 6 15"></polyline>
                    </svg>
                </button>

                <div class="role-img-wrap">
                    <img id="roleImg" src="" alt="Role">
                </div>

                <div class="role-label" id="roleLabel">Pembeli</div>

                <button class="arrow-btn arrow-down" onclick="nextRole()" title="Role berikutnya">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                        stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>
            </div>

            <!-- Panel kanan — form -->
            <div class="form-panel">
                <div class="form-title">Login Sebagai <span id="formRoleLabel">Pembeli</span></div>

                <?php if (!empty($error)): ?>
                    <div class="alert-error">⚠ <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form action="../../auth/login.php" method="POST" id="loginForm">
                    <input type="hidden" name="role" id="roleInput" value="pembeli">
                    <input type="hidden" name="tipe_pembeli" id="tipePembeli" value="siswa">

                    <!-- Toggle siswa/guru — hanya untuk role pembeli -->
                    <div id="pembeliToggle" style="display:none; margin-bottom:10px;">
                        <div class="toggle-wrap">
                            <button type="button" class="toggle-tab active" id="tabSiswa"
                                onclick="setPembeli('siswa')">Siswa</button>
                            <button type="button" class="toggle-tab" id="tabGuru"
                                onclick="setPembeli('guru')">Guru</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label id="fieldLabel">NISN</label>
                        <input type="text" id="fieldInput" name="identifier" placeholder="Masukkan NISN (10 digit)"
                            required autocomplete="off">
                    </div>

                    <!-- Dropdown kantin — hanya untuk penjual -->
                    <div class="form-group" id="tokoGroup" style="margin-top:12px; display:none;">
                        <label>Nama Kantin</label>
                        <select name="id_toko" id="tokoSelect" class="input-select">
                            <option value="">Pilih kantin...</option>
                            <?php foreach ($daftarToko as $t): ?>
                                <option value="<?= $t['id_toko'] ?>"><?= htmlspecialchars($t['nama_toko']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-top:12px">
                        <label>Password</label>
                        <div class="input-password">
                            <input type="password" id="password" name="password" placeholder="Masukkan password"
                                required autocomplete="current-password">
                            <button type="button" class="toggle-password" onclick="toggleVis('password', this)">
                                <svg class="eye-icon eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <svg class="eye-icon eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                    style="display:none">
                                    <path
                                        d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" />
                                    <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" />
                                    <line x1="1" y1="1" x2="23" y2="23" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Kode aktivasi — hanya untuk admin -->
                    <div class="form-group" id="activationGroup" style="margin-top:12px; display:none;">
                        <label>Kode Aktivasi</label>
                        <div class="input-password">
                            <input type="password" id="activationCode" name="kode_aktivasi"
                                placeholder="Masukkan kode aktivasi" autocomplete="off">
                            <button type="button" class="toggle-password" onclick="toggleVis('activationCode', this)">
                                <svg class="eye-icon eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <svg class="eye-icon eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                    style="display:none">
                                    <path
                                        d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" />
                                    <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" />
                                    <line x1="1" y1="1" x2="23" y2="23" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-login" style="margin-top:20px;width:100%">Masuk</button>
                </form>
            </div>

        </div>

        <p class="login-footer">&copy; <?= date('Y') ?> E-Kantin &mdash; SMKN 1 Boyolangu</p>
    </div>

    <script>
        const roles = <?= json_encode($roles) ?>;
        let current = 0;

        const roleImg = document.getElementById('roleImg');
        const roleLabel = document.getElementById('roleLabel');
        const formLabel = document.getElementById('formRoleLabel');
        const fieldLabel = document.getElementById('fieldLabel');
        const fieldInput = document.getElementById('fieldInput');
        const roleInput = document.getElementById('roleInput');
        const rolePanel = document.getElementById('rolePanel');
        const activationGroup = document.getElementById('activationGroup');
        const activationCode = document.getElementById('activationCode');
        const tokoGroup = document.getElementById('tokoGroup');
        const tokoSelect = document.getElementById('tokoSelect');
        const pembeliToggle = document.getElementById('pembeliToggle');
        const tipePembeli = document.getElementById('tipePembeli');
        const tabSiswa = document.getElementById('tabSiswa');
        const tabGuru = document.getElementById('tabGuru');

        function setPembeli(tipe) {
            tipePembeli.value = tipe;
            if (tipe === 'siswa') {
                tabSiswa.classList.add('active');
                tabGuru.classList.remove('active');
                fieldLabel.textContent = 'NISN';
                fieldInput.placeholder = 'Masukkan NISN (10 digit)';
            } else {
                tabGuru.classList.add('active');
                tabSiswa.classList.remove('active');
                fieldLabel.textContent = 'NUPTK / Nama';
                fieldInput.placeholder = 'Masukkan NUPTK (16 digit) atau nama';
            }
            fieldInput.value = '';
        }

        function updateRole() {
            const r = roles[current];

            roleImg.style.opacity = '0';
            roleImg.style.transform = 'translateY(10px)';
            setTimeout(() => {
                roleImg.src = r.img;
                roleImg.style.opacity = '1';
                roleImg.style.transform = 'translateY(0)';
            }, 50);

            roleLabel.textContent = r.label;
            formLabel.textContent = r.label;
            fieldInput.name = r.field_name;
            roleInput.value = r.key;

            // Aktivasi
            const isAdmin = r.has_activation;
            activationGroup.style.display = isAdmin ? 'block' : 'none';
            activationCode.required = isAdmin;
            if (!isAdmin) activationCode.value = '';

            // Toko
            tokoGroup.style.display = r.has_toko ? 'block' : 'none';
            tokoSelect.required = r.has_toko ?? false;

            // Toggle siswa/guru
            if (r.has_toggle) {
                pembeliToggle.style.display = 'block';
                setPembeli('siswa'); // reset ke siswa tiap ganti role
            } else {
                pembeliToggle.style.display = 'none';
                fieldLabel.textContent = r.field_label;
                fieldInput.placeholder = r.placeholder;
            }
        }

        function nextRole() {
            current = (current + 1) % roles.length;
            localStorage.setItem('lastRole', roles[current].key);
            updateRole();
        }

        function prevRole() {
            current = (current - 1 + roles.length) % roles.length;
            localStorage.setItem('lastRole', roles[current].key);
            updateRole();
        }

        function toggleVis(inputId, btn) {
            const inp = document.getElementById(inputId);
            const eyeOpen = btn.querySelector('.eye-open');
            const eyeClosed = btn.querySelector('.eye-closed');
            const isHidden = inp.type === 'password';

            inp.type = isHidden ? 'text' : 'password';
            eyeOpen.style.display = isHidden ? 'none' : 'block';
            eyeClosed.style.display = isHidden ? 'block' : 'none';
            btn.title = isHidden ? 'Sembunyikan' : 'Tampilkan';
        }

        // Swipe gesture
        let touchStartY = 0;
        rolePanel.addEventListener('touchstart', e => { touchStartY = e.touches[0].clientY; });
        rolePanel.addEventListener('touchend', e => {
            const diff = touchStartY - e.changedTouches[0].clientY;
            if (Math.abs(diff) > 30) diff > 0 ? nextRole() : prevRole();
        });

        // Auto fade error
        const alertEl = document.querySelector('.alert-error');
        if (alertEl) {
            setTimeout(() => {
                alertEl.style.transition = 'opacity 0.8s ease';
                alertEl.style.opacity = '0';
                setTimeout(() => alertEl.remove(), 800);
            }, 3000);
        }

        // Restore role dari localStorage
        const savedRole = localStorage.getItem('lastRole');
        if (savedRole) {
            const idx = roles.findIndex(r => r.key === savedRole);
            if (idx !== -1) current = idx;
        }

        updateRole();
    </script>

</body>

</html>