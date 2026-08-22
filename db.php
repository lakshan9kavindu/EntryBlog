<?php
declare(strict_types=1);

$environmentFile = __DIR__ . '/.env';
$environment = is_file($environmentFile)
    ? parse_ini_file($environmentFile, false, INI_SCANNER_RAW)
    : [];

$databaseHost = $environment['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1';
$databaseName = $environment['DB_NAME'] ?? getenv('DB_NAME') ?: 'blog_system';
$databaseUser = $environment['DB_USER'] ?? getenv('DB_USER') ?: 'root';
$databasePassword = $environment['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';

$databaseDsn = "mysql:host={$databaseHost};dbname={$databaseName};charset=utf8mb4";

try {
    $pdo = new PDO($databaseDsn, $databaseUser, $databasePassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $articleColumns = $pdo->query('SHOW COLUMNS FROM articles')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('title', $articleColumns, true)) {
        $pdo->exec("ALTER TABLE articles ADD COLUMN title VARCHAR(255) NOT NULL DEFAULT '' AFTER id");
    }
    if (!in_array('category', $articleColumns, true)) {
        $pdo->exec("ALTER TABLE articles ADD COLUMN category VARCHAR(100) NOT NULL DEFAULT 'Technology' AFTER thumbnail");
    }
} catch (PDOException $exception) {
    http_response_code(500);
    exit('Database connection failed. Import blog-system.sql and check your XAMPP MySQL settings.');
}