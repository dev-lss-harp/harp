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
namespace etc\HarpDesignTemplate;

use etc\HarpDesignTemplate\components\HarpFileComponent;
use etc\HarpDesignTemplate\DesignTemplateEnum;
use etc\HarpDesignTemplate\components\HandleHtml\HandleHtmlEnum;
use etc\HarpDesignTemplate\plugins\DesignTemplatePlugin;
use etc\HarpDesignTemplate\components\ComponentSource;
use ArrayObject;
use Exception;

class HarpDesignTemplate
{
    private $RequestConfiguration;
    private $Components;
    public static $DesignTemplateEnum;
    public static $HandleHtmlEnum;
    private $FragmentsComponentSource;
    private $baseNameTemplate;
    const PREFIX = 'HDT_';
    const BASE = 'BASE';
    const FRAGMENTS_PATH = __DIR__.'/components/fragments/elements.frag';

    public function __construct(ArrayObject $RequestConfiguration = null)
    { 
        include_once(__DIR__.'/components/HarpFileComponent.class.php');
        include_once(__DIR__.'/DesignTemplateEnum.class.php');
        include_once(__DIR__.'/components/HandleHtml/HandleHtmlEnum.class.php');
        include_once(__DIR__.'/components/ComponentSource.interface.php');
        include_once(__DIR__.'/components/ComponentSourcePath.class.php');
        include_once(__DIR__.'/components/ComponentSourceString.class.php');
        
        $this->RequestConfiguration = &$RequestConfiguration;
        
        $this->FragmentsComponentSource = new components\ComponentSourcePath(self::FRAGMENTS_PATH);
        
        $this->Components = new ArrayObject(Array(),ArrayObject::ARRAY_AS_PROPS);

        self::$DesignTemplateEnum = new DesignTemplateEnum();
        
        self::$HandleHtmlEnum = new HandleHtmlEnum();
    }
    
    public function getRealId($id)
    {
       $newId = mb_strtoupper(self::PREFIX.$id); 
       
       return $newId;
    }
    
    private function defineConstant($newId)
    {
        if(!defined($newId))
        {
            define($newId,$newId);
        }
        
    }
    
    public function isLoadedBase()
    {
        return !empty($this->baseNameTemplate);
    }
    
    public function isLoaded($id)
    {
        $newId = $this->getRealId($id);
        
        return $this->Components->offsetExists($newId);
    }
    
    public function createComponentWithAutoId(ComponentSource $ComponentSource,$base = false)
    {
        if($ComponentSource == null)
        {
            $ComponentSource = $this->FragmentsComponentSource;
        }
        
        $Component = null;
        
        try
        {     
            if($ComponentSource instanceof components\ComponentSourcePath)
            {
                $id = basename($ComponentSource->getSource());
                
                $pid = explode('.',$id);
                
                if(count($pid) > 0)
                {
                    unset($pid[count($pid) - 1]);
                }    
                
                $id = mb_strtoupper(implode('.',$pid));
            }
            
            $newId = $this->getRealId($id);

            if(!$this->Components->offsetExists($newId))
            {
                $Component = new HarpFileComponent($id,$ComponentSource);
              
                $this->Components->offsetSet($newId,$Component);
            }
            else
            {
                $Component = $this->Components->offsetGet($newId);  
            }
            
            $this->defineConstant($newId);

            if($base){ $this->baseNameTemplate = $id; }
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return $Component;
    }

    public function createComponent($id, ComponentSource $ComponentSource = null,$base = false)
    {
        if($ComponentSource == null)
        {
            $ComponentSource = $this->FragmentsComponentSource;
        }
        
        $Component = null;
        
        try
        {     
            if(empty($id))
            {
                throw new \Harp\bin\ArgumentException('Id can not be empty or null!');
            }
            else if(!$ComponentSource instanceof ComponentSource)
            {
                 throw new \Harp\bin\ArgumentException('Component Source must be an instance of ComponentSourcePath or ComponentStringPath!');
            }
            
            $newId = $this->getRealId($id);

            if(!$this->Components->offsetExists($newId))
            {
                $Component = new HarpFileComponent($id,$ComponentSource);
              
                $this->Components->offsetSet($newId,$Component);
            }
            else
            {
                $Component = $this->Components->offsetGet($newId);  
            }
            
            $this->defineConstant($newId);
            
            if($base){ $this->baseNameTemplate = $id; }
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return $Component;
    }    
    
    public function replaceComponent($id,$path)
    {
        $Component = null;
        
        try
        {            
            $newId = $this->getRealId($id);
            
            if(!$this->Components->offsetExists($newId))
            {
                throw new \Harp\bin\ArgumentException('To replace a component it must exist previously!');
            }
            else if(empty($path))
            {
                 throw new \Harp\bin\ArgumentException('Path to file can not be empty or null!');
            }
            
            $Component = new HarpFileComponent($id,$path);

            $this->Components->offsetSet($newId,$Component);            

        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return $Component;
    }    
    
    public function getPlugin($pluginName,$subPluginName = null)
    {
        try
        {
            if(!$this->RequestConfiguration instanceof ArrayObject)
            {
                throw new \Harp\bin\ArgumentException("To load plugins it is necessary to enter the requisition information!");
            }   
            
            include_once(__DIR__.'/plugins/DesignTemplatePlugin.class.php');
            $Plugin = new DesignTemplatePlugin($pluginName);
            $args[plugins\PluginEnum::REQUEST_CONFIGURATION] = $this->RequestConfiguration;
         
            $Plugin->setArguments($args)->configUrlPlugin();
            
            $subPluginName = empty($subPluginName) ? $pluginName : $subPluginName;
            
            return $Plugin->getPlugin($subPluginName);
        }
        catch(Exception $ex)
        {
            throw $ex;
        } 
    }   
    
    public function getComponentById($id)
    {
        $newId = $this->getRealId($id);
        
        try
        {
            if(!$this->Components->offsetExists($newId))
            {
                throw new \Harp\bin\ArgumentException("Could not load template: [".$id."]!");
            } 
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return $this->Components->offsetGet($newId);
    }
    
    public function getCurrentBase($baseName = null)
    {
        $k = empty($baseName) ? (!empty($this->baseNameTemplate) ? mb_strtoupper(self::PREFIX.$this->baseNameTemplate) : self::PREFIX.self::BASE): mb_strtoupper(self::PREFIX.$baseName);
        
        if($this->Components->offsetExists($k))
        { 
           return $this->Components->offsetGet($k);
        }
    }


    public function setBaseNameTemplate($name)
    {
        $this->baseNameTemplate = $name;
    }
    
    public function getBaseNameTemplate()
    {
        return $this->baseNameTemplate;
    }    
    
    public function showBase($baseName = null)
    {
        try
        {
            $k = empty($baseName) ? (!empty($this->baseNameTemplate) ? mb_strtoupper(self::PREFIX.$this->baseNameTemplate) : self::PREFIX.self::BASE): mb_strtoupper(self::PREFIX.$baseName);

            if($this->Components->offsetExists($k))
            { 
                $this->Components->offsetGet($k)->show();
            }
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
    }    
        
    public function getComponentByRealId($newId)
    {
        try
        {
            if(!$this->Components->offsetExists($newId))
            {
                throw new \Harp\bin\ArgumentException("Could not load template: [".$newId."]!");
            } 
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return $this->Components->offsetGet($newId);
    }    
}
