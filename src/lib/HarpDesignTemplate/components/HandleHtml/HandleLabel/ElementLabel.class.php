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
namespace etc\HarpDesignTemplate\components\HandleHtml\ElementLabel;

include_once(dirname(__DIR__).'/IHtmlElement.interface.php');

use etc\HarpDesignTemplate\components\HandleHtml\IHtmlElement;
use DOMDocument;
use DOMXPath;
use DOMElement;

class ElementLabel implements IHtmlElement
{
    private $label;
    private $DomDocument;

    public function __construct() 
    {                   
        $this->DomDocument = new DOMDocument();
        $this->DomDocument->normalizeDocument();
        $this->DomDocument->formatOutput = true;
        $this->label = $this->DomDocument->createElement('label');
    }  
        
    private function finish()
    {
        $this->DomDocument->appendChild($this->label);  
    }
        
    public function setAttribute($attrName,$attrValue)
    {
        $this->label->setAttribute($attrName,$attrValue);
    } 
    
    public function append(DOMElement $Element)
    {
        $this->label->appendChild($Element);
    }    
    
    public function get()
    {
        $this->finish();
        
        return $this->DomDocument->saveHTML();
    }
}
