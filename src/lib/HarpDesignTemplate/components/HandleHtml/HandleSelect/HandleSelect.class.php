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
namespace etc\HarpDesignTemplate\components\HandleHtml\HandleSelect;

use etc\HarpDesignTemplate\components\HarpFileComponent;
use etc\HarpDesignTemplate\components\HandleHtml\HandleHtml;
use etc\HarpDesignTemplate\components\HandleHtml\HandleHtmlEnum;
use BadMethodCallException;
use Exception;
use DOMDocument;
use DOMXPath;

class HandleSelect extends HandleHtml
{
    private $htmlSelect;
    private $currentKey;

    public function __construct(HarpFileComponent &$HarpFileComponent) 
    {           
        parent::__construct($HarpFileComponent);
        
        $this->htmlSelect = null;
    }  

    public function findByTag($tag)
    {
        throw  new BadMethodCallException('A utilização de tags ainda não foi implementada para este manipulador. Tag:'.$tag);
    }    

    private function getDinamicTags($id)
    {
        $result = Array();

        preg_match_all('#{H:=Select:'.$id.':Option:Set}(.*?){/H:=Select:'.$id.':Option:Set}#is',$this->HarpFileComponent->getFile()->file,$result);
        
        if(count($result) == 2)
        { 
            return $result;
        } 
        
        return null;
    }
    
    public function setDinamicOptions($id,$optionsCollection)
    {
        if(empty($id))
        {
           return false; 
        }
        
        $result = $this->getDinamicTags($id);

        if(empty($result[1][0]))
        {
            return false;
        }

        if(is_array($optionsCollection) || is_object($optionsCollection))
        {        
            foreach($result[1] as $ks => $v)
            {
                $option = $v;

                     $optionsCollection = (Array)$optionsCollection;

                     $options = '';

                     foreach($optionsCollection as $key => $value)
                     {  
                         $options .= $option;

                         if(is_array($value))
                         {
                             foreach($value as $k2 => $v2)
                             {
                                 $options = str_ireplace(Array('{H:=Select:'.$id.':Option:Set:'.$k2.'}'),$v2,$options);
                             }
                         }
                         else
                         {
                             $options = str_ireplace(Array('{H:=Select:'.$id.':Option:Set:'.$key.'}'),$value,$options);
                         }                  
                     }

                     $this->HarpFileComponent->getFile()->file = str_ireplace($result[0][$ks],$options,$this->HarpFileComponent->getFile()->file); 
            }             
        }

        return $this;
    }

    public function setOptions($name,$keyValue,$keyDisplay,$options,Array $selectAttributes = Array(),Array $optionsAttributes = Array())
    {
        $element = $this->findByAttr('name',$name);
        
        if(isset($element[0][0]))
        {
            $dropDown = $element[0][0];

            $Dom = $this->HandleHtmlAttribute->getDomDocument();

            $Dom->loadHtml($this->HarpFileComponent->getFile()->file);

            $Xpath = new DOMXPath($Dom);

            $pathSelect = '//'.$this->getName().'[@name="'.$name.'"]';

            $nodesSelect = $Xpath->query($pathSelect);

            if($nodesSelect->length > 0)
            {
                $node = $nodesSelect->item(0);

                foreach($options as $v)
                {
                    if(!empty($v[$keyValue]) && !empty($v[$keyDisplay]))
                    {
                        $option = $Dom->createElement('option',$v[$keyDisplay]);
                        $option->setAttribute('value',$v[$keyValue]);
                        foreach($optionsAttributes as $k => $a)
                        {
                            if(isset($v[$a]))
                            {
                                 $option->setAttribute($k,$v[$a]);
                            }
                        }
                        
                        $node->appendChild($option);
                    }
                }
                
                $this->HarpFileComponent->getFile()->file = str_ireplace($dropDown,$Dom->saveHTML($node),$this->HarpFileComponent->getFile()->file); 

            }
        }
        
        return $this;
    }
    
    public function selected($valueSelected,$attr)
    {       
        $keyAttr = key($attr);
        $valueAttr = $attr[$keyAttr];
        $attr = preg_quote($keyAttr.'="'.$valueAttr);
        
        preg_match('`<select*[^>]*'.$attr.'"*[^>]*>(.*?)<\/select>`is',$this->HarpFileComponent->getFile()->file,$m);

        if(!empty($m))
        {
            foreach($m as $i => $v)
            {
                $s =  preg_replace('`selected[^>]=[^>]"selected"`','',$v);
                
                if($i % 2 == 0)
                {
                     if(preg_match('`value="'.$valueSelected.'"`',$v,$matches))
                     {
                        $sl = $matches[0].chr(32).'selected';

                        $s = str_ireplace($matches[0],$sl,$s);
                        
                        $this->HarpFileComponent->getFile()->file = str_ireplace($v,$s,$this->HarpFileComponent->getFile()->file); 
                        
                     }
                }
            }
        }
        
        return $this;
    }    
    
    public function setSelected($valueSelected = null)
    {       
        libxml_use_internal_errors(true) AND libxml_clear_errors();

        if(!empty($this->genericIdentifier))
        {
            $valueSelected = trim($valueSelected);

            $Doc = new DOMDocument('1.0','UTF-8');

            $Doc->preserveWhiteSpace = true;
            
            $Doc->formatOutput = true;

            $Doc->loadHTML($this->HarpFileComponent->getFile()->file);
            
            $Xpath = new DOMXPath($Doc);
            
            $query = $Xpath->query('//*[@name="'.$this->genericIdentifier.'"]/option');

            if($query->length > 0)
            {                
                $select = $this->element;

                foreach($query as $node) 
                {
                    if(trim($valueSelected) == trim($node->getAttribute('value')))
                    {
                        $previousNode = $node->C14N();
                        
                        $node->setAttribute("selected","selected");

                        $select = str_ireplace($previousNode,$Doc->saveHTML($node),$select);
                    }
                    else if($node->hasAttributes())
                    {
                        $previousNode = $node->C14N();
                        
                        $node->removeAttribute("selected");
                        
                        $select = str_ireplace($previousNode,$Doc->saveHTML($node),$select);
                    }
                }
                
                $this->HarpFileComponent->getHandleReplacement()->insertAfter($this->genericIdentifier,$this->element,$select);
            }
            
            return $this;
        }
        
        throw new Exception('Para realizar a inserção do atributo selected é necessário buscar um elemento do tipo select antes!'); 
    }
    
    public function getCurrentKey()
    {
        return $this->currentKey;
    }
    
    public function getName()
    {
        return HandleHtmlEnum::SELECT;
    }    
}
