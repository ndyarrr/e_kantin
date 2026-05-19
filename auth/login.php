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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - e-Kantin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            background: #f0f4f8;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .page-wrapper {
            width: 100%;
            max-width: 440px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.5rem;
        }

        .brand { text-align: center; }

        .brand-icon {
            width: 64px; height: 64px;
            background: linear-gradient(135deg, #1a5276, #148f77);
            border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 0.75rem;
            font-size: 2rem;
            box-shadow: 0 8px 24px rgba(26,82,118,0.3);
        }

        .brand h1 { font-size: 1.75rem; font-weight: 700; color: #1a5276; }
        .brand p { font-size: 0.85rem; color: #7f8c8d; margin-top: 0.2rem; }

        .card {
            width: 100%;
            background: #fff;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            border: 1px solid #e8ecef;
        }

        .card-title { font-size: 1.15rem; font-weight: 600; color: #1c2833; margin-bottom: 1.5rem; }

        .alert-error {
            background: #fdf2f2;
            border: 1px solid #f5c6c6;
            border-left: 4px solid #e74c3c;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            color: #c0392b;
            margin-bottom: 1.25rem;
        }

        .form-group { margin-bottom: 1.1rem; }

        .form-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: #555;
            margin-bottom: 0.4rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 1.5px solid #dde1e7;
            border-radius: 10px;
            font-size: 0.95rem;
            color: #1c2833;
            background: #fff;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            appearance: none;
            -webkit-appearance: none;
        }

        .form-group select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23888' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            padding-right: 2.5rem;
            cursor: pointer;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #1a5276;
            box-shadow: 0 0 0 3px rgba(26,82,118,0.12);
        }

        .form-group input::placeholder { color: #aab0bb; }

        .dynamic-field {
            overflow: hidden;
            max-height: 0;
            opacity: 0;
            transition: max-height 0.3s ease, opacity 0.25s ease, margin 0.3s ease;
            margin-bottom: 0;
        }

        .dynamic-field.visible {
            max-height: 100px;
            opacity: 1;
            margin-bottom: 1.1rem;
        }

        .dynamic-field label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: #555;
            margin-bottom: 0.4rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .dynamic-field input {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 1.5px solid #dde1e7;
            border-radius: 10px;
            font-size: 0.95rem;
            color: #1c2833;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .dynamic-field input:focus {
            border-color: #1a5276;
            box-shadow: 0 0 0 3px rgba(26,82,118,0.12);
        }

        .dynamic-field input::placeholder { color: #aab0bb; }

        .btn-login {
            width: 100%;
            padding: 0.85rem;
            background: linear-gradient(135deg, #1a5276 0%, #148f77 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 0.5rem;
            transition: opacity 0.2s, transform 0.1s, box-shadow 0.2s;
            box-shadow: 0 4px 16px rgba(26,82,118,0.3);
        }

        .btn-login:hover { opacity: 0.92; }
        .btn-login:active { transform: scale(0.98); }

        .footer { font-size: 0.78rem; color: #aaa; text-align: center; }
    </style>
</head>
<body>

<div class="page-wrapper">

    <div class="brand">
        <div class="brand-icon">🍽️</div>
        <h1>e-Kantin</h1>
        <p>Sistem Manajemen Kantin Digital</p>
    </div>

    <div class="card">
        <p class="card-title">Masuk ke Akun</p>

        <?php if ($error): ?>
            <div class="alert-error">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Masukkan username" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Masukkan password" required>
            </div>

            <div class="form-group">
                <label>Role</label>
                <select name="role" id="role" onchange="cekRole()">
                    <option value="siswa">Siswa</option>
                    <option value="guru">Guru</option>
                    <option value="kantin">Penjual Kantin</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <div id="nisn_box" class="dynamic-field">
                <label>NISN</label>
                <input type="text" name="nisn" placeholder="Nomor Induk Siswa Nasional">
            </div>

            <div id="nuptk_box" class="dynamic-field">
                <label>NUPTK</label>
                <input type="text" name="nuptk" placeholder="Nomor Unik Pendidik & Tenaga Kependidikan">
            </div>

            <div id="lapak_box" class="dynamic-field">
                <label>Nomor Lapak</label>
                <input type="text" name="nomor_lapak" placeholder="Nomor lapak kantin">
            </div>

            <div id="kode_box" class="dynamic-field">
                <label>Kode Aktivasi</label>
                <input type="text" name="kode_aktivasi" placeholder="Kode aktivasi admin">
            </div>

            <button type="submit" class="btn-login">Masuk</button>

        </form>
    </div>

    <p class="footer">&copy; <?= date('Y') ?> e-Kantin. All rights reserved.</p>

</div>

<script>
    function cekRole() {
        const role = document.getElementById('role').value;
        const map = {
            siswa:  'nisn_box',
            guru:   'nuptk_box',
            kantin: 'lapak_box',
            admin:  'kode_box',
        };
        Object.values(map).forEach(id => {
            document.getElementById(id).classList.remove('visible');
        });
        if (map[role]) {
            document.getElementById(map[role]).classList.add('visible');
        }
    }
    cekRole();
</script>

</body>
</html>