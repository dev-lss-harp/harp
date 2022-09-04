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

class PluginEnum extends HarpEnum
{    
    //const DIR_RUNNABLE = 'RunnablePlugin';
    //const DEFAULT_RUNNABLE_METHOD = 'runnable';
    const RUNNABLE_DIRECTORY = 'runnable';
    const PREFIX_RUNNABLE_CLASS = 'Runnable';
    const REQUEST_CONFIGURATION = 'REQUEST_CONFIGURATION';
    const PATH_RUNNABLE = 'PATH_RUNNABLE';
    const PATH_PLUGIN = 'PATH_PLUGIN';
    const BASE_URL_PLUGIN = 'BASE_URL_PLUGIN';
    const EXT_DIRECTORY = 'ext';
    const PLG_DIRECTORY = 'plg';

    public static function get($key)
    {
        $thisClass = __CLASS__;
        
        $Enum = new $thisClass($key);
        
        return $Enum->getValue();        
    }
}
