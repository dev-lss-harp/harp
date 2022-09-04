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
namespace etc\HarpDesignTemplate\components;

include_once(__DIR__.'/HarpTemplateFile.class.php');
include_once(__DIR__.'/HandleReplacement.class.php');
include_once(__DIR__.'/HandleGrouping.class.php');
include_once(__DIR__.'/HandleRepeater.class.php');
include_once(__DIR__.'/HandleBlock.class.php');
include_once(__DIR__.'/HandleHtml/HandleHTags.class.php');
include_once(__DIR__.'/ComponentSource.interface.php');
include_once(__DIR__.'/ComponentSourcePath.class.php');
include_once(__DIR__.'/ComponentSourceString.class.php');

use etc\HarpDesignTemplate\components\HarpTemplateFile;
use etc\HarpDesignTemplate\components\HandleReplacemet;
use etc\HarpDesignTemplate\components\ComponentSource;
use etc\HarpDesignTemplate\DesignTemplateEnum;
use etc\HarpDesignTemplate\components\HandleHtml\HandleHTags;

use ArrayObject;

class HarpFileComponent extends HarpTemplateFile
{
    private $id;
    private $Components;
    private $ParentComponents;
    private $HandleReplacement;
    private $HandleGrouping;
    private $HandleRepeater;
    private $startedFinish;
  //  private $fileName;
    private $ComponentSource;
    public $HandleHTags;

    public function __construct($id,ComponentSource $ComponentSource)
    {
        $this->ComponentSource = $ComponentSource;
        
        parent::__construct($ComponentSource);
        
        parent::load();
        
      /*  if($ComponentSource instanceof ComponentSourcePath)
        {
            $this->fileName = basename($ComponentSource->getSource());
        }
*/
        $this->Components = new ArrayObject(Array(),ArrayObject::ARRAY_AS_PROPS);
        
        $this->ParentComponents = Array();
        
        $this->id = $id;
        
        $this->HandleReplacement = new HandleReplacemet($this);
        $this->HandleGrouping = new HandleGrouping($this);
        $this->HandleRepeater = new HandleRepeater($this);
        $this->HandleHTags = new HandleHTags($this);
        $this->startedFinish = false;
        
        include_once(__DIR__.'/HandleHtml/HandleHtml.class.php');
    }
    
    public function getHandleHtml($element)
    {
        $element = 'Handle'.ucfirst($element);

        if(file_exists(__DIR__.'/HandleHtml/'.$element.'/'.$element.'.class.php'))
        {
            include_once(__DIR__.'/HandleHtml/'.$element.'/'.$element.'.class.php');
         
            $Class = DesignTemplateEnum::BASE_NAMESPACE_HANDLE_HTML.'\\'.$element.'\\'.$element;

            return new $Class($this);
        }
        
        return null;
    }    
    
    public function addComponent(HarpFileComponent &$HarpFileComponent)
    {
        if(!$this->Components->offsetExists($HarpFileComponent->getId()))
        {
            $this->Components->offsetSet($HarpFileComponent->getId(),$HarpFileComponent);
            
            $HarpFileComponent->setParent($this->getId());
        }    
    }
    
    private function setParent($id)
    {
        $this->ParentComponents[$id] = $id;
    }
    
    public function getParents()
    {
        return $this->ParentComponents;
    }

    private function finish()
    {
        $containnerComponents = Array();

        foreach($this->Components as $Component)
        {
           $Component->getHandleReplacement()->execute(); 
             
           $ReflectionMethod = new \ReflectionMethod($Component,'finish');
           
           $ReflectionMethod->setAccessible(true);
           
           $ReflectionMethod->invoke($Component);
           
           $containnerComponents[$Component->getId()] = $Component->getFile()->file;
           
           $ReflectionMethod->setAccessible(false);
        }

        $this->getHandleReplacement()->addAllBefore($containnerComponents);
        
        $this->getHandleReplacement()->execute();
    }
    
    public function show(Array $exclude = Array())
    {
         $this->finish();
         
         $this->getHandleReplacement()->closeCursor($exclude);
                  
         exit(print($this->getFile()->file));
    }
    
    public function getTemplate()
    {
         $this->finish();
         
         $this->getHandleReplacement()->closeCursor();
         
         return $this->getFile()->file;
    }
    
    public function getHandleReplacement()
    {
        return $this->HandleReplacement;
    }
    
    public function getHandleGrouping()
    {
        return $this->HandleGrouping;
    } 
    
    public function getHandleGrid()
    {
        return $this->HandleGrid;
    } 

    public function getHandleRepeater()
    {
        return $this->HandleRepeater;
    } 
    
    public function getHandleBlock()
    {
        return new HandleBlock($this);
    }    

    private function getStartedFinish()
    {
        return $this->startedFinish;
    }

    public function getFileComponent($id = null)
    {
        if(!empty($id) && $this->Components->offsetExists($id))
        {
            return $this->Components->offsetGet($id);
        }
        
        return null;
    }

    public function getId()
    {
        return $this->id;
    }
}