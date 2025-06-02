<?php

namespace App\db;

use App\config\DbConfig;
use \PDO;
class Database
{
    private const SQL_SCRIPT = __DIR__ . '/../../ascii-art-db.sql';

    private $connection;

    public function runSqlFile(string $sql_file_path, string $db_host, string $username, string $password): void
    {
        echo "<p>Start using method: $sql_file_path</p>";
        if (!file_exists($sql_file_path)) {
            echo "<p>SQL file not found: $sql_file_path</p>";
            throw new \RuntimeException("SQL file not found: $sql_file_path");
        }

        $sql = file_get_contents($sql_file_path);
        if ($sql === false) {
            echo "<p>Could not read SQL file: $sql_file_path</p>";
            throw new \RuntimeException("Could not read SQL file: $sql_file_path");
        }

        echo "<p>Running SQL file: $sql_file_path</p>";

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

    public function __construct()
    {
        $db_host = DbConfig::get_setting("HOST");
        //$db_name = DbConfig::get_setting("NAME");
        $username = DbConfig::get_setting("USERNAME");
        $password = DbConfig::get_setting("PASSWORD");

        $this->runSqlFile(self::SQL_SCRIPT, $db_host, $username, $password);
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    public function closeConnection(): void {
        $this->connection = null;
    }   
}


