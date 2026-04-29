<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

$error = null;
$sukses = null;

// Tambah siswa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    $nama = trim($_POST['nama']);
    $nisn = trim($_POST['nisn']);
    $pass = password_hash($nisn, PASSWORD_BCRYPT); // default password = nisn
    $kelas = trim($_POST['kelas']);

    if (empty($nama) || empty($nisn) || empty($kelas)) {
        $error = "Semua kolom wajib diisi.";
    } else {
        $namaEsc = mysqli_real_escape_string($conn, $nama);
        $nisnEsc = mysqli_real_escape_string($conn, $nisn);
        $kelasEsc = mysqli_real_escape_string($conn, $kelas);

        // Cek nama sudah ada
        $cek = mysqli_query($conn, "SELECT id FROM users WHERE nama = '$namaEsc' LIMIT 1");
        if (mysqli_num_rows($cek) > 0) {
            $error = "Username sudah terdaftar.";
        } else {
            // Insert ke users
            mysqli_query($conn, "INSERT INTO users (nama, password, role) VALUES ('$namaEsc', '$pass', 'siswa')");
            $user_id = mysqli_insert_id($conn);

            // Insert ke siswa
            mysqli_query($conn, "INSERT INTO siswa (user_id, nisn, kelas) VALUES ('$user_id', '$nisnEsc', '$kelasEsc')");
            $sukses = "Siswa berhasil ditambahkan.";
        }
    }
}

// Hapus siswa
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM users WHERE id = '$id' AND role = 'siswa'");
    header('Location: siswa.php?hapus_sukses=1');
    exit;
}

// Ambil semua siswa
$result = mysqli_query($conn, "
    SELECT u.id, u.nama, u.created_at, s.nisn, s.kelas
    FROM users u
    LEFT JOIN siswa s ON u.id = s.user_id
    WHERE u.role = 'siswa'
    ORDER BY u.created_at DESC
");
$siswas = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kelola Siswa - e-Kantin</title>
</head>

<body>

    <h2>Kelola Siswa</h2>
    <a href="dashboard.php">← Kembali</a>

    <hr>

    <h3>Tambah Siswa</h3>

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
        <p style="color:green">Siswa berhasil dihapus.</p>
    <?php endif; ?>

    <form method="POST">
        <label>Nama</label><br>
        <input type="text" name="nama" required><br><br>

        <label>NISN</label><br>
        <input type="text" name="nisn" required><br><br>

        <label>Kelas</label><br>
        <input type="text" name="kelas" required><br><br>

        <small>*Password default = NISN</small><br><br>

        <button type="submit" name="tambah">Tambah Siswa</button>
    </form>

    <hr>

    <h3>Daftar Siswa</h3>

    <?php if (empty($siswas)): ?>
        <p>Belum ada siswa.</p>
    <?php else: ?>
        <table border="1" cellpadding="6">
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>NISN</th>
                <th>Kelas</th>
                <th>Terdaftar</th>
                <th>Aksi</th>
            </tr>
            <?php foreach ($siswas as $i => $s): ?>
                <tr>
                    <td>
                        <?= $i + 1 ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($s['nama']) ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($s['nisn']) ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($s['kelas']) ?>
                    </td>
                    <td>
                        <?= $s['created_at'] ?>
                    </td>
                    <td><a href="siswa.php?hapus=<?= $s['id'] ?>" onclick="return confirm('Hapus siswa ini?')">Hapus</a></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

</body>

</html>