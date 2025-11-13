<?php
// api/manage_categories.php

session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'غير مصرح لك.']);
        exit;
    }
}

require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"));

switch ($method) {
    case 'GET':
        $result = $conn->query("SELECT * FROM categories ORDER BY display_order, name ASC");
        $categories = [];
        while($row = $result->fetch_assoc()) { $categories[] = $row; }
        echo json_encode($categories);
        break;

    case 'POST': // يعالج الإضافة والتعديل
        $id = $data->id ?? null;
        $name = $data->name ?? '';
        $description = $data->description ?? '';
        if (empty($name)) { 
            echo json_encode(['status' => 'error', 'message' => 'اسم القسم مطلوب.']);
            exit;
        }

        if ($id) { // تعديل
            $stmt = $conn->prepare("UPDATE categories SET name=?, description=? WHERE id=?");
            $stmt->bind_param("ssi", $name, $description, $id);
        } else { // إضافة
            $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $description);
        }
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'تم حفظ القسم بنجاح.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'فشل حفظ القسم.']);
        }
        $stmt->close();
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? 0;
        if ($id == 0) {
            echo json_encode(['status' => 'error', 'message' => 'معرف القسم غير صحيح.']);
            exit;
        }
        
        // ملاحظة: في تطبيق حقيقي، يجب التحقق مما إذا كانت هناك منتجات مرتبطة بهذا القسم قبل حذفه.
        $stmt = $conn->prepare("DELETE FROM categories WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'تم حذف القسم بنجاح.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'فشل حذف القسم.']);
        }
        $stmt->close();
        break;
}

$conn->close();
?>