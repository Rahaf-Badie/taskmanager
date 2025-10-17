<?php
// تشغيل عرض الأخطاء أثناء التطوير
error_reporting(E_ALL);
ini_set('display_errors', 1);

// بدء الجلسة بأمان
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// إعداد الاتصال بقاعدة البيانات
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=taskmanager;charset=utf8mb4", // غير taskmanager إلى اسم قاعدة بياناتك
        "root",      // غير اسم المستخدم إذا كان مختلف
        ""           // ضع كلمة مرور MySQL لو موجودة
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // إرجاع خطأ بصيغة JSON لو فشل الاتصال
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Database connection failed', 'details' => $e->getMessage()]);
    exit;
}
