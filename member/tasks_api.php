<?php
session_start();
require_once "../config.php"; // يفترض $pdo = new PDO(...)
header('Content-Type: application/json; charset=utf-8');

// ---- أدوات مساعدة ----
function out($data, int $code=200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function err422($msg){ out(['error'=>$msg], 422); }

$DEBUG = isset($_GET['debug']) ? 1 : 0;
$allowedStatus = ['pending','inProgress','completed'];

function toCamel($s){
    $x = strtolower(trim((string)$s));
    if ($x==='in progress' || $x==='in_progress' || $x==='inprogress') return 'inProgress';
    if ($x==='completed') return 'completed';
    return 'pending';
}
function toDb($s){
    $x = strtolower(trim((string)$s));
    if ($x==='inprogress' || $x==='in_progress') return 'In Progress';
    if ($x==='completed') return 'Completed';
    return 'Pending';
}

// ---- بيانات الجلسة ----
$userEmail = $_SESSION['email'] ?? null;
$userId    = (int)($_SESSION['id'] ?? 0);
$role      = $_SESSION['role']  ?? null;

// السماح فقط للعضو (member)
if (!$DEBUG) {
    if ($role !== 'member' || !$userEmail || $userId <= 0) {
        out(['error'=>'Unauthorized or invalid session','session'=>$_SESSION], 401);
    }
}

$method = $_SERVER['REQUEST_METHOD'];

/* ===== GET: جلب مهام العضو فقط ===== */


if ($method==='GET') {
    $filter = $_GET['filter'] ?? 'all';

    if ($DEBUG && isset($_GET['all'])) {
        // جميع المهام (debug فقط)
        $sql = "SELECT id,title,description,status,priority,due_date,user_id FROM tasks
                ORDER BY (due_date IS NULL), due_date ASC, id DESC";
        $st = $pdo->query($sql);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // فقط مهام العضو الحالي
        $params = [$userId];
        $where  = "WHERE user_id = ?";
        if ($filter !== 'all') {
            if (!in_array($filter, $allowedStatus, true)) err422('invalid filter');
            $where .= " AND status = ?";
            $params[] = toDb($filter);
        }
        $sql = "SELECT id,title,description,status,priority,due_date
                FROM tasks
                $where
                ORDER BY (due_date IS NULL), due_date ASC, id DESC";
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    }

    // تحويل البيانات
    $tasks = [];
    foreach ($rows as $r){
        $tasks[] = [
            'id' => (int)$r['id'],
            'title' => $r['title'] ?? '',
            'description' => $r['description'] ?? '',
            'due_date' => $r['due_date'] ?? null,
            'priority' => $r['priority'] ?? 'Medium',
            'status' => toCamel($r['status'] ?? 'Pending')
        ];
    }

    // إحصائيات
    $stats = ['total'=>0,'pending'=>0,'inProgress'=>0,'completed'=>0];
    foreach($tasks as $t){
        $stats['total']++;
        $stats[$t['status']]++;
    }

    out(['tasks'=>$tasks,'stats'=>$stats]);
}

/* ===== PATCH: تحديث حالة المهمة للعضو ===== */
if ($method==='PATCH') {
    parse_str(file_get_contents('php://input'), $input);

    $idRaw = $input['id'] ?? '';
    $stIn  = $input['status'] ?? '';
    $id    = (int)$idRaw;
    if ($id<=0 || $stIn==='') err422('Invalid id or status');

    $norm = strtolower(str_replace([' ','-'],'_',$stIn));
    if (!in_array($norm,['pending','in_progress','inprogress','completed'],true)) {
        err422('Invalid status');
    }
    $statusDb = toDb($norm);

    if ($DEBUG) {
        $sql = "UPDATE tasks SET status=? WHERE id=?";
        $st = $pdo->prepare($sql);
        $ok = $st->execute([$statusDb,$id]);
        if ($ok && $st->rowCount()>0) out(['ok'=>true,'newStatus'=>$statusDb]);
        out(['error'=>'Not updated'],403);
    } else {
        // تحديث فقط إذا المهمة تخص العضو الحالي
        $sql = "UPDATE tasks SET status=? WHERE id=? AND user_id=?";
        $st = $pdo->prepare($sql);
        $ok = $st->execute([$statusDb,$id,$userId]);
        if ($ok && $st->rowCount()>0) out(['ok'=>true,'newStatus'=>$statusDb]);
        out(['error'=>'Not allowed or task not found'],403);
    }
}

out(['error'=>'Method not allowed'],405);

