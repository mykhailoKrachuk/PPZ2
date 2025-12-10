<?php
declare(strict_types=1);
global $db;

require __DIR__ . '/require_auth.php';
requireRole('deliver');                 // только курьер
require __DIR__ . '/config.php';

function p(string $k): string { return trim((string)($_POST[$k] ?? '')); }
function back(array $qs): void {
    // соберём query-string аккуратно
    $pairs = [];
    foreach ($qs as $k => $v) $pairs[] = rawurlencode($k) . '=' . rawurlencode((string)$v);
    header('Location: /Salfetka/delivery.php?' . implode('&', $pairs));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    back(['err' => 'badmethod']);
}

$number = p('parcel_number');           // char(15)
$next   = p('next_status');             // sent | in_transit | received | canceled

if ($number === '' || $next === '') {
    back(['err' => 'empty']);
}

// допустимые переходы
$allowed = [
    'created'    => ['sent','canceled'],
    'sent'       => ['in_transit','canceled'],
    'in_transit' => ['received','canceled'],
    'received'   => [],                 // далее выдаёт pracownik -> delivered
    'delivered'  => [],
    'canceled'   => [],
];

try {
    // читаем текущий статус
    $q = $db->prepare("SELECT status FROM parcel WHERE parcel_number = :n LIMIT 1");
    $q->execute([':n' => $number]);
    $row = $q->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        back(['err' => 'notfound', 'num' => $number]);
    }

    $cur = (string)$row['status'];
    if (!isset($allowed[$cur]) || !in_array($next, $allowed[$cur], true)) {
        back(['err' => 'illegal', 'cur' => $cur, 'next' => $next, 'num' => $number]);
    }

    // готовим SET-часть
    $set = "status = :s";
    if ($next === 'sent')      $set .= ", sent_at = now()";
    if ($next === 'received')  $set .= ", received_at = now()";

    $u = $db->prepare("UPDATE parcel SET {$set} WHERE parcel_number = :n");
    $u->execute([':s' => $next, ':n' => $number]);

    back(['ok' => 'updated', 'num' => $number, 'to' => $next]);

} catch (Throwable $e) {
    // покажем короткий код причины для отладки
    back(['err' => 'server', 'e' => substr($e->getMessage(), 0, 80)]);
}
