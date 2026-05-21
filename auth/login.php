<?php
if (session_status() === PHP_SESSION_NONE)
    session_start();
require_once __DIR__ . '/../controllers/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/login/index.php');
    exit;
}

$error = login();

if ($error) {
    $_SESSION['login_error'] = $error;
    header('Location: ../views/login/index.php');
    exit;
}