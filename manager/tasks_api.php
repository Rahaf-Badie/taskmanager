<?php
session_start();
error_log('ROLE: ' . ($_SESSION['role'] ?? 'not set'));
require_once "../config.php"; // لازم يعرّف $pdo = new PDO(...)

header('Content-Type: application/json; charset=utf-8');

// مسموح فقط للـ manager
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'manager') {
  http_response_code(403);
  echo json_encode(['error' => 'forbidden']);
  exit;
}

function json422($msg) {
  http_response_code(422);
  echo json_encode(['error' => $msg]);
  exit;
}

$allowedStatus = ['pending','inProgress','completed'];
$allowedPrio   = ['Low','Medium','High'];

/* ========= MODE: members =========
   GET  /manager/tasks_api.php?mode=members
   يرجع أعضاء الفريق role='member'
*/
if (($_GET['mode'] ?? '') === 'members') {
  //$stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE role='member' ORDER BY name");
  $stmt = $pdo->prepare("SELECT id, name, email FROM members ORDER BY name");
  $stmt->execute();
  $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode(['members' => $members]);
  exit;
}

/* ========= GET tasks (مع فلتر اختياري) =========
   GET /manager/tasks_api.php?filter=all|pending|inProgress|completed
*/
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $filter = $_GET['filter'] ?? 'all';
  $where  = '';
  $params = [];

  if ($filter !== 'all') {
    if (!in_array($filter, $allowedStatus, true)) json422('invalid filter');
    $where = "WHERE t.status = ?";
    $params[] = $filter;
  }

  $sql = "
    SELECT 
      t.id, t.title, t.description, t.status, t.priority, t.due_date, t.created_at,
      u.id AS user_id, u.name AS assigned_name, u.email AS assigned_email
    FROM tasks t
    LEFT JOIN users u ON u.id = t.user_id
    $where
    ORDER BY t.created_at DESC, t.id DESC
  ";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // إحصائيات عامة
  $stats = $pdo->query("
    SELECT
      COUNT(*)                            AS total,
      SUM(status='pending')               AS pending,
      SUM(status='inProgress')            AS inProgress,
      SUM(status='completed')             AS completed
    FROM tasks
  ")->fetch(PDO::FETCH_ASSOC);

  echo json_encode(['tasks' => $rows, 'stats' => $stats]);
  exit;
}

/* ========= POST: إنشاء مهمة =========
   حقلـات مطلوبة: title, description, user_id, due_date
   اختيارية: priority (Low|Medium|High), status (pending|inProgress|completed)
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title'] ?? '');
  $desc  = trim($_POST['description'] ?? '');
  $uid   = (int)($_POST['user_id'] ?? 0);
  $prio  = $_POST['priority'] ?? 'Medium';
  $stat  = $_POST['status'] ?? 'pending';
  $due   = $_POST['due_date'] ?? '';

  if ($title === '' || $desc === '' || $uid <= 0 || $due === '') json422('missing fields');
  if (!in_array($prio, $allowedPrio, true))   json422('invalid priority');
  if (!in_array($stat, $allowedStatus, true)) json422('invalid status');

  // تأكيد أن المستخدم عضو
  //$chk = $pdo->prepare("SELECT id FROM users WHERE id=? AND role='member'");
  $chk = $pdo->prepare("SELECT id FROM members WHERE id=?");
  $chk->execute([$uid]);
  if (!$chk->fetch()) json422('member not found');

  $ins = $pdo->prepare("
    INSERT INTO tasks (title, description, user_id, priority, status, due_date, created_at)
    VALUES (?, ?, ?, ?, ?, ?, NOW())
  ");
  $ins->execute([$title, $desc, $uid, $prio, $stat, $due]);

  echo json_encode(['ok' => 1, 'id' => $pdo->lastInsertId()]);
  exit;
}

/* ========= PATCH: تعديل الحالة فقط =========
   body (x-www-form-urlencoded): id, status
*/
if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
  parse_str(file_get_contents('php://input'), $patch);
  $id   = (int)($patch['id'] ?? 0);
  $stat = $patch['status'] ?? '';

  if ($id <= 0 || !in_array($stat, $allowedStatus, true)) json422('invalid id or status');

  $up = $pdo->prepare("UPDATE tasks SET status=? WHERE id=?");
  $up->execute([$stat, $id]);

  echo json_encode(['ok' => 1]);
  exit;
}

/* ========= PUT: تعديل أي حقول =========
   body (x-www-form-urlencoded): id و أي من (title, description, user_id, priority, status, due_date)
*/
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
  parse_str(file_get_contents('php://input'), $put);
  $id = (int)($put['id'] ?? 0);
  if ($id <= 0) json422('invalid id');

  $set  = [];
  $vals = [];

  if (isset($put['title']))       { $set[]='title=?';       $vals[] = trim($put['title']); }
  if (isset($put['description'])) { $set[]='description=?'; $vals[] = trim($put['description']); }

  if (isset($put['user_id'])) {
    $uid = (int)$put['user_id'];
    if ($uid <= 0) json422('invalid user_id');
    $chk = $pdo->prepare("SELECT id FROM users WHERE id=? AND role='member'");
    $chk->execute([$uid]);
    if (!$chk->fetch()) json422('member not found');
    $set[]='user_id=?'; $vals[]=$uid;
  }

  if (isset($put['priority'])) {
    if (!in_array($put['priority'], $allowedPrio, true)) json422('invalid priority');
    $set[]='priority=?'; $vals[]=$put['priority'];
  }

  if (isset($put['status'])) {
    if (!in_array($put['status'], $allowedStatus, true)) json422('invalid status');
    $set[]='status=?'; $vals[]=$put['status'];
  }

  if (isset($put['due_date'])) {
    $set[]='due_date=?'; $vals[]=$put['due_date'];
  }

  if (!$set) json422('no fields');

  $vals[] = $id;
  $sql = "UPDATE tasks SET ".implode(', ', $set)." WHERE id=?";
  $st  = $pdo->prepare($sql);
  $st->execute($vals);

  echo json_encode(['ok' => 1]);
  exit;
}

/* ========= DELETE =========
   body (x-www-form-urlencoded): id
*/
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
  parse_str(file_get_contents('php://input'), $del);
  $id = (int)($del['id'] ?? 0);
  if ($id <= 0) json422('invalid id');

  $st = $pdo->prepare("DELETE FROM tasks WHERE id=?");
  $st->execute([$id]);

  echo json_encode(['ok' => 1]);
  exit;
}

http_response_code(405);
echo json_encode(['error' => 'method not allowed']);