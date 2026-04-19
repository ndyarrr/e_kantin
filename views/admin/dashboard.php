<?php

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin</title>
</head>

<body>

    <h2>Selamat datang, <?= htmlspecialchars($_SESSION['user_nama']) ?>!</h2>
    <p>Ini adalah dashboard admin.</p>

    <a href="../../auth/login.php">Logout</a>

</body>

</html>