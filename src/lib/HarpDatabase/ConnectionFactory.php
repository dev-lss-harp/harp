<?php
namespace Harp\lib\HarpDatabase;

use Harp\lib\HarpDatabase\HarpConnection;
use etc\HarpDatabase\drivers\ConnectionDriverEnum;
use Exception;
use Harp\bin\ArgumentException;
use Throwable;

class ConnectionFactory
{
    
  const PREFIX_CLASS_DRIVER = 'ConnDriver';
  const DIRECTORY_DRIVER = 'drivers';
  const DRIVER_BASE_NAMESPACE = 'Harp\lib\HarpDatabase\drivers';
  
  private static $instance;
  private $ContainerDriver;
  private $ConnectionID;
   
   public function __construct()
   {
       $this->ContainerDriver = new \ArrayObject(Array(),\ArrayObject::ARRAY_AS_PROPS);
   }
   
   public function getConnection(HarpConnection &$InfoConnection)
   {
       try
       {     
            $Driver = $this->getDriver($InfoConnection);
          
            $DriverObject = self::DRIVER_BASE_NAMESPACE.'\\'.$Driver;
            
            $this->ConnectionID = $InfoConnection->getConnName().':'
                    .self::DRIVER_BASE_NAMESPACE
                    .'\\'.$Driver;
            
            $InfoConnection->setConnectionID($this->ConnectionID);
           
            if(!$this->ContainerDriver->offsetExists($this->getConnectionID()))
            {  
                $this->ContainerDriver->offsetSet
                (
                    $this->getConnectionID(),
                    new $DriverObject($InfoConnection)
                );
            }

            return $this->ContainerDriver->offsetGet($this->getConnectionID());
       }
       catch(Throwable $th)
       {
           throw $th;
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


   private function getDriver(HarpConnection &$InfoConnection)
   {
       
        $driver = self::PREFIX_CLASS_DRIVER.ucfirst($InfoConnection->getDriverName());

        $driverFile = $driver.'.php';

        $path = __DIR__.DIRECTORY_SEPARATOR.self::DIRECTORY_DRIVER;

        if(!file_exists($path.DIRECTORY_SEPARATOR.$driverFile))
        {
            throw new ArgumentException('{'.$driver.'} driver not found in {'.$path.'}'); 
        }

        return $driver;
   }
   
   public function getConnectionID()
   {
       return $this->ConnectionID;
   }
}
