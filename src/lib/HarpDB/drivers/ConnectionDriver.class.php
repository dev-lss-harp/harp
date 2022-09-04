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

use etc\HarpDatabase\connection\InfoConnection;
use etc\HarpDatabase\commands\CommandText;
use etc\HarpDatabase\commands\CommandParameter;
use etc\HarpDatabase\drivers\DriverInterface;
use etc\HarpDatabase\commands\CommandXml;
use etc\HarpDatabase\commands\CommandJson;
use etc\HarpDatabase\ORM\XmlORM;
use etc\HarpDatabase\ORM\JsonORM;
use etc\HarpCryptography\HarpCryptography;
use Harp\bin\ArgumentException;
use Exception;

include_once(__DIR__.'/DriverInterface.interface.php'); 
include_once(dirname(__DIR__).'/DatabaseHandleException.class.php');
include_once(dirname(__DIR__).'/commands/CommandText.class.php'); 
include_once(dirname(__DIR__).'/commands/CommandParameter.class.php'); 
include_once(dirname(__DIR__).'/ORM/XmlORM.class.php'); 
include_once(dirname(__DIR__).'/commands/CommandXml.class.php'); 
include_once(dirname(__DIR__).'/pagination/Pagination.class.php'); 
include_once(dirname(__DIR__).'/DatabaseEnum.class.php');

abstract class ConnectionDriver implements DriverInterface
{
    protected $encryptionKey;
    protected $encryptionFields;
    /****
     * A depender da seleção do app algum arquivo de configuração da database será instanciado
     */
    protected $CommandXml;
    protected $XmlORM;  
    protected $CommandJson;
    protected $JsonORM;  
    
    protected $InfoConnection;
    public $CommandText;
    public $CommandParameter;
    public $DatabaseHandleException;
    public $CommandHandlerSQL;
    public $HarpCryptography;
    


    public abstract function getSgbdName();

    protected function __construct(InfoConnection &$InfoConnection)
    {
       $this->InfoConnection = &$InfoConnection;
       //echo '<pre>'; print_r($this->InfoConnection->getConnectionName());exit;
       
       $this->CommandText = new CommandText(); 
       $this->CommandParameter = new CommandParameter($this->CommandText);
       $this->CommandHandlerSQL = $this->getCommandSql();
        
       $this->selectOriginORM();
       
       $this->cleanColumnsToEncrypt();
    }
    
    private function selectOriginORM()
    {
       if($this->InfoConnection->getOriginORM() == 'json')
       {
            $this->CommandJson = new CommandJson($this->getSgbdName()); 
            $this->JsonORM = new JsonORM($this);  
       }
       else if($this->InfoConnection->getOriginORM() == 'xml')
       {
            $this->CommandXml = new CommandXml($this->getSgbdName()); 
            $this->XmlORM = new XmlORM($this);
       }   
    }
    
    public function cleanColumnsToEncrypt()
    {
        $this->encryptionFields = [];
    }
    
    public function clearColumnsToEncrypt()
    {
        $this->encryptionFields = Array();
    }  
    
    protected function instanceCryptography()
    {
        if(!$this->HarpCryptography instanceof HarpCryptography)
        {
            $this->HarpCryptography = new HarpCryptography();
        }
    }    
    
    private function getCommandSql()
    {
        $DriverCommandObject = null;
        
        try
        {
            $driver = ucfirst($this->InfoConnection->getConnectionDriver());
            
            $className = 'Command'.$driver;
            
            $Object = 'etc\HarpDatabase\commands\Command'.$driver;
            
            if(!file_exists(dirname(__DIR__).'/commands/'.$className.'.class.php'))
            {
                throw new ArgumentException('Could not load driver command {'.$driver.'}!','An error occurred','error');
            }

            include_once(dirname(__DIR__).'/commands/'.$className.'.class.php');
            
            $DriverCommandObject = new $Object($this->CommandParameter,$this->CommandText); 
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return $DriverCommandObject;
    }

    public function setColumnsToEncrypt(Array $columns)
    {
        if(!empty($columns))
        {
           $this->encryptionFields = array_combine($columns,$columns);
           
           return true;
        }
        
        return false;
    }
    
    public function getInfoConnection()
    {
        return $this->InfoConnection;
    }

    public function getDriverName()
    {
        return $this->InfoConnection->getConnectionDriver();
    }
    
    public function getConnectionName()
    {
        return $this->InfoConnection->getConnectionName();
    }
    
    public function getCommandXml()
    {
          return $this->CommandXml; 
    }
    
    public function getXmlORM()
    {
         return $this->XmlORM;
    }
    
    public function getCommandJson()
    {
          return $this->CommandJson; 
    }    
    
    public function getJsonORM()
    {
         return $this->JsonORM;
    }    
    
    public function getMigrate()
    {
        return new \etc\HarpDatabase\reflect\HarpMigrate($this);
    }    
    
    public function getCharacterCaseSensitive()
    {
        return '';
    }
}
