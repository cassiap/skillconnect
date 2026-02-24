<?php
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/helpers.php';

$host = getenv('MYSQLHOST') ?: env('DB_HOST', '127.0.0.1');
$user = getenv('MYSQLUSER') ?: env('DB_USER', 'root');
$pass = getenv('MYSQLPASSWORD') ?: env('DB_PASS', '');
$db   = getenv('MYSQLDATABASE') ?: env('DB_NAME', 'skillconnect');
$port = getenv('MYSQLPORT') ?: env('DB_PORT', 3306);

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";

    $cx = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

} catch (PDOException $e) {
    die("Erro DB: " . $e->getMessage());
}