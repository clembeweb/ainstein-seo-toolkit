<?php

namespace Core;

use PDO;
use PDOException;
use PDOStatement;

class Database
{
    private static ?PDO $pdo = null;
    private static array $config = [];
    private static int $maxRetries = 2;

    /**
     * Ottiene la connessione PDO (con lazy loading)
     */
    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            self::connect();
        }
        return self::$pdo;
    }

    /**
     * Crea nuova connessione al database
     */
    private static function connect(): void
    {
        if (empty(self::$config)) {
            self::$config = require __DIR__ . '/../config/database.php';
        }

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            self::$config['host'],
            self::$config['dbname'],
            self::$config['charset'] ?? 'utf8mb4'
        );

        // Determina timezone per MySQL (sincronizza con PHP)
        $phpTimezone = date_default_timezone_get();
        $mysqlTimezone = '+00:00'; // Default UTC
        if ($phpTimezone === 'Europe/Rome') {
            // Italia: +01:00 (inverno) o +02:00 (estate/DST)
            $mysqlTimezone = date('P'); // Ritorna offset corrente es. "+01:00"
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, time_zone = '{$mysqlTimezone}'"
        ];

        try {
            self::$pdo = new PDO(
                $dsn,
                self::$config['username'],
                self::$config['password'],
                $options
            );
            Logger::channel('database')->debug('PDO connection created');
        } catch (\Exception $e) {
            Logger::channel('database')->error('PDO connection failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Forza riconnessione
     */
    public static function reconnect(): void
    {
        Logger::channel('database')->debug('Reconnect requested');
        self::$pdo = null;
        try {
            self::connect();
            Logger::channel('database')->info('Reconnect successful');
        } catch (\Exception $e) {
            Logger::channel('database')->error('Reconnect failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Verifica se connessione è attiva
     */
    public static function ping(): bool
    {
        if (self::$pdo === null) {
            return false;
        }
        try {
            self::$pdo->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Verifica se errore è "gone away" e richiede reconnect
     */
    private static function isGoneAway(PDOException $e): bool
    {
        $code = $e->getCode();
        $msg = $e->getMessage();

        // Error 2006: MySQL server has gone away
        // Error 2013: Lost connection to MySQL server
        // HY000: General error (with gone away in message)
        $isGoneAway = (
            $code === 2006 || $code === '2006' ||
            $code === 2013 || $code === '2013' ||
            $code === 'HY000' ||
            stripos($msg, 'server has gone away') !== false ||
            stripos($msg, 'Lost connection') !== false ||
            stripos($msg, 'gone away') !== false
        );

        // Log per debug
        if ($isGoneAway) {
            Logger::channel('database')->warning('Gone away detected', ['code' => $code, 'message' => $msg]);
        }

        return $isGoneAway;
    }

    /**
     * Esegue query con auto-reconnect
     */
    public static function query(string $sql, array $params = []): PDOStatement|false
    {
        $lastException = null;

        for ($attempt = 0; $attempt <= self::$maxRetries; $attempt++) {
            try {
                $pdo = self::getConnection();
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                return $stmt;
            } catch (PDOException $e) {
                $lastException = $e;

                if (self::isGoneAway($e) && $attempt < self::$maxRetries) {
                    Logger::channel('database')->info('Auto-reconnect attempt', ['attempt' => $attempt + 1, 'error' => $e->getMessage()]);
                    self::reconnect();
                    continue;
                }
                throw $e;
            }
        }

        throw $lastException;
    }

    /**
     * Esegue query senza ritorno dati (INSERT/UPDATE/DELETE con SQL complesso)
     */
    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::query($sql, $params);
        return $stmt ? $stmt->rowCount() : 0;
    }

    /**
     * Esegue query e ritorna tutti i risultati
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::query($sql, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Esegue query e ritorna prima riga
     */
    public static function fetch(string $sql, array $params = []): ?array
    {
        $stmt = self::query($sql, $params);
        $result = $stmt ? $stmt->fetch() : false;
        return $result ?: null;
    }

    /**
     * Esegue query e ritorna singolo valore
     */
    public static function fetchColumn(string $sql, array $params = [], int $column = 0): mixed
    {
        $stmt = self::query($sql, $params);
        return $stmt ? $stmt->fetchColumn($column) : false;
    }

    /**
     * Esegue INSERT e ritorna last insert ID
     */
    public static function insert(string $table, array $data): int|false
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

        $lastException = null;
        for ($attempt = 0; $attempt <= self::$maxRetries; $attempt++) {
            try {
                $pdo = self::getConnection();
                $stmt = $pdo->prepare($sql);
                $stmt->execute(array_values($data));
                return (int) $pdo->lastInsertId();
            } catch (PDOException $e) {
                $lastException = $e;
                Logger::channel('database')->warning('INSERT failed', ['attempt' => $attempt + 1, 'table' => $table, 'error' => $e->getMessage()]);
                if (self::isGoneAway($e) && $attempt < self::$maxRetries) {
                    Logger::channel('database')->info('Reconnect on INSERT', ['attempt' => $attempt + 1]);
                    sleep(1);
                    try {
                        self::reconnect();
                        self::$pdo->query('SELECT 1');
                        Logger::channel('database')->info('Reconnect verified after INSERT retry');
                    } catch (\Exception $reconnectError) {
                        Logger::channel('database')->error('Reconnect failed on INSERT retry', ['error' => $reconnectError->getMessage()]);
                        sleep(2);
                        self::reconnect();
                    }
                    continue;
                }
                Logger::channel('database')->error('INSERT giving up', ['attempt' => $attempt + 1, 'table' => $table]);
                throw $e;
            }
        }
        throw $lastException;
    }

    /**
     * Esegue UPDATE e ritorna righe affected
     */
    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(' = ?, ', array_keys($data)) . ' = ?';
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";

        $stmt = self::query($sql, array_merge(array_values($data), $whereParams));
        return $stmt ? $stmt->rowCount() : 0;
    }

    /**
     * Esegue DELETE e ritorna righe affected
     */
    public static function delete(string $table, string $where, array $params = []): int
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = self::query($sql, $params);
        return $stmt ? $stmt->rowCount() : 0;
    }

    /**
     * Conta righe in una tabella
     */
    public static function count(string $table, string $where = '1=1', array $params = []): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$where}";
        $result = self::fetch($sql, $params);
        return (int) ($result['count'] ?? 0);
    }

    /**
     * Inizia transazione con auto-reconnect
     */
    public static function beginTransaction(): bool
    {
        if (!self::ping()) {
            self::reconnect();
        }
        return self::getConnection()->beginTransaction();
    }

    /**
     * Commit transazione
     */
    public static function commit(): bool
    {
        return self::getConnection()->commit();
    }

    /**
     * Rollback transazione
     */
    public static function rollback(): bool
    {
        return self::getConnection()->rollBack();
    }

    /**
     * Verifica se siamo in una transazione
     */
    public static function inTransaction(): bool
    {
        return self::getConnection()->inTransaction();
    }

    /**
     * Escape per LIKE
     */
    public static function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }

    /**
     * Getter per PDO diretto (per casi speciali)
     */
    public static function getPdo(): PDO
    {
        if (!self::ping()) {
            self::reconnect();
        }
        return self::getConnection();
    }

    /**
     * Alias per compatibilità con codice esistente
     */
    public static function getInstance(): PDO
    {
        return self::getPdo();
    }

    /**
     * Ottiene ultimo ID inserito
     */
    public static function lastInsertId(): string|false
    {
        return self::getConnection()->lastInsertId();
    }
}
