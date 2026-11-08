<?php
require_once __DIR__ . '/../includes/config.php';
requireRole('admin');

$db = getDB();

// Stats
$total_hires   = $db->query("SELECT COUNT(*) FROM users WHERE role='new_hire'")->fetchColumn();
$total_buddies = $db->query("SELECT COUNT(*) FROM users WHERE role='buddy'")->fetchColumn();
$assigned      = $db->query("SELECT COUNT(*) FROM buddy_assignments WHERE status='active'")->fetchColumn();
$unassigned    = $total_hires - $assigned;

// Recent new hires
$hires = $db->query("
    SELECT u.*, ba.buddy_id,
           b.name AS buddy_name,
           (SELECT COUNT(*) FROM onboarding_progress op WHERE op.new_hire_id=u.id AND op.status='done') AS done_count,
           (SELECT COUNT(*) FROM onboarding_progress op WHERE op.new_hire_id=u.id) AS total_tasks
    FROM users u
    LEFT JOIN buddy_assignments ba ON ba.new_hire_id = u.id AND ba.status='active'
    LEFT JOIN users b ON b.id = ba.buddy_id
    WHERE u.role='new_hire'
    ORDER BY u.created_at DESC
")->fetchAll();

$user = currentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Dashboard — <?= APP_NAME ?></title>
<link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="layout">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-brand">
      <h2>🤝 <?= APP_NAME ?></h2>
      <span>Panel Admin</span>
    </div>
    <nav class="sidebar-nav">
      <a href="dashboard.php" class="active"><span class="icon">🏠</span> Dashboard</a>
      <a href="match_buddy.php"><span class="icon">🔗</span> Assign Buddy</a>
      <a href="users.php"><span class="icon">👥</span> Kelola Pengguna</a>
      <a href="tasks.php"><span class="icon">✅</span> Template Tugas</a>
      <a href="../auth.php?action=logout" onclick="this.closest('form')?.submit()" style="margin-top:auto">
        <span class="icon">🚪</span> Keluar
      </a>
    </nav>
    <div class="sidebar-footer">
      <strong><?= htmlspecialchars($user['name']) ?></strong>
      Administrator
    </div>
  </aside>

  <!-- Main -->
  <main class="main">
    <div class="topbar">
      <h1>Dashboard Admin</h1>
      <a href="match_buddy.php" class="btn btn-primary">🔗 Assign Buddy</a>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="label">Karyawan Baru</div>
        <div class="value"><?= $total_hires ?></div>
        <div class="sub">Terdaftar</div>
      </div>
      <div class="stat-card">
        <div class="label">Buddy Aktif</div>
        <div class="value"><?= $total_buddies ?></div>
        <div class="sub">Tersedia</div>
      </div>
      <div class="stat-card">
        <div class="label">Sudah Dipasangkan</div>
        <div class="value"><?= $assigned ?></div>
        <div class="sub">dengan buddy</div>
      </div>
      <div class="stat-card">
        <div class="label">Belum Dipasangkan</div>
        <div class="value" style="color:var(--danger)"><?= $unassigned ?></div>
        <div class="sub">perlu ditindaklanjuti</div>
      </div>
    </div>

    <!-- New Hire Table -->
    <div class="card">
      <div class="card-title">Daftar Karyawan Baru</div>
      <table>
        <thead>
          <tr>
            <th>Nama</th>
            <th>Departemen</th>
            <th>Mulai Kerja</th>
            <th>Buddy</th>
            <th>Progress Onboarding</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($hires as $h): ?>
          <?php
            $pct = $h['total_tasks'] > 0 ? round(($h['done_count'] / $h['total_tasks']) * 100) : 0;
          ?>
          <tr>
            <td>
              <strong><?= htmlspecialchars($h['name']) ?></strong><br>
              <small style="color:#94a3b8"><?= htmlspecialchars($h['email']) ?></small>
            </td>
            <td><?= htmlspecialchars($h['department'] ?: '-') ?></td>
            <td><?= $h['start_date'] ? date('d M Y', strtotime($h['start_date'])) : '-' ?></td>
            <td>
              <?php if ($h['buddy_name']): ?>
                <span class="badge badge-success"><?= htmlspecialchars($h['buddy_name']) ?></span>
              <?php else: ?>
                <span class="badge badge-danger">Belum diassign</span>
              <?php endif; ?>
            </td>
            <td style="min-width:130px">
              <div style="display:flex;align-items:center;gap:8px">
                <div class="progress-bar" style="flex:1">
                  <div class="progress-fill" style="width:<?= $pct ?>%"></div>
                </div>
                <span style="font-size:12px;color:#64748b;white-space:nowrap"><?= $pct ?>%</span>
              </div>
            </td>
            <td>
              <a href="match_buddy.php?hire_id=<?= $h['id'] ?>" class="btn btn-outline btn-sm">Assign</a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($hires)): ?>
          <tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:32px">Belum ada karyawan baru terdaftar</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </main>
</div>
</body>
</html>
