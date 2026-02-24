<?php
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/helpers.php';

if (!extension_loaded('mysqli') || !class_exists('mysqli')) {
    http_response_code(500);
    die("Erro DB: driver MySQL indisponivel no servidor.");
}

if (function_exists('mysqli_report') && defined('MYSQLI_REPORT_ERROR') && defined('MYSQLI_REPORT_STRICT')) {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
}

$host = env('MYSQLHOST', env('DB_HOST', '127.0.0.1'));
$user = env('MYSQLUSER', env('DB_USER', 'root'));
$pass = env('MYSQLPASSWORD', env('DB_PASS', ''));
$db   = env('MYSQLDATABASE', env('DB_NAME', 'skillconnect'));
$port = (int) env('MYSQLPORT', env('DB_PORT', 3306));

// Fallback: permite usar URL unica de conexao (MYSQL_URL / DATABASE_URL).
if ((!$host || !$user || !$db) && (env('MYSQL_URL') || env('DATABASE_URL'))) {
    $dbUrl = env('MYSQL_URL', env('DATABASE_URL', ''));
    $parts = parse_url($dbUrl);
    if (is_array($parts)) {
        $host = $parts['host'] ?? $host;
        $port = isset($parts['port']) ? (int) $parts['port'] : $port;
        $user = isset($parts['user']) ? rawurldecode((string) $parts['user']) : $user;
        $pass = isset($parts['pass']) ? rawurldecode((string) $parts['pass']) : $pass;
        $path = (string) ($parts['path'] ?? '');
        $dbFromUrl = ltrim($path, '/');
        if ($dbFromUrl !== '') {
            $db = $dbFromUrl;
        }
    }
}

try {
    $cx = new mysqli($host, $user, $pass, $db, $port);
    $cx->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    die("Erro DB: falha ao conectar com o banco.");
}
