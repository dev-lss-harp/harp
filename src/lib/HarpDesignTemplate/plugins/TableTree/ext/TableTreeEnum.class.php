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
namespace etc\HarpDesignTemplate\plugins\TableTree;

use bin\enm\HarpEnum;

class TableTreeEnum extends HarpEnum
{    
    const ARRAY_PERMISSIONS = 'ARRAY_PERMISSIONS';
    const TABLE_TREE_PLUGIN = 'TableTree';
    const TABLE_TREE_PLUGIN_CHECKBOX = 'TableTreeCheckbox';
    const ID = 'id';
    const PARENT_ID = 'parentId';
    const DEFAULT_HTML_ID = 'pluginTableTree';
    const DEFAULT_HTML_CLASS = 'pluginTableTree';
    const DEFAULT_CHECKBOX_NAME = 'item';
        
    public static function get($key)
    {
        $thisClass = __CLASS__;
        
        $Enum = new $thisClass($key);
        
        return $Enum->getValue();
    }  
}