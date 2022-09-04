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

use etc\HarpHttp\HarpHttpRequest\RequestEnum;
use etc\HarpDesignTemplate\plugins\Plugin;
use DirectoryIterator;
use Exception;

include_once(__DIR__.'/RunnableInterface.interface.php');
include_once(__DIR__.'/Plugin.class.php');
include_once(__DIR__.'/PluginEnum.class.php');

class DesignTemplatePlugin 
{
    private $pluginName;
    private $pathRunnable;
    private $Runnable;
    private $nameMainFile;
    private $basePath;
    private $baseUrl;
    private $contextTemplate;
    private $pluginDirectory;
    private $nameClass;
    private $CurrentPlugin;
    private $pluginArguments;
    private $resultRun;
    private $HarpEngineTemplate;
    
    public $PluginEnum;

    public function __construct($pluginName) 
    {
        $this->pluginName = trim($pluginName);
        
        $this->setPluginDirectory($pluginName);
                       
        $this->PluginEnum = new PluginEnum();
        
        $this->CurrentPlugin = new \ArrayObject(Array(),\ArrayObject::ARRAY_AS_PROPS);
        
        $this->pluginArguments = new \ArrayObject(Array(),\ArrayObject::ARRAY_AS_PROPS);
    }
    
    private function setPluginDirectory($pluginDirectory)
    {
        $this->pluginDirectory = $pluginDirectory;
    }
    
    public function getPluginDirectory()
    {
        return $this->pluginDirectory;
    }


    public function getEngineTemplate()
    {
        return $this->HarpEngineTemplate;
    }

    public function getNameMainFile() 
    {
        return $this->nameMainFile;
    }

    public function getBasePath() 
    {
        return $this->basePath;
    }

    public function getContextTemplate() 
    {
        return $this->contextTemplate;
    }

    public function getNameClass() 
    {
        return $this->nameClass;
    }

    public function setBasePath($basePath) 
    {
        $this->basePath = $basePath;
        
        if(empty($this->pathRunnable))
        {
           $this->setPathRunnable($this->getBasePath());
        }
    }

    public function setContextTemplate(&$contextTemplate) 
    {
        $this->contextTemplate = &$contextTemplate;
    }
    
    public function setArguments(Array $pluginArguments)
    {
        $this->pluginArguments->exchangeArray($pluginArguments);
  
        return $this;
    }
    
    private function loadExtPlugin()
    {
        if(is_dir($this->getPluginPath().'/'.PluginEnum::EXT_DIRECTORY))
        {
            $ExtIterator = new DirectoryIterator($this->getPluginPath().'/'.PluginEnum::EXT_DIRECTORY);

            foreach ($ExtIterator as $item) 
            {
                if(!$item->isDot()) 
                {
                    include_once($item->getPath().'/'.$item->getFilename());
                }
            }                 
        }
    }
    
    private function loadRunnable(DesignTemplatePlugin $DesignPlugin)
    {        
        $ClassName = PluginEnum::PREFIX_RUNNABLE_CLASS.$this->getPluginDirectory();

        $pathRunnable = $this->getPathRunnable().'/'.$ClassName.'.class.php';

        if(!file_exists($pathRunnable))
        {
            throw new Exception('Runnable class to {'. $ClassName.'} not exist in directory: {'.$this->getPathRunnable().'}!'); 
        }
        else
        {
            include_once($pathRunnable);
            
            $this->Runnable = new $ClassName($DesignPlugin);
            
            if(!$this->Runnable instanceof RunnableInterface)
            {
                throw new Exception('{'.$ClassName.'} class must implement the interface {RunnableInterface}!'); 
            }
        }
    }


    private function validatePlugin()
    {    
        try
        {
            if(!is_dir($this->getPluginPath().'/'.PluginEnum::PLG_DIRECTORY))
            {
                 throw new Exception('{'.PluginEnum::PLG_DIRECTORY.'} directory does not exist or is empty on: {'.$this->getPluginPath().'}!');           
            }
            
            $this->loadExtPlugin();

            $DirectoryIterator = new DirectoryIterator($this->getPluginPath().'/'.PluginEnum::PLG_DIRECTORY);

            foreach ($DirectoryIterator as $item) 
            {
                if(!$item->isDot()) 
                {
                    include_once($item->getPath().'/'.$item->getFilename());

                    $ClassName = stristr($item->getBasename(),'.',true);
                   
                    $Object = new $ClassName($this);
                   
                    if($Object instanceof Plugin)
                    {
                        $this->CurrentPlugin->offsetSet($ClassName,$Object);
                    }
                }
            }

            if($this->CurrentPlugin->count() == 0)
            {
               throw new Exception('Class {'.$ClassName.'} in Directory {'.PluginEnum::PLG_DIRECTORY.'} must be sub class {Plugin}!');                  
            }           
        }
        catch (Exception $ex)
        {
            throw $ex;
        }
    }
    
    public function configPathPlugin()
    {        
        try
        {
            $this->basePath = $this->getArgument(PluginEnum::REQUEST_CONFIGURATION)->offsetGet(RequestEnum::PATH_ETC).'/HarpDesignTemplate/plugins';

            if($this->getArguments()->offsetExists(PluginEnum::PATH_PLUGIN))
            {
                $this->basePath = $this->getArgument(PluginEnum::PATH_PLUGIN);
            } 

            $this->validatePlugin(); 
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return $this;
          
    }
    
    public function configUrlPlugin()
    {
        $this->baseUrl = $this->getArgument(PluginEnum::REQUEST_CONFIGURATION)->offsetGet(RequestEnum::HTTP_URL_ETC).'/HarpDesignTemplate/plugins';
        
        if($this->getArguments()->offsetExists(PluginEnum::BASE_URL_PLUGIN))
        {
            $this->baseUrl = $this->getArgument(PluginEnum::BASE_URL_PLUGIN);
        }  
       
        return $this->configPathPlugin();
    }
    
    private function configPathRunnable()
    {  
        $this->pathRunnable = $this->getBasePath().'/'.PluginEnum::RUNNABLE_DIRECTORY;

        if($this->getArguments()->offsetExists(PluginEnum::PATH_RUNNABLE) && is_dir($this->getArgument(PluginEnum::PATH_RUNNABLE)))
        {
            $this->pathRunnable = $this->getArgument(PluginEnum::PATH_RUNNABLE).'/'.PluginEnum::RUNNABLE_DIRECTORY;
        }

        return $this;        
    }

    public function getPathRunnable()
    {
        return $this->pathRunnable;
    }
        
    public function getArguments()
    {
        return $this->pluginArguments;
    }  
    
    public function getArgument($key)
    {
        if(!empty($key))
        {
          return $this->pluginArguments->offsetGet($key);  
        }
        
        return null;
    }      
    
    public function getBaseUrl() 
    {
        return $this->baseUrl;
    }

    public function getPluginPath()
    {
        return $this->getBasePath().'/'.$this->getPluginDirectory();
    }

    public function getPluginUrl()
    {
        return $this->getBaseUrl().'/'.$this->getPluginDirectory();
    }
    
    
    
  /*  public function setBaseUrl($baseUrl) 
    {
        $this->baseUrl = $baseUrl;
    }*/

        
   /* public function instance()
    { 
        if($this->exists())
        {
            require_once($this->getBasePath().'/AbstractPluginElement.class.php');

            require_once($this->getPluginPath().'/'.$this->getNameMainFile());

            $nameClass = $this->getNameClass();

            $this->CurrentPlugin = new $nameClass($this); 

            return $this->CurrentPlugin;
        }
        
        return null;
    }*/
        
    public function getRunnablePlugin()
    {
        return $this->Runnable;
    }
    
    
    public function getPlugin($subPluginName)
    {
        if(!empty($subPluginName))
        {
            return $this->CurrentPlugin->offsetGet($subPluginName);
        }
  
        return $this->CurrentPlugin;
        
    }
    
    public function build($method = PluginEnum::DEFAULT_RUNNABLE_METHOD)
    {
        $fileRunnable = $this->getPathRunnable().'/'.$this->PluginEnum->get(PluginEnum::DIR_RUNNABLE).'/Runnable'.$this->getNameMainFile();

        if(file_exists($fileRunnable))
        {
            require_once($this->getBasePath().'/'.$this->PluginEnum->get(PluginEnum::DIR_RUNNABLE).'/RunnablePluginInterface.interface.php');
            require_once($this->getPathRunnable().'/'.$this->PluginEnum->get(PluginEnum::DIR_RUNNABLE).'/Runnable'.$this->getNameMainFile());            

            $class = $this->PluginEnum->get(PluginEnum::PREFIX_RUNNABLE_CLASS).$this->getNameClass();
            
            $RunnablePlugin = new $class($this);
            
            if(!method_exists($RunnablePlugin,$method))
            {
                return false;
            }            
            
            $this->resultRun = $RunnablePlugin->$method(); 
                  
            return $this;
        }
        
        throw new Exception('File: {'.basename($fileRunnable).'} not found in {'.  dirname($fileRunnable).'}!');

    }
    
    public function run()
    {
        return $this->resultRun;
    }
}
