<?php
/**
 * PDO 연결 (요청당 1회 생성). 쿼리는 반드시 prepared statement 사용.
 *   $stmt = db()->prepare('SELECT * FROM posts WHERE id = ?');
 *   $stmt->execute([$id]);
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = app_config()['db'];
        $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}
