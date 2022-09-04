<?php
namespace Harp\lib\HarpDatabase\drivers;

use Harp\bin\ArgumentException;
use Harp\lib\HarpDatabase\drivers\ConnectionDriver;
use Harp\lib\HarpDatabase\HarpConnection;
use Throwable;

class ConnDriverRedis extends ConnectionDriver
{
    const DEFAULT_PORT = 6379;

    private $Connection; 

    public function __construct(HarpConnection &$InfoConnection) 
    {
        parent::__construct($InfoConnection);
    }
    
    public function getSgbdName()
    {
        return self::SGBD_REDIS;
    }

    public function defineInsertOID($returnColumn = null)
    {
        throw new ArgumentException('Method {'.__METHOD__.'} not implemented!');
    }

    public function connect()
    {
        $server = $this->InfoConnection->getServerName();
     
        $port = self::DEFAULT_PORT;
        
        if(self::DEFAULT_PORT != $this->InfoConnection->getPort())
        {
            $port = $this->getInfoConnection()->getPort();
        }
        
        try
        {
            if(empty($server))
            {
                throw new ArgumentException('Server name or ip Appears to be empty and returned type {%s}!',500);
            }

            $this->Connection = new \Redis();

            $this->Connection->connect($server,$port);
            $this->Connection->auth([$this->InfoConnection->getPassword()]);
            $this->Connection->select($this->InfoConnection->getDatabaseName());  
        }
        catch(Throwable $th)
        {
            throw $th;
        }
    }



    private function loadORM()
    {
        throw new ArgumentException(
            'Method {'.__METHOD__.'} not implemented!',
            'Not Implemented!',
            ArgumentException::ERROR_TYPE_EXCEPTION
        );
    }
    
    public function cleanColumnsToEncrypt()
    {
        throw new ArgumentException(
            'Method {'.__METHOD__.'} not implemented!',
            'Not Implemented!',
            ArgumentException::ERROR_TYPE_EXCEPTION
        );
    }
    
    public function clearColumnsToEncrypt()
    {
        throw new ArgumentException(
            'Method {'.__METHOD__.'} not implemented!',
            'Not Implemented!',
            ArgumentException::ERROR_TYPE_EXCEPTION
        );
    }  
    
    protected function instanceCryptography()
    {
        throw new ArgumentException(
            'Method {'.__METHOD__.'} not implemented!',
            'Not Implemented!',
            ArgumentException::ERROR_TYPE_EXCEPTION
        );
    }    
    
    private function getCommandSql()
    {
        throw new ArgumentException(
            'Method {'.__METHOD__.'} not implemented!',
            'Not Implemented!',
            ArgumentException::ERROR_TYPE_EXCEPTION
        );
    }

    public function setColumnsToEncrypt(Array $columns)
    {
        throw new ArgumentException(
            'Method {'.__METHOD__.'} not implemented!',
            'Not Implemented!',
            ArgumentException::ERROR_TYPE_EXCEPTION
        );
    }
    
    public function getInfoConnection()
    {
        return parent::getInfoConnection();
    }

    public function getDriverName()
    {
        return parent::getDriverName();
    }
    
    public function getConnectionName()
    {
        return parent::getConnectionName();
    }
    
    public function getJsonORM()
    {
        throw new ArgumentException(
            'Method {'.__METHOD__.'} not implemented!',
            'Not Implemented!',
            ArgumentException::ERROR_TYPE_EXCEPTION
        );
    }    
    
    public function getMigrate()
    {
        throw new ArgumentException(
            'Method {'.__METHOD__.'} not implemented!',
            'Not Implemented!',
            ArgumentException::ERROR_TYPE_EXCEPTION
        );
    }    
    
    public function getCharacterCaseSensitive()
    {
        throw new ArgumentException(
            'Method {'.__METHOD__.'} not implemented!',
            'Not Implemented!',
            ArgumentException::ERROR_TYPE_EXCEPTION
        );
    }
    
    public function getConnection()
    {
        return $this->Connection;
    }
}