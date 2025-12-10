<?php
declare(strict_types=1);
global $db;

require __DIR__ . '/require_auth.php';
requireRole('worker');              // только сотрудник
require __DIR__ . '/config.php';

function p(string $k): string { return trim((string)($_POST[$k] ?? '')); }
function back(string $qs): void {
    header('Location: /Salfetka/worker.php?' . $qs);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    back('err=badmethod');
}

$sender_name    = p('sender_name');
$sender_address = p('sender_address');
$phone          = p('phone');
$description    = p('description');
$size           = p('size');
$weight         = p('weight');
$price          = p('price');
$user_mail      = p('user_mail');
$user_phone     = p('user_phone');

$errs = [];
if ($sender_name===''||$sender_address===''||$phone===''||$description===''||$size===''||$weight===''||$price==='') {
    $errs[] = 'fill';
}
if ($user_mail==='' && $user_phone==='') {
    $errs[] = 'user';
}
if ($weight !== '' && !is_numeric($weight)) $errs[] = 'w';
if ($price  !== '' && !is_numeric($price))  $errs[] = 'pr';

if ($errs) {
    back('err=' . implode(',', $errs));
}

try {
    // Найти клиента по e-mail или телефону
    $q = $db->prepare("
        SELECT id FROM users
        WHERE (:mail <> '' AND lower(mail) = lower(:mail))
           OR (:tel  <> '' AND phone_number = :tel)
        LIMIT 1
    ");
    $q->execute([':mail'=>$user_mail, ':tel'=>$user_phone]);
    $u = $q->fetch(PDO::FETCH_ASSOC);

    if (!$u) {
        back('err=nouser');
    }

    // Создать посылку. parcel_number генерируется триггером по дефолту в таблице
    $ins = $db->prepare("
        INSERT INTO parcel (phone, sender_name, sender_address, description, size, weight, price, user_id)
        VALUES (:phone, :sender_name, :sender_address, :description, :size, :weight, :price, :user_id)
        RETURNING parcel_number
    ");
    $ins->execute([
        ':phone'=>$phone,
        ':sender_name'=>$sender_name,
        ':sender_address'=>$sender_address,
        ':description'=>$description,
        ':size'=>$size,
        ':weight'=>(float)$weight,
        ':price'=>(float)$price,
        ':user_id'=>(int)$u['id'],
    ]);

    $row = $ins->fetch(PDO::FETCH_ASSOC);
    $num = $row['parcel_number'] ?? '';

    back('ok=created&num=' . urlencode($num));
} catch (Throwable $e) {
    back('err=server');
}
