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
namespace etc\HarpDesignTemplate\plugins;

use bin\enm\HarpEnum;

class PluginElementEnum extends HarpEnum
{
    const LINK = 1; 
    const SCRIPT = 2; 
    const DEFAULT_TYPE_LINK = 'text/css';
    const DEFAULT_TYPE_JS = 'text/javascript';
    
    public static function get($key)
    {
        $Enum = new PluginElementEnum($key);
        
        return $Enum->getValue();        
    }
}
