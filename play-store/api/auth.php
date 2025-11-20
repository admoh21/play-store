<?php
// api/auth.php

// --- START: Enhanced Error Handling ---
error_reporting(0);
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) {
            http_response_code(500);
            header("Content-Type: application/json; charset=UTF-8");
        }
        echo json_encode([
            'status' => 'error',
            'message' => 'حدث خطأ فادح في الخادم.',
            'error_details' => [
                'type' => $error['type'],
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
            ]
        ]);
    }
});
// --- END: Enhanced Error Handling ---

require_once '../config.php'; // للوصول إلى الاتصال بقاعدة البيانات

header("Content-Type: application/json; charset=UTF-8");
$response = ['status' => 'error', 'message' => 'حدث خطأ غير متوقع.'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    $response['message'] = 'الطريقة غير مسموح بها.';
    echo json_encode($response);
    exit;
}

// التحقق من رمز CSRF
$csrf_token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrf_token)) {
    http_response_code(403); // Forbidden
    $response['message'] = 'طلب غير صالح أو جلسة منتهية. يرجى تحديث الصفحة والمحاولة مرة أخرى.';
    echo json_encode($response);
    exit;
}

if (isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action == 'register') {
        $full_name = $_POST['full_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($full_name) || empty($email) || empty($password)) {
            $response['message'] = 'الرجاء ملء جميع الحقول.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['message'] = 'البريد الإلكتروني غير صالح.';
        } else {
            // التحقق من وجود البريد الإلكتروني مسبقًا
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $response['message'] = 'هذا البريد الإلكتروني مسجل بالفعل.';
            } else {
                // تشفير كلمة المرور
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $full_name, $email, $hashed_password);
                if ($stmt->execute()) {
                    $response['status'] = 'success';
                    $response['message'] = 'تم إنشاء الحساب بنجاح! يمكنك الآن تسجيل الدخول.';
                } else {
                    $response['message'] = 'حدث خطأ أثناء إنشاء الحساب.';
                }
            }
            $stmt->close();
        }
    }

    elseif ($action == 'login') {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $response['message'] = 'الرجاء إدخال البريد الإلكتروني وكلمة المرور.';
        } else {
            $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($user = $result->fetch_assoc()) {
                // التحقق من تطابق كلمة المرور
                if (password_verify($password, $user['password'])) {
                    // كلمة المرور صحيحة، بدء وتأمين الجلسة
                    session_regenerate_id(true); // منع تثبيت الجلسة
                    $_SESSION['user_id'] = $user['id'];

                    // إعادة إنشاء رمز CSRF بعد تسجيل الدخول
                    unset($_SESSION['csrf_token']);
                    generate_csrf_token();

                    $response['status'] = 'success';
                    $response['message'] = 'تم تسجيل الدخول بنجاح.';
                    $response['csrf_token'] = $_SESSION['csrf_token']; // إرسال الرمز الجديد إلى العميل
                } else {
                    $response['message'] = 'البريد الإلكتروني أو كلمة المرور غير صحيحة.';
                }
            } else {
                $response['message'] = 'البريد الإلكتروني أو كلمة المرور غير صحيحة.';
            }
            $stmt->close();
        }
    }
}

echo json_encode($response);
$conn->close();
?>