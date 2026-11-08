<?php
require_once __DIR__ . '/includes/config.php';

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard.php'); exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Daftar — <?= APP_NAME ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card" style="max-width:500px">
    <div class="logo">
      <h1>🤝 Daftar Karyawan Baru</h1>
      <p>Isi data berikut untuk membuat akun onboarding</p>
    </div>

    <?php if ($err = flash('error')): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <form method="POST" action="auth.php">
      <input type="hidden" name="action" value="register">

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group">
          <label>Nama Lengkap *</label>
          <input type="text" name="name" class="form-control" placeholder="John Doe" required>
        </div>
        <div class="form-group">
          <label>Email *</label>
          <input type="email" name="email" class="form-control" placeholder="john@company.com" required>
        </div>
        <div class="form-group">
          <label>Departemen</label>
          <select name="department" class="form-control">
            <option value="">-- Pilih Departemen --</option>
            <option>Engineering</option>
            <option>Marketing</option>
            <option>Finance</option>
            <option>Human Resources</option>
            <option>Operations</option>
            <option>Product</option>
            <option>Sales</option>
          </select>
        </div>
        <div class="form-group">
          <label>Posisi / Jabatan</label>
          <input type="text" name="position" class="form-control" placeholder="Junior Developer">
        </div>
        <div class="form-group">
          <label>Tanggal Mulai Kerja</label>
          <input type="date" name="start_date" class="form-control">
        </div>
        <div class="form-group">
          <label>Password *</label>
          <input type="password" name="password" class="form-control" placeholder="Min. 8 karakter" required minlength="8">
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-block" style="margin-top:4px">Buat Akun</button>
    </form>

    <p style="text-align:center;margin-top:20px;font-size:13px;color:#64748b">
      Sudah punya akun? <a href="login.php">Masuk di sini</a>
    </p>
  </div>
</div>
</body>
</html>
