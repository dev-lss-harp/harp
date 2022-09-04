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

include_once(__DIR__.'/HandleHtmlAttribute.class.php');
include_once(__DIR__.'/IHandleHtml.interface.php');

use etc\HarpDesignTemplate\components\HarpFileComponent;
use Exception;

abstract class HandleHtml implements IHandleHtml
{
    protected $HarpFileComponent;
    protected $element;
    protected $genericIdentifier;
    protected $HandleHtmlAttribute;
    protected $currentManipulatedElement;
    public abstract function getName();
    protected function __construct(HarpFileComponent &$HarpFileComponent)
    {
        $this->HarpFileComponent = &$HarpFileComponent;

        $this->element = Array();

        $this->HandleHtmlAttribute = new HandleHtmlAttribute($this);
    }
    /**
     * @param type $name
     * @return \etc\HarpDesignTemplate\components\HandleHtml\HandleHtml
     * @throws Elemento {elemento} não encontrado!
     */
    public function findByName($name)
    {
        $this->element = Array();
        
        if(empty($name))
        {
            throw new Exception('Nome do elemento não pode ser vazio ou nulo!'); 
        }

        $elementIdentifier = str_ireplace(Array('Element','Handle'),Array('',''),basename(str_ireplace(Array('\\','/'),DIRECTORY_SEPARATOR,get_class($this))));

        $regex = '`\<'.$elementIdentifier.'*[^>]*name=*[^\>]*"'.preg_quote($name).'".*?\>(.+?)\</'.$elementIdentifier.'\>`is';

        $result = Array();

        preg_match($regex,$this->HarpFileComponent->getFile()->file,$result);

        if(empty($result[0]))
        {
            throw new Exception('Elemento '.$name.' não encontrado!'); 
        }

        $this->genericIdentifier = $name;

        $this->element = $result[0];

        return $this;        
    }
    
    protected function findByAttr($attr,$valueAttr)
    {
        $this->element = Array();
        
        if(empty($attr) || empty($valueAttr))
        {
            throw new Exception('attr or value attr is empty!'); 
        }

        $elementIdentifier = str_ireplace(Array('Element','Handle'),Array('',''),basename(str_ireplace(Array('\\','/'),DIRECTORY_SEPARATOR,get_class($this))));

        $regex = '`\<'.$elementIdentifier.'*[^>]*'.$attr.'=*[^\>]*"'.preg_quote($valueAttr).'".*?\>(.+?)\</'.$elementIdentifier.'\>`is';

        $result = Array();

        preg_match_all($regex,$this->HarpFileComponent->getFile()->file,$result);

        if(empty($result[0]))
        {
            throw new Exception('Element  '.$attr.' = '.$valueAttr.' not found!'); 
        }

        return $result;        
    }    
    
    public function finish()
    {
        if(!empty($this->currentManipulatedElement))
        {
            return $this->HarpFileComponent->getHandleReplacement()->replaceImmediately($this->currentManipulatedElement);
        }
        
        return false;
    }
        
    public function getFileDocument()
    {
        return $this->HarpFileComponent;
    }
    
    public function getHandleAttribute()
    {
        return $this->HandleHtmlAttribute;
    }
    
    public function getCurrent()
    {
        return $this->currentManipulatedElement;
    }
}
