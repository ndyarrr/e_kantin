<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/../controllers/auth.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = login();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Login - e-Kantin</title>
</head>

<body>

    <h2>Login</h2>

    <?php if ($error): ?>
        <p style="color:red">
            <?= htmlspecialchars($error) ?>
        </p>
    <?php endif; ?>

    <form method="POST">
        <label>Username</label><br>
        <input type="text" name="username" required><br><br>

        <label>Password</label><br>
        <input type="password" name="password" required><br><br>

        <label>Role</label><br>
        <select name="role" id="role" onchange="cekRole()">
            <option value="siswa">Siswa</option>
            <option value="guru">Guru</option>
            <option value="kantin">Penjual Kantin</option>
            <option value="admin">Admin</option>
        </select><br><br>

        <div id="nisn_box" style="display:none">
            <label>NISN</label><br>
            <input type="text" name="nisn"><br><br>
        </div>

        <div id="nuptk_box" style="display:none">
            <label>NUPTK</label><br>
            <input type="text" name="nuptk"><br><br>
        </div>

        <div id="lapak_box" style="display:none">
            <label>Nomor Lapak</label><br>
            <input type="text" name="nomor_lapak"><br><br>
        </div>

        <div id="kode_box" style="display:none">
            <label>Kode Aktivasi</label><br>
            <input type="text" name="kode_aktivasi"><br><br>
        </div>

        <button type="submit">Login</button>
    </form>

    <script>
        function cekRole() {
            const role = document.getElementById('role').value;
            document.getElementById('nisn_box').style.display = role === 'siswa' ? 'block' : 'none';
            document.getElementById('nuptk_box').style.display = role === 'guru' ? 'block' : 'none';
            document.getElementById('lapak_box').style.display = role === 'kantin' ? 'block' : 'none';
            document.getElementById('kode_box').style.display = role === 'admin' ? 'block' : 'none';
        }
        cekRole();
    </script>

</body>

</html>