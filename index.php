<?php
session_start();

// Kalau udah login, langsung redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>e-Kantin</title>
</head>

<body>

    <h1>e-Kantin</h1>
    <p>Selamat datang di e-Kantin SMKN 1 Boyolangu</p>

    <a href="login.php">Login</a> |
    <a href="register.php">Daftar</a>

</body>

</html>