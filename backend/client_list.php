<?php
declare(strict_types=1);
global $db;

require __DIR__ . '/config.php';
require __DIR__ . '/require_auth.php';
requireRole('user'); // только авторизованный клиент

header('Content-Type: application/json; charset=utf-8');

$uid = (int)($_SESSION['user']['id'] ?? 0);
if ($uid <= 0) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

/**
 * Фильтры из query:
 *   q            — часть номера посылки
 *   date         — YYYY-MM-DD (фильтр по дате создания)
 *   status       — для активных: created|sent|in_transit|received (опционально)
 */
$q       = trim((string)($_GET['q'] ?? ''));
$date    = trim((string)($_GET['date'] ?? ''));
$status  = trim((string)($_GET['status'] ?? ''));

$whereCommon = ['user_id = :uid'];
$params = [':uid' => $uid];

if ($q !== '') {
    $whereCommon[] = 'parcel_number ILIKE :q';
    $params[':q'] = '%' . $q . '%';
}
if ($date !== '') {
    // фильтр по дате создания (DATE(created_at) = :date)
    $whereCommon[] = 'DATE(created_at) = :d';
    $params[':d'] = $date;
}

function fetchAll(PDO $db, string $sql, array $params): array {
    $st = $db->prepare($sql);
    $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

// Активные (все кроме delivered и canceled/issued)
$whereActive = $whereCommon;
$whereActive[] =
    "status NOT IN ('delivered','canceled','issued')";

if ($status !== '' && in_array($status, ['created','sent','in_transit','received'], true)) {
    $whereActive[] = 'status = :st';
    $params[':st'] = $status;
}

$sqlActive = "
  SELECT id, parcel_number, status, created_at::timestamp(0) AS created_at,
         phone, description
  FROM parcel
  WHERE " . implode(' AND ', $whereActive) . "
  ORDER BY created_at DESC, id DESC
  LIMIT 200
";

$sqlArchive = "
  SELECT id, parcel_number, status, created_at::timestamp(0) AS created_at,
         phone, description
  FROM parcel
  WHERE " . implode(' AND ', $whereCommon) . "
    AND status IN ('delivered','issued','canceled')
  ORDER BY created_at DESC, id DESC
  LIMIT 200
";

echo json_encode([
    'active'  => fetchAll($db, $sqlActive,  $params),
    'archive' => fetchAll($db, $sqlArchive, $params),
], JSON_UNESCAPED_UNICODE);
