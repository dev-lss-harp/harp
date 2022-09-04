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
namespace etc\HarpDesignTemplate\components\HandleHtml\HandleButton;

use etc\HarpDesignTemplate\components\HarpFileComponent;
use etc\HarpDesignTemplate\components\HandleHtml\HandleHtml;
use Exception;
use DOMDocument;
use DOMXPath;

class ElementButton extends HandleHtml
{
    private $currentIdentifier;
    private $htmlSelect;

    public function __construct(HarpFileComponent &$HarpFileComponent) 
    {           
        parent::__construct($HarpFileComponent);
        
        $this->currentIdentifier = null;
        
        $this->htmlSelect = null;
    }  
    
    public function findByTag($tag)
    {
        throw  new BadMethodCallException('A utilização de tags ainda não foi implementada para este manipulador. Tag:'.$tag);
    }      
        
    public function findByName($name)
    {
            libxml_use_internal_errors(true) AND libxml_clear_errors();

            $Doc = new DOMDocument('1.0','UTF-8');

            $Doc->encoding = 'UTF-8';

            $Doc->loadHTML('<?xml version="1.0" encoding="UTF-8"?>'.$this->HarpFileComponent->getFile()->file);

            $Xpath = new DOMXPath($Doc);

            $query = $Xpath->query('//*[@name="'.$name.'"]');

            if($query->length < 1)
            {
                throw new Exception('select '.$name.' não encontrado!'); 
            }
            
            $this->currentIdentifier = $name;

            $this->htmlSelect = html_entity_decode($query->item(0)->C14N(),ENT_COMPAT,'UTF-8');

            return $this;
    }
    
    private function findInsertionTags()
    {
        $result = Array();

        preg_match('#{H:=Select:Option:Set}(.*){/H:=Select:Option:Set}#is',$this->htmlSelect,$result);
        
        if(count($result) == 2)
        { 
            return $result;
        } 
        
        return null;
    }
    
    private function setUniDimensionalOptions($optionsCollection)
    {
       if(!empty($this->currentIdentifier))
       {
           if(!empty($optionsCollection))
           {    
               $insertionTags = $this->findInsertionTags();
               
               echo '<pre>';print_r($insertionTags);exit;
               //if($this->findInsertionTags())
               
                $options = null;

                if(is_array($optionsCollection) || is_object($optionsCollection))
                {
                     foreach($optionsCollection as $key => $value)
                     {  
                          $options .= str_ireplace(Array('{H:=Select:Option:Set:key}','{H:=Select:Option:Set:value}'),Array($key,$value),$this->htmlSelect[2]);
                     }

                     $select = str_ireplace($this->htmlSelect[1],$options,$this->htmlSelect[0]); 

                     $this->HarpFileComponent->getFile()->file = str_ireplace($this->htmlSelect[0],$select,$this->HarpFileComponent->getFile()->file);
                }  
           }
        }
    }

    private function setTwoDimensionalOptions($optionsCollection,$name)
    {
       if(!empty($optionsCollection) && $this->findByName($name))
       {
            $options = null;
           
            $Replace = Array();
            
            if(is_array($optionsCollection) || is_object($optionsCollection))
            {
                 foreach($optionsCollection as $i => $option)
                 {  
                     foreach($option as $x => $element)
                     {
                         $Replace[$i]['Search'][$x] = '{H:=Select:Option:Set:'.$x.'}';
                         $Replace[$i]['Replace'][$x] = $element;
                     }
                     
                     $options .= str_ireplace($Replace[$i]['Search'],$Replace[$i]['Replace'],$this->htmlSelect[2]);

                     unset($Replace[$i]);
                 }
                // print_r($options);exit;
                 $this->HarpFileComponent->getFile()->file = str_ireplace($this->htmlSelect[1],$options,$this->HarpFileComponent->getFile()->file); 

                // $this->HarpTemplate->setComponentFile($this);

                 //return $this;
            }     
            
         //   return $this;
       }
    }  

    public function setOptions($options)
    {
       if(is_string($this->currentIdentifier) && !empty($options))
       {
           $k = key((Array)$options);
           
           if(!is_array($options[$k]))
           {
               $this->setUniDimensionalOptions($options);
           }
           else
           {
               $this->setTwoDimensionalOptions($options); 
           }
           
           return $this;
       }
       
       throw new Exception('Para realizar a inserção de options é necessário buscar um elemento do tipo select antes!'); 
    }
    
    public function setSelected($valueSelected = null)
    {       
        libxml_use_internal_errors(true) AND libxml_clear_errors();
        
        if(!empty($this->currentIdentifier))
        {
            $valueSelected = trim($valueSelected);

            $Doc = new DOMDocument('1.0','UTF-8');

            $Doc->preserveWhiteSpace = true;
            
            $Doc->formatOutput = true;

            $Doc->loadHTML($this->HarpFileComponent->getFile()->file);
            
            $Xpath = new DOMXPath($Doc);
            
            $query = $Xpath->query('//*[@name="'.$this->currentIdentifier.'"]/option');

            $regex = '`\<select*[^>]*name=*[^\>]*"'.preg_quote($this->currentIdentifier).'".*?[^\>]\>(.*)</select>`is';
                
            $result = Array();
 
            preg_match($regex,$this->HarpFileComponent->getFile()->file,$result);

            if($query->length > 0 && count($result) == 2)
            {                
                $select = $result[0];

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
                
                $this->HarpFileComponent->getHandleReplacement()->insertAfter($this->currentIdentifier,$result[0],$select);
            }
            
            return $this;
        }
        
        throw new Exception('Para realizar a inserção do atributo selected é necessário buscar um elemento do tipo select antes!'); 
    }     
}
