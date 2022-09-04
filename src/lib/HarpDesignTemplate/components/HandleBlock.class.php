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
use Harp\bin\ArgumentException;
use Exception;

class HandleBlock
{
    public  $block;
    private $HarpFileComponent;
    private $result = Array();
    
    public function __construct(HarpFileComponent &$HarpFileComponent) 
    {           
        $this->HarpFileComponent = &$HarpFileComponent;
    }
            
    public function loadBlock($nameBlock)
    {
        if(empty($nameBlock))
        {
            throw new ArgumentException('Empty block name!');
        }
        
        $this->block = preg_quote($nameBlock);
        
        preg_match('`{H:=Block:'.  $this->block.'}(.*?){/H:=Block:'.$this->block.'}`is',$this->HarpFileComponent->getFile()->file,$this->result);
               
        if(!isset($this->result[0]))
        {
            throw new ArgumentException('Block: {'.$nameBlock.'} not found!');
        }
        
        $this->result[2] = $this->result[1];
        
        return $this;
    } 
    
    public function getBlock()
    {
        return isset($this->result[2]) ? $this->result[2] : null;
    }
    
    public function replace(string $key,string $value)
    {
        if(!empty($key) && !empty($this->result[2]))
        {            
            $this->result[2] =  str_ireplace('{H:=Block:'.$this->block.':'.$key.'}',$value,$this->result[2]);
        }

        return $this;
    }
    
    public function appendStr(string $str,$pos = 0)
    {   
         $result = Array();
         
         preg_match('`(<.*[^>]>)(.*?)(<\/.*?>)`is',$this->result[2],$result);
         
         if(count($result) == 4)
         {
             if(!$pos)
             {
                $this->result[2] = $result[1].$str.$result[2].$result[3]; 
             }
             else
             {
                $this->result[2] = $result[1].$result[2].$str.$result[3];  
             }
             
         }

         return $this;
    }
    
    public function append(HandleBlock $Block,$pos = 0)
    {   
         $result = Array();
         
         preg_match('`(<.*[^>]>)(.*?)(<\/.*?>)`is',$this->result[2],$result);
         
         if(count($result) == 4)
         {
             if(!$pos)
             {
                $this->result[2] = $result[1].$Block->getBlock().$result[2].$result[3]; 
             }
             else
             {
                $this->result[2] = $result[1].$result[2].$Block->getBlock().$result[3];  
             }
             
         }

         return $this;
    }  
    
    public function addAttribute($key,$value)
    {   
         $result = Array();
         
         if(!empty($key))
         {
            if(!preg_match('`((<.*[^>])(>))(.*?)(<\/.*?>)`is',$this->result[2],$result))
            {
                preg_match('`((<.*[^>])(>))`is',$this->result[2],$result);
            }
            
            $e = '';
            
            if(count($result) == 6)
            {
                $r = Array();

                if(preg_match('`'.$key.'\s*=\s*"(.*?)"`',$result[2],$r))
                {
                    $attr = $key.'="'.$r[1].' '.$value.'"';
                    $e  = str_ireplace($r[0],$attr,$result[2]);
                    $e .= $result[3].$result[4].$result[5];
                }
                else
                {
                    $e = $result[2].' '.$key.'="'.$value.'"'.$result[3].$result[4].$result[5];
                }
            }
            else if(count($result) == 4)
            { 

                if(preg_match('`'.$key.'\s*=\s*"(.*?)"`',$result[2],$r))
                {
                    $attr = $key.'="'.$value.'"';
                    $e  = str_ireplace($r[0],$attr,$result[2]);
                    $e .= $result[3];
                                      
                }
                else
                {
                    $e = $result[2].' '.$key.'="'.$value.'"'.$result[3];
                } 
            }
           
            $this->result[2] = $e;
         }

         return $this;
    }     
}



