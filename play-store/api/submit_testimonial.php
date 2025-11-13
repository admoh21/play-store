<?php
// api/submit_testimonial.php

require_once '../config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    exit(json_encode(['status' => 'error', 'message' => 'طريقة الطلب غير صحيحة.']));
}

$name = $_POST['author_name'] ?? '';
$rating = $_POST['rating'] ?? 0;
$comment = $_POST['comment'] ?? '';

// التحقق من صحة المدخلات
if (empty($name) || empty($rating) || empty($comment)) {
    http_response_code(400); // Bad Request
    exit(json_encode(['status' => 'error', 'message' => 'الرجاء ملء جميع الحقول.']));
}
if (!is_numeric($rating) || $rating < 1 || $rating > 5) {
    http_response_code(400);
    exit(json_encode(['status' => 'error', 'message' => 'التقييم غير صالح.']));
}

// إدراج التقييم في قاعدة البيانات
$stmt = $conn->prepare("INSERT INTO testimonials (author_name, comment, rating) VALUES (?, ?, ?)");
$stmt->bind_param("ssi", $name, $comment, $rating);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'شكرًا لك! تم إرسال تقييمك بنجاح.']);
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'حدث خطأ أثناء حفظ التقييم.']);
}

$stmt->close();
$conn->close();
?>