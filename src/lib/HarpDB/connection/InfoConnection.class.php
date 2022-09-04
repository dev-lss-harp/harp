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

class InfoConnection
{
    private $connectionDriver;
    private $connectionName;
    private $serverName;
    private $serverIP;
    private $port;
    private $userName;
    private $databaseName;
    private $password;
    private $originORM = 'json';
    
    public function __construct($connectionName,$connectionDriver)
    {
        $this->connectionName = $connectionName;
        
        $this->connectionDriver = $connectionDriver;
    }
    
    public function getConnectionDriver()
    {
        return $this->connectionDriver;
    }

    public function getConnectionName()
    {
        return $this->connectionName;
    }

    public function getServerName()
    {
        return $this->serverName;
    }

    public function getServerIP()
    {
        return $this->serverIP;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function getUserName()
    {
        return $this->userName;
    }

    public function getDatabaseName()
    {
        return $this->databaseName;
    }

    public function getPassword()
    {
        return $this->password;
    }
    
    public function getServerID()
    {
        $serverName = $this->getServerName();
        
        return !empty($serverName) ? $serverName : $this->getServerIP();
    }

    public function setServerName($serverName)
    {
        $this->serverName = $serverName;
        
        return $this;
    }

    public function setServerIP($serverIP)
    {
        $this->serverIP = $serverIP;
        
        return $this;
    }

    public function setPort($port)
    {
        $this->port = $port;
        
        return $this;
    }

    public function setUserName($userName)
    {
        $this->userName = $userName;
        
        return $this;
    }

    public function setDatabaseName($databaseName)
    {
        $this->databaseName = $databaseName;
        
        return $this;
    }

    public function setPassword($password)
    {
        $this->password = $password;
        
        return $this;
    }

    public function getOriginORM() 
    {
        return $this->originORM;
    }

    public function setOriginORM($originORM) 
    {
        $this->originORM = $originORM;
        return $this;
    }    
}
