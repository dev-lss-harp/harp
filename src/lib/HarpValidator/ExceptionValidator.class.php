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
namespace Lib\pvt\HarpValidator;

use ArrayObject;
use DOMDocument;
use Exception;
use Harp\bin\ArgumentException;
use Harp\lib\HarpValidator\EnumValidator;

class ExceptionValidator 
{
    private $ContainerException;
    private $EnumValidator;
    private $DomDocument;
        
    public function __construct(EnumValidator $EnumValidator)
    {
        try
        {            
            if(!file_exists(__DIR__.'/xml/'.EnumValidator::FILE_XML_MESSAGES))
            {
                throw new ArgumentException('File {'.EnumValidator::FILE_XML_MESSAGES.'} not found!'); 
            }
            
            libxml_use_internal_errors(false);

            $this->DomDocument = new DOMDocument('UTF-8');
            $this->DomDocument->validateOnParse = true;
            $this->DomDocument->preserveWhiteSpace = false;               
            $this->DomDocument->load(__DIR__.'/xml/'.EnumValidator::FILE_XML_MESSAGES);
        }
        catch(Exception $e)
        {
            if(!$e instanceof \Harp\bin\ArgumentException)
            {
                $e = new \Harp\bin\ArgumentException($e->getMessage(),'Error','error');
            }
            
            throw $e;
        }

        $this->EnumValidator = &$EnumValidator;
        
        $this->ContainerException = new ArrayObject(Array(),ArrayObject::ARRAY_AS_PROPS);
    }
    
    public function add($key,Array $params = Array())
    {
        if(!$this->ContainerException->offsetExists($key))
        {    
            $XPath = new \DOMXPath($this->DomDocument);

            $Node = $XPath->query('//*[@name="'.$key.'"]');

            if($Node->length != 1)
            {
                throw new \Harp\bin\ArgumentException('Message {'.$key.'} not configured in {'.EnumValidator::FILE_XML_MESSAGES.'}'); 
            }

            foreach($Node->item(0)->childNodes as $v)
            {
               if($v->getAttribute('id') == $this->EnumValidator->getLanguage())
               {
                   array_unshift($params,$v->textContent);

                   $msg =  call_user_func_array('sprintf',$params); 

                   $this->ContainerException->offsetSet($key,$msg);

                   break;
               }
            }   
        }
    } 
    
    public function count()
    {
        return $this->ContainerException->count();
    }
    
    public function get($key)
    {
        if(!empty($key))
        {
            return $this->ContainerException->offsetGet($key);
        }
        
        return null;
    }
    
    public function getAll()
    {
        return $this->ContainerException;
    }
    
    public function toArray()
    {
        return $this->ContainerException->getArrayCopy();
    }
    
    public function toString()
    {
     //   print_r($this->toArray());exit;
        return implode(';'.PHP_EOL,$this->toArray());
    }
    
    public function addMessage($msg)
    {
        $copy = $this->ContainerException->getArrayCopy();
        //não deixar colocar mensagem repetida
        if(!empty($msg) && !in_array($msg,$copy))
        {
            $this->ContainerException->offsetSet(uniqid(),$msg);
            
            return true;
        }
        
        return false;
    }    
        
    public function overwriteMessage($key,$msg)
    {
        if($this->ContainerException->offsetExists($key) && !empty($msg))
        {
            $this->ContainerException->offsetSet($key,$msg);
            
            return true;
        }
        
        return false;
    }
}
