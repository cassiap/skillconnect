mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Primeiro tenta usar as variáveis do Railway.
// Se não existir (ambiente local), cai pro env() / padrão.
$host = getenv('MYSQLHOST') ?: env('DB_HOST', '127.0.0.1');
$user = getenv('MYSQLUSER') ?: env('DB_USER', 'root');
$pass = getenv('MYSQLPASSWORD') ?: env('DB_PASS', '');
$db   = getenv('MYSQLDATABASE') ?: env('DB_NAME', 'skillconnect');
$port = getenv('MYSQLPORT') ?: env('DB_PORT', 3306);

$cx = new mysqli(
    $host,
    $user,
    $pass,
    $db,
    (int) $port
);

$cx->set_charset("utf8mb4");