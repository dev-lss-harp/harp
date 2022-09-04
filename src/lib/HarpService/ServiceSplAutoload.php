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
namespace Harp\lib\HarpService;

use \RecursiveDirectoryIterator;
use \RecursiveIteratorIterator;

class ServiceSplAutoload
{
    private static $paths = [];
    private $ignoredPaths = [];
    private static $instance;
    
    const CLASS_EXTENSION = '.class.php';
    const MAIN_EXTENSION = '.php';
    const INTERFACE_EXTENSION = '.interface.php';
    const TRAIT_EXTENSION = '.trait.php';
    const SPL_METHOD = 'SplAutoload';
    
    private function __construct()
    {
        spl_autoload_register();

        spl_autoload_extensions(ServiceSplAutoload::CLASS_EXTENSION.','.ServiceSplAutoload::INTERFACE_EXTENSION.','.ServiceSplAutoload::MAIN_EXTENSION);

        spl_autoload_register(array($this,ServiceSplAutoload::SPL_METHOD));          
    }
       
    public static function getInstance()
    {
        $thisClass = __CLASS__;
        
        return (self::$instance == null) ? new $thisClass() : self::$instance;
    } 
    
    public function setIgnoreDir($path,$dir)
    {
        if(!isset($this->ignoredPaths[$path]))
        {

                $this->ignoredPaths[$path] = [];
        }
        
        array_push($this->ignoredPaths[$path],$dir);
    }
    
    public function getIgnoredDirectories()
    {
        return $this->ignoredPaths;
    }
    
    public function isIgnored($path,$dir)
    {
        return isset($this->ignoredPaths[$path][$dir]);
    }    
    
    public function setPath($path)
    {
        if(!isset(self::$paths[$path]) && is_dir($path))
        {
            self::$paths[$path] = $path;
        }
    }
    
    private function setPathValidate(Array $ignoredDirectories,string $path, \SplFileInfo $dir)
    {
        

        if(empty($ignoredDirectories) && $dir->isDir())
        {
            self::$paths[$path] = $path;
        }
        else
        {

            $validPath = true;
            for($p = 0; $p < count($ignoredDirectories);++$p)
            {
                    $directory = $ignoredDirectories[$p];
                    if(preg_match('`\b'.preg_quote($directory).'\b`',$path))
                    {
                        $validPath = false;     
                        break;
                    }
            }

            if($validPath){ self::$paths[$path] = $path;}
        }
    }
    
    public function mapPath($path)
    {            
        clearstatcache();
        
        if(is_dir($path))
        {
            self::$paths[$path] = str_ireplace(['\\'],['/'],$path);
            
            $ignoredDirectories = isset($this->ignoredPaths[$path]) ? $this->ignoredPaths[$path] : [];
            
            $DirectoryIterator = new RecursiveIteratorIterator(
               new RecursiveDirectoryIterator($path,RecursiveDirectoryIterator::SKIP_DOTS),
               RecursiveIteratorIterator::SELF_FIRST,
               RecursiveIteratorIterator::CATCH_GET_CHILD
            );    

            foreach($DirectoryIterator as $path => $dir) 
            {               
               $path = str_ireplace(['\\'],['/'],$path);

               $this->setPathValidate($ignoredDirectories,$path,$dir);
            }
            
            return true;
        }    

        return false;
    }
        
    public function getPaths()
    {
        return self::$paths;
    }


    public function setPaths(Array $paths)
    {
        self::$paths = $paths;
    }    
    
    private function tryWithNamespace($name)
    {
        $matches  = preg_grep ('`'.preg_quote(str_ireplace(['\\'],['/'],$name)).'`i', $this->getPaths());
        
        if(!empty($matches))
        {
            foreach($matches as $path)
            {
                if(!is_file($path))
                {
                    continue;
                } 
                
                include_once($path);
            }
            
            return true;
        }
        
        return false;
    }
    
    private function tryWithoutNamespace($name)
    {
        $matches  = preg_grep ('`'.preg_quote(str_ireplace(['\\'],['/'],$name)).'`i', $this->getPaths());
        
        if(!empty($matches))
        {
            foreach($matches as $path)
            {
                if(!is_file($path))
                {
                    continue;
                } 
                
                include_once($path);
            }
            
            return true;
        }
        
        return false;
    }  
    
    
    private function tryToFindMatch($name)
    {
        foreach(self::$paths as  $path)
        { 
            $path = str_ireplace('\\','/',$path);

            if(!is_dir($path))
            {
                continue;
            }

            $file = $path.'/'.$name;

             if(file_exists($file.ServiceSplAutoload::CLASS_EXTENSION))
             {
                 include_once($file.ServiceSplAutoload::CLASS_EXTENSION);

                 break;
             }
             else if(file_exists($file.ServiceSplAutoload::INTERFACE_EXTENSION))
             {
                 include_once ($file.ServiceSplAutoload::INTERFACE_EXTENSION);

                 break;                     
             }
             else if(file_exists($file.ServiceSplAutoload::TRAIT_EXTENSION))
             {
                 include_once ($file.ServiceSplAutoload::TRAIT_EXTENSION);

                 break;                     
             }
             else if(file_exists($file.ServiceSplAutoload::MAIN_EXTENSION))
             {
                 include_once($file.ServiceSplAutoload::MAIN_EXTENSION);

                 break; 
             }
        } 
    }

    private function splAutoload($name = null)
    {
        if(!$this->tryWithNamespace($name))
        {
            $name = !empty($name) ? basename(str_ireplace('\\','/',$name)) : $name;
            
            if(!$this->tryWithoutNamespace($name))
            {
                $this->tryToFindMatch($name);
            }
        }
      
    }    
}
