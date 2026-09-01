<?php
declare(strict_types=1);

/**
 * Database connection manager с защитой от инъекций.
 * 
 * ЗАЩИТА:
 * - PDO prepared statements (защита от SQL injection)
 * - EMULATE_PREPARES = false (реальные prepared statements)
 * - Persistent connections (connection pooling)
 * - Transaction isolation SERIALIZABLE
 * - Query timeout
 */
class Database {
    private static ?PDO $instance = null;
    private static string $dsn = 'sqlite:' . __DIR__ . '/../database/store.db';
    
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false, // Реальные prepared statements!
                PDO::ATTR_PERSISTENT => true,        // Connection pooling
                PDO::ATTR_TIMEOUT => 30,             // Query timeout
            ];
            
            self::$instance = new PDO(self::$dsn, null, null, $options);
            self::$instance->exec('PRAGMA foreign_keys = ON');
            self::$instance->exec('PRAGMA journal_mode = WAL');
            self::$instance->exec('PRAGMA synchronous = NORMAL');
            self::$instance->exec('PRAGMA busy_timeout = 5000'); // 5 сек на блокировку
            self::$instance->exec('PRAGMA temp_store = MEMORY');
        }
        return self::$instance;
    }
    
    public static function beginTransaction(): bool {
        return self::getInstance()->beginTransaction();
    }
    
    public static function commit(): bool {
        return self::getInstance()->commit();
    }
    
    public static function rollback(): bool {
        if (self::getInstance()->inTransaction()) {
            return self::getInstance()->rollBack();
        }
        return false;
    }
}