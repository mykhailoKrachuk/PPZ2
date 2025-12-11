<?php
declare(strict_types=1);

// ==== DB creds (AWS RDS) ====
const DB_HOST = 'paczki-db.cnmqqyasmye2.eu-north-1.rds.amazonaws.com';
const DB_PORT = '5432';
const DB_NAME = 'paczki';
const DB_USER = 'postgres';
const DB_PASS = '38PD97FbCW88gHnqW9F0';

// ==== Роли ====
const ROLE_USER   = 'user';
const ROLE_WORKER = 'worker';

// ==== Email SMTP настройки ====
// Для Gmail: используйте "Пароль приложения" (не обычный пароль!)
// Инструкция: https://support.google.com/accounts/answer/185833
const SMTP_ENABLED = true; // Установите false, чтобы отключить отправку email
const SMTP_HOST = 'smtp.gmail.com'; // Для Gmail
const SMTP_PORT = 587;
const SMTP_USER = 'salfetka.coo@gmail.com'; // ВАШ email
const SMTP_PASS = 'dbrpjkujnokbruxj'; // Пароль приложения Gmail (не обычный пароль!)
const SMTP_FROM_EMAIL = 'salfetka.coo@gmail.com'; // Должен совпадать с SMTP_USER для Gmail
const SMTP_FROM_NAME = 'Salfetka';

// ==== Подключение ====
$dsn = "pgsql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";options='--client_encoding=UTF8'";

$db = new PDO($dsn, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// Устанавливаем кодировку UTF-8 для подключения
$db->exec("SET client_encoding TO 'UTF8'");

// Сессии на всём бэке
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
