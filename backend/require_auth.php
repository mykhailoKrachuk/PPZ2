<?php
require __DIR__ . '/config.php';

function requireLogin(): array {
    if (empty($_SESSION['user'])) {
        header('Location: /Salfetka/login.html?err=auth');
        exit;
    }
    return $_SESSION['user'];
}

function requireRole(string $role): array {
    $u = requireLogin();
    if (($u['role'] ?? '') !== $role) {
        http_response_code(403);
        exit('Forbidden');
    }
    return $u;
}
