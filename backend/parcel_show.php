<?php
declare(strict_types=1);
global $db;

require __DIR__ . '/config.php';
require __DIR__ . '/require_auth.php';
requireRole('user'); // клиент смотрит только свои посылки

header('Content-Type: application/json; charset=utf-8');

$uid = (int)($_SESSION['user']['id'] ?? 0);
$num = trim((string)($_GET['number'] ?? ''));

if ($uid <= 0 || $num === '') {
    http_response_code(400);
    echo json_encode(['error' => 'bad_request']);
    exit;
}

$sql = "
  SELECT id, parcel_number, phone,
         sender_name, sender_address,
         description, size, weight, price,
         status,
         created_at::timestamp(0)  AS created_at,
         sent_at::timestamp(0)     AS sent_at,
         received_at::timestamp(0) AS received_at,
         issued_at::timestamp(0)   AS issued_at
  FROM parcel
  WHERE parcel_number = :n AND user_id = :uid
  LIMIT 1
";
$st = $db->prepare($sql);
$st->execute([':n' => $num, ':uid' => $uid]);
$row = $st->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    echo json_encode(['error' => 'not_found']);
    exit;
}

echo json_encode($row, JSON_UNESCAPED_UNICODE);
