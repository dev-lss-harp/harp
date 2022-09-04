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
use Exception;

class HandleRepeater
{
    public $repeatMultipleEquals;
    public $flag;
    private $currentKey;
    public $values;
    private $HarpFileComponent;
    
    public function __construct(HarpFileComponent &$HarpFileComponent) 
    {           
        $this->HarpFileComponent = &$HarpFileComponent;
        
        $this->repeatMultipleEquals = false;
    }
    
    public function repeatMultipleEquals($status)
    {
        $this->repeatMultipleEquals = (bool) $status;
    }

    private function __recursiveValues($value,&$vlr,$key = null)
    {
                   //    echo '<pre>'; print_r($value);
        foreach($value as $index => $v)
        {

            if(is_object($v) || is_array($v))
            {
                
               $vlr =  $this->__recursiveValues($v, $vlr,$index);
               
            }
            else
            {
                $k = !empty($key) ? $key. ucfirst($index) : $index;
                $vlr[$k] = $v;
            }   
            
          
                    
            //    echo '<pre>';print_r($v);
            //$vlr[$index] = $v;
            //$keys[$index] = '{H:=Repeat:'.$this->flag.':'.$index.'}';
        }

          return $vlr;
     /*   if(is_object($value) || is_array($value))
        {
            foreach($value as $index => $v)
            {
                    echo '<pre>';print_r($v);
                $vlr[$index] = $v;
                $keys[$index] = '{H:=Repeat:'.$this->flag.':'.$index.'}';
            } 
        }
        else
        {
            $vlr[$index] = $v;
        }    */    
        

    }


    private function simpleRepetition($result,$dinamicKeys)
    {   
        
        if(!empty($result[1][0]))
        {
            foreach($result[1] as $i => $r)
            {           
                         $keys = false;

                         $block = '';
  
                         foreach($this->values as $y => $value)
                         {                 
                                
                                $allvalues = Array(); 
                                $keys = Array();
                                
                                if(!is_array($value) && !is_object($value))
                                {
                                    $value = [$y => $value];
                                }

                                $vlr = $this->__recursiveValues($value,$vlr,null);
                                
                                foreach($vlr as $idx => $vv)
                                {
                                        $allvalues[$idx] = $vv;
                                        $keys[$idx] = '{H:=Repeat:'.$this->flag.':'.$idx.'}';
                                }
                             
                                 $bl = str_ireplace($keys,$allvalues,$r);
                                
                                 $block .= $bl;
                                 
                         }

                         $this->HarpFileComponent->updateFile($result[0][$i],$block);

            }
            
            return true;
        }    

        return false;
    }
    
    public function add($flag,$values,$dinamicKeys = false)
    {
        if(!empty($flag))
        {
            $this->flag = preg_quote($flag);

            $this->values = $values;

            preg_match_all('#{H:=Repeat:'.$this->flag.'}(.*?){/H:=Repeat:'.$this->flag.'}#is',$this->HarpFileComponent->getFile()->file,$result);

            if(!empty($result[0])) 
            {
                 if(!$this->simpleRepetition($result,$dinamicKeys))
                 {
                     throw new Exception('Não foi possível utilizar o controle de Repetição verifique os parâmetros passados!'); 
                 }
            }
            else
            {
                throw new Exception('Falha ao fazer parse, verifique no seu arquivo a correta declaração das tags: {H:=Repeat:'.$this->flag.'}(.*?){/H:=Repeat:'.$this->flag.'} no arquivo em questão!');
            }
        }
        else
        {
            throw new Exception('O parâmetro flag não pode ser vazio ou nulo!'); 
        }
        
        return $this;
     
    }
    
    public function execute($key = null)
    {
        $key = empty($key) ? $this->currentKey : $key;
        
        return $this->HarpFileComponent->getHandleReplacement()->replaceImmediately($key);
    }  
    
    public function finish($key = null)
    {
        $key = empty($key) ? $this->currentKey : $key;
        
        return $this->HarpFileComponent->getHandleReplacement()->replaceImmediately($key);
    }       
    
    public function getCurrentKey()
    {
        return $this->currentKey;
    }
}



