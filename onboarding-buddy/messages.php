<?php
require_once __DIR__ . '/includes/config.php';
requireLogin();

if ($_SESSION['role'] === 'admin') {
    header('Location: admin/dashboard.php'); exit;
}

$db  = getDB();
$uid = $_SESSION['user_id'];

// Get buddy
$buddy = $db->prepare("
    SELECT u.* FROM buddy_assignments ba
    JOIN users u ON u.id = ba.buddy_id
    WHERE ba.new_hire_id = ? AND ba.status='active' LIMIT 1
");
$buddy->execute([$uid]);
$buddy = $buddy->fetch();

// Get messages
$msgs = [];
if ($buddy) {
    $mstmt = $db->prepare("
        SELECT m.*, u.name AS sender_name FROM messages m
        JOIN users u ON u.id = m.sender_id
        WHERE (m.sender_id=? AND m.receiver_id=?)
           OR (m.sender_id=? AND m.receiver_id=?)
        ORDER BY m.sent_at ASC
    ");
    $mstmt->execute([$uid, $buddy['id'], $buddy['id'], $uid]);
    $msgs = $mstmt->fetchAll();

    // Mark as read
    $db->prepare("UPDATE messages SET is_read=1 WHERE receiver_id=? AND sender_id=?")->execute([$uid, $buddy['id']]);
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
<title>Pesan Buddy — <?= APP_NAME ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <div class="sidebar-brand"><h2>🤝 <?= APP_NAME ?></h2><span>Portal Karyawan Baru</span></div>
    <nav class="sidebar-nav">
      <a href="dashboard.php"><span class="icon">🏠</span> Dashboard</a>
      <a href="tasks.php"><span class="icon">✅</span> Tugas Onboarding</a>
      <a href="messages.php" class="active"><span class="icon">💬</span> Pesan Buddy</a>
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
    <div class="topbar"><h1>💬 Pesan dengan Buddy</h1></div>

    <?php if (!$buddy): ?>
    <div class="card" style="text-align:center;padding:48px">
      <div style="font-size:48px;margin-bottom:12px">👤</div>
      <h3>Buddy Belum Diassign</h3>
      <p style="color:#64748b;margin-top:8px">Hubungi admin HR untuk mendapatkan buddy onboarding Anda.</p>
      <a href="dashboard.php" class="btn btn-primary" style="margin-top:16px">← Kembali ke Dashboard</a>
    </div>
    <?php else: ?>

    <div class="card" style="max-width:700px">
      <!-- Buddy header -->
      <div style="display:flex;align-items:center;gap:14px;padding-bottom:16px;border-bottom:1px solid var(--border);margin-bottom:16px">
        <div class="buddy-avatar" style="width:44px;height:44px;font-size:16px"><?= initials($buddy['name']) ?></div>
        <div>
          <strong><?= htmlspecialchars($buddy['name']) ?></strong><br>
          <small style="color:#94a3b8"><?= htmlspecialchars($buddy['position'] ?: 'Buddy') ?> · <?= htmlspecialchars($buddy['department'] ?: '') ?></small>
        </div>
        <span class="badge badge-success" style="margin-left:auto">Online</span>
      </div>

      <!-- Messages -->
      <div class="chat-box" id="chat-box">
        <?php if (empty($msgs)): ?>
        <p style="text-align:center;color:#94a3b8;padding:24px">Belum ada pesan. Mulai percakapan dengan buddy Anda!</p>
        <?php endif; ?>
        <?php foreach ($msgs as $m): ?>
        <div class="msg <?= $m['sender_id'] == $uid ? 'me' : '' ?>">
          <div>
            <div class="msg-bubble"><?= nl2br(htmlspecialchars($m['message'])) ?></div>
            <div class="msg-meta"><?= htmlspecialchars($m['sender_name']) ?> · <?= date('d M H:i', strtotime($m['sent_at'])) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Send form -->
      <form method="POST" action="auth.php" style="display:flex;gap:10px;margin-top:16px;border-top:1px solid var(--border);padding-top:16px">
        <input type="hidden" name="action" value="send_message">
        <input type="hidden" name="receiver_id" value="<?= $buddy['id'] ?>">
        <textarea name="message" class="form-control" placeholder="Tulis pesan..." rows="2" required
          style="resize:none;flex:1" onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();this.form.submit()}"></textarea>
        <button type="submit" class="btn btn-primary" style="align-self:flex-end">Kirim</button>
      </form>
    </div>
    <?php endif; ?>
  </main>
</div>
<script>
var cb = document.getElementById('chat-box');
if (cb) cb.scrollTop = cb.scrollHeight;
</script>
</body>
</html>
