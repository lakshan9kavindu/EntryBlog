<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: signup.html');
    exit;
}

$email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
$password = (string) ($_POST['password'] ?? '');

if ($email === false || strlen($password) < 8 || strlen($password) > 255) {
    http_response_code(422);
    exit('Please provide a valid email and a password between 8 and 255 characters.');
}

$checkUser = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
$checkUser->execute(['email' => $email]);

if ($checkUser->fetch()) {
    http_response_code(409);
    exit('An account with that email already exists.');
}

$avatarOptions = [
    'assets/avatars/avatar-1.svg',
    'assets/avatars/avatar-2.svg',
    'assets/avatars/avatar-3.svg',
    'assets/avatars/avatar-4.svg',
    'assets/avatars/avatar-5.svg',
    'assets/avatars/avatar-6.svg',
];
$randomAvatar = $avatarOptions[array_rand($avatarOptions)];

$createUser = $pdo->prepare(
    'INSERT INTO users (email, password, dp) VALUES (:email, :password, :dp)'
);
$createUser->execute([
    'email' => $email,
    'password' => password_hash($password, PASSWORD_DEFAULT),
    'dp' => $randomAvatar,
]);

header('Location: login.html?registered=1');
exit;