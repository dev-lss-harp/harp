<?php
namespace Harp\lib\HarpDatabase\orm;

use Harp\lib\HarpDatabase\orm\ORM;
use Harp\lib\HarpDatabase\drivers\ConnectionDriver;
use Harp\lib\HarpDatabase\orm\EntityHandler;
use Exception;
use Harp\bin\ArgumentException;

class ORMConfig  extends ORM
{
    private $currentEntityName;
    private $tables = [];
    private $path;
    private $identifierInstance = null;
    private $currentEntity = null;
    private $factoryInstances = [];

    const TABLES_TAG = 'tables';
    
    
    public function __construct(ConnectionDriver $ConnectionDriver)
    {
     
        $this->identifierInstance = $ConnectionDriver->getInfoConnection()->getConnectionID();
        $this->factoryInstances[$this->identifierInstance]['driver'] = $ConnectionDriver;
        $this->factoryInstances[$this->identifierInstance]['tables'] = [];

        parent::__construct($this);

    }

    public function load($path = null)
    {
        try
        {   
                clearstatcache();

                $appName = is_dir(PATH_APP.DIRECTORY_SEPARATOR.APP_NAME) 
                                    ? 
                                    APP_NAME 
                                    : 
                                    (is_dir(PATH_APP.DIRECTORY_SEPARATOR.mb_strtolower(APP_NAME)) ? mb_strtolower(APP_NAME) : '');

                $path = empty($path) ? PATH_APP.DIRECTORY_SEPARATOR.$appName : $path;
                
                //path para carregar o json
                $this->factoryInstances[$this->identifierInstance]['jsonPath'] = $path;

                //caminho para o json com o mapeamento das tabelas
                $p = $path.'/'.
                $this->factoryInstances[$this->identifierInstance]['driver']->
                                    getInfoConnection()->
                                    getDatabaseName().'.json';

                if(!file_exists($p))
                {
                    throw new ArgumentException('{'.$p.'} not found!',404);
                } 

                $tables = json_decode(file_get_contents($p),true);

                if(!isset($tables['tables']))
                {
                    throw new ArgumentException('the json file does not contain the {tables} root tag.',500);
                }
                else if(empty($tables['tables']))
                {
                    throw new ArgumentException('there are no tables configured for the database:  
                        {'. $this->factoryInstances[$this->identifierInstance]['driver']->
                        getInfoConnection()->
                        getDatabaseName().'} in the json file.',500);
                }

                $this->factoryInstances[$this->identifierInstance][self::TABLES_TAG] = $tables[self::TABLES_TAG];  
            
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return $this;
    }

    private function verifyExtendsEntity($info)
    {

        $table = $this->factoryInstances[$this->identifierInstance]['tables'][$info['attributes']['table']];

        if(isset($info['attributes']['extends']) && isset($info['attributes']['entityExtends']))
        {
            $tableName = $info['attributes']['extends'];

            if(!isset($this->factoryInstances[$this->identifierInstance]['tables'][$tableName]))
            {
                throw new ArgumentException('The extends table 
                            {'.$info['attributes']['extends'].'} 
                            not found in json file.',404);
            }

            $table = $this->factoryInstances[$this->identifierInstance]['tables'][$tableName];
        }

        return $table;
    }
    
    public function mapByEntity(EntityHandler $Entity)
    {        
        try
        {
            if(!($Entity instanceof EntityHandlerInterface))
            { 
                throw new ArgumentException('The entity 
                                {'.$Entity->getEntityName().'} 
                                must be an instance of: {EntityHandlerInterface}',500);
            }

            $this->currentEntity = trim($Entity->getEntityName());
      
            foreach($this->factoryInstances[$this->identifierInstance]['tables'] as $name => $infoTable)
            {
            
                if(!(isset($infoTable['attributes']['entity'])) || (isset($infoTable['attributes']['entity']) && trim($infoTable['attributes']['entity']) != $this->currentEntity))
                {
                    continue;   
                }

                $tbl =  $this->verifyExtendsEntity($infoTable);
                $this->currentEntity =  trim($tbl['attributes']['entity']);

            
                $this->factoryInstances[$this->identifierInstance]['mapped'][$this->currentEntity] = 
                [
                    'name' => $this->currentEntity,
                    'entity' => $Entity,
                    'table' => $tbl
                ];

                break;
            }

            if(empty($this->factoryInstances[$this->identifierInstance]['mapped'][$this->currentEntity]))
            {
                throw new ArgumentException('the json file does not contain 
                                    the definition for the entity: {'.$Entity->getEntityName().'}.',500);
            }
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return $this;
    }
    
    public function getJsonObject()
    {
        return isset($this->factoryInstances[$this->identifierInstance]) ?
                $this->factoryInstances[$this->identifierInstance][self::TABLES_TAG]
                : [];
    }
    
    public function getProperty($key)
    {
        $prop = null;
        try
        {
            if($this->currentEntity == null)
            {
                throw new ArgumentException('entity is not defined!',500);
            }
            else if ($this->currentEntity != null && !isset($this->factoryInstances[$this->identifierInstance]['mapped'][$this->currentEntity][$key]))
            {
                throw new ArgumentException('property {'.$key.'} is not defined!',500);
            }

            $prop =  $this->factoryInstances[$this->identifierInstance]['mapped'][$this->currentEntity][$key];
        }
        catch(\Exception $ex)
        {
            throw $ex;
        }

        return $prop;
    }

    public function getTables()
    {
        return $this->tables;
    }
    
    public function getTableByName($name)
    {
        $tables = $this->getJsonObject();
        return !empty($name) && isset($tables[$name]) ? $tables[$name] : null;
    }   
    
    public function getTableByNameEntity($name)
    {
        $tables = $this->getJsonObject();

        $table = [];

        foreach($tables as $nameTable => $tbl)
        {
            if(trim($tbl['attributes']['entity']) != trim($name))
            {
                continue;
            }

            $table = $tbl;
            break;
        }
        
        return $table;
    }      
    
    public function getCurrentTable()
    {
        return isset($this->tables[$this->currentEntityName]) ? $this->tables[$this->currentEntityName] : null;
    }    
    
    public function getConnectionDriver()
    {
        return $this->factoryInstances[$this->identifierInstance]['driver'];
    }
    
    public function getPath()
    {
        return $this->path;
    }
}