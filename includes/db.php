<?php
/**
 * Returns a shared PDO connection (singleton).
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $cfg = require __DIR__ . '/../config/database.php';
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $cfg['host'], $cfg['port'], $cfg['database'], $cfg['charset']);
        $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

/** Run a prepared query and return the statement. */
function q(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}
