<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
require __DIR__ . '/require_auth.php';
requireRole('deliver');

header('Content-Type: application/json');

try {
    // Получаем посылки, которые созданы но не отправлены (created)
    // ИЛИ отправлены но не получены (sent)
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
            code
        FROM parcel
        WHERE (sent_at IS NULL AND created_at IS NOT NULL)  -- created
           OR (sent_at IS NOT NULL AND received_at IS NULL)  -- sent
        ORDER BY 
          CASE 
            WHEN sent_at IS NULL THEN 1  -- created first
            ELSE 2  -- sent second
          END,
          COALESCE(sent_at, created_at) DESC
    ");
    
    $stmt->execute();
    $parcels = $stmt->fetchAll();
    
    // Преобразуем данные в формат для фронтенда
    $result = array_map(function($parcel) {
        // Определяем тип посылки
        $type = $parcel['sent_at'] ? 'sent' : 'created';
        
        return [
            'id' => $parcel['parcel_number'],
            'parcel_id' => $parcel['id'], // ID из БД для обновления
            'type' => $type,
            'status' => $parcel['status'],
            'created_at' => $parcel['created_at'],
            'sent_at' => $parcel['sent_at'],
            'code' => $parcel['code'] ?? null
        ];
    }, $parcels);
    
    echo json_encode($result);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Błąd serwera']);
}

