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
namespace Harp\lib\HarpService;

class GenericService
{
   private $nameService; 
   private $ServiceResources;
   
   public function __construct($nameService)
   {
       $this->nameService = $nameService;
       
       $this->ServiceResources = new \ArrayObject(Array(),\ArrayObject::ARRAY_AS_PROPS);
   }
   
   public function getNameService()
   {
       return $this->nameService;
   }
   
   public function set($key,$value)
   {
       if(!$this->ServiceResources->offsetExists($key))
       {
           $this->ServiceResources->offsetSet($key,$value);
       }
       
       return $this;
   }
   
   public function get($key)
   {         
       if(!empty($key))
       {
           $baseName = basename(str_ireplace(Array('\\','/'),DIRECTORY_SEPARATOR,$key));

           if($this->ServiceResources->offsetExists($key))
           {
               return $this->ServiceResources->offsetGet($key); 
           }
           else  if($this->ServiceResources->offsetExists($baseName))
           {
                return $this->ServiceResources->offsetGet($baseName); 
           }
           
           return null;
       }      
   }
   
   public function exists($key)
   {
       if(!empty($key))
       {
           return $this->ServiceResources->offsetExists($key);
       }
       
       return false;
   }
}
