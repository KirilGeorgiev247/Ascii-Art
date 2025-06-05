<?php

namespace App\db\repository;

use App\db\Database;

class Repository {

    protected $database;
    protected $className;
    protected $tableName;
    // protected $tableFields;

    public function __construct($table_name, $className)
    {
        $this->database = new Database();
        $this->tableName = $table_name;
        // $this->tableFields = array_filter(array_keys(get_class_vars($className)), function ($key) {
        //     return !str_starts_with($key, '_');
        // });
        $this->className = $className;
    }

    public function __destruct()
    {
        $this->database->closeConnection();
    }
    
    // getById

    // getAll

    // create

    // update

    // delete
}

