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

use ArrayObject;
use etc\HarpDatabase\connection\InfoConnection;

class ContainerInfoConnection
{
    private $ContainerInfoConnection;
    private $PlatformConnection;
    
    public function __construct()
    {
        $this->ContainerInfoConnection = new ArrayObject(Array(),ArrayObject::ARRAY_AS_PROPS);
        
        include_once(__DIR__.'/PlatformConnection.class.php');
        
        $this->PlatformConnection = new PlatformConnection();
    }
    
    public function instanceInfoConnection($connectionName,$connectionDriver)
    {
        if(!$this->ContainerInfoConnection->offsetExists($connectionName))
        {
            if(is_string($connectionName) && is_string($connectionDriver))
            {
                
                
                $this->ContainerInfoConnection->offsetSet($key,new InfoConnection($connectionName,$connectionDriver));
                
                return $this->ContainerInfoConnection->offsetGet($key);
            }    
        }
        else if($this->ContainerInfoConnection->offsetExists($key))
        {
            return $this->ContainerInfoConnection->offsetGet($key);
        }
        
        return null;
    }
    
    public function get($key)
    {
        if($this->ContainerInfoConnection->offsetExists($key))
        {
            return $this->ContainerInfoConnection->offsetGet($key);
        }
        
        return null;
    } 
    
    public function getAll()
    {
        return $this->ContainerInfoConnection;
    }    
}
