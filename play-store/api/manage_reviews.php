<?php
// api/manage_reviews.php

require_once '../config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'يجب تسجيل الدخول لإضافة مراجعة.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { exit; }

$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'] ?? 0;
$rating = $_POST['rating'] ?? 0;
$comment = $_POST['comment'] ?? '';

if (empty($product_id) || empty($rating) || empty($comment)) {
    echo json_encode(['status' => 'error', 'message' => 'الرجاء ملء جميع الحقول.']);
    exit;
}

// يمكنك إضافة تحقق هنا للتأكد من أن المستخدم قد اشترى المنتج بالفعل قبل التقييم

$stmt = $conn->prepare("INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiis", $product_id, $user_id, $rating, $comment);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'شكرًا لك! تم إرسال مراجعتك بنجاح.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'حدث خطأ. ربما قمت بتقييم هذا المنتج من قبل.']);
}
$stmt->close();
$conn->close();
?>