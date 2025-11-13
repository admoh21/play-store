<?php
// api/manage_payments.php
session_start();
header("Content-Type: application/json; charset=UTF-8");
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true)) {
    http_response_code(403); exit(json_encode(['status' => 'error', 'message' => 'غير مصرح لك.']));
}
require_once '../config.php';
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $result = $conn->query("SELECT * FROM payment_methods ORDER BY id ASC");
        $methods = [];
        while($row = $result->fetch_assoc()) {
            $row['config_details'] = json_decode($row['config_details'], true);
            $methods[] = $row;
        }
        echo json_encode($methods);
        break;
    
    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $data['id'] ?? null;
        $name = $data['name'] ?? '';
        $gateway_code = $data['gateway_code'] ?? '';
        $is_active = !empty($data['is_active']);
        $config_details = json_encode($data['config_details'] ?? []);

        if ($id) { // تعديل
            $stmt = $conn->prepare("UPDATE payment_methods SET name=?, gateway_code=?, is_active=?, config_details=? WHERE id=?");
            $stmt->bind_param("ssisi", $name, $gateway_code, $is_active, $config_details, $id);
        } else { // إضافة
            $stmt = $conn->prepare("INSERT INTO payment_methods (name, gateway_code, is_active, config_details) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssis", $name, $gateway_code, $is_active, $config_details);
        }
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'تم حفظ طريقة الدفع.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'فشل حفظ طريقة الدفع.']);
        }
        $stmt->close();
        break;
    
    case 'DELETE':
        $id = $_GET['id'] ?? 0;
        $stmt = $conn->prepare("DELETE FROM payment_methods WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'تم الحذف بنجاح.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'فشل الحذف.']);
        }
        $stmt->close();
        break;
}
$conn->close();
?>