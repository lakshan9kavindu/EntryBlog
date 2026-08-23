<?php
declare(strict_types=1);

$environmentFile = is_file(dirname(__DIR__) . '/.env')
    ? dirname(__DIR__) . '/.env'
    : (is_file(__DIR__ . '/.env') ? __DIR__ . '/.env' : null);

$environment = ($environmentFile !== null && is_readable($environmentFile))
    ? parse_ini_file($environmentFile, false, INI_SCANNER_RAW)
    : [];

$databaseHost = (string) ($environment['DB_HOST'] ?? getenv('DB_HOST') ?? '');
$databaseName = (string) ($environment['DB_NAME'] ?? getenv('DB_NAME') ?? '');
$databaseUser = (string) ($environment['DB_USER'] ?? getenv('DB_USER') ?? '');
$databasePassword = (string) ($environment['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?? '');

if ($databaseHost === '' || $databaseName === '' || $databaseUser === '') {
    http_response_code(500);
    exit('Database configuration missing. Please ensure DB_HOST, DB_NAME, and DB_USER are defined in your .env file.');
}

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
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS saved_articles (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            userid INT UNSIGNED NOT NULL,
            articleid INT UNSIGNED NOT NULL,
            FOREIGN KEY (userid) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (articleid) REFERENCES articles(id) ON DELETE CASCADE,
            UNIQUE (userid, articleid)
        )'
    );
} catch (PDOException $exception) {
    http_response_code(500);
    exit('Database connection failed. Check your .env database credentials and MySQL status.');
}