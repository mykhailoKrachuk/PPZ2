<?php
declare(strict_types=1);

global $db;
require __DIR__ . '/config.php';

$login = $_POST['login']     ?? '';
$pass  = $_POST['password']  ?? '';

$login = trim($login);
if ($login === '' || $pass === '') {
    header('Location: /Salfetka/login.html?err=empty'); exit;
}

try {
    $stmt = $db->prepare("
        SELECT id, username, password, role
        FROM users
        WHERE username = :u
        LIMIT 1
    ");
    $stmt->execute([':u' => $login]);
    $user = $stmt->fetch();
} catch (Throwable $e) {
    // На проде — логируем. Пользователю — общий экран.
    header('Location: /Salfetka/login.html?err=auth'); exit;
}

if (!$user || !password_verify($pass, $user['password'])) {
    header('Location: /Salfetka/login.html?err=badcreds'); exit;
}

$_SESSION['user'] = [
    'id'       => (int)$user['id'],
    'username' => $user['username'],
    'role'     => $user['role'],
];

switch ($user['role']) {
    case 'worker':
        header('Location: /Salfetka/worker.php');   break;
    case 'deliver':
        header('Location: /Salfetka/delivery.php');  break;
    default: // 'user'
        header('Location: /Salfetka/client.php');    break;
}
exit;
