<?php
declare(strict_types=1);

const DB_HOST = 'localhost';
const DB_PORT = '5432';
const DB_NAME = 'baza_grupy';
const DB_USER = 'postgres';
const DB_PASS = 'strongPass';

$dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', DB_HOST, DB_PORT, DB_NAME);

try {
    $db = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    exit('DB connection failed: ' . htmlspecialchars($e->getMessage()));
}

ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
session_start();
