<?php
session_start();

function requireRole(string $role): void {
    if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== $role) {
        // Проверяем, ожидается ли JSON ответ
        $isJsonRequest = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false 
                      || strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false
                      || $_SERVER['REQUEST_METHOD'] === 'POST'; // API endpoints обычно POST
        
        if ($isJsonRequest) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        header('Location: /Salfetka/login.html?err=auth'); 
        exit;
    }
}

function requireAnyRole(array $roles): void {
    if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'] ?? '', $roles, true)) {
        header('Location: /Salfetka/login.html?err=auth'); exit;
    }
}

function userRole(): ?string {
    return $_SESSION['user']['role'] ?? null;
}
