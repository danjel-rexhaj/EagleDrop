<?php

if (!defined('ENV_FILE_LOADED')) {
    define('ENV_FILE_LOADED', true);

    $envPath = __DIR__ . '/../.env';
    if (is_file($envPath)) {
        foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if (getenv($key) === false) {
                putenv("$key=$value");
            }
        }
    }
}

function env(string $key, $default = null) {
    $value = getenv($key);
    if ($value === false) {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? false;
    }
    return $value !== false && $value !== null && $value !== '' ? $value : $default;
}
