<?php
// api/save_payment_settings.php
session_start();
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403); exit(json_encode(['status' => 'error', 'message' => 'غير مصرح لك.']));
}

require_once '../config.php';
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    http_response_code(400); exit(json_encode(['status' => 'error', 'message' => 'بيانات غير صالحة.']));
}

$conn->begin_transaction();
try {
    // إعداد البيانات الافتراضية لكل بوابة
    $gateways_data = [
        'stripe' => [
            'name' => 'البطاقات الائتمانية',
            'description' => 'الدفع الآمن عبر فيزا أو ماستركارد',
            'logo_url' => 'https://js.stripe.com/v3/fingerprinted/img/visa-729c23851b83d5a86a7ee7f5d1a5822f.svg'
        ],
        'bank_transfer' => [
            'name' => 'التحويل البنكي',
            'description' => 'التحويل المباشر إلى أحد حساباتنا',
            'logo_url' => 'https://image.pngaaa.com/383/823383-middle.png'
        ]
        // أضف Binance Pay هنا بنفس الطريقة
    ];
    
    // استعلام UPSERT
    $stmt = $conn->prepare("
        INSERT INTO payment_methods (gateway_code, name, is_active, description, logo_url, config_details) 
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            is_active = VALUES(is_active),
            config_details = VALUES(config_details)
    ");

    foreach ($data as $gateway_code => $settings) {
        if (array_key_exists($gateway_code, $gateways_data)) {
            $is_active = !empty($settings['is_active']);
            
            // التعامل مع المفتاح السري لـStripe (لا نحدثه إذا كان فارغًا)
            if ($gateway_code == 'stripe' && empty($settings['config_details']['api_key_secret'])) {
                // جلب المفتاح السري القديم من قاعدة البيانات
                $q = $conn->query("SELECT config_details FROM payment_methods WHERE gateway_code = 'stripe'");
                $old_config = json_decode($q->fetch_assoc()['config_details'] ?? '{}', true);
                $settings['config_details']['api_key_secret'] = $old_config['api_key_secret'] ?? '';
            }

            $config_details = json_encode($settings['config_details'] ?? []);
            
            $stmt->bind_param("ssisss", 
                $gateway_code, 
                $gateways_data[$gateway_code]['name'], 
                $is_active, 
                $gateways_data[$gateway_code]['description'], 
                $gateways_data[$gateway_code]['logo_url'], 
                $config_details
            );
            $stmt->execute();
        }
    }

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'تم حفظ إعدادات الدفع بنجاح!']);

} catch (mysqli_sql_exception $exception) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'فشل حفظ الإعدادات: ' . $exception->getMessage()]);
}
$stmt->close();
$conn->close();
?>