<?php

namespace App\db\repository;

use App\db\Database;

class Repository
{

    protected $database;
    protected $className;
    protected $tableName;

    public function __construct($table_name, $className)
    {
        $this->database = new Database();
        $this->tableName = $table_name;

        $this->className = $className;
    }

    public function __destruct()
    {
        $this->database->closeConnection();
    }

}

