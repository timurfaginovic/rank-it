<?php

class Database
{
    public static function getConnection(): PDO
    {
        static $connection = null;
        if ($connection !== null) {
            return $connection;
        }

        $path = __DIR__ . '/../config/config.php';
        if (!is_file($path)) {
            throw new RuntimeException('Copy config.example.php to config.php and set your database credentials.');
        }
        $config = require $path;
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
        try {
            $connection = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 3,
            ]);
        } catch (PDOException $error) {
            throw new RuntimeException('Database connection failed. Check config.php and the MySQL server.');
        }
        return $connection;
    }
}
