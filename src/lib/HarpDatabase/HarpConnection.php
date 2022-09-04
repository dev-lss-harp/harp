<?php
namespace Harp\lib\HarpDatabase;

use Exception;
use Harp\lib\HarpCryptography\HarpCryptography;

class HarpConnection
{
    const DV_POSTGRES = 'pgsql';
    const DV_SQL_SERVER = 'sqlsrv';
    const DV_MYSQL = 'mysqli';
    const DV_MARIA_DB = 'mysqli';    
    const DV_REDIS = 'redis';

    private $suportedDrivers = [
        self::DV_MARIA_DB,
        self::DV_MYSQL,
        self::DV_POSTGRES,
        self::DV_SQL_SERVER,
        self::DV_REDIS
    ];
    
    private $infoConn = [];
    private $connName;
    private $connectionID = null;

    private function defConnectionName(?string $connName,?string $driver)
    {

        $i = 0;
        $strIncr = '';
        $underline = '';
        $cnName = $connName;
    
        do
        {
            $cnName = sprintf('%s%s%s',$driver,$underline,$strIncr);
            
            ++$i;
            $strIncr = strval($i);
            $underline = '_';

        }
        while(isset($this->infoConn[$cnName]));

        $this->connName = $cnName;
    }
    
    public function __construct($driver,$connName = null)
    {
        if(!in_array($driver,$this->suportedDrivers))
        {
            throw new \Exception('Unsupported driver{'.$driver.'}, supported drivers: {'.implode(',',$this->suportedDrivers).'}');
        }
        
        $this->defConnectionName($connName,$driver);
        
        $this->infoConn[$this->connName]['driver'] = $driver;
        
        return $this;
    }
    
    public function setConnectionID($id)
    {
        $this->connectionID = $id;
    }    
    
    public function getConnectionID()
    {
        return $this->connectionID;
    }
    
    public function getDriverName()
    {
        return $this->infoConn[$this->connName]['driver']; 
    }

    public function setServerName($serverName)
    {
        $this->infoConn[$this->connName]['serverName'] = $serverName;
        return $this;
    }

    public function setServerIP($serverIP)
    {
        $this->infoConn[$this->connName]['serverIP'] = $serverIP;
        return $this;
    }

    public function setPort($port)
    {
        $this->infoConn[$this->connName]['port'] = $port;
        return $this;
    }

    public function setUserName($userName)
    {
        $this->infoConn[$this->connName]['username'] = $userName;
        return $this;
    }

    public function setDatabaseName($databaseName)
    {
        $this->infoConn[$this->connName]['databaseName'] = $databaseName;
        return $this;
    }

    public function setPassword($password)
    {
        $this->infoConn[$this->connName]['password'] = $password;
        return $this;
    } 
    
    public function getServerName()
    {
        return $this->infoConn[$this->connName]['serverName'];
    }

    public function getServerIP()
    {
        
        return $this->infoConn[$this->connName]['serverIP'];
    }

    public function getPort()
    {
        return $this->infoConn[$this->connName]['port'];
    }

    public function getUserName()
    {
        return $this->infoConn[$this->connName]['username'];
    }

    public function getDatabaseName()
    {
        return $this->infoConn[$this->connName]['databaseName'];
    }

    public function getPassword()
    {
        return $this->infoConn[$this->connName]['password'];
    }     
    
    public function getSuportedDrivers()
    {
        return $this->suportedDrivers;
    }

    public function getInfoConn()
    {
        return $this->infoConn;
    }

    public function getConnName()
    {
        return $this->connName;
    }

    public function createConnectionFile($passEncryptionFile,$operatorMode)
    {
        try
        {
            $pFile = dirname(dirname(dirname(__DIR__))).'/cnf';

            if(!file_exists($pFile.DIRECTORY_SEPARATOR.$this->getDatabaseName()))
            {
                if(empty(trim($passEncryptionFile)))
                {
                    throw new Exception('to save a database configuration file it is necessary to enter a key, this key will be used to decrypt the data even in the cli.');
                }
                $cn = 
                [
                    'dbdriver' => $this->infoConn[$this->connName]['driver'],
                    'dbserver' => $this->getServerName(),
                    'dbport' => $this->getPort(),
                    'dbname' => $this->getDatabaseName(),
                    'dbuser' => $this->getUserName(),
                    'dbpass' => $this->getPassword(),
                ];
        
        
                $json = json_encode($cn);
           
                $crypt = new HarpCryptography($operatorMode,$passEncryptionFile);
                $encrypted = $crypt->encrypt($json);
                if(!is_string($encrypted))
                {
                    throw new Exception('encryption failed!');
                }

                if(!is_dir($pFile))
                {
                    throw new Exception('directory {cnf} not found in: {'.$pFile.'}');
                }
 
                file_put_contents($pFile.'/'.$this->getDatabaseName(),$encrypted);
            }
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
    }

}
