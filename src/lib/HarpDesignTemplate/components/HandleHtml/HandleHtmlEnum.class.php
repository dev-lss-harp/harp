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

use bin\enm\HarpEnum;

class HandleHtmlEnum extends HarpEnum
{
    const TABLE = 'table';
    const BUTTON = 'button';
    const IMG = 'img';
    const SELECT = 'select';
    const INPUT = 'input';
    const DIV = 'div';
    const I = 'i';
    const REPEATER = 'Repeat';
    
    const TABLE_TH = 'th';
    const TABLE_TD = 'td';
    const TABLE_TR = 'tr';
    
    const TABLE_TFOOT = 'tfoot';
    const TABLE_THEAD = 'thead';
    const TABLE_TBODY = 'tbody';
    
    const KEY  ='key';
    const VALUE = 'value';
    
    public static function get($key)
    {
        $Enum = new HandleHtmlEnum($key);
        
        return $Enum->getValue();
    }      
}
