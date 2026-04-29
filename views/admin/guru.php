<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

$error = null;
$sukses = null;

// Tambah guru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    $nama = trim($_POST['nama']);
    $nuptk = trim($_POST['nuptk']);
    $pass = password_hash($_POST['password'], PASSWORD_BCRYPT);

    if (empty($nama) || empty($nuptk) || empty($_POST['password'])) {
        $error = "Semua kolom wajib diisi.";
    } else {
        $namaEsc = mysqli_real_escape_string($conn, $nama);
        $nuptkEsc = mysqli_real_escape_string($conn, $nuptk);

        $cek = mysqli_query($conn, "SELECT id FROM users WHERE nama = '$namaEsc' LIMIT 1");
        if (mysqli_num_rows($cek) > 0) {
            $error = "Username sudah terdaftar.";
        } else {
            mysqli_query($conn, "INSERT INTO users (nama, password, role, nuptk) VALUES ('$namaEsc', '$pass', 'guru', '$nuptkEsc')");
            $sukses = "Guru berhasil ditambahkan.";
        }
    }
}

// Hapus guru
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM users WHERE id = '$id' AND role = 'guru'");
    header('Location: guru.php?hapus_sukses=1');
    exit;
}

// Ambil semua guru
$result = mysqli_query($conn, "SELECT * FROM users WHERE role = 'guru' ORDER BY created_at DESC");
$gurus = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kelola Guru - e-Kantin</title>
</head>

<body>

    <h2>Kelola Guru</h2>
    <a href="dashboard.php">← Kembali</a>

    <hr>

    <h3>Tambah Guru</h3>

    <?php if ($error): ?>
        <p style="color:red">
            <?= htmlspecialchars($error) ?>
        </p>
    <?php endif; ?>
    <?php if ($sukses): ?>
        <p style="color:green">
            <?= htmlspecialchars($sukses) ?>
        </p>
    <?php endif; ?>
    <?php if (isset($_GET['hapus_sukses'])): ?>
        <p style="color:green">Guru berhasil dihapus.</p>
    <?php endif; ?>

    <form method="POST">
        <label>Nama</label><br>
        <input type="text" name="nama" required><br><br>

        <label>NUPTK</label><br>
        <input type="text" name="nuptk" required><br><br>

        <label>Password</label><br>
        <input type="password" name="password" required><br><br>

        <button type="submit" name="tambah">Tambah Guru</button>
    </form>

    <hr>

    <h3>Daftar Guru</h3>

    <?php if (empty($gurus)): ?>
        <p>Belum ada guru.</p>
    <?php else: ?>
        <table border="1" cellpadding="6">
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>NUPTK</th>
                <th>Terdaftar</th>
                <th>Aksi</th>
            </tr>
            <?php foreach ($gurus as $i => $g): ?>
                <tr>
                    <td>
                        <?= $i + 1 ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($g['nama']) ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($g['nuptk']) ?>
                    </td>
                    <td>
                        <?= $g['created_at'] ?>
                    </td>
                    <td><a href="guru.php?hapus=<?= $g['id'] ?>" onclick="return confirm('Hapus guru ini?')">Hapus</a></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

</body>

</html>