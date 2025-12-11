<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
require __DIR__ . '/require_auth.php';
requireRole('worker');

header('Content-Type: application/json');

try {
    // Получаем только посылки, которые получены (received_at не NULL), но еще не выданы (issued_at NULL)
    $stmt = $db->prepare("
        SELECT 
            id,
            parcel_number,
            phone,
            sender_name,
            sender_address,
            description,
            status,
            size,
            weight,
            price,
            created_at,
            sent_at,
            received_at,
            issued_at
        FROM parcel
        WHERE received_at IS NOT NULL 
          AND issued_at IS NULL
        ORDER BY received_at DESC
    ");
    
    $stmt->execute();
    $parcels = $stmt->fetchAll();
    
    // Преобразуем данные в формат для фронтенда
    $result = array_map(function($parcel) {
        // Извлекаем город из адреса (берем последнее слово после запятой или первое слово)
        $destination = 'Warszawa'; // по умолчанию
        if ($parcel['sender_address']) {
            $parts = explode(',', $parcel['sender_address']);
            if (count($parts) > 1) {
                $destination = trim($parts[count($parts) - 1]);
            } else {
                $words = explode(' ', trim($parcel['sender_address']));
                $destination = $words[0] ?? 'Warszawa';
            }
        }
        
        // Разделяем имя отправителя на имя и фамилию
        $nameParts = explode(' ', $parcel['sender_name'] ?? '');
        $receiverFirst = $nameParts[0] ?? '';
        $receiverLast = isset($nameParts[1]) ? implode(' ', array_slice($nameParts, 1)) : '';
        
        // Определяем статус для отображения
        $displayStatus = 'Otrzymana'; // если есть received_at, значит получена
        
        return [
            'id' => $parcel['parcel_number'],
            'destination' => $destination,
            'status' => $displayStatus,
            'date' => date('Y-m-d', strtotime($parcel['received_at'])),
            'receiverFirst' => $receiverFirst,
            'receiverLast' => $receiverLast,
            'address' => $parcel['sender_address'] ?? '',
            'phone' => $parcel['phone'] ?? '',
            'note' => $parcel['description'] ?? '',
            'parcel_id' => $parcel['id'] // ID из БД для обновления
        ];
    }, $parcels);
    
    echo json_encode($result);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Błąd serwera']);
}

