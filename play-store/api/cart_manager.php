<?php
// api/cart_manager.php

// --- START: Enhanced Error Handling ---
// This will catch any fatal errors and send them as a clean JSON response.
// This is crucial for debugging APIs, as it prevents the generic "network error"
// message in the frontend.
error_reporting(0); // Turn off default error reporting
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        // Check if headers have already been sent
        if (!headers_sent()) {
            http_response_code(500); // Internal Server Error
            header("Content-Type: application/json; charset=UTF-8");
        }
        echo json_encode([
            'status' => 'error',
            'message' => 'حدث خطأ فادح في الخادم.',
            'error_details' => [ // Provide details for debugging
                'type' => $error['type'],
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
            ]
        ]);
    }
});
// --- END: Enhanced Error Handling ---

require_once '../config.php';

// We already set this in the error handler, but we set it here again for the normal execution path.
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'الطريقة غير مسموح بها.']);
    exit;
}

// التحقق من رمز CSRF
$csrf_token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrf_token)) {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'طلب غير صالح أو جلسة منتهية. يرجى تحديث الصفحة والمحاولة مرة أخرى.']);
    exit;
}

// التأكد من تهيئة السلة كمصفوفة في بداية الجلسة
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// إذا لم يكن المستخدم مسجلاً، لا نسمح بأي إجراء ونرسل خطأ واضحًا
if (!isLoggedIn()) {
    http_response_code(403); // Forbidden
    echo json_encode([
        'status' => 'error',
        'message' => 'يجب عليك تسجيل الدخول أولاً.',
        'action_required' => 'login',
        'cart_count' => count($_SESSION['cart']) // نرسل العدد الحالي (الذي يجب أن يكون 0 للزائر)
    ]);
    exit();
}

// الآن نحن متأكدون أن المستخدم مسجل دخوله
$response = ['status' => 'error', 'message' => 'حدث خطأ غير متوقع.'];
$action = $_POST['action'] ?? '';

if ($action == 'add') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

    if ($product_id > 0) {
        // التحقق من أن المنتج موجود بالفعل في قاعدة البيانات (خطوة أمان إضافية)
        $stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // المنتج موجود، يمكن إضافته
            if (!in_array($product_id, $_SESSION['cart'])) {
                $_SESSION['cart'][] = $product_id;
            }
            // THIS IS THE FIX: The missing semicolon is now added.
            $response['status'] = 'success';
            $response['message'] = 'تمت إضافة المنتج إلى السلة!';
        } else {
            $response['message'] = 'المنتج المطلوب غير موجود.';
        }
        $stmt->close();
    } else {
        $response['message'] = 'معرف المنتج غير صالح.';
    }
}

// دائمًا نرجع العدد الصحيح والمحدث للعناصر في السلة
$response['cart_count'] = count($_SESSION['cart']);

echo json_encode($response);

$conn->close();
?>