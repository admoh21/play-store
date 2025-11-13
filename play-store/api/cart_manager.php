<?php
// api/cart_manager.php

require_once '../config.php';

header("Content-Type: application/json; charset=UTF-8");

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