<?php
namespace server\classes;

class ImMysql extends ChatBase{
    private static $pdo;

    private function __construct()
    {
        $config = require DOMAIN . '/config/db.config.php';
        $dsn = $config['type'] . ':host=' . $config['host'] . ';dbname=' . $config['dbname'];
        try{
            self::$pdo = new \PDO($dsn . ';charset=utf8mb4', array(\PDO::ATTR_ERRMODE=>\PDO::ERRMODE_EXCEPTION));
        }catch (\PDOException $e){
            echo $e->errorInfo;
        }
    }

    public static function getInstance(){
        if(empty(self::$pdo)){
            self::$pdo = new self;
        }
        return self::$pdo;
    }

    public static function close(){
        self::$pdo = null;
    }
}