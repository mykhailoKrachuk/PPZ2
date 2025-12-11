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

$parcel_number = $_POST['parcel_number'] ?? '';
$code = $_POST['code'] ?? '';

if (empty($parcel_number)) {
    http_response_code(400);
    echo json_encode(['error' => 'Numer przesyłki jest wymagany']);
    exit;
}

if (empty($code) || strlen($code) !== 6 || !ctype_digit($code)) {
    http_response_code(400);
    echo json_encode(['error' => 'Nieprawidłowy kod. Wymagany 6-cyfrowy kod.']);
    exit;
}

try {
    // Проверяем, что посылка существует и получена, но еще nie wydana
    $stmt = $db->prepare("
        SELECT id, parcel_number, received_at, issued_at, code
        FROM parcel
        WHERE parcel_number = :number
        AND received_at IS NOT NULL
        AND issued_at IS NULL
        LIMIT 1
    ");
    
    $stmt->execute([':number' => $parcel_number]);
    $parcel = $stmt->fetch();
    
    if (!$parcel) {
        http_response_code(404);
        echo json_encode(['error' => 'Nie znaleziono przesyłki lub przesyłka nie może być wydana']);
        exit;
    }
    
    // Проверяем kod
    if (empty($parcel['code'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Przesyłka nie ma przypisanego kodu. Skontaktuj się z działem dostaw.']);
        exit;
    }
    
    if ($parcel['code'] !== $code) {
        http_response_code(403);
        echo json_encode(['error' => 'Nieprawidłowy kod. Sprawdź kod i spróbuj ponownie.']);
        exit;
    }
    
    // Обновляем статус на "delivered" и записываем issued_at
    $updateStmt = $db->prepare("
        UPDATE parcel
        SET status = 'delivered',
            issued_at = NOW()
        WHERE id = :id
    ");
    
    $updateStmt->execute([':id' => $parcel['id']]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Przesyłka została wydana pomyślnie',
        'parcel_number' => $parcel_number
    ]);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Błąd serwera']);
}

