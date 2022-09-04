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
namespace etc\HarpDatabase\reflect;

use bin\ext\HarpHandler\EntityHandler;
use etc\HarpDatabase\drivers\ConnectionDriver;
use etc\HarpSessionStorage\AbstractStorage;
use Exception;

class ReflectDatabase
{
    private $Driver;
    private $Entity;
    private $ConnDriver;
    private $props = [];
    private $dataReflect = [];
    private $signature;
    private $namespace;
 //  private $storageDocumentKey = PROJECT_NAME.'.'.APPLICATION_NAME;
    
    public function __construct(ConnectionDriver $Driver, EntityHandler $Entity)
    {
        $this->Driver = $Driver;
        
        $this->Entity = $Entity;
        
        $Ref = new \ReflectionClass($Entity);
        $this->props['private'] = $Ref->getProperties(\ReflectionProperty::IS_PRIVATE);
        $this->props['public'] = $Ref->getProperties(\ReflectionProperty::IS_PUBLIC);
        $this->namespace = basename(str_ireplace(['\\'],['/'],$Ref->getNamespaceName()));
              
        $annotation = $Ref->getDocComment();
        
        $this->ConnDriver = $Driver;
        
        if(preg_match_all('`\@({*{.*?}});`i',$annotation, $result))
        {
            if(isset($result[1]))
            {
                foreach ($result[1] as $k => $j)
                {
                    $j2 = @json_decode($j,true);

                    if(is_array($j2))
                    {
                      
                        foreach($this->props['private'] as $obj)
                        {
                            if(isset($j2[$obj->name]))
                            {
                                $j2[$obj->name]['hash'] = md5($j);
                                $j2[$obj->name]['migrate'] = 0;
                                $this->dataReflect[$obj->name] = $j2[$obj->name];
                                break;
                            }
                        }
                        
                        foreach($this->props['public'] as $obj)
                        {
                            $cAttr = 'id'.$obj->name;

                            if(isset($j2[$cAttr]))
                            {
                                $j2[$cAttr]['hash'] = md5($j);
                                $j2[$cAttr]['migrate'] = 0;
                                $this->dataReflect[$cAttr] = $j2[$cAttr];
                                break;
                            }
                            
                        }
                    }                  
                }
            } 
        }

        $this->signature = json_encode($this->dataReflect);

        return $this;
    }
    
    public function execMigrate(AbstractStorage $Storage)
    {
       // $documentDefault = $Storage->getObjectReadable()->read($this->storageDocumentKey);
        
        $lastSignature = null;
        $lastFields = null;
        $currentFields = null;
        $fieldsMigrate = [];
        $Storage->getObjectReadable()->reload();
        
        if(!$Storage->getObjectReadable()->keyExist($this->Entity->getClassName()))
        {
            $Storage->write($this->Entity->getClassName(),$this->signature);
            $fieldsMigrate =  json_decode($this->signature,true);
        }
        else
        {
            $lastSignature = $Storage->getObjectReadable()->read($this->Entity->getClassName());

        }

        if(($lastSignature != $this->signature) && !empty($lastSignature))
        {
                $currentFields =  json_decode($this->signature,true);
                $lastFields = json_decode($lastSignature,true);

                foreach($lastFields as $kf => $f)
                {
                    foreach($currentFields as $knf => $nf)
                    {   
                       if($kf == $knf && $f['hash'] == $nf['hash'])
                       {
                           $nf['migrate'] = 0;//NO MODIFICATION OR CREATE TABLE
                           $fieldsMigrate[$knf] = $nf;
                       }
                       else if(!isset($lastFields[$knf]))
                       {
                           $nf['migrate'] = 1;//ADD
                           $fieldsMigrate[$knf] = $nf;

                       }
                       else if($kf == $knf && $f['hash'] != $nf['hash'])
                       {
                           $nf['migrate'] = 2;//DELETE AND ADD
                           $fieldsMigrate[$kf] = $nf;
                       }
                       else if(!isset($currentFields[$kf]))
                       {
                           $f['migrate'] = 3;//DELETE
                           $fieldsMigrate[$kf] = $f;
                       }

                    }
                }
           
                $this->alterTable($fieldsMigrate);
               // $Storage->write($this->storageDocumentKey,$this->Entity->getClassName(),$this->signature);
           }
           else if(empty($lastSignature))
           {          
                $this->ConnDriver->getMigrate()->createTable($this->namespace,$this->Entity->getEntityName(),$fieldsMigrate);
                
           }
         
           $Storage->write($this->Entity->getClassName(),$this->signature);
    }
    
    private function alterTable($fieldsMigrate)
    {
        try
        {
            foreach($fieldsMigrate as $f => $properties)
            {
                switch($properties['migrate'])
                {
                    case 1:
                        $this->ConnDriver
                                        ->getMigrate()
                                        ->addColumn
                                        (
                                            $f,
                                            $properties['props'],
                                            $this->namespace,    
                                            $this->Entity->getEntityName()  
                                        );
                    break;
                    case 2:
                        $this->ConnDriver
                                        ->getMigrate()
                                        ->changeColumn
                                        (
                                            $f,
                                            $properties['props'],
                                            $this->namespace,    
                                            $this->Entity->getEntityName()   
                                        );                        
                    break;   
                    case 3:
                        $this->ConnDriver
                                        ->getMigrate()
                                        ->dropColumn
                                        (
                                            $f,
                                            $this->namespace,    
                                            $this->Entity->getEntityName()   
                                        );                        
                    break; 
                    default: 
                    break;
                }
            }

        }
        catch(Exception $ex)
        {
            throw $ex;
        }
    }
}
