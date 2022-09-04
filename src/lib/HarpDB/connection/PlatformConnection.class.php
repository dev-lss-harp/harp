<?php
/*
 * Copyright 2010 Leonardo Souza da Silva <allezo.lss@gmail.com>.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace Harp\lib\HarpDB;

include_once(__DIR__.'/ConnectionEnum.class.php');

use etc\HarpDatabase\connection\ConnectionEnum;

use ArrayObject;
use Exception;

class PlatformConnection
{
    private $ContainerInfoConnection;
    private $ConnectionEnum;
    private $defaultConnection;
    const SO_WINDOWS = 'win';
    const SO_LINUX = 'lin';
        
    public function __construct()
    {
        include_once (__DIR__.'/InfoConnection.class.php');
        
        $this->ContainerInfoConnection = new ArrayObject([],ArrayObject::ARRAY_AS_PROPS);
    
        $this->ConnectionEnum = new ConnectionEnum();
    }
    
    private function getSystemOperation()
    {
        return strtolower(substr(PHP_OS,0,3));
    }
    
    private function getDriverSuportSqlServer($connectionDriver)
    {
        $os = $this->getSystemOperation();
        
        $dName = null;

        try
        {
            if($connectionDriver == ConnectionEnum::DB_CONNECTION_PLATFORM_SQL_SERVER)
            {
                if($os == self::SO_WINDOWS)
                {
                    if(!extension_loaded('sqlsrv'))
                    {
                        throw new \Harp\bin\ArgumentException('drive {sqlsrv} is not load to operating system : '.$os);
                    }

                    $dName = ConnectionEnum::DB_CONNECTION_SQLSRV;
                }
                else if($os == self::SO_LINUX)
                {
                    if(!extension_loaded('mssql'))
                    {
                        throw new \Harp\bin\ArgumentException('drive {mssql} is not load to operating system : '.$os);
                    }
                    
                    $dName = ConnectionEnum::DB_CONNECTION_MSSQL;
                }
                else
                {
                    throw new \Harp\bin\ArgumentException('No operating system support to: '.$os);
                }            
            }
        }
        catch(Exception $ex)
        {
            throw $ex;
        }

        return $dName;
    }
    
    private function getDriverSuportMysql($connectionDriver)
    {
        $dConn = null;
        
        try
        {
            if($connectionDriver == ConnectionEnum::DB_CONNECTION_PLATFORM_MYSQL)
            {
                if(extension_loaded('mysqli'))
                {
                    $dConn = ConnectionEnum::DB_CONNECTION_MYSQLI;
                }
                else if(extension_loaded('mysql'))
                {
                    $dConn = ConnectionEnum::DB_CONNECTION_MYSQL;
                }
                else
                {
                    throw new \Harp\bin\ArgumentException('drive for sgbd {mysql} is not load!'); 
                }
            }
            else
            {
                throw new \Harp\bin\ArgumentException('drive {'.$connectionDriver.'} for sgbd {mysql} not found!');
            }
        }
        catch(Exception $ex)
        {
            throw $ex;
        }

        return $dConn;        
    }

    private function getConnectionDriver($connectionDriver)
    {
        $connDriver = null;
        
        try
        {
            if($connectionDriver == ConnectionEnum::DB_CONNECTION_PLATFORM_SQL_SERVER)
            {
                $connDriver = $this->getDriverSuportSqlServer($connectionDriver);
            }
            else if($connectionDriver == ConnectionEnum::DB_CONNECTION_PLATFORM_MYSQL)
            {
                $connDriver = $this->getDriverSuportMysql($connectionDriver);
            }
            else
            {
                if($this->ConnectionEnum->isSupportedDriver($connectionDriver))
                {       
                    $connDriver = $connectionDriver;
                }             
            }
        }
        catch(Exception $ex)
        {
            throw $ex;
        }

        return $connDriver;
    }

    public function getInfoConnection($connectionName,$connectionDriver)
    {
        if(empty($connectionName) || empty($connectionDriver))
        {
            throw new Exception('Informe o nome da conexão e o driver que deseja utilizar!');
        }
        else if(!$this->ContainerInfoConnection->offsetExists($connectionName))
        {      
            $connDriver = $this->getConnectionDriver($connectionDriver);

            if(!empty($connDriver))
            {
                $this->ContainerInfoConnection->offsetSet($connectionName,new InfoConnection($connectionName,$connDriver)); 
            }
            else
            {
                throw new Exception('Não foi possível instanciar o objeto para criar uma conexão, verifique se o driver informado é permitido!');
            }
        }    
        
        return $this->ContainerInfoConnection->offsetGet($connectionName);
    }
    
    public function get($connectionName)
    {
        try
        {
            if(!$this->ContainerInfoConnection->offsetExists($connectionName) || empty($connectionName))
            {
                throw new \Harp\bin\ArgumentException('Não foi possível encontrar a conexão de identificação: '.$connectionName);
            }
            
            return $this->ContainerInfoConnection->offsetGet($connectionName);
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
    }
    
    public function setDefaultConnection($nameConn)
    {
        if(!empty($nameConn) && $this->ContainerInfoConnection->offsetExists($nameConn))
        {
            $this->defaultConnection = $nameConn;
        }
    }
    
    public function getDefaultConnection()
    {
        if(!empty($this->defaultConnection))
        {
            return $this->defaultConnection;
        }
        
        if($this->ContainerInfoConnection->count() > 0)
        {
                $iterator = $this->ContainerInfoConnection->getIterator();

                while ($iterator->valid()) 
                {
                    $this->defaultConnection = $iterator->current()->getConnectionName(); 
                    break;
                }
        }
        
        return $this->defaultConnection; 
    }

    public function getAll()
    {
        return clone $this->ContainerInfoConnection;
    } 
}
