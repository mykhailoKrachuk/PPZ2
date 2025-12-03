<?php
session_start();

function requireRole(string $role): void {
    if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== $role) {
        header('Location: /Salfetka/login.html?err=auth'); exit;
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
