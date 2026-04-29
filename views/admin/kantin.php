<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

$error = null;
$sukses = null;

// Tambah kantin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    $nama = trim($_POST['nama']);
    $lapak = trim($_POST['nomor_lapak']);
    $pass = password_hash($_POST['password'], PASSWORD_BCRYPT);

    if (empty($nama) || empty($lapak) || empty($_POST['password'])) {
        $error = "Semua kolom wajib diisi.";
    } else {
        $namaEsc = mysqli_real_escape_string($conn, $nama);
        $lapakEsc = mysqli_real_escape_string($conn, $lapak);

        $cek = mysqli_query($conn, "SELECT id FROM users WHERE nama = '$namaEsc' LIMIT 1");
        if (mysqli_num_rows($cek) > 0) {
            $error = "Username sudah terdaftar.";
        } else {
            $cekLapak = mysqli_query($conn, "SELECT id FROM users WHERE nomor_lapak = '$lapakEsc' LIMIT 1");
            if (mysqli_num_rows($cekLapak) > 0) {
                $error = "Nomor lapak sudah dipakai.";
            } else {
                mysqli_query($conn, "INSERT INTO users (nama, password, role, nomor_lapak) VALUES ('$namaEsc', '$pass', 'kantin', '$lapakEsc')");
                $sukses = "Penjual kantin berhasil ditambahkan.";
            }
        }
    }
}

// Hapus kantin
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM users WHERE id = '$id' AND role = 'kantin'");
    header('Location: kantin.php?hapus_sukses=1');
    exit;
}

// Ambil semua kantin
$result = mysqli_query($conn, "SELECT * FROM users WHERE role = 'kantin' ORDER BY created_at DESC");
$kantins = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kelola Kantin - e-Kantin</title>
</head>

<body>

    <h2>Kelola Kantin</h2>
    <a href="dashboard.php">← Kembali</a>

    <hr>

    <h3>Tambah Penjual Kantin</h3>

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
        <p style="color:green">Penjual kantin berhasil dihapus.</p>
    <?php endif; ?>

    <form method="POST">
        <label>Nama</label><br>
        <input type="text" name="nama" required><br><br>

        <label>Nomor Lapak</label><br>
        <input type="text" name="nomor_lapak" required><br><br>

        <label>Password</label><br>
        <input type="password" name="password" required><br><br>

        <button type="submit" name="tambah">Tambah Penjual</button>
    </form>

    <hr>

    <h3>Daftar Penjual Kantin</h3>

    <?php if (empty($kantins)): ?>
        <p>Belum ada penjual kantin.</p>
    <?php else: ?>
        <table border="1" cellpadding="6">
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>Nomor Lapak</th>
                <th>Terdaftar</th>
                <th>Aksi</th>
            </tr>
            <?php foreach ($kantins as $i => $k): ?>
                <tr>
                    <td>
                        <?= $i + 1 ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($k['nama']) ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($k['nomor_lapak']) ?>
                    </td>
                    <td>
                        <?= $k['created_at'] ?>
                    </td>
                    <td><a href="kantin.php?hapus=<?= $k['id'] ?>" onclick="return confirm('Hapus penjual ini?')">Hapus</a></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

</body>

</html>