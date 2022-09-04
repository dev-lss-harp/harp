<?php
namespace Harp\lib\HarpDB;

class ConnectionEnum
{
    const DB_CONNECTION_PLATFORM_SQL_SERVER = 'sqlsrv|mssql';
    const DB_CONNECTION_PLATFORM_MYSQL = 'mysql|mysqli';
    const DB_CONNECTION_MYSQLI = 'mysqli';
    const DB_CONNECTION_MYSQL  = 'mysql';
    const DB_CONNECTION_SQLSRV = 'sqlsrv';
    const DB_CONNECTION_MSSQL = 'mssql'; 
    const DB_CONNECTION_PGSQL = 'pgsql';
    const SO_LINUX = 'lin';
    const SO_WINDOWS = 'win';
    
    private $supportedDrivers = Array
    (
        self::DB_CONNECTION_MSSQL,
        self::DB_CONNECTION_MYSQL,
        self::DB_CONNECTION_SQLSRV,
        self::DB_CONNECTION_PLATFORM_MYSQL,
        self::DB_CONNECTION_PLATFORM_SQL_SERVER,
        self::DB_CONNECTION_PGSQL,
    );      
    
    public static function get($key)
    {
        return new ConnectionEnum($key);
    }
    
    public function isSupportedDriver($nameDriver)
    {
        return in_array($nameDriver,$this->supportedDrivers);
    }
}
