<?php
require_once __DIR__ . '/includes/config.php';
requireLogin();

// Admin redirect
if ($_SESSION['role'] === 'admin') {
    header('Location: admin/dashboard.php'); exit;
}

$db  = getDB();
$uid = $_SESSION['user_id'];

// Get buddy assignment
$buddy = $db->prepare("
    SELECT u.* FROM buddy_assignments ba
    JOIN users u ON u.id = ba.buddy_id
    WHERE ba.new_hire_id = ? AND ba.status = 'active'
    LIMIT 1
");
$buddy->execute([$uid]);
$buddy = $buddy->fetch();

// Task progress
$tasks = $db->prepare("
    SELECT t.*, op.status, op.completed_at
    FROM task_templates t
    LEFT JOIN onboarding_progress op ON op.task_id = t.id AND op.new_hire_id = ?
    ORDER BY t.day_target, t.id
");
$tasks->execute([$uid]);
$tasks = $tasks->fetchAll();

$done_count  = count(array_filter($tasks, fn($t) => $t['status'] === 'done'));
$total_tasks = count($tasks);
$progress    = $total_tasks > 0 ? round(($done_count / $total_tasks) * 100) : 0;

// Unread messages
$unread = $db->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
$unread->execute([$uid]);
$unread = $unread->fetchColumn();

// Recent messages (with buddy)
$msgs = [];
if ($buddy) {
    $mstmt = $db->prepare("
        SELECT m.*, u.name AS sender_name FROM messages m
        JOIN users u ON u.id = m.sender_id
        WHERE (m.sender_id = ? AND m.receiver_id = ?)
           OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.sent_at DESC LIMIT 20
    ");
    $mstmt->execute([$uid, $buddy['id'], $buddy['id'], $uid]);
    $msgs = array_reverse($mstmt->fetchAll());

    // Mark as read
    $db->prepare("UPDATE messages SET is_read=1 WHERE receiver_id=? AND sender_id=?")->execute([$uid, $buddy['id']]);
}

$user = currentUser();

function initials(string $name): string {
    $parts = explode(' ', trim($name));
    return strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard — <?= APP_NAME ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="layout">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-brand">
      <h2>🤝 <?= APP_NAME ?></h2>
      <span>Portal Karyawan Baru</span>
    </div>
    <nav class="sidebar-nav">
      <a href="dashboard.php" class="active"><span class="icon">🏠</span> Dashboard</a>
      <a href="tasks.php"><span class="icon">✅</span> Tugas Onboarding</a>
      <a href="messages.php"><span class="icon">💬</span> Pesan Buddy
        <?php if ($unread > 0): ?><span class="badge badge-danger" style="margin-left:auto"><?= $unread ?></span><?php endif; ?>
      </a>
      <a href="profile.php"><span class="icon">👤</span> Profil Saya</a>
    </nav>
    <div class="sidebar-footer">
      <strong><?= htmlspecialchars($user['name']) ?></strong>
      <?= htmlspecialchars($user['department'] ?: 'Karyawan Baru') ?>
      <br><br>
      <form method="POST" action="auth.php">
        <input type="hidden" name="action" value="logout">
        <button type="submit" class="btn btn-outline btn-sm" style="width:100%">Keluar</button>
      </form>
    </div>
  </aside>

  <!-- Main -->
  <main class="main">
    <div class="topbar">
      <div>
        <h1>Selamat datang, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?>! 👋</h1>
        <p style="color:#64748b;font-size:13px;margin-top:4px">
          <?= $user['start_date'] ? 'Bergabung sejak ' . date('d M Y', strtotime($user['start_date'])) : 'Mulai perjalanan onboarding Anda' ?>
        </p>
      </div>
    </div>

    <!-- Progress overview -->
    <div class="stats-grid">
      <div class="stat-card" style="grid-column:span 2">
        <div class="label">Progress Onboarding Keseluruhan</div>
        <div style="display:flex;align-items:center;gap:16px;margin-top:8px">
          <div class="progress-bar" style="flex:1;height:12px">
            <div class="progress-fill" style="width:<?= $progress ?>%;background:<?= $progress >= 100 ? 'var(--success)' : 'var(--primary)' ?>"></div>
          </div>
          <span style="font-size:20px;font-weight:700"><?= $progress ?>%</span>
        </div>
        <div class="sub" style="margin-top:4px"><?= $done_count ?> dari <?= $total_tasks ?> tugas selesai</div>
      </div>
      <div class="stat-card">
        <div class="label">Tugas Selesai</div>
        <div class="value" style="color:var(--success)"><?= $done_count ?></div>
      </div>
      <div class="stat-card">
        <div class="label">Tugas Tersisa</div>
        <div class="value" style="color:var(--warning)"><?= $total_tasks - $done_count ?></div>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 340px;gap:20px">

      <!-- Task checklist -->
      <div class="card">
        <div class="card-title" style="display:flex;justify-content:space-between">
          <span>Tugas Onboarding</span>
          <a href="tasks.php" style="font-size:12px">Lihat semua →</a>
        </div>
        <?php foreach (array_slice($tasks, 0, 6) as $t): ?>
        <div class="task-item">
          <div class="task-check <?= $t['status'] === 'done' ? 'done' : ($t['status'] === 'in_progress' ? 'inprog' : '') ?>"
               onclick="toggleTask(<?= $t['id'] ?>, '<?= $t['status'] === 'done' ? 'pending' : 'done' ?>', this)">
            <?php if ($t['status'] === 'done'): ?>✓<?php endif; ?>
          </div>
          <div class="task-body">
            <h4 style="<?= $t['status'] === 'done' ? 'text-decoration:line-through;color:#94a3b8' : '' ?>">
              <?= htmlspecialchars($t['title']) ?>
            </h4>
            <p><?= htmlspecialchars($t['description']) ?></p>
            <div class="meta">
              <span class="badge badge-info"><?= htmlspecialchars($t['category']) ?></span>
              · Hari ke-<?= $t['day_target'] ?>
              <?php if ($t['completed_at']): ?>
                · ✓ <?= date('d M', strtotime($t['completed_at'])) ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Right column -->
      <div style="display:flex;flex-direction:column;gap:20px">

        <!-- Buddy card -->
        <div class="card">
          <div class="card-title">Buddy Anda</div>
          <?php if ($buddy): ?>
          <div class="buddy-card">
            <div class="buddy-avatar"><?= initials($buddy['name']) ?></div>
            <div class="buddy-info">
              <h3><?= htmlspecialchars($buddy['name']) ?></h3>
              <p><?= htmlspecialchars($buddy['position'] ?: 'Buddy') ?></p>
              <p><?= htmlspecialchars($buddy['department'] ?: '') ?></p>
              <?php if ($buddy['phone']): ?>
              <p>📞 <?= htmlspecialchars($buddy['phone']) ?></p>
              <?php endif; ?>
            </div>
          </div>
          <a href="messages.php" class="btn btn-primary btn-block btn-sm">💬 Kirim Pesan</a>
          <?php else: ?>
          <div style="text-align:center;padding:24px;color:#94a3b8">
            <div style="font-size:36px">👤</div>
            <p style="margin-top:8px">Buddy Anda belum diassign.<br>Hubungi tim HR untuk informasi lebih lanjut.</p>
          </div>
          <?php endif; ?>
        </div>

        <!-- Quick chat preview -->
        <?php if ($buddy && count($msgs) > 0): ?>
        <div class="card">
          <div class="card-title">Pesan Terbaru</div>
          <div class="chat-box" style="max-height:200px">
            <?php foreach (array_slice($msgs, -4) as $m): ?>
            <div class="msg <?= $m['sender_id'] == $uid ? 'me' : '' ?>">
              <div>
                <div class="msg-bubble"><?= htmlspecialchars($m['message']) ?></div>
                <div class="msg-meta"><?= date('H:i', strtotime($m['sent_at'])) ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <a href="messages.php" class="btn btn-outline btn-sm btn-block" style="margin-top:12px">Buka Percakapan →</a>
        </div>
        <?php endif; ?>

      </div>
    </div>
  </main>
</div>

<script>
function toggleTask(taskId, newStatus, el) {
    fetch('auth.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=update_task&task_id=' + taskId + '&status=' + newStatus
    }).then(r => r.json()).then(d => {
        if (d.ok) location.reload();
    });
}
</script>
</body>
</html>
