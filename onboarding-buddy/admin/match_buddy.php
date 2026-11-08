<?php
require_once __DIR__ . '/../includes/config.php';
requireRole('admin');

$db = getDB();
$msg = '';

// Pre-selected hire
$hire_id = (int)($_GET['hire_id'] ?? 0);

// Handle assignment form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_hire  = (int)$_POST['new_hire_id'];
    $post_buddy = (int)$_POST['buddy_id'];

    if ($post_hire && $post_buddy) {
        // Upsert assignment
        $stmt = $db->prepare('INSERT INTO buddy_assignments (new_hire_id, buddy_id) VALUES (?,?)
                              ON DUPLICATE KEY UPDATE buddy_id=?, status="active", assigned_at=NOW()');
        $stmt->execute([$post_hire, $post_buddy, $post_buddy]);
        $msg = 'Buddy berhasil diassign!';
    }
}

// Load unassigned / all new hires
$hires = $db->query("
    SELECT u.*, ba.buddy_id, b.name AS buddy_name
    FROM users u
    LEFT JOIN buddy_assignments ba ON ba.new_hire_id=u.id AND ba.status='active'
    LEFT JOIN users b ON b.id=ba.buddy_id
    WHERE u.role='new_hire'
    ORDER BY ba.buddy_id IS NOT NULL, u.created_at DESC
")->fetchAll();

$buddies = $db->query("SELECT id, name, department, position FROM users WHERE role='buddy' ORDER BY name")->fetchAll();

// Count assignments per buddy
$counts = [];
$rows = $db->query("SELECT buddy_id, COUNT(*) AS cnt FROM buddy_assignments WHERE status='active' GROUP BY buddy_id")->fetchAll();
foreach ($rows as $r) $counts[$r['buddy_id']] = $r['cnt'];

$user = currentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Assign Buddy — <?= APP_NAME ?></title>
<link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <div class="sidebar-brand">
      <h2>🤝 <?= APP_NAME ?></h2>
      <span>Panel Admin</span>
    </div>
    <nav class="sidebar-nav">
      <a href="dashboard.php"><span class="icon">🏠</span> Dashboard</a>
      <a href="match_buddy.php" class="active"><span class="icon">🔗</span> Assign Buddy</a>
      <a href="users.php"><span class="icon">👥</span> Kelola Pengguna</a>
      <a href="tasks.php"><span class="icon">✅</span> Template Tugas</a>
    </nav>
    <div class="sidebar-footer">
      <strong><?= htmlspecialchars($user['name']) ?></strong>Administrator
      <br><a href="../auth.php" onclick="event.preventDefault();document.getElementById('lf').submit()">Keluar</a>
      <form id="lf" method="POST" action="../auth.php"><input type="hidden" name="action" value="logout"></form>
    </div>
  </aside>

  <main class="main">
    <div class="topbar">
      <h1>Assign Buddy ke Karyawan Baru</h1>
      <a href="dashboard.php" class="btn btn-outline">← Kembali</a>
    </div>

    <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

      <!-- Assignment form -->
      <div class="card">
        <div class="card-title">Form Penugasan</div>
        <form method="POST">
          <div class="form-group">
            <label>Karyawan Baru</label>
            <select name="new_hire_id" class="form-control" required>
              <option value="">-- Pilih Karyawan Baru --</option>
              <?php foreach ($hires as $h): ?>
              <option value="<?= $h['id'] ?>" <?= $hire_id == $h['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($h['name']) ?> (<?= htmlspecialchars($h['department'] ?: 'N/A') ?>)
                <?= $h['buddy_name'] ? ' ← ' . $h['buddy_name'] : ' [belum diassign]' ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Buddy yang Ditugaskan</label>
            <select name="buddy_id" class="form-control" required>
              <option value="">-- Pilih Buddy --</option>
              <?php foreach ($buddies as $b): ?>
              <option value="<?= $b['id'] ?>">
                <?= htmlspecialchars($b['name']) ?> — <?= htmlspecialchars($b['department']) ?>
                (<?= $counts[$b['id']] ?? 0 ?> mentee)
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-primary">Assign Buddy</button>
        </form>
      </div>

      <!-- Buddy workload -->
      <div class="card">
        <div class="card-title">Beban Kerja Buddy</div>
        <?php foreach ($buddies as $b): ?>
        <?php $load = $counts[$b['id']] ?? 0; ?>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f1f5f9">
          <div>
            <strong style="font-size:13.5px"><?= htmlspecialchars($b['name']) ?></strong><br>
            <small style="color:#94a3b8"><?= htmlspecialchars($b['department']) ?> · <?= htmlspecialchars($b['position']) ?></small>
          </div>
          <span class="badge <?= $load >= 3 ? 'badge-danger' : ($load >= 1 ? 'badge-warning' : 'badge-success') ?>">
            <?= $load ?> mentee
          </span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Current assignments table -->
    <div class="card" style="margin-top:20px">
      <div class="card-title">Semua Penugasan Aktif</div>
      <table>
        <thead>
          <tr><th>Karyawan Baru</th><th>Departemen</th><th>Buddy</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php foreach ($hires as $h): ?>
          <tr>
            <td><strong><?= htmlspecialchars($h['name']) ?></strong></td>
            <td><?= htmlspecialchars($h['department'] ?: '-') ?></td>
            <td><?= $h['buddy_name'] ? htmlspecialchars($h['buddy_name']) : '<span class="badge badge-muted">Belum</span>' ?></td>
            <td>
              <?php if ($h['buddy_name']): ?>
                <span class="badge badge-success">Aktif</span>
              <?php else: ?>
                <span class="badge badge-danger">Unassigned</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
</body>
</html>
