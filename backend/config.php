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

// ==== Подключение ====
$dsn = "pgsql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME;

$db = new PDO($dsn, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// Сессии на всём бэке
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
