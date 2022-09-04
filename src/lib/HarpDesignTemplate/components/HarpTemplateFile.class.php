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

include_once(__DIR__.'/IFileComponent.interface.php');

use Exception;
use stdClass;
use DOMDocument;
use Harp\bin\ArgumentException;
use etc\HarpDesignTemplate\components\ComponentSource;

abstract class HarpTemplateFile implements IFileComponent
{
    //private $path;
    private $stdClassFile;
    private $DomDocument;
    private $ComponentSource;
    protected $file;

    protected function __construct(ComponentSource $ComponentSource)
    {      
        $this->ComponentSource = $ComponentSource;
        
      //  $this->path = $path;
        
        $this->DomDocument = new DOMDocument('1.0','UTF-8');
        
        $this->DomDocument->preserveWhiteSpace = true;    
        $this->DomDocument->validateOnParse = true;
        
        $this->stdClassFile = new stdClass();
        
        $this->stdClassFile->file = null;
    }
    
    protected function load()
    {
        try
        {   
            libxml_use_internal_errors(true) AND libxml_clear_errors();

            $file = $this->ComponentSource->getSource();
            
            if($this->ComponentSource instanceof ComponentSourcePath)
            {   
                $FileExists = new \bin\env\FileExists($this->ComponentSource->getSource());

                $ArgException = new ArgumentException();
                $ArgException->setMessage($FileExists->formatMessage(new \bin\env\IntegrityCheckMessage('template %s does not exist in %s')));
                $ArgException->addArgument(ArgumentException::TYPE_EXCEPTION,ArgumentException::WARNING_TYPE_EXCEPTION);            

                $file = @file_get_contents($this->ComponentSource->getSource());

                if(!$FileExists->verify() || $file === false)
                {                             
                    throw $ArgException;
                }
            }
            
            $this->stdClassFile->file = html_entity_decode($file,ENT_QUOTES);   
            
        } 
        catch(Exception $e)
        {
            throw $e;
        }
    }  
    
    public function updateFile($oldElement,$newElement)
    { 
        $this->stdClassFile->file = str_ireplace($oldElement,$newElement,$this->stdClassFile->file);  
    }
    
    public function updateFileByRegex($regex,$newElement)
    {   
        $this->stdClassFile->file = preg_replace($regex,$newElement,$this->stdClassFile->file);
    }
    
    public function &getFile()
    {
        return $this->stdClassFile;
    }
    
    public function &getDomDocument()
    {
        return $this->DomDocument;
    }
}
