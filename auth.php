<?php
declare(strict_types=1);

function startUserSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
        session_start();
    }
}

function escapeOutput(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function requireUser(): int
{
    startUserSession();

    if (!isset($_SESSION['user_id']) || !is_int($_SESSION['user_id'])) {
        header('Location: login.html');
        exit;
    }

    return $_SESSION['user_id'];
}