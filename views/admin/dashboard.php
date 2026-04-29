<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - e-Kantin</title>
</head>

<body>

    <h2>Dashboard Admin</h2>
    <p>Halo,
        <?= htmlspecialchars($_SESSION['user_nama']) ?>!
    </p>
    <a href="../../auth/logout.php">Logout</a>

    <hr>

    <ul>
        <li><a href="siswa.php">Kelola Siswa</a></li>
        <li><a href="guru.php">Kelola Guru</a></li>
        <li><a href="kantin.php">Kelola Kantin</a></li>
    </ul>

</body>

</html>