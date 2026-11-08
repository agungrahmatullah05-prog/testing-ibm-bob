<?php
require_once __DIR__ . '/includes/config.php';
requireLogin();

if ($_SESSION['role'] === 'admin') {
    header('Location: admin/dashboard.php'); exit;
}

$db  = getDB();
$uid = $_SESSION['user_id'];

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = (int)$_POST['task_id'];
    $status  = $_POST['status'];
    if (in_array($status, ['pending', 'in_progress', 'done'])) {
        $completed = $status === 'done' ? date('Y-m-d H:i:s') : null;
        $db->prepare('UPDATE onboarding_progress SET status=?, notes=?, completed_at=? WHERE new_hire_id=? AND task_id=?')
           ->execute([$status, trim($_POST['notes'] ?? ''), $completed, $uid, $task_id]);
    }
    header('Location: tasks.php'); exit;
}

$tasks = $db->prepare("
    SELECT t.*, op.status, op.notes, op.completed_at
    FROM task_templates t
    LEFT JOIN onboarding_progress op ON op.task_id=t.id AND op.new_hire_id=?
    ORDER BY t.day_target, t.id
");
$tasks->execute([$uid]);
$tasks = $tasks->fetchAll();

$done   = count(array_filter($tasks, fn($t) => $t['status'] === 'done'));
$inprog = count(array_filter($tasks, fn($t) => $t['status'] === 'in_progress'));
$pct    = count($tasks) > 0 ? round(($done / count($tasks)) * 100) : 0;

$user = currentUser();

$statusLabel = ['pending' => 'Belum Mulai', 'in_progress' => 'Sedang Dikerjakan', 'done' => 'Selesai'];
$statusBadge = ['pending' => 'badge-muted', 'in_progress' => 'badge-warning', 'done' => 'badge-success'];

// Group by category
$grouped = [];
foreach ($tasks as $t) {
    $grouped[$t['category']][] = $t;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tugas Onboarding — <?= APP_NAME ?></title>
<link rel="stylesheet" href="assets/style.css">
<style>
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.4); z-index:999; align-items:center; justify-content:center; }
.modal-overlay.show { display:flex; }
.modal { background:#fff; border-radius:12px; padding:28px; width:100%; max-width:460px; }
.modal h3 { margin-bottom:16px; }
</style>
</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <div class="sidebar-brand"><h2>🤝 <?= APP_NAME ?></h2><span>Portal Karyawan Baru</span></div>
    <nav class="sidebar-nav">
      <a href="dashboard.php"><span class="icon">🏠</span> Dashboard</a>
      <a href="tasks.php" class="active"><span class="icon">✅</span> Tugas Onboarding</a>
      <a href="messages.php"><span class="icon">💬</span> Pesan Buddy</a>
      <a href="profile.php"><span class="icon">👤</span> Profil Saya</a>
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
    <div class="topbar"><h1>Tugas Onboarding</h1></div>

    <!-- Summary -->
    <div class="stats-grid" style="margin-bottom:20px">
      <div class="stat-card" style="grid-column:span 2">
        <div class="label">Progress Keseluruhan</div>
        <div style="display:flex;align-items:center;gap:16px;margin-top:8px">
          <div class="progress-bar" style="flex:1;height:12px">
            <div class="progress-fill" style="width:<?= $pct ?>%"></div>
          </div>
          <strong style="font-size:18px"><?= $pct ?>%</strong>
        </div>
      </div>
      <div class="stat-card"><div class="label">Selesai</div><div class="value" style="color:var(--success)"><?= $done ?></div></div>
      <div class="stat-card"><div class="label">Dikerjakan</div><div class="value" style="color:var(--warning)"><?= $inprog ?></div></div>
      <div class="stat-card"><div class="label">Total</div><div class="value"><?= count($tasks) ?></div></div>
    </div>

    <!-- Tasks grouped by category -->
    <?php foreach ($grouped as $cat => $cat_tasks): ?>
    <div class="card" style="margin-bottom:16px">
      <div class="card-title">
        <?= htmlspecialchars($cat) ?>
        <span style="font-size:12px;color:#94a3b8;font-weight:400;margin-left:8px">
          (<?= count(array_filter($cat_tasks, fn($t) => $t['status']==='done')) ?>/<?= count($cat_tasks) ?> selesai)
        </span>
      </div>
      <?php foreach ($cat_tasks as $t): ?>
      <div class="task-item">
        <div class="task-check <?= $t['status'] === 'done' ? 'done' : ($t['status'] === 'in_progress' ? 'inprog' : '') ?>">
          <?= $t['status'] === 'done' ? '✓' : ($t['status'] === 'in_progress' ? '…' : '') ?>
        </div>
        <div class="task-body" style="flex:1">
          <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px">
            <div>
              <h4 style="<?= $t['status']==='done' ? 'text-decoration:line-through;color:#94a3b8' : '' ?>">
                <?= htmlspecialchars($t['title']) ?>
              </h4>
              <p><?= htmlspecialchars($t['description']) ?></p>
              <div class="meta" style="margin-top:6px">
                <span class="badge <?= $statusBadge[$t['status'] ?? 'pending'] ?>"><?= $statusLabel[$t['status'] ?? 'pending'] ?></span>
                · Hari ke-<?= $t['day_target'] ?>
                <?php if ($t['completed_at']): ?>· ✓ Selesai <?= date('d M Y', strtotime($t['completed_at'])) ?><?php endif; ?>
                <?php if ($t['notes']): ?><br><em style="color:#94a3b8"><?= htmlspecialchars($t['notes']) ?></em><?php endif; ?>
              </div>
            </div>
            <button class="btn btn-outline btn-sm"
              onclick="openModal(<?= $t['id'] ?>, '<?= addslashes($t['title']) ?>', '<?= $t['status'] ?? 'pending' ?>', '<?= addslashes($t['notes'] ?? '') ?>')">
              Update
            </button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
  </main>
</div>

<!-- Modal update status -->
<div class="modal-overlay" id="modal">
  <div class="modal">
    <h3 id="modal-title">Update Tugas</h3>
    <form method="POST">
      <input type="hidden" name="task_id" id="modal-task-id">
      <div class="form-group">
        <label>Status</label>
        <select name="status" id="modal-status" class="form-control">
          <option value="pending">Belum Mulai</option>
          <option value="in_progress">Sedang Dikerjakan</option>
          <option value="done">Selesai</option>
        </select>
      </div>
      <div class="form-group">
        <label>Catatan (opsional)</label>
        <textarea name="notes" id="modal-notes" class="form-control" rows="3"></textarea>
      </div>
      <div style="display:flex;gap:10px">
        <button type="submit" class="btn btn-primary">Simpan</button>
        <button type="button" class="btn btn-outline" onclick="closeModal()">Batal</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(id, title, status, notes) {
    document.getElementById('modal-task-id').value = id;
    document.getElementById('modal-title').textContent = 'Update: ' + title;
    document.getElementById('modal-status').value = status || 'pending';
    document.getElementById('modal-notes').value = notes;
    document.getElementById('modal').classList.add('show');
}
function closeModal() {
    document.getElementById('modal').classList.remove('show');
}
document.getElementById('modal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
</body>
</html>
