<?php
declare(strict_types=1);
global $db;

require __DIR__ . '/require_auth.php';
requireRole('worker');              // выдаёт сотрудник
require __DIR__ . '/config.php';

function p(string $k): string { return trim((string)($_POST[$k] ?? '')); }
function back(string $qs): void {
    header('Location: /Salfetka/worker.php?' . $qs);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    back('err=badmethod');
}

$num = p('parcel_number');
if ($num === '') {
    back('err=nonum');
}

try {
    $upd = $db->prepare("
        UPDATE parcel
        SET status = 'delivered',
            issued_at = now()
        WHERE parcel_number = :n
          AND status <> 'delivered'
          AND status <> 'canceled'
        RETURNING parcel_number
    ");
    $upd->execute([':n'=>$num]);
    $row = $upd->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        back('ok=delivered&num=' . urlencode($row['parcel_number']));
    } else {
        back('err=notallowed');
    }
} catch (Throwable $e) {
    back('err=server');
}
