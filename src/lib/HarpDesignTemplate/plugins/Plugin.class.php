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

use etc\HarpDesignTemplate\plugins\DesignTemplatePlugin;

include_once(__DIR__.'/PluginInterface.interface.php');

abstract class Plugin implements PluginInterface
{    
    protected $DesignTemplatePlugin;
    protected $scripts = Array();
    protected $css = Array();

    protected function __construct(DesignTemplatePlugin $DesignTemplatePlugin) 
    {
       $this->DesignTemplatePlugin = &$DesignTemplatePlugin; 
    }
    
    protected function bindScript($urlFile)
    {
        if(!empty($urlFile) && !filter_var($urlFile,FILTER_VALIDATE_URL) === false)
        {
            return '<script type="text/javascript" src="'.$urlFile.'"></script>';      
        }
        
    }
    
    protected function bindCss($urlFile)
    {
        if(!empty($urlFile) && !filter_var($urlFile,FILTER_VALIDATE_URL) === false)
        {
             return '<link rel="stylesheet" href="'.$urlFile.'"/>';       
        }
        
    }    

    public function unshiftCssFile($urlFile)
    {
        array_unshift($this->css,$this->bindCss($urlFile));
    } 

    public function unshiftScriptFile($urlFile)
    {
        array_unshift($this->scripts,$this->bindScript($urlFile));
    }
    
    public function pushCssFile($urlFile)
    {
        array_push($this->css,$this->bindCss($urlFile));     
    } 

    public function pushScriptFile($urlFile)
    {
        array_push($this->scripts,$this->bindScript($urlFile));
    }  
    
    public function getLinkedScripts()
    {
        if(!empty($this->scripts))
        {
            return implode(PHP_EOL,$this->scripts).PHP_EOL;
        }
        
        return null;
    }
    
    public function getLinkedCss()
    {
        if(!empty($this->css))
        {
            return implode(PHP_EOL,$this->css).PHP_EOL;
        }
        
        return null;
    }    
}
