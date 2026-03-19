<?php
/**
 * Carrega variaveis do arquivo .env
 */
$envFile = __DIR__ . '/../.env';
if (is_file($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        // Remove BOM UTF-8 do primeiro item, se existir.
        $key = preg_replace('/^\xEF\xBB\xBF/', '', $key);
        $value = trim($value);

        // Suporta valores com aspas no estilo dotenv.
        $hasDoubleQuotes = str_starts_with($value, '"') && str_ends_with($value, '"');
        $hasSingleQuotes = str_starts_with($value, "'") && str_ends_with($value, "'");
        if (($hasDoubleQuotes || $hasSingleQuotes) && strlen($value) >= 2) {
            $value = substr($value, 1, -1);
        }

        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

/**
 * Retorna variavel de ambiente ou valor padrao
 */
function env(string $key, $default = null) {
    return $_ENV[$key] ?? (getenv($key) ?: $default);
}
