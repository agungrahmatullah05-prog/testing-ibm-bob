<?php
require_once __DIR__ . '/includes/config.php';

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login — <?= APP_NAME ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="logo">
      <h1>🤝 <?= APP_NAME ?></h1>
      <p>Selamat datang! Masuk untuk melanjutkan</p>
    </div>

    <?php if ($err = flash('error')): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>
    <?php if ($ok = flash('success')): ?>
      <div class="alert alert-success"><?= htmlspecialchars($ok) ?></div>
    <?php endif; ?>

    <form method="POST" action="auth.php">
      <input type="hidden" name="action" value="login">
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" class="form-control" placeholder="nama@perusahaan.com" required autofocus>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Masuk</button>
    </form>

    <p style="text-align:center;margin-top:20px;font-size:13px;color:#64748b">
      Belum punya akun? <a href="register.php">Daftar sebagai Karyawan Baru</a>
    </p>
    <p style="text-align:center;margin-top:10px;font-size:12px;color:#94a3b8">
      Demo: <code>admin@company.com</code> / <code>password</code>
    </p>
  </div>
</div>
</body>
</html>
