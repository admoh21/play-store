<?php
// api/manage_products.php

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

switch ($method) {
    case 'GET':
        $sql = "SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC";
        $result = $conn->query($sql);
        $products = [];
        while($row = $result->fetch_assoc()) { $products[] = $row; }
        echo json_encode($products);
        break;

    case 'POST': // هذا سيعالج الإضافة والتعديل معًا
        $id = $_POST['id'] ?? null;
        $name = $_POST['name'] ?? '';
        $price = $_POST['price'] ?? 0;
        $category_id = $_POST['category_id'] ?? 0;
        $original_price = !empty($_POST['original_price']) ? $_POST['original_price'] : null;
        $image_url = $_POST['current_image_url'] ?? ''; // الصورة القديمة

        // منطق رفع الصورة
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "../uploads/";
            if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
            $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
            $target_file = $target_dir . uniqid('prod_') . '.' . $file_extension;
            
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_url = str_replace('../', '', $target_file);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'فشل رفع الصورة.']); exit;
            }
        }
        
        if (empty($name) || empty($price) || empty($category_id)) {
             echo json_encode(['status' => 'error', 'message' => 'الرجاء ملء اسم المنتج والسعر والقسم.']); exit;
        }

        if ($id) { // تعديل منتج موجود
            $sql = "UPDATE products SET name=?, price=?, original_price=?, image_url=?, category_id=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sddsii", $name, $price, $original_price, $image_url, $category_id, $id);
        } else { // إضافة منتج جديد
            $sql = "INSERT INTO products (name, price, original_price, image_url, category_id) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sddsi", $name, $price, $original_price, $image_url, $category_id);
        }

        if ($stmt->execute()) {
            $message = $id ? 'تم تعديل المنتج بنجاح' : 'تمت إضافة المنتج بنجاح';
            echo json_encode(['status' => 'success', 'message' => $message]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'فشل حفظ المنتج: ' . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? 0;
        $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'تم حذف المنتج بنجاح']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'فشل حذف المنتج']);
        }
        $stmt->close();
        break;
}
$conn->close();
?>