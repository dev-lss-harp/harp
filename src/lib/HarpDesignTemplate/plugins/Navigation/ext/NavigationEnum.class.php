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

use bin\enm\HarpEnum;
use bin\ext\HarpHandler\EntityHandlerInterface;

class NavigationEnum extends HarpEnum
{    
    
    const NAVIGATION_MENU = 'NavigationMenu';
    const NAVIGATION_LINK = 'NavigationLink';
    const NAVIGATION_BREADCRUMB = 'NavigationBreadcrump';
    const START_PARENT_ID = 'START_PARENT_ID';
    const NAVIGATION_LIST = 'NAVIGATION_LIST';
    const NAME = 'name';
    const PARENT_ID = 'parentId';
    const TYPE = 'type';
    const ID = 'id';
    const TITLE = 'title';
    const ICON = 'icon';
    const SITUATION = 'situation';
    const SUB_LIST = 'sublist';
    const DEFAULT_ID = 'PluginNavigation';
    const DEFAULT_CLASS = 'PluginNavigation';
    
    
    
    private $parameters = Array
    (
        self::NAME => self::NAME,
        self::PARENT_ID => self::PARENT_ID,
        self::ID => self::ID,
        self::TITLE => self::TITLE ,
        self::ICON => self::ICON,
        self::SITUATION => self::SITUATION,
        self::TYPE => self::TYPE,
        self::SUB_LIST  =>  self::SUB_LIST,     
    );

    private $methods = Array();
    private $startParentId = 0;
    private $baseUrl;
    
    public function getParentId()
    {
        return $this->startParentId;
    }
    
    public function setStartParentId($id)
    {
        if(ctype_digit($this->startParentId) || is_int($this->startParentId))
        {
            $this->startParentId = $id;
        }
    }
    
    public function setBaseUrl($url)
    {
        if(filter_var($url, FILTER_VALIDATE_URL) === false) 
        {
            return false;
        }
        
        $this->baseUrl = $url;
    }
    
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    public function setMethodToExecution($key,$value)
    {
     //   print($key);print($value);print('<br/>');
        if(isset($this->parameters[$key]) && !empty($value))
        {
            $this->methods[$key] = EntityHandlerInterface::METHOD_PREFIX_GET.ucfirst($value);
        }
    }
    
    public function addDefaultMethods()
    {
        foreach($this->parameters as $k => $v)
        {
            $this->setMethodToExecution($k,$v);
        }
    }
    
    public function getMethod($key)
    {
        return isset($this->methods[$key]) ? $this->methods[$key] : null;
    }
    
    public function getMethods()
    {
        return $this->methods;
    }
    
    public static function get($key)
    {
        $Enum = new NavigationEnum($key);
        
        return $Enum->getValue();
    }  
}
