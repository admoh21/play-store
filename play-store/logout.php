<?php
// logout.php

// ابدأ الجلسة لتتمكن من الوصول إليها
session_start();

// 1. ألغِ تعيين جميع متغيرات الجلسة.
$_SESSION = array();

// 2. إذا كنت ترغب في تدمير الجلسة بالكامل، فاحذف أيضًا ملف تعريف الارتباط الخاص بالجلسة.
// ملاحظة: هذا سيدمر الجلسة، وليس فقط بيانات الجلسة!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. أخيرًا، قم بتدمير الجلسة.
session_destroy();

// 4. أعد التوجيه إلى الصفحة الرئيسية.
header("Location: index.php");
exit();
?>
<?php
// config.php

// --- تعديلات أمان الجلسة ---
// التأكد من أن الجلسات تستخدم الكوكيز فقط
ini_set('session.use_only_cookies', 1);
// بدء الجلسة إذا لم تكن قد بدأت بالفعل
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// --- نهاية تعديلات أمان الجلسة ---


// 1. إعدادات الاتصال بقاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'rxt_store_final');

// 2. إنشاء اتصال بقاعدة البيانات
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
$conn->set_charset("utf8mb4");

// 3. وظائف مساعدة
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUser($conn) {
    if (!isLoggedIn()) {
        return null;
    }
    // يجب إعادة فتح الاتصال إذا تم إغلاقه سابقًا
    if ($conn->connect_error) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset("utf8mb4");
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