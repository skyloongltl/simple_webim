<?php

namespace server\classes;

class Predis
{
    private static $instance;

    private function __construct()
    {
        $config = require DOMAIN . '/config/redis.config.php';
        self::$instance = new \Redis();
        self::$instance->connect($config['host'], $config['port']);
    }

    public static function getInstance()
    {
        if(!isset(self::$instance)){
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function close(){
        if(isset(self::$instance)){
            self::$instance->close();
        }
    }
}