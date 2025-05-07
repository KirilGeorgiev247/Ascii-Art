<?php

namespace App\db;

use App\config\DbConfig;
use \PDO;
class Database
{
    private $connection;

    public function __construct()
    {
        $db_host = DbConfig::get_setting("HOST");
        $db_name = DbConfig::get_setting("NAME");
        $username = DbConfig::get_setting("USERNAME");
        $password = DbConfig::get_setting("PASSWORD");

        $this->connection = new PDO("mysql:host=$db_host;dbname=$db_name", $username, $password);
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    public function closeConnection(): void {
        $this->connection = null;
    }   
}


