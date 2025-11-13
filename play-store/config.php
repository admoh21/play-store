<?php
// config.php

// بدء الجلسة في كل الصفحات
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. إعدادات الاتصال بقاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'rxt_store_final');
define('DB_PORT', '3306'); // <-- ★★ أضف هذا السطر. استبدل 3307 بالمنفذ الجديد الذي تستخدمه ★★

// 2. إنشاء اتصال بقاعدة البيانات مع تحديد المنفذ
// لاحظ أننا أضفنا DB_PORT كمعامل خامس
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

// التحقق من الاتصال
if ($conn->connect_error) {
    // عرض رسالة خطأ أكثر تفصيلاً للمساعدة في التشخيص
    die("Connection failed: " . $conn->connect_error . " (Error No: " . $conn->connect_errno . ")");
}

$conn->set_charset("utf8mb4");

// 3. وظائف مساعدة (الكود الخاص بك هنا لا يحتاج تعديل)
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}
function getCurrentUser($conn) {
    if (!isLoggedIn()) {
        return null;
    }
    $stmt = $conn->prepare("SELECT id, full_name, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}
?>