<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

// ==== تأكد من اتصال PDO ====
require_once "../config.php"; // يجب أن يعرف $pdo = new PDO(...)

// ==== تحقق من الصلاحيات ====
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'manager') {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden', 'session_role' => $_SESSION['role'] ?? null]);
    exit;
}

// ==== دوال مساعدة ====
function json422($msg) {
    http_response_code(422);
    echo json_encode(['error' => $msg]);
    exit;
}

$allowedStatus = ['pending','inProgress','completed'];
$allowedPrio   = ['Low','Medium','High'];

// ============================
// MODE: members
// ============================
if (($_GET['mode'] ?? '') === 'members') {
    try {
        $stmt = $pdo->query("SELECT id, name, email FROM users WHERE role='member' ORDER BY name");
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['members' => $members]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
    }
    exit;
}

// ============================
// GET tasks
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $filter = $_GET['filter'] ?? 'all';
    $where  = '';
    $params = [];

    if ($filter !== 'all') {
        if (!in_array($filter, $allowedStatus, true)) json422('invalid filter');
        $where = "WHERE t.status = ?";
        $params[] = $filter;
    }

    try {
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
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // إحصائيات
        $stats = $pdo->query("
            SELECT
                COUNT(*) AS total,
                SUM(status='pending') AS pending,
                SUM(status='inProgress') AS inProgress,
                SUM(status='completed') AS completed
            FROM tasks
        ")->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['tasks' => $tasks, 'stats' => $stats]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
    }
    exit;
}

// ============================
// POST: إنشاء مهمة
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $uid   = (int)($_POST['user_id'] ?? 0);
    $prio  = $_POST['priority'] ?? 'Medium';
    $stat  = $_POST['status'] ?? 'pending';
    $due   = $_POST['due_date'] ?? '';

    if ($title === '' || $desc === '' || $uid <= 0 || $due === '') json422('missing fields');
    if (!in_array($prio, $allowedPrio, true)) json422('invalid priority');
    if (!in_array($stat, $allowedStatus, true)) json422('invalid status');

    // تأكيد أن المستخدم عضو
    $chk = $pdo->prepare("SELECT id FROM users WHERE id=? AND role='member'");
    $chk->execute([$uid]);
    if (!$chk->fetch()) json422('member not found');

    try {
        $ins = $pdo->prepare("
            INSERT INTO tasks (title, description, user_id, priority, status, due_date, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $ins->execute([$title, $desc, $uid, $prio, $stat, $due]);
        echo json_encode(['ok' => 1, 'id' => $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
    }
    exit;
}

// ============================
// PATCH: تعديل الحالة فقط
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    parse_str(file_get_contents('php://input'), $patch);
    $id   = (int)($patch['id'] ?? 0);
    $stat = $patch['status'] ?? '';

    if ($id <= 0 || !in_array($stat, $allowedStatus, true)) json422('invalid id or status');

    try {
        $up = $pdo->prepare("UPDATE tasks SET status=? WHERE id=?");
        $up->execute([$stat, $id]);
        echo json_encode(['ok' => 1]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
    }
    exit;
}

// ============================
// PUT: تعديل أي حقول
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    parse_str(file_get_contents('php://input'), $put);
    $id = (int)($put['id'] ?? 0);
    if ($id <= 0) json422('invalid id');

    $set  = [];
    $vals = [];

    if (isset($put['title']))       { $set[]='title=?'; $vals[] = trim($put['title']); }
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

    if (isset($put['due_date'])) { $set[]='due_date=?'; $vals[]=$put['due_date']; }

    if (!$set) json422('no fields');

    $vals[] = $id;
    try {
        $sql = "UPDATE tasks SET ".implode(', ', $set)." WHERE id=?";
        $st  = $pdo->prepare($sql);
        $st->execute($vals);
        echo json_encode(['ok' => 1]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
    }
    exit;
}

// ============================
// DELETE
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents('php://input'), $del);
    $id = (int)($del['id'] ?? 0);
    if ($id <= 0) json422('invalid id');

    try {
        $st = $pdo->prepare("DELETE FROM tasks WHERE id=?");
        $st->execute([$id]);
        echo json_encode(['ok' => 1]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
    }
    exit;
}

// أي طريقة غير مدعومة
http_response_code(405);
echo json_encode(['error' => 'method not allowed']);
