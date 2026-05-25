<?php
if (session_status() === PHP_SESSION_NONE)
    session_start();
require_once __DIR__ . '/../config/database.php';
catatLog($conn, 'Logout', 'User berhasil logout');
session_destroy();
header('Location: login.php');
exit; 