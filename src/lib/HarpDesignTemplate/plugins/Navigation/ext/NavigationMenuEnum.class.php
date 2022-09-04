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
namespace etc\HarpDesignTemplate\plugins\Navigation;

class NavigationMenuEnum extends NavigationEnum
{    
    const LIB_NAME = 'LIB_NAME';
    const LIB_STYLE = 'LIB_STYLE';
    const ATTR_CLASS = 'class';
    const ATTR_ID = 'id';
    const ATTR_NAV_ID = 'navId';
    const ATTR_NAV_CLASS = 'navClass';
    const IDENTIFIER = 'IDENTIFIER';
    const ATTRIBUTE = 'ATTRIBUTE';
    const ATTR_VALUE = 'ATTR_VALUE';
    const DEFAULT_ID = 'PluginNavigationMenuId';
    const DEFAULT_CLASS = 'PluginNavigationMenuClass';
    const DEFAULT_NAV_ID = 'PluginNavigationMenuNavId';
    const DEFAULT_NAV_CLASS = 'PluginNavigationMenuNavClass';
    const ELEMENT_IDENTIFIER_RUN_SCRIPT = 'ELEMENT_IDENTIFIER_RUN_SCRIPT';
    const START_PARENT_ID = 'START_PARENT_ID';
    const NAVIGATION_LIST = 'NAVIGATION_LIST';
    const NAME = 'name';
    const PARENT_ID = 'parentId';
    const ID = 'id';
    const TITLE = 'title';
    const SITUATION = 'situation';
    const SUB_LIST = 'sublist';
   
    public static function get($key)
    {
        $thisClass = __CLASS__;
        
        $Enum = new $thisClass($key);
        
        return $Enum->getValue();
    }  
}
