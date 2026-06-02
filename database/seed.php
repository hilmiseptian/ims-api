#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$port = $_ENV['DB_PORT'] ?? '5432';
$name = $_ENV['DB_NAME'] ?? 'uam_db';
$user = $_ENV['DB_USER'] ?? 'postgres';
$pass = $_ENV['DB_PASS'] ?? '';

$dsn = "pgsql:host={$host};port={$port};dbname={$name}";
$pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$seedDir = __DIR__ . '/seeds';
$files = glob($seedDir . '/*.sql');
sort($files);

echo "Running seeds...\n";
foreach ($files as $file) {
    echo "  > " . basename($file) . "\n";
    $sql = file_get_contents($file);
    $pdo->exec($sql);
}
echo "Done.\n";

// Fix admin password hash
echo "Updating admin password hash...\n";
$hash = password_hash('Admin123!', PASSWORD_BCRYPT, ['cost' => 12]);
$stmt = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE username = 'admin'");
$stmt->execute([':hash' => $hash]);
$hash2 = password_hash('Password123!', PASSWORD_BCRYPT, ['cost' => 12]);
$stmt = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE username != 'admin'");
$stmt->execute([':hash' => $hash2]);
echo "Passwords set. admin=Admin123!, others=Password123!\n";
