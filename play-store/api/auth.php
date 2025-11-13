<?php
// api/auth.php
require_once '../config.php'; // للوصول إلى الاتصال بقاعدة البيانات

header("Content-Type: application/json; charset=UTF-8");
$response = ['status' => 'error', 'message' => 'حدث خطأ غير متوقع.'];

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
                    // كلمة المرور صحيحة، بدء الجلسة
                    $_SESSION['user_id'] = $user['id'];
                    $response['status'] = 'success';
                    $response['message'] = 'تم تسجيل الدخول بنجاح.';
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