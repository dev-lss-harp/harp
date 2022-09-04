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

class HandleReplacemet
{
    private $replacements;
    private $HarpFileComponent;
    const PATTERN = 'PATTERN';
    const KEY_PATTERN = 'KEY_PATTERN';
    const VALUE_PATTERN = 'VALUE_PATTERN';
    
    public function __construct(HarpFileComponent &$HarpFileComponent) 
    {           
        $this->HarpFileComponent = &$HarpFileComponent;
        $this->replacements[self::PATTERN][self::KEY_PATTERN] = Array();
        $this->replacements[self::PATTERN][self::VALUE_PATTERN] = Array();
    }
        
    public function addAll($values)
    {
        if(is_array($values) || is_object($values))
        {
            foreach($values as $f => $v)
            {
                 $this->replacements[self::PATTERN][self::KEY_PATTERN][$f] = '`{H:=Replace:'.preg_quote($f).'}`is';
                 $this->replacements[self::PATTERN][self::VALUE_PATTERN][$f] = $v;
            }    
               
            return true;
        }
        
        return false;
    }
    
    public function addAllBefore($values)
    {
        if(is_array($values) || is_object($values))
        {
            $insertedRegex = Array();
            
            $insertedValues = Array();
            
            foreach($values as $f => $v)
            {
                 $insertedRegex[$f] = '`{H:=Replace:'.preg_quote($f).'}`is';
                 $insertedValues[$f] = $v;
            } 
            
            $this->replacements[self::PATTERN][self::KEY_PATTERN] = $insertedRegex + $this->replacements[self::PATTERN][self::KEY_PATTERN];
            $this->replacements[self::PATTERN][self::VALUE_PATTERN] = $insertedValues + $this->replacements[self::PATTERN][self::VALUE_PATTERN];  
            
            return true;
        }
        
        return false;
    }    
    
    public function insertBefore($key,$source,$destination)
    {        
        if(!empty($source) && !empty($key))
        {
            $insertedRegex = Array($key => '`'.  preg_quote($source,'`/`').'`i');
            
            $this->replacements[self::PATTERN][self::KEY_PATTERN] = $insertedRegex + $this->replacements[self::PATTERN][self::KEY_PATTERN];
            
            $insertedValues = Array($key => $destination);
            
            $this->replacements[self::PATTERN][self::VALUE_PATTERN] = $insertedValues + $this->replacements[self::PATTERN][self::VALUE_PATTERN];

            return true;
        }
        
        return false;
    }
    
    public function insertAfter($key,$source,$destination)
    {        
        if(!empty($source) && !empty($key))
        {
            $insertedRegex = Array($key => '`'.  preg_quote($source,'`/`').'`i');
            
            $this->replacements[self::PATTERN][self::KEY_PATTERN] =  $this->replacements[self::PATTERN][self::KEY_PATTERN] + $insertedRegex;
            
            $insertedValues = Array($key => $destination);
            
            $this->replacements[self::PATTERN][self::VALUE_PATTERN] = $this->replacements[self::PATTERN][self::VALUE_PATTERN] + $insertedValues;

            return true;
        }
        
        return false;
    }    
    
    public function add($flag,$value)
    {
        $this->replacements[self::PATTERN][self::KEY_PATTERN][$flag] = '`{H:=Replace:'.preg_quote($flag).'}`is';
        $this->replacements[self::PATTERN][self::VALUE_PATTERN][$flag] = $value;  
        
        return $this;
    }
    
    public function addByKey($key,$source,$destination)
    {
        if(!empty($source) && !empty($key))
        {
            //Usando o delimitador de regex (`) para evitar erro 
            $this->replacements[self::PATTERN][self::KEY_PATTERN][$key] = '`'.  preg_quote($source,'`/`').'`i';
            $this->replacements[self::PATTERN][self::VALUE_PATTERN][$key] = $destination;     
            
            return true;
        } 
        
        return false;
    }    
    
    public function addFromPattern($pattern,$value)
    {
        if(!empty($pattern))
        {
            $this->replacements[self::PATTERN][self::KEY_PATTERN][$pattern] = $pattern;
            $this->replacements[self::PATTERN][self::VALUE_PATTERN][$pattern] = $value;   

            return true;
        } 
        
        return false;
    }  
    
    public function replaceNow($key)
    {
        if(!empty($this->replacements[self::PATTERN][self::KEY_PATTERN][$key]))
        {   
            $this->HarpFileComponent->updateFileByRegex($this->replacements[self::PATTERN][self::KEY_PATTERN][$key],$this->replacements[self::PATTERN][self::VALUE_PATTERN][$key]);

            unset($this->replacements[self::PATTERN][self::KEY_PATTERN][$key],$this->replacements[self::PATTERN][self::VALUE_PATTERN][$key]);

            return true;
        }
        
        return false;
    }    
    
    public function replaceImmediately($key)
    {
        if(!empty($this->replacements[self::PATTERN][self::KEY_PATTERN][$key]))
        {
            $this->HarpFileComponent->updateFileByRegex($this->replacements[self::PATTERN][self::KEY_PATTERN][$key],$this->replacements[self::PATTERN][self::VALUE_PATTERN][$key]);
            
            unset($this->replacements[self::PATTERN][self::KEY_PATTERN][$key],$this->replacements[self::PATTERN][self::VALUE_PATTERN][$key]);

            return true;
        }
        
        return false;
    }
    
    public function execute()
    {
        if(!empty($this->replacements[self::PATTERN]))
        {            
          $this->HarpFileComponent->updateFileByRegex($this->replacements[self::PATTERN][self::KEY_PATTERN],$this->replacements[self::PATTERN][self::VALUE_PATTERN]);  
            
           return true;
        }

        return false;
    }
    
    public function closeCursor(Array $exclude = Array())
    {
       $patternExec = [];
       
       if(empty($exclude))
       {
           $patternExec[] = '`{(/|)\H:=[a-zA-Z0-9_][^}].*?}`i';
           $patternExec[] = '`<H:code>.*[^<\/H]</H:code>`i';
           $patternExec[] = '`<H:exRem>.*[^<\/H]</H:exRem>`i';
       } 

       if(!empty($patternExec))
       {
           foreach ($patternExec as $exec){ $this->HarpFileComponent->updateFileByRegex($exec,'');}
       }      
    }
}



