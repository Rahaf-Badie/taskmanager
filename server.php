<?php
// ===== Debug (اختياري اثناء التطوير) =====
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ===== الجلسة والاتصال =====
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "taskmanager"; // غيري حسب اسم قاعدة بياناتك

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
require_once 'config.php';

// مصفوفتا أخطاء لإظهارها في index.php
$register_errors = [];
$login_errors    = [];

/* =========================================================
 * ================      REGISTER       ====================
 * ========================================================= */
if (isset($_POST['register'])) {
    $full_name = trim($_POST['full-name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $role      = strtolower(trim($_POST['role'] ?? ''));

    // تحقق المدخلات
    if ($full_name === '') $register_errors[] = "Full name is required";
    if ($email === '')     $register_errors[] = "Email address is required";
    if ($password === '')  $register_errors[] = "Password is required";
    if ($role === '')      $register_errors[] = "Role is required";

    // هل الإيميل موجود أصلاً؟
    if (empty($register_errors)) {
        if ($stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? LIMIT 1")) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $register_errors[] = "Email already exists";
            }
            mysqli_stmt_close($stmt);
        } else {
            $register_errors[] = "Database error: " . mysqli_error($conn);
        }
    }

    // إدخال المستخدم الجديد
    if (empty($register_errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        if ($stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())")) {
            mysqli_stmt_bind_param($stmt, "ssss", $full_name, $email, $hash, $role);
            if (mysqli_stmt_execute($stmt)) {
                // <<< مهم: احفظ id في الجلسة
                $new_id = mysqli_insert_id($conn);
                $_SESSION['id']      = $new_id;
                $_SESSION['name']    = $full_name;
                $_SESSION['email']   = $email;
                $_SESSION['role']    = $role;
                $_SESSION['success'] = "Register Successful";
                $_SESSION['logged_in'] = true;

                mysqli_stmt_close($stmt);

                // توجيه بحسب الدور — تأكد من المسارات عندك
                if ($role === 'member') {
                    header('Location: /taskmanager/member/dashboard.php'); exit;
                } elseif ($role === 'manager') {
                    header('Location: /taskmanager/manager/dashboard.php'); exit;
                } else {
                    header('Location: /taskmanager/index.php'); exit;
                }
            } else {
                $register_errors[] = "Database error: " . mysqli_error($conn);
                mysqli_stmt_close($stmt);
            }
        } else {
            $register_errors[] = "Database error: " . mysqli_error($conn);
        }
    }
}

/* =========================================================
 * =================       LOGIN        =====================
 * ========================================================= */
if (isset($_POST['login'])) {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '')    $login_errors[] = "Email is required";
    if ($password === '') $login_errors[] = "Password is required";

    if (empty($login_errors)) {
        if ($stmt = mysqli_prepare($conn, "SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1")) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) === 1) {
                mysqli_stmt_bind_result($stmt, $id, $name, $email_db, $password_hash, $role);
                mysqli_stmt_fetch($stmt);

                if (password_verify($password, $password_hash)) {
                    // <<< مهم: احفظ id في الجلسة
                    $_SESSION['id']    = $id;
                    $_SESSION['email'] = $email_db;
                    $_SESSION['name']  = $name;
                    $_SESSION['role']  = $role;
                    $_SESSION['logged_in'] = true;

                    // مسارات صحيحة لكلا الدورين (لاحظ taskmanager)
                    $target = ($role === 'member')
                        ? '/taskmanager/member/dashboard.php'
                        : '/taskmanager/manager/dashboard.php';

                    // لو الرؤوس تم إرسالها مسبقاً لأي سبب، لا نفشل بصمت
                    if (!headers_sent()) {
                        header('Location: ' . $target);
                        exit;
                    }
                } else {
                    $login_errors[] = "Invalid password";
                }
            } else {
                $login_errors[] = "Invalid email";
            }
            mysqli_stmt_close($stmt);
        } else {
            $login_errors[] = "Database error: " . mysqli_error($conn);
        }
    }
}