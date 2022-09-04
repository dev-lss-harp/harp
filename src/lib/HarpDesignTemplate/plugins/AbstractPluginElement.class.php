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
use etc\HarpDesignTemplate\plugins\DesignTemplatePlugin;

include_once(__DIR__.'/IPlugin.interface.php');
include_once(__DIR__.'/PluginElementEnum.class.php');

abstract class AbstractPluginElement implements IPlugin
{    
    private $link;
    private $script;
    private $class;
    protected $Template;
    protected  $DesignTemplatePlugin;
    protected $htmlElement;
    protected $htmlPlugin;
    
    abstract public function getElement();
    abstract public function getPlugin();
    
    protected function __construct(DesignTemplatePlugin &$DesignTemplatePlugin)
    {
        $this->DesignTemplatePlugin = &$DesignTemplatePlugin;
        
        $this->link = null;
        
        $this->script = null;
        
        $this->htmlElement = null;
        
        $this->class = null;
    }
    
    public function addLink($relativePathCss,$fileName,$extension = null,$type = null,$rel = 'stylesheet')
    {
        clearstatcache();

        $extension = empty($extension) ? IHarpPlugin::EXT_CSS : $extension;
        
        $rel = empty($rel) ? null : 'rel="'.$rel.'"';
        
        $type = empty($type) ? null : 'type="'.$type.'"';
        
        if(file_exists($this->DesignTemplatePlugin->getPluginPath().'/'.$relativePathCss.'/'.$fileName.'.'.$extension))
        {
            $this->link .= '<link href="'.$this->DesignTemplatePlugin->getPluginUrl().'/'.$relativePathCss.'/'.$fileName.'.'.$extension.'" '.$rel.' '.$type.'/>'.PHP_EOL;

            return true;
        }

        return false;
    } 
    
    public function addScript($relativePathCss,$fileName,$extension = null,$type = null)
    {
        clearstatcache();
        
        $extension = empty($extension) ? 'js' : $extension;
    //ALTERAR AQUI
        $type = empty($type) ? null : 'type="'.$type.'"';

        if(file_exists($this->DesignTemplatePlugin->getPluginPath().'/'.$relativePathCss.'/'.$fileName.'.'.$extension))
        {
            $this->script .= '<script src="'.$this->DesignTemplatePlugin->getPluginUrl().'/'.$relativePathCss.'/'.$fileName.'.'.$extension.'" '.$type.'></script>'.PHP_EOL;

            return true;
        }
        
        return false;
    }
    
    public function addClass($className)
    {
        $this->class .= chr(32).$className;
    }   
    
    public function getClass()
    {
        return trim($this->class);
    }      
    
    protected  function getLink()
    {
         return $this->link;
    }

    protected  function getScript()
    {
        return $this->script;
    }
}
