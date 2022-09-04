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
namespace etc\HarpDatabase\drivers;

use ArrayObject;

use stdClass;

include_once(PATH_BIN.'/env/ArgumentException.class.php');

class DatabaseHandleException extends \Harp\bin\ArgumentException
{
    private $Exceptions;
    
    public function __construct()
    {
        parent::__construct(null,0, null,null);
        
        $this->Exceptions = new ArrayObject(array(),ArrayObject::ARRAY_AS_PROPS);
    }
    
    public function setException($message,$info = null,$code = '00')
    {
        $Exception = new stdClass();
        $Exception->code = $code;
        $Exception->message = $message;
        $Exception->info = $info;
        
        if(!$this->Exceptions->offsetExists($code))
        {
             $this->Exceptions->offsetSet($code,$Exception);
        }
        
        return $this;
    }
    
    public function exists()
    {
        return $this->Exceptions->count() > 0 ? true : false;
    }
    
    
    public function count()
    {
       return $this->Exceptions->count(); 
    }
    
    public function toArray()
    {
       return $this->Exceptions->getArrayCopy();
    }
    
    public function toString()
    {
        $iterator = $this->Exceptions->getIterator();

        $strMessages = null;
        
        while($iterator->valid()) 
        {
            $strMessages .= 'code: '.$iterator->current()->code.', message: '.$iterator->current()->message.($iterator->current()->info == null ? null : ', info: '.$iterator->current()->info)."\r\n";

            $iterator->next();
        } 
        
       return $strMessages;
    }
    
    public function clearAll()
    {
        $iterator = $this->Exceptions->getIterator();
        
        while($iterator->valid()) 
        {
            $this->Exceptions->offsetUnset($iterator->current()->code);
            
            $iterator->next();
        }         
    }
}
