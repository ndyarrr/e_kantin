<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../controllers/auth.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = register();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Register - e-Kantin</title>
</head>

<body>

    <h2>Daftar Akun</h2>

    <?php if ($error): ?>
        <p style="color:red"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Username</label><br>
        <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required><br><br>

        <label>Password</label><br>
        <input type="password" name="password" required><br><br>

        <label>Konfirmasi Password</label><br>
        <input type="password" name="konfirm_password" required><br><br>

        <label>Daftar sebagai</label><br>
        <select name="role" id="role" onchange="cekRole()">
            <option value="siswa">Siswa</option>
            <option value="guru">Guru</option>
        </select><br><br>

        <div id="kode_box" style="display:none">
            <label>Kode Aktivasi</label><br>
            <input type="text" name="kode_aktivasi"><br><br>
        </div>

        <script>
            function cekRole() {
                const role = document.getElementById('role').value;
                const kodeBox = document.getElementById('kode_box');
                kodeBox.style.display = role === 'guru' ? 'block' : 'none';
            }
        </script>

        <button type="submit">Daftar</button>
    </form>

    <br>
    <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>

</body>

</html>