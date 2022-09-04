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

use Harp\lib\HarpDB\InfoConnection;
use etc\HarpDatabase\drivers\ConnectionDriverEnum;
use Exception;

include_once(dirname(__DIR__).'/drivers/ConnectionDriverEnum.class.php');

class ConnectionFactory
{
  private static $instance;
  private $ContainerDriver;
  private $ConnectionID;
   
   public function __construct()
   {
       $this->ContainerDriver = new \ArrayObject(Array(),\ArrayObject::ARRAY_AS_PROPS);
   }
   
   public function getConnection(InfoConnection &$InfoConnection)
   {
       try
       {
            $Driver = $this->getDriver($InfoConnection);
           
            $DriverObject = ConnectionDriverEnum::DRIVER_BASE_NAMESPACE.'\\'.$Driver;
            
            $this->ConnectionID = $InfoConnection->getConnectionName().':'.ConnectionDriverEnum::DRIVER_BASE_NAMESPACE.'\\'.$Driver;

            if(!$this->ContainerDriver->offsetExists($this->getConnectionID()))
            {   
                $this->ContainerDriver->offsetSet($this->getConnectionID(),new $DriverObject($InfoConnection));
            }

            return $this->ContainerDriver->offsetGet($this->getConnectionID());
       }
       catch(Exception $e)
       {
           throw $e;
       }
   }
   
   public static function getInstance()
   {
       $thisClass = __CLASS__;
       
       if(!self::$instance instanceof $thisClass)
       {
           self::$instance = new $thisClass();
       }
       
       return self::$instance;
   }


   private function getDriver(InfoConnection &$InfoConnection)
   {
        $driver = ConnectionDriverEnum::PREFIX_CLASS_DRIVER.ucfirst($InfoConnection->getConnectionDriver());

        $driverFile = $driver.'.class.php';

        $path = dirname(__DIR__).'/'.ConnectionDriverEnum::DIRECTORY_DRIVER;
        
        $FileExists = new \bin\env\FileExists($path);
      
        if(!$FileExists->verify())
        {
            throw new Exception($FileExists->interrupt(new \bin\env\IntegrityCheckMessage('%s driver not found in %s'))); 
        }

        include_once($path.'/ConnectionDriver.class.php');
        include_once($path.'/'.$driverFile);  
        
        return $driver;
   }
   
   public function getConnectionID()
   {
       return $this->ConnectionID;
   }
   
  /* public function getObjectDriver()
   {
       $driverName = ConnectionDriverEnum::DRIVER_BASE_NAMESPACE.'\\'.$this->getDriver();

       if(!$this->ContainerDriver->offsetExists($this->getConnectionID()))
       {
           $this->ContainerDriver->offsetSet($this->getConnectionID(),new $driverName($this->InfoConnection));
       }
      
       return $this->ContainerDriver->offsetGet($this->getConnectionID());

   }  */  
}
