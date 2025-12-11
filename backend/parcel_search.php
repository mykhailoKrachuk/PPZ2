<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

header('Content-Type: application/json');

$parcel_number = $_GET['number'] ?? '';

if (empty($parcel_number)) {
    http_response_code(400);
    echo json_encode(['error' => 'Numer przesyłki jest wymagany']);
    exit;
}

try {
    $stmt = $db->prepare("
        SELECT 
            id,
            parcel_number,
            status,
            sender_name,
            sender_address,
            description,
            size,
            weight,
            price,
            created_at,
            sent_at,
            received_at,
            issued_at
        FROM parcel
        WHERE parcel_number = :number
        LIMIT 1
    ");
    
    $stmt->execute([':number' => $parcel_number]);
    $parcel = $stmt->fetch();
    
    if (!$parcel) {
        http_response_code(404);
        echo json_encode(['error' => 'Nie znaleziono przesyłki']);
        exit;
    }
    
    // Формируем историю на основе дат
    $history = [];
    
    if ($parcel['created_at']) {
        $history[] = [
            'date' => date('d-m-Y H:i', strtotime($parcel['created_at'])),
            'status' => 'Utworzona',
            'location' => $parcel['sender_address'] ?? 'Warszawa'
        ];
    }
    
    if ($parcel['sent_at']) {
        $history[] = [
            'date' => date('d-m-Y H:i', strtotime($parcel['sent_at'])),
            'status' => 'Wysłana',
            'location' => $parcel['sender_address'] ?? 'Warszawa'
        ];
    }
    
    if ($parcel['received_at']) {
        $history[] = [
            'date' => date('d-m-Y H:i', strtotime($parcel['received_at'])),
            'status' => 'Otrzymana',
            'location' => 'Punkt odbioru'
        ];
    }
    
    if ($parcel['issued_at']) {
        $history[] = [
            'date' => date('d-m-Y H:i', strtotime($parcel['issued_at'])),
            'status' => 'Wydana klientowi',
            'location' => 'Punkt odbioru'
        ];
    }
    
    // Определяем текущий статус на основе последней даты
    $currentStatus = 'Utworzona';
    if ($parcel['issued_at']) {
        $currentStatus = 'Wydana klientowi';
    } elseif ($parcel['received_at']) {
        $currentStatus = 'Otrzymana';
    } elseif ($parcel['sent_at']) {
        $currentStatus = 'Wysłana';
    }
    
    // Маппинг статусов из БД на польские названия
    $statusMap = [
        'received' => 'Otrzymana',
        'delivered' => 'Wydana klientowi',
        'canceled' => 'Anulowana'
    ];
    
    $displayStatus = $statusMap[$parcel['status']] ?? $currentStatus;
    
    // Формируем ответ
    $result = [
        'id' => $parcel['parcel_number'],
        'status' => $displayStatus,
        'date' => date('Y-m-d', strtotime($parcel['created_at'])),
        'destination' => $parcel['sender_address'] ?? 'Warszawa',
        'history' => array_reverse($history), // Обратный порядок - от нового к старому
        'details' => [
            'sender_name' => $parcel['sender_name'],
            'sender_address' => $parcel['sender_address'],
            'description' => $parcel['description'],
            'size' => $parcel['size'],
            'weight' => $parcel['weight'],
            'price' => $parcel['price']
        ]
    ];
    
    echo json_encode($result);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Błąd serwera']);
}

