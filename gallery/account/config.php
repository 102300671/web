<?php
$host = 'localhost';  // 或 127.0.0.1
$db   = 'gallery_db';
$user = 'gallery';
$pass = 'gallery';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    exit("数据库连接失败: " . $e->getMessage());
}