<?php
require_once __DIR__ . '/includes/config.php';
requireLogin();

if ($_SESSION['role'] === 'admin') {
    header('Location: admin/dashboard.php'); exit;
}

$db  = getDB();
$uid = $_SESSION['user_id'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = trim($_POST['name'] ?? '');
    $phone  = trim($_POST['phone'] ?? '');
    $dept   = trim($_POST['department'] ?? '');
    $pos    = trim($_POST['position'] ?? '');
    $newpwd = $_POST['new_password'] ?? '';

    $stmt = $db->prepare('UPDATE users SET name=?, phone=?, department=?, position=? WHERE id=?');
    $stmt->execute([$name, $phone, $dept, $pos, $uid]);

    if ($newpwd) {
        $db->prepare('UPDATE users SET password=? WHERE id=?')
           ->execute([password_hash($newpwd, PASSWORD_BCRYPT), $uid]);
    }

    // Refresh session
    $u = $db->prepare('SELECT * FROM users WHERE id=?');
    $u->execute([$uid]);
    $_SESSION['user'] = $u->fetch();
    $msg = 'Profil berhasil diperbarui!';
}

$user = currentUser();

function initials(string $name): string {
    $parts = explode(' ', trim($name));
    return strtoupper(substr($parts[0],0,1) . (isset($parts[1]) ? substr($parts[1],0,1) : ''));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Profil — <?= APP_NAME ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <div class="sidebar-brand"><h2>🤝 <?= APP_NAME ?></h2><span>Portal Karyawan Baru</span></div>
    <nav class="sidebar-nav">
      <a href="dashboard.php"><span class="icon">🏠</span> Dashboard</a>
      <a href="tasks.php"><span class="icon">✅</span> Tugas Onboarding</a>
      <a href="messages.php"><span class="icon">💬</span> Pesan Buddy</a>
      <a href="profile.php" class="active"><span class="icon">👤</span> Profil Saya</a>
    </nav>
    <div class="sidebar-footer">
      <strong><?= htmlspecialchars($user['name']) ?></strong>
      <?= htmlspecialchars($user['department'] ?: '') ?>
      <br><br>
      <form method="POST" action="auth.php"><input type="hidden" name="action" value="logout">
        <button class="btn btn-outline btn-sm" style="width:100%">Keluar</button>
      </form>
    </div>
  </aside>

  <main class="main">
    <div class="topbar"><h1>Profil Saya</h1></div>

    <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:200px 1fr;gap:20px">

      <!-- Avatar -->
      <div class="card" style="text-align:center;align-self:start">
        <div style="width:80px;height:80px;border-radius:50%;background:var(--primary);color:#fff;
             display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:700;margin:0 auto 12px">
          <?= initials($user['name']) ?>
        </div>
        <strong><?= htmlspecialchars($user['name']) ?></strong><br>
        <span class="badge badge-primary" style="margin-top:6px">
          <?= $user['role'] === 'buddy' ? 'Buddy' : 'Karyawan Baru' ?>
        </span>
        <?php if ($user['start_date']): ?>
        <p style="font-size:12px;color:#94a3b8;margin-top:10px">
          Bergabung<br><?= date('d M Y', strtotime($user['start_date'])) ?>
        </p>
        <?php endif; ?>
      </div>

      <!-- Edit form -->
      <div class="card">
        <div class="card-title">Edit Profil</div>
        <form method="POST">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
            <div class="form-group">
              <label>Nama Lengkap</label>
              <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
            </div>
            <div class="form-group">
              <label>Email</label>
              <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled style="background:#f8fafc">
            </div>
            <div class="form-group">
              <label>Nomor Telepon</label>
              <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="08xx-xxxx-xxxx">
            </div>
            <div class="form-group">
              <label>Departemen</label>
              <select name="department" class="form-control">
                <?php foreach (['Engineering','Marketing','Finance','Human Resources','Operations','Product','Sales'] as $d): ?>
                <option <?= $user['department'] === $d ? 'selected' : '' ?>><?= $d ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Posisi / Jabatan</label>
              <input type="text" name="position" class="form-control" value="<?= htmlspecialchars($user['position'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label>Password Baru (kosongkan jika tidak ingin diubah)</label>
              <input type="password" name="new_password" class="form-control" placeholder="Min. 8 karakter" minlength="8">
            </div>
          </div>
          <button type="submit" class="btn btn-primary" style="margin-top:4px">Simpan Perubahan</button>
        </form>
      </div>
    </div>
  </main>
</div>
</body>
</html>
