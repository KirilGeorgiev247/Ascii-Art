<?php

namespace App\db;

use App\config\DbConfig;
use \PDO;
use \PDOException;

class Database
{
    private const SQL_SCRIPT = __DIR__ . '/../../ascii-art-db.sql';

    private static ?self $instance = null;
    private ?PDO $connection = null;

    private function __construct()
    {
        $db_host = DbConfig::get_setting("HOST");
        $db_name = DbConfig::get_setting("NAME");
        $username = DbConfig::get_setting("USERNAME");
        $password = DbConfig::get_setting("PASSWORD");

        $this->runSqlFile(self::SQL_SCRIPT, $db_host, $username, $password);

        $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
        try {
            $this->connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException("Database connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    public function runSqlFile(string $sql_file_path, string $db_host, string $username, string $password): void
    {
        if (!file_exists($sql_file_path)) {
            throw new \RuntimeException("SQL file not found: $sql_file_path");
        }

        $sql = file_get_contents($sql_file_path);
        if ($sql === false) {
            throw new \RuntimeException("Could not read SQL file: $sql_file_path");
        }

        try {
            $pdo = new PDO("mysql:host=$db_host", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
                if ($statement) {
                    $pdo->exec($statement);
                }
            }
        } catch (PDOException $e) {
            throw new \RuntimeException("Error running SQL file: " . $e->getMessage());
        }
    }
}