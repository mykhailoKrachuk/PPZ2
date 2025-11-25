<?php
require __DIR__ . '/config.php';

// Читаем поля из формы
$login = trim($_POST['login'] ?? '');
$pass  = $_POST['password'] ?? '';

if ($login === '' || $pass === '') {
    header('Location: /Salfetka/login.html?err=empty');
    exit;
}


//   users(id, login, password_hash, role)
$stmt = $db->prepare("SELECT id, login, password_hash, role FROM users WHERE login = :login LIMIT 1");
$stmt->execute([':login' => $login]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: /Salfetka/login.html?err=badcreds');
    exit;
}

// Проверка пароля
$ok = false;
if (isset($user['password_hash'])) {
    // Рекомендуется хранить хэш (bcrypt/argon2)
    $ok = password_verify($pass, $user['password_hash']);
} else {
    // Временный вариант, если пароли пока в plaintext (ПЛОХО, но бывает в учебных БД)
    $ok = ($pass === ($user['password'] ?? null));
}

if (!$ok) {
    header('Location: /Salfetka/login.html?err=badcreds');
    exit;
}

// Формируем роль
$role = $user['role'] ?? ((!empty($user['is_employee']) && $user['is_employee']) ? 'employee' : 'user');

// Сохраняем в сессию и редиректим
$_SESSION['user'] = [
    'id'    => (int)$user['id'],
    'login' => $user['login'],
    'role'  => $role,
];

header('Location: ' . ($role === 'employee' ? '/Salfetka/worker.php' : '/Salfetka/client.php'));
exit;
