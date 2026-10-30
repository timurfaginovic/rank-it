<?php

if (PHP_SAPI !== 'cli') {
    exit('Run this script from the command line.');
}

require_once __DIR__ . '/../dao/Database.php';

try {
    $connection = Database::getConnection();
    if ($connection->query('SELECT DATABASE()')->fetchColumn() !== 'rank_it') {
        throw new RuntimeException('Import is restricted to rank_it.');
    }
    if ($connection->query('SHOW TABLES')->fetchColumn() !== false) {
        throw new RuntimeException('rank_it is not empty. Import refused; existing data was preserved.');
    }

    $sql = file_get_contents(__DIR__ . '/rank_it.sql');
    if ($sql === false) {
        throw new RuntimeException('Could not read rank_it.sql.');
    }
    // The configured database already exists; the SQL export also supports manual creation.
    $sql = preg_replace('/\ACREATE DATABASE[^;]+;\s*USE rank_it;\s*/', '', $sql, 1);
    $connection->exec($sql);
    echo "PASS: imported schema and sample data into rank_it.\n";
} catch (Throwable $error) {
    if (isset($connection) && $connection->inTransaction()) {
        $connection->rollBack();
    }
    $message = $error instanceof PDOException
        ? 'MySQL import failed (code ' . ($error->errorInfo[1] ?? 0) . '). Inspect the database before retrying; DDL cannot be rolled back.'
        : $error->getMessage();
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}
