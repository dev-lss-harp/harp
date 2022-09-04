<?php
namespace Harp\lib\HarpTemplate;

use Exception;
use Harp\bin\View;
use Harp\bin\Enum;
use Harp\bin\ArgumentException;

class HarpTemplate
{

        const __SERVER_VARIABLES = '__SERVER_VARIABELS';
        const __APP_PROPERTIES = '__APP_PROPERTIES';

        private $properties;
        private $permittedExtensions = [
                'html',
                'php'
        ];
        private $View;
        private $Replacer;
        private $Repeater;
        private $listTemplates = [];
        
        public function __construct(View $ViewObject)
        {
            $this->View = $ViewObject;
            
            $this->properties = new \stdClass();

            
            $this->Replacer = new HarpReplacer($this);
            $this->Repeater = new HarpRepeater($this);
        }
        
        public function sanitizeFiles($keyFile)
        {            
            $this->properties->{$keyFile} = str_ireplace(["\r\n",PHP_EOL,"\r","\n"],[chr(32),chr(32),chr(32),chr(32)],$this->properties->{$keyFile});
        }

        public function verifyFileFound($path,$fileName)
        {
            return file_exists($path.'/'.$fileName);
        }

        public function getTemplateID($k)
        {
            return (is_int($k) && array_key_exists($k,$this->listTemplates)) ? $this->listTemplates[$k] : null;
        }

        private function definePath($directoryORFullPath,$fileName)
        {

            $directory = !is_null($directoryORFullPath) && !is_dir($directoryORFullPath) ?  DIRECTORY_SEPARATOR.$directoryORFullPath : '';
            $fullPath = !is_null($directoryORFullPath) && is_dir($directoryORFullPath) ? $directoryORFullPath : '';

            $paths = [
                PATH_VIEW_APP.$directory,
                PATH_PUBLIC_LAYOUTS_APP.$directory,
                PATH_PUBLIC_LAYOUTS.$directory,
                PATH_PUBLIC_TEMPLATES_APP.$directory,
                PATH_PUBLIC_TEMPLATES.$directory,
                PATH_PUBLIC.$directory
            ];

            $path = '';

            if((is_null($directoryORFullPath) || !empty($directory)) && empty($fullPath))
            {

                foreach($paths as $p)
                {
                    if(!$this->verifyFileFound($p,$fileName))
                    {
                        continue;
                    }

                    $path = $p;
                }

            }
            else if(is_dir($fullPath))
            {
                $path =  $fullPath;
            }

            if(empty($path) || !is_dir($path))
            {
                throw new \Exception(
                    sprintf('Could not determine path to file {%s}, path does not exist or is null. valid paths are: {%s}',$fileName,implode(' OR ',$paths))
                );
            }
            else if(!file_exists($path.'/'.$fileName))
            {
                throw new \Exception('file {'.$fileName.'} not found in path: {'.$path.'}');
            }  
           
            return $path;
        }

        public function verifyExtension($fileName)
        {
            $extP = explode('.',$fileName);
            $ext = isset($extP[1]) ? $extP[1] : '';
            return in_array($ext,$this->permittedExtensions);
        }

        public function loadByFullPath($path)
        {
            $key = 0;
            
         
            try
            {

                $fileKey = md5($path);

                $this->properties->{$fileKey} = file_get_contents($path);
                
                $this->sanitizeFiles($fileKey);
    
                $this->getReplacer()->parseFile($fileKey);

                array_push($this->listTemplates,$fileKey);

                $key = count($this->listTemplates) - 1;
               
            }
            catch(\Throwable $th)
            {
                throw $th; 
            }
            
            return $key;
        }

        public function load($fileName,$directoryORFullPath = null) : int
        {
            $key = 0;
         
            try
            {
  
                $fileKey = md5($fileName);
                $key = count($this->listTemplates);
                $this->listTemplates[$key] = $fileKey;
             
                $path = $this->definePath($directoryORFullPath,$fileName);
            
                $this->properties->{$fileKey} = file_get_contents($path.'/'.$fileName);

                $this->sanitizeFiles($fileKey);
    
                $this->getReplacer()->parseFile($fileKey);
            }
            catch(\Throwable $th)
            {
                throw $th; 
            }

            return $key;
        }

        public function setPermittedExtensions(Array $exts = [])
        {
            foreach($exts as $ext)
            {
                if(!in_array($ext,$this->permittedExtensions) && is_string($ext))
                {
                    array_push($this->permittedExtensions,$ext);
                }
            }
        }

        public function getPermittedExtensions()
        {
            return $this->permittedExtensions;
        }
        
        public function setProperty($key,&$value)
        {
 
             if(!empty($key))
             {
                 $this->properties->{$key} = &$value;
             }   

             return $this;
        }

        public function getProperty($key)
        {
            $prop = null;

            $props = (Array)$this->properties;

            if(isset($props[$key]))
            {
                $prop = $this->properties->{$key};
            }

            return $prop;
        }        
        
        public function getView()
        {
            return $this->View;
        }
        
        public function getReplacer()
        {
            return $this->Replacer;
        }

        public function getHandler($name)
        {
            $name = ucfirst($name);
            $namespace = '\\Harp\lib\HarpTemplate\Handler';
            $nameClass = 'Handler'.$name;
            $class = $namespace.'\\'.$nameClass;
            
            if(!class_exists($class))
            {
                throw new ArgumentException(
                    'Handler {'.$name.'} does not exists!', 
                    404
                );
            }

            $obj = new $class($this);
           
            return $obj;
        }
        
        public function getRepeater()
        {
            return $this->Repeater;
        }        
        
        public function getProperties()
        {
            return $this->properties;
        }

        public function contentType($type)
        {
            header('Content-Type:'.$type);
        }
                
        public function show($key)
        {
            $fKey = $this->getTemplateID($key);

            $file = $this->getProperty($fKey);

            print($file);
        }
}
