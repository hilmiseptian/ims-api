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

$migrationDir = __DIR__ . '/migrations';
$files = glob($migrationDir . '/*.sql');
sort($files);

echo "Running migrations...\n";
foreach ($files as $file) {
    echo "  > " . basename($file) . "\n";
    $sql = file_get_contents($file);
    $pdo->exec($sql);
}
echo "Done.\n";
