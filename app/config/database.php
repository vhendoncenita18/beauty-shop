<?php
declare(strict_types=1);

function database_connection(): PDO
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $settings = parse_ini_file(dirname(__DIR__, 2) . '/.env', false, INI_SCANNER_RAW);
    if ($settings === false) {
        throw new RuntimeException('Database configuration is unavailable.');
    }

    foreach (['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'] as $key) {
        if (!array_key_exists($key, $settings)) {
            throw new RuntimeException('Database configuration is incomplete.');
        }
    }

    if (!preg_match('/^[A-Za-z0-9_]+$/', $settings['DB_NAME'])) {
        throw new RuntimeException('Database configuration is invalid.');
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $settings['DB_HOST'],
        (int) $settings['DB_PORT'],
        $settings['DB_NAME']
    );

    $connection = new PDO($dsn, $settings['DB_USER'], $settings['DB_PASSWORD'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $connection;
}
