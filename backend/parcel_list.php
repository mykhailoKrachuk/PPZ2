<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
require __DIR__ . '/require_auth.php';
requireRole('user');

header('Content-Type: application/json');

$user_id = $_SESSION['user']['id'];

try {
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
        WHERE user_id = :user_id
        ORDER BY created_at DESC
    ");
    
    $stmt->execute([':user_id' => $user_id]);
    $parcels = $stmt->fetchAll();
    
    // Преобразуем данные в формат для фронтенда
    $result = array_map(function($parcel) {
        // Определяем текущий статус на основе дат
        $currentStatus = 'Utworzona';
        if ($parcel['issued_at']) {
            $currentStatus = 'Wydana';
        } elseif ($parcel['received_at']) {
            $currentStatus = 'Otrzymana';
        } elseif ($parcel['sent_at']) {
            $currentStatus = 'Wysłana';
        }
        
        // Маппинг статусов из БД
        $statusMap = [
            'received' => 'Otrzymana',
            'delivered' => 'Wydana',
            'canceled' => 'Anulowana'
        ];
        
        $displayStatus = $statusMap[$parcel['status']] ?? $currentStatus;
        
        // Формируем историю (route)
        $route = [];
        
        if ($parcel['created_at']) {
            $route[] = [
                'status' => 'Utworzona',
                'label' => 'Utworzona',
                'date' => date('d-m-Y H:i', strtotime($parcel['created_at'])),
                'location' => $parcel['sender_address'] ?? 'Warszawa'
            ];
        }
        
        if ($parcel['sent_at']) {
            $route[] = [
                'status' => 'Wysłana',
                'label' => 'Wysłana',
                'date' => date('d-m-Y H:i', strtotime($parcel['sent_at'])),
                'location' => $parcel['sender_address'] ?? 'Warszawa'
            ];
        }
        
        if ($parcel['received_at']) {
            $route[] = [
                'status' => 'Otrzymana',
                'label' => 'Otrzymana',
                'date' => date('d-m-Y H:i', strtotime($parcel['received_at'])),
                'location' => 'Punkt odbioru'
            ];
        }
        
        if ($parcel['issued_at']) {
            $route[] = [
                'status' => 'Wydana',
                'label' => 'Wydana klientowi',
                'date' => date('d-m-Y H:i', strtotime($parcel['issued_at'])),
                'location' => 'Punkt odbioru'
            ];
        }
        
        return [
            'id' => $parcel['parcel_number'],
            'status' => $displayStatus,
            'date' => date('Y-m-d', strtotime($parcel['created_at'])),
            'created_at' => $parcel['created_at'] ? date('Y-m-d H:i', strtotime($parcel['created_at'])) : null,
            'sent_at' => $parcel['sent_at'] ? date('Y-m-d H:i', strtotime($parcel['sent_at'])) : null,
            'received_at' => $parcel['received_at'] ? date('Y-m-d H:i', strtotime($parcel['received_at'])) : null,
            'issued_at' => $parcel['issued_at'] ? date('Y-m-d H:i', strtotime($parcel['issued_at'])) : null,
            'phone' => $parcel['phone'],
            'sender_name' => $parcel['sender_name'],
            'sender_address' => $parcel['sender_address'],
            'description' => $parcel['description'],
            'size' => $parcel['size'],
            'weight' => $parcel['weight'],
            'price' => $parcel['price'] ? number_format((float)$parcel['price'], 2, '.', '') . ' PLN' : null,
            'route' => $route
        ];
    }, $parcels);
    
    echo json_encode($result);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Błąd serwera']);
}

