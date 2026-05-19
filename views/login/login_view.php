<?php
// File ini diletakkan di: views/login_view.php
// Dipanggil dari: auth/login.php

$error = $error ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - E-Kantin</title>
  <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>

  <div class="login-wrapper">

    <div class="login-brand">
      <img src="../assets/img/logo.png" alt="Logo E-Kantin" onerror="this.style.display='none'">
      <h1>E-Kantin</h1>
      <p>Sistem Manajemen Kantin Digital</p>
    </div>

    <div class="login-card">
      <h2>Masuk ke Akun</h2>

      <?php if (!empty($error)): ?>
        <div class="alert alert-error">
          <span>&#9888;</span> <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form action="../auth/login.php" method="POST">

        <div class="form-group">
          <label for="username">Username</label>
          <input
            type="text"
            id="username"
            name="username"
            placeholder="Masukkan username"
            required
            autocomplete="username"
          >
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="input-password">
            <input
              type="password"
              id="password"
              name="password"
              placeholder="Masukkan password"
              required
              autocomplete="current-password"
            >
            <button type="button" class="toggle-password" onclick="togglePassword()">
              &#128065;
            </button>
          </div>
        </div>

        <button type="submit" class="btn-login">Masuk</button>

      </form>
    </div>

    <p class="login-footer">&copy; <?= date('Y') ?> E-Kantin. All rights reserved.</p>

  </div>

  <script>
    function togglePassword() {
      const input = document.getElementById('password');
      input.type = input.type === 'password' ? 'text' : 'password';
    }
  </script>

</body>
</html>