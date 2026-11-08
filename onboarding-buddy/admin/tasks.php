<?php
require_once __DIR__ . '/../includes/config.php';
requireRole('admin');

$db = getDB();

// Handle add/delete task template
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';

    if ($act === 'add') {
        $stmt = $db->prepare('INSERT INTO task_templates (title, description, category, day_target, created_by) VALUES (?,?,?,?,?)');
        $stmt->execute([
            trim($_POST['title']),
            trim($_POST['description']),
            trim($_POST['category']),
            (int)$_POST['day_target'],
            $_SESSION['user_id']
        ]);
        flash('success', 'Template tugas berhasil ditambahkan.');
    }

    if ($act === 'delete') {
        $db->prepare('DELETE FROM task_templates WHERE id=?')->execute([(int)$_POST['task_id']]);
        flash('success', 'Template tugas dihapus.');
    }

    header('Location: tasks.php'); exit;
}

$tasks = $db->query('SELECT * FROM task_templates ORDER BY day_target, id')->fetchAll();
$user  = currentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Template Tugas — <?= APP_NAME ?></title>
<link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <div class="sidebar-brand"><h2>🤝 <?= APP_NAME ?></h2><span>Panel Admin</span></div>
    <nav class="sidebar-nav">
      <a href="dashboard.php"><span class="icon">🏠</span> Dashboard</a>
      <a href="match_buddy.php"><span class="icon">🔗</span> Assign Buddy</a>
      <a href="users.php"><span class="icon">👥</span> Kelola Pengguna</a>
      <a href="tasks.php" class="active"><span class="icon">✅</span> Template Tugas</a>
    </nav>
    <div class="sidebar-footer"><strong><?= htmlspecialchars($user['name']) ?></strong>Administrator</div>
  </aside>

  <main class="main">
    <div class="topbar"><h1>Template Tugas Onboarding</h1></div>

    <?php if ($ok = flash('success')): ?><div class="alert alert-success"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 360px;gap:20px">

      <!-- Task list -->
      <div class="card">
        <div class="card-title">Semua Template Tugas (<?= count($tasks) ?>)</div>
        <table>
          <thead><tr><th>#</th><th>Judul Tugas</th><th>Kategori</th><th>Hari ke-</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($tasks as $i => $t): ?>
            <tr>
              <td style="color:#94a3b8"><?= $i+1 ?></td>
              <td>
                <strong><?= htmlspecialchars($t['title']) ?></strong><br>
                <small style="color:#94a3b8"><?= htmlspecialchars($t['description']) ?></small>
              </td>
              <td><span class="badge badge-info"><?= htmlspecialchars($t['category']) ?></span></td>
              <td>Hari <?= $t['day_target'] ?></td>
              <td>
                <form method="POST" onsubmit="return confirm('Hapus tugas ini?')">
                  <input type="hidden" name="act" value="delete">
                  <input type="hidden" name="task_id" value="<?= $t['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Add task form -->
      <div class="card" style="align-self:start">
        <div class="card-title">Tambah Template Baru</div>
        <form method="POST">
          <input type="hidden" name="act" value="add">
          <div class="form-group">
            <label>Judul Tugas *</label>
            <input type="text" name="title" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Deskripsi</label>
            <textarea name="description" class="form-control" rows="3" style="resize:vertical"></textarea>
          </div>
          <div class="form-group">
            <label>Kategori</label>
            <input type="text" name="category" class="form-control" placeholder="IT, HR, Sosial, dll">
          </div>
          <div class="form-group">
            <label>Target Hari ke-</label>
            <input type="number" name="day_target" class="form-control" value="1" min="1">
          </div>
          <button type="submit" class="btn btn-primary btn-block">+ Tambah Tugas</button>
        </form>
      </div>
    </div>
  </main>
</div>
</body>
</html>
