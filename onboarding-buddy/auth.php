<?php
require_once __DIR__ . '/includes/config.php';

$action = $_POST['action'] ?? '';

// ---- LOGIN ----
if ($action === 'login') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        flash('error', 'Email dan password wajib diisi.');
        header('Location: login.php'); exit;
    }

    $stmt = getDB()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        flash('error', 'Email atau password salah.');
        header('Location: login.php'); exit;
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role']    = $user['role'];
    $_SESSION['user']    = $user;

    if ($user['role'] === 'admin') {
        header('Location: admin/dashboard.php'); exit;
    }
    header('Location: dashboard.php'); exit;
}

// ---- REGISTER ----
if ($action === 'register') {
    $name       = trim($_POST['name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $department = trim($_POST['department'] ?? '');
    $position   = trim($_POST['position'] ?? '');
    $start_date = $_POST['start_date'] ?? '';

    if (!$name || !$email || !$password) {
        flash('error', 'Nama, email, dan password wajib diisi.');
        header('Location: register.php'); exit;
    }

    $db = getDB();
    $check = $db->prepare('SELECT id FROM users WHERE email = ?');
    $check->execute([$email]);
    if ($check->fetch()) {
        flash('error', 'Email sudah terdaftar.');
        header('Location: register.php'); exit;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $db->prepare('INSERT INTO users (name, email, password, role, department, position, start_date) VALUES (?,?,?,?,?,?,?)');
    $stmt->execute([$name, $email, $hash, 'new_hire', $department, $position, $start_date ?: null]);
    $new_id = $db->lastInsertId();

    // Auto-assign default tasks
    $tasks = $db->query('SELECT id FROM task_templates')->fetchAll();
    $ins   = $db->prepare('INSERT INTO onboarding_progress (new_hire_id, task_id) VALUES (?,?)');
    foreach ($tasks as $t) {
        $ins->execute([$new_id, $t['id']]);
    }

    flash('success', 'Akun berhasil dibuat! Silakan login.');
    header('Location: login.php'); exit;
}

// ---- LOGOUT ----
if ($action === 'logout') {
    session_destroy();
    header('Location: login.php'); exit;
}

// ---- UPDATE TASK STATUS ----
if ($action === 'update_task') {
    requireLogin();
    $task_id = (int)($_POST['task_id'] ?? 0);
    $status  = $_POST['status'] ?? 'pending';
    $uid     = $_SESSION['user_id'];

    if (!in_array($status, ['pending', 'in_progress', 'done'])) {
        echo json_encode(['ok' => false]); exit;
    }

    $completed = $status === 'done' ? date('Y-m-d H:i:s') : null;
    $stmt = getDB()->prepare('UPDATE onboarding_progress SET status = ?, completed_at = ? WHERE new_hire_id = ? AND task_id = ?');
    $stmt->execute([$status, $completed, $uid, $task_id]);
    echo json_encode(['ok' => true]); exit;
}

// ---- SEND MESSAGE ----
if ($action === 'send_message') {
    requireLogin();
    $receiver_id = (int)($_POST['receiver_id'] ?? 0);
    $message     = trim($_POST['message'] ?? '');

    if ($receiver_id && $message) {
        $stmt = getDB()->prepare('INSERT INTO messages (sender_id, receiver_id, message) VALUES (?,?,?)');
        $stmt->execute([$_SESSION['user_id'], $receiver_id, $message]);
    }
    header('Location: ' . $_SERVER['HTTP_REFERER']); exit;
}

header('Location: login.php');
