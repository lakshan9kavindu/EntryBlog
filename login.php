<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.html');
    exit;
}

$email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
$password = (string) ($_POST['password'] ?? '');

if ($email === false || $password === '') {
    http_response_code(422);
    exit('Please provide a valid email and password.');
}

$findUser = $pdo->prepare('SELECT id, email, password FROM users WHERE email = :email LIMIT 1');
$findUser->execute(['email' => $email]);
$user = $findUser->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    http_response_code(401);
    exit('Invalid email or password.');
}

startUserSession();
session_regenerate_id(true);
$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['user_email'] = (string) $user['email'];

header('Location: profile.php');
exit;