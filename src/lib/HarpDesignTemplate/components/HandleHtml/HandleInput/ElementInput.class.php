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
namespace etc\HarpDesignTemplate\components\HandleHtml\ElementInput;

include_once(dirname(__DIR__).'/IHtmlElement.interface.php');

use etc\HarpDesignTemplate\components\HandleHtml\IHtmlElement;
use BadMethodCallException;
use DOMDocument;
use DOMXPath;

class ElementInput implements IHtmlElement
{
    private $input;
    private $name;
    private $DomDocument;

    public function __construct($name) 
    {                   
        $this->name = $name;
        $this->DomDocument = new DOMDocument();
        $this->DomDocument->normalizeDocument();
        $this->DomDocument->formatOutput = true;
        $this->input = $this->DomDocument->createElement('input');
       // print_r($this->input);exit;
        $this->input->setAttribute('name',$this->name);   
    }  
        
    private function finish()
    {
        $this->DomDocument->appendChild($this->input);  
    }
    
    private function isFinish()
    {
        $Xpath = new DOMXPath($this->DomDocument);

        $query = $Xpath->query('//*[@name="'.$this->name.'"]');
        
        return $query->length;
    }
    
    public function append(DOMElement $Element)
    {
        throw new BadMethodCallException('O elemento input não suporta este método');
    }    
    
    public function setAttribute($attrName,$attrValue)
    {
        $this->input->setAttribute($attrName,$attrValue);
    }    
    
    public function get()
    {
        if(!$this->isFinish())
        {
            $this->finish();
        }
     
        return $this->DomDocument->saveHTML();
    }
}
