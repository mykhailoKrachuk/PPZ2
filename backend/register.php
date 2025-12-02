<?php
declare(strict_types=1);
require __DIR__ . '/config.php';   // $db = new PDO(...)
global $db;

$fields = [
    'username','password','mail','phone_number',
    'name','surname','address','country','city','postal_code'
];

// 2.1 Валидация «все поля заполнены»
$data = [];
foreach ($fields as $f) {
    $v = trim($_POST[$f] ?? '');
    if ($v === '') {
        header('Location: /Salfetka/register.html?err=empty'); exit;
    }
    $data[$f] = $v;
}

try {
    // 2.2 Проверка уникальности логина и e-mail
    $q = $db->prepare('SELECT 1 FROM users WHERE username = :u OR mail = :m LIMIT 1');
    $q->execute([':u' => $data['username'], ':m' => $data['mail']]);
    if ($q->fetch()) {
        header('Location: /Salfetka/register.html?err=exists'); exit;
    }

    // 2.3 Хэш пароля (bcrypt). Совместим с password_verify() в твоём login.php
    $hash = password_hash($data['password'], PASSWORD_BCRYPT);

    // 2.4 Вставка пользователя (роль — user)
    $ins = $db->prepare(
        'INSERT INTO users
     (username, password, role, mail, phone_number, name, surname, address, country, city, postal_code)
     VALUES
     (:username, :password, :role, :mail, :phone_number, :name, :surname, :address, :country, :city, :postal_code)'
    );
    $ins->execute([
        ':username'     => $data['username'],
        ':password'     => $hash,
        ':role'         => 'user',
        ':mail'         => $data['mail'],
        ':phone_number' => $data['phone_number'],
        ':name'         => $data['name'],
        ':surname'      => $data['surname'],
        ':address'      => $data['address'],
        ':country'      => $data['country'],
        ':city'         => $data['city'],
        ':postal_code'  => $data['postal_code'],
    ]);

    // 2.5 Готово → на логин с сообщением
    header('Location: /Salfetka/login.html?registered=1'); exit;

} catch (Throwable $e) {
    // при желании: error_log($e->getMessage());
    header('Location: /Salfetka/register.html?err=server'); exit;
}
