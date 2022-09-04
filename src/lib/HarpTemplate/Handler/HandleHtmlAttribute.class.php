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
namespace etc\HarpDesignTemplate\components\HandleHtml;

use etc\HarpDesignTemplate\components\HandleHtml\HandleHtml;
use DOMXPath;

class HandleHtmlAttribute
{
    private $DomDocument;
    private $HtmlComponent;
    private $nodesElement;
    private $f1;
    private $f2;
    private $selfClosingTags = [
                                    'area',
                                    'base',
                                    'br',
                                    'col',
                                    'command',
                                    'embed',
                                    'hr',
                                    'img',
                                    'input',
                                    'keygen',
                                    'link',
                                    'meta',
                                    'param',
                                    'source',
                                    'track',
                                    'wbr',
                                ];
    
    public function __construct(HandleHtml $HtmlComponent)
    {
        $this->f1 = defined('LIBXML_HTML_NOIMPLIED') ? LIBXML_HTML_NOIMPLIED : 8192;
        $this->f2 = defined('LIBXML_HTML_NODEFDTD') ? LIBXML_HTML_NODEFDTD : 4;
        
        $this->HtmlComponent = $HtmlComponent;
    }
    
    private function findElementByAttr($attr,$value)
    {
        if(!empty($attr))
        {                
            $this->HtmlComponent->getFileDocument()->getDomDocument()->loadHtml($this->HtmlComponent->getFileDocument()->getFile()->file,$this->f1 | $this->f2 );
            
            $Xpath = new DOMXPath($this->HtmlComponent->getFileDocument()->getDomDocument());

            $path = '//'.$this->HtmlComponent->getName().'[@'.$attr.'="'.$value.'"]';
          
            $this->nodesElement = $Xpath->query($path);

            if(!empty($this->nodesElement) && $this->nodesElement->length > 0)
            {                
                return true;
            }
        }
        
        return false;
    }
    
    public function elementCanBeFoundByAttr(Array $attributes)
    {
        $queryXpath = '';
        
        $c = 0;
        
        $cAttrs = count($attributes);
        
        foreach($attributes as $i => $v)
        {
            if($c > 0 && $c < $cAttrs)
            {
                $queryXpath .= ' and ';
            }
            
            $queryXpath .= '@'.$i.' = "'.$v.'"'; 
            
            ++$c;
        }
        
        if(empty($queryXpath))
        {
           throw new \Harp\bin\ArgumentException('Attribute(s) not informed!');  
        }
        
        return $this->findElementByAttrs($queryXpath);
    }
    
    private function findElementByAttrs($queryXpath)
    {   
        
        if(!empty($queryXpath))
        {
            $this->HtmlComponent->getFileDocument()->getDomDocument()->loadHtml($this->HtmlComponent->getFileDocument()->getFile()->file,$this->f1 | $this->f2);
            
            $Xpath = new DOMXPath($this->HtmlComponent->getFileDocument()->getDomDocument());
            
            $path = '//'.$this->HtmlComponent->getName().'['.$queryXpath.']';
           
            $this->nodesElement = $Xpath->query($path);

            if(!empty($this->nodesElement) && $this->nodesElement->length > 0)
            {
                return true;
            }
        }
        
        return false;
    }    
        
    public function getElementByConjunctionAttributes(Array $attributes)
    {
        $queryXpath = '';
        
        $c = 0;
        
        $cAttrs = count($attributes);
  
        foreach($attributes as $i => $v)
        {
            if($c > 0 && $c < $cAttrs)
            {
                $queryXpath .= ' and ';
            }
            
            $queryXpath .= '@'.$i.' = "'.$v.'"'; 
            
            ++$c;
        }

        if(empty($queryXpath))
        {
           throw new \Harp\bin\ArgumentException('Attribute(s) not informed!');  
        }
     
        if(!$this->findElementByAttrs($queryXpath))
        {
            throw new \Harp\bin\ArgumentException('element with attributes {'.implode(',',$attributes).'} not found!'); 
        }
        
        return $this;
    }


    public function getElementById($id)
    {
        if(!$this->findElementByAttr('id',$id))
        {
            throw new \Harp\bin\ArgumentException('element with id {'.$id.'} not found!'); 
        }
        
        return $this;
    }
    
    public function getElementByName($name)
    {
        if(!$this->findElementByAttr('name',$name))
        {
            throw new \bin\env\SimpleArgument\Harp\bin\ArgumentException('element with name {'.$name.'} not found!'); 
        }
        
        return $this;
    }  
    
    public function getElementsByClass($class)
    {
        if(!$this->findElementByAttr('class',$class))
        {
            throw new \Harp\bin\ArgumentException('element with class {'.$class.'} not found!'); 
        }
        
        return $this;       
    }
    
    protected function normalizeSpecialChars($element)
    {
        return utf8_decode(str_ireplace(['%7B','%7D'],['{','}'], $element));
    }

    public function addAttribute($attr,$value,Array $attrsToSearchElement = [],$clearCurrentAttributes = false)
    {        
        if(!empty($attrsToSearchElement)){ $this->getElementByConjunctionAttributes($attrsToSearchElement); }
        
        if(!empty($this->nodesElement))
        { 
            foreach($this->nodesElement as $item)
            { 
                $oldElement = $item->ownerDocument->saveHTML($item);

                preg_match('`(<'.$this->HtmlComponent->getName().'*[^>].*)>`',$oldElement,$r);
               
                if(!empty($r[0]))
                {   
                    $oldElement = $r[0];
                  
                    $attrElementValue = $item->getAttribute($attr);
                    // print_r($oldElement);print_r($item->C14N());
                    $item->removeAttribute($attr);
                    
                    $newAttribute = '';
               
                    if($attr == 'style'  && !empty($attrElementValue))
                    {                        
                        $separator = ';';

                        $tr = explode(':',$value);
                  
                        foreach($tr as $k => $atr)
                        {
                            if($k % 2 == 0)
                            {
                                $attrElementValue = preg_replace('`'.$atr.'*[^:]:*(.*?)*;`','',$attrElementValue);
                            }
                              print_r($attrElementValue);exit;
                        }
                        
                        $newAttribute = !empty($attrElementValue) ? $attrElementValue.$separator : $attrElementValue;         
                       
                    }
                    else if($attr == 'class' && !empty($attrElementValue))
                    {
                        $separator = chr(32);
                        
                        if(!$clearCurrentAttributes)
                        {
                             $newAttribute = !empty($attrElementValue) ? $attrElementValue.$separator : $attrElementValue;  
                        }
                    }

                    $newAttribute .= $value;
            

                    $item->setAttribute($attr,$newAttribute);
                   
                    $newElement = $item->ownerDocument->saveHTML($item);
                    
                    if(isset($r[1]) && in_array($this->HtmlComponent->getName(),$this->selfClosingTags))
                    {
                        $newElement = substr($newElement,0,-1);
                        $oldElement = $r[1];
                        preg_match('`<'.$this->HtmlComponent->getName().'*[^>].*`',$newElement,$r);
                    }
                    else
                    {
                        preg_match('`<'.$this->HtmlComponent->getName().'*[^>].*>`',$newElement,$r);
                    }

                    if(!empty($r[0]))
                    {                
                        $newElement = $r[0];
                       
                        $this->HtmlComponent->getFileDocument()->updateFile($oldElement,$newElement);
                    }
                
                }
               
            }
        }
    }
    
    public function getSelfTags()
    {
        return $this->selfClosingTags;
    }
    
    public function getDomDocument()
    {
        return $this->DomDocument;
    }
    
    public function getNodes()
    {
        return $this->nodesElement;
    }
}
