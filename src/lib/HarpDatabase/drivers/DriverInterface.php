<?php
namespace Harp\lib\HarpDatabase\drivers;

interface DriverInterface
{
    const SGBD_POSTGRE_SQL = 'postgreSQL';
    const SGBD_SQL_SERVER = 'SqlServer';
    const SGBD_MYSQL = 'Mysql';
    const SGBD_MARIA_DB = 'MariaDB';
    const SGBD_REDIS = 'Redis';
    
    public function getDriverName();
    public function getConnectionName();
    public function getCharacterCaseSensitive();
}
