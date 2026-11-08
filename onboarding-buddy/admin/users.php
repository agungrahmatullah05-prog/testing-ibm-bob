<?php
require_once __DIR__ . '/../includes/config.php';
requireRole('admin');

$db = getDB();

$users = $db->query("SELECT * FROM users ORDER BY role, name")->fetchAll();
$user  = currentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kelola Pengguna — <?= APP_NAME ?></title>
<link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <div class="sidebar-brand"><h2>🤝 <?= APP_NAME ?></h2><span>Panel Admin</span></div>
    <nav class="sidebar-nav">
      <a href="dashboard.php"><span class="icon">🏠</span> Dashboard</a>
      <a href="match_buddy.php"><span class="icon">🔗</span> Assign Buddy</a>
      <a href="users.php" class="active"><span class="icon">👥</span> Kelola Pengguna</a>
      <a href="tasks.php"><span class="icon">✅</span> Template Tugas</a>
    </nav>
    <div class="sidebar-footer"><strong><?= htmlspecialchars($user['name']) ?></strong>Administrator</div>
  </aside>

  <main class="main">
    <div class="topbar">
      <h1>Kelola Pengguna</h1>
    </div>

    <div class="card">
      <div class="card-title">Semua Pengguna (<?= count($users) ?>)</div>
      <table>
        <thead>
          <tr><th>Nama</th><th>Email</th><th>Role</th><th>Departemen</th><th>Bergabung</th></tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <?php
            $roleMap = ['admin' => 'badge-primary', 'buddy' => 'badge-info', 'new_hire' => 'badge-warning'];
            $roleLabel = ['admin' => 'Admin', 'buddy' => 'Buddy', 'new_hire' => 'Karyawan Baru'];
          ?>
          <tr>
            <td><strong><?= htmlspecialchars($u['name']) ?></strong><br><small style="color:#94a3b8"><?= htmlspecialchars($u['position'] ?: '') ?></small></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><span class="badge <?= $roleMap[$u['role']] ?>"><?= $roleLabel[$u['role']] ?></span></td>
            <td><?= htmlspecialchars($u['department'] ?: '-') ?></td>
            <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
</body>
</html>
