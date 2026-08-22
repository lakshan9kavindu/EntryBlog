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

function csrfToken(): string
{
    startUserSession();
    $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(string $token): void
{
    startUserSession();

    if ($token === '' || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
        http_response_code(403);
        exit('Invalid form token. Please reload the page and try again.');
    }
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