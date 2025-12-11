<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
require __DIR__ . '/require_auth.php';
requireRole('worker');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Получаем данные из формы
$receiver_name = trim($_POST['receiver_name'] ?? '');
$receiver_address = trim($_POST['receiver_address'] ?? '');
$receiver_phone = trim($_POST['receiver_phone'] ?? '');
$description = trim($_POST['description'] ?? '');
$size = trim($_POST['size'] ?? '');
$weight = trim($_POST['weight'] ?? '');
$price = trim($_POST['price'] ?? '');

// Валидация обязательных полей
if (empty($receiver_name)) {
    http_response_code(400);
    echo json_encode(['error' => 'Imię i nazwisko odbiorcy jest wymagane']);
    exit;
}

if (empty($receiver_address)) {
    http_response_code(400);
    echo json_encode(['error' => 'Adres dostawy jest wymagany']);
    exit;
}

if (empty($receiver_phone)) {
    http_response_code(400);
    echo json_encode(['error' => 'Numer telefonu jest wymagany']);
    exit;
}

try {
    // Ищем пользователя по номеру телефона
    $userStmt = $db->prepare("
        SELECT id 
        FROM users 
        WHERE phone_number = :phone 
        LIMIT 1
    ");
    
    $userStmt->execute([':phone' => $receiver_phone]);
    $user = $userStmt->fetch();
    
    $user_id = null;
    if ($user) {
        $user_id = $user['id'];
    }
    // Если пользователь не найден, создаем посылку без user_id (может быть создана для не зарегистрированного клиента)
    
    // Генерируем уникальный номер посылки (15 символов: буквы и цифры, все капсом)
    function generateParcelNumber(): string {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $length = 15;
        $parcel_number = '';
        for ($i = 0; $i < $length; $i++) {
            $parcel_number .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $parcel_number;
    }
    
    $parcel_number = generateParcelNumber();
    
    // Проверяем уникальность (на всякий случай)
    $checkStmt = $db->prepare("SELECT id FROM parcel WHERE parcel_number = :number LIMIT 1");
    $checkStmt->execute([':number' => $parcel_number]);
    while ($checkStmt->fetch()) {
        $parcel_number = generateParcelNumber();
        $checkStmt->execute([':number' => $parcel_number]);
    }
    
    // Создаем посылку со статусом "created" (created_at будет установлен автоматически)
    $insertStmt = $db->prepare("
        INSERT INTO parcel (
            parcel_number,
            user_id,
            phone,
            sender_name,
            sender_address,
            description,
            size,
            weight,
            price,
            status,
            created_at
        ) VALUES (
            :parcel_number,
            :user_id,
            :phone,
            :sender_name,
            :sender_address,
            :description,
            :size,
            :weight,
            :price,
            'created',
            NOW()
        )
    ");
    
    $insertStmt->execute([
        ':parcel_number' => $parcel_number,
        ':user_id' => $user_id,
        ':phone' => $receiver_phone,
        ':sender_name' => $receiver_name,
        ':sender_address' => $receiver_address,
        ':description' => $description ?: null,
        ':size' => $size ?: null,
        ':weight' => $weight ?: null,
        ':price' => $price ? (float)$price : null
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Przesyłka została utworzona pomyślnie',
        'parcel_number' => $parcel_number
    ]);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Błąd serwera: ' . $e->getMessage()]);
}

