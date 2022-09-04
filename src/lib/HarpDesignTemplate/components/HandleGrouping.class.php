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
namespace etc\HarpDesignTemplate\components;

use etc\HarpDesignTemplate\components\HarpFileComponent;

class HandleGrouping
{
    public $group;
    public $values;
    private $index;
    private $currentKey;
    private $HarpFileComponent;

    public function __construct(HarpFileComponent &$HarpFileComponent) 
    {           
        $this->HarpFileComponent = &$HarpFileComponent;
    }
            
    public function add($group,$values = [],Array $index = [])
    {
        if(empty($group))
        {
            return false;
        }
        
        $this->group = preg_quote($group);

        $this->values = $values;
        
        $this->index = $index;
      
        $expr = '{H:=Group:'.  $this->group.'}(.*?){/H:=Group:'.$this->group.'}';
        
        preg_match('`'.$expr.'`is',$this->HarpFileComponent->getFile()->file,$result);

        if(isset($result[0]))
        {
             $block = null;
           
             if(empty($this->index) && !empty($this->values))
             {
                 $exchange = array();
                 
                 if(is_object($this->values))
                 {
                     $this->values = (array) $this->values;
                 }

                 if(is_array($this->values))
                 {
                        foreach($this->values as $x => $v)
                        {
                            if(!is_object($v) && !is_array($v))
                            {
                               $exchange[$x] = '{H:=Group:'.$this->group.':'.$x.'}'; 
                            }
                            else
                            {
                                unset($this->values[$x]);
                            }
                            
                        }                      
                 }
                 else if(!is_object($v) && !is_array($v))
                 {
                    $exchange[0] = '{H:=Group:'.$this->group.':'.$this->values.'}';
                 }
                 
                 $block = str_ireplace($exchange,$this->values,$result[0]);
             } 
             else if(!empty($this->index) && !empty($this->values) && (count($this->index) == count($this->values)))
             {                               
                 $block = str_ireplace($this->index,$this->values,$result[0]);
             }
             
             if(!empty($block))
             {
                   $this->currentKey = 'group_'.$group.uniqid($group);

                   $block = $this->HarpFileComponent->HandleHTags->execExpr($block);

                   $this->HarpFileComponent->updateFile($result[0],$block);                 
             }
        }
        
        return $this;
    } 
    
    public function execute($group = null)
    {
        $group = empty($group) ? $this->currentKey : $group;
        
        return $this->HarpFileComponent->getHandleReplacement()->replaceImmediately($group);
    }
    
    public function finish($group = null)
    {
        $group = empty($group) ? $this->currentKey : $group;
       // echo $this->currentKey;exit;
        return $this->HarpFileComponent->getHandleReplacement()->replaceImmediately($group);
    }    
    
    public function getCurrentKey()
    {
        return $this->currentKey;
    }
}



