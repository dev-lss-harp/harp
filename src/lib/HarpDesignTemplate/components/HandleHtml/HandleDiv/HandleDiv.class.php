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
namespace etc\HarpDesignTemplate\components\HandleHtml\HandleDiv;

use etc\HarpDesignTemplate\components\HarpFileComponent;
use etc\HarpDesignTemplate\components\HandleHtml\HandleHtml;
use etc\HarpDesignTemplate\components\HandleHtml\HandleHtmlEnum;

include_once(__DIR__.'/DivElement.class.php');

class HandleDiv extends HandleHtml
{    
    public $DivElement;
    
    public function __construct(HarpFileComponent &$HarpFileComponent)
    {  
        parent::__construct($HarpFileComponent);
        
        $this->DivElement = new DivElement($this);
    }
    
    public function findByTag($tag){ return $tag; }

    public function getName()
    {
        return HandleHtmlEnum::DIV;
    }    
}
