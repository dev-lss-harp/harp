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

use Harp\bin\ArgumentException;
use DOMDocument;
use DOMNodeList;

class CommandJson
{
    private $jsonFile;
    private $jsonArray;
    private $sgbdName;
    
    public function __construct($sgbdName)
    {
        $this->sgbdName = $sgbdName;
    }
    
    public function load($fileName)
    {
        try
        {   
            $FileExists = new \bin\env\FileExists($fileName);
            
            if(!$FileExists->verify())
            {
                $Message = new \bin\env\IntegrityCheckMessage('%s not exists in %s!');

                throw new ArgumentException($FileExists->formatMessage($Message),'An error occurred','error');
            } 
            
            
            $this->jsonFile = file_get_contents($fileName);
            $this->jsonArray = json_decode($this->jsonFile,true);
            
            
            
//            libjson_use_internal_errors(false);
//
//            $this->jsonFile = new DOMDocument('UTF-8');
//            $this->jsonFile->validateOnParse = true;
//            $this->jsonFile->preserveWhiteSpace = false;
//
//            $this->jsonFile->load($fileName);
//
//            $this->jsonNode = $this->jsonFile->getElementsByTagName($this->sgbdName);
//        
//            if($this->jsonNode->length < 1)
//            {
//                $Message = new \bin\env\IntegrityCheckMessage('The setting for the {'.$this->sgbdName.'} DBMS does not exist');
//                
//                throw new ArgumentException($Message->format()->getMessage(),'An error occurred','error');
//            }
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
    }
    
    public function getCommand($name)
    {
        
        echo $name;exit;
        $command = null;
        
        try
        {
            if($this->jsonNode instanceof DOMNodeList && !empty($name))
            {
                $name = trim($name);

                foreach($this->jsonNode->item(0)->childNodes as $n)
                {
                    if($n->getAttribute('name') == $name)
                    {
                        $command = trim($n->nodeValue);

                        break;
                    }       
                }         
            }

            $EmptyCommand = new \bin\env\EmptyValue($command);

            if($EmptyCommand->verify())
            {
                throw new ArgumentException($EmptyCommand->formatMessage(new \bin\env\IntegrityCheckMessage('node {'.$name.'} not found in {'.  get_class().'} returned value {%s}!')),'An error occurred','error');
            }
        }
        catch(Exception $ex)
        {
            throw $ex;
        }

        return $command;
    }
}
