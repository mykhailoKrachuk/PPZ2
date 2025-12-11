<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
require __DIR__ . '/require_auth.php';
requireRole('deliver');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$parcel_number = $_POST['parcel_number'] ?? '';

if (empty($parcel_number)) {
    http_response_code(400);
    echo json_encode(['error' => 'Numer przesyłki jest wymagany']);
    exit;
}

try {
    // Проверяем, что посылка существует и еще не отправлена
    $stmt = $db->prepare("
        SELECT id, parcel_number, sent_at
        FROM parcel
        WHERE parcel_number = :number
        AND sent_at IS NULL
        AND created_at IS NOT NULL
        LIMIT 1
    ");
    
    $stmt->execute([':number' => $parcel_number]);
    $parcel = $stmt->fetch();
    
    if (!$parcel) {
        http_response_code(404);
        echo json_encode(['error' => 'Nie znaleziono przesyłki lub przesyłka została już wysłana']);
        exit;
    }
    
    // Обновляем: ставим sent_at и меняем статус на "sent"
    $updateStmt = $db->prepare("
        UPDATE parcel
        SET sent_at = NOW(),
            status = 'sent'
        WHERE id = :id
    ");
    
    $updateStmt->execute([':id' => $parcel['id']]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Przesyłka została wysłana',
        'parcel_number' => $parcel_number
    ]);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Błąd serwera']);
}

