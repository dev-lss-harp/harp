<?php
namespace Harp\lib\HarpTemplate;

use Exception;
use Harp\bin\View;

class HarpTemplate
{

        private $properties;
        private $viewResources = 
        [
                'html',
                'php'
        ];
        private $View;
        private $Replacer;
        private $Repeater;
        private $listTemplates = [];
        private $paths = [];
        private $pathLoadedFiles = [];
        private $fileLoadedNames = [];
        private $firstInterpolationSymbol = '{{';
        private $lastInterpolationSymbol = '}}';     
        
        public function __construct(View $ViewObject)
        {
            $this->View = $ViewObject;
            
            $this->properties = new \stdClass();

            $this->Replacer = new HarpReplacer($this);
            $this->Repeater = new HarpRepeater($this);
        }

        public function getFirstInterpolationSymbol()
        {
            return $this->firstInterpolationSymbol;
        }

        public function getLastInterpolationSymbol()
        {
            return $this->lastInterpolationSymbol;
        }

        public function setInterpolationSymbols($firstSymbol,$lastSymbol)
        {
            if(mb_strlen($firstSymbol) > 2 || mb_strlen($lastSymbol) > 2)
            {
                throw new Exception('First and last symbol interpolation must contain a maximum of 2 characters!');
            }
            
            $this->firstInterpolationSymbol = $firstSymbol ?? $this->firstInterpolationSymbol;
            $this->lastInterpolationSymbol = $lastSymbol ?? $this->lastInterpolationSymbol;
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
            $fullPath = !is_null($directoryORFullPath) && is_dir($directoryORFullPath) ? $directoryORFullPath : null;

            $this->paths = [
                PATH_PUBLIC_LAYOUTS_MODULE,
                PATH_PUBLIC_TEMPLATES_MODULE,
                PATH_PUBLIC_LAYOUTS_GROUP,
                PATH_PUBLIC_TEMPLATES_GROUP,
                PATH_PUBLIC_LAYOUTS_APP,
                PATH_PUBLIC_LAYOUTS,
                PATH_PUBLIC_TEMPLATES_APP,
                PATH_PUBLIC_TEMPLATES,
                PATH_PUBLIC,
            ];

            $path = $fullPath ?? null;

            if((is_null($directoryORFullPath) || !empty($directory)) && empty($fullPath))
            {

                foreach($this->paths as $p)
                {
                    $pth = sprintf('%s%s',$p,$directory);
                    if(!$this->verifyFileFound($pth,$fileName))
                    {
                        continue;
                    }

                    $path = $pth;
                }

            }

            if(empty($path) || !is_dir($path))
            {
                throw new \Exception(
                    sprintf('Could not determine path to file {%s}, path does not exist or is null. valid paths are: {%s}',$fileName,implode(' OR ',$this->paths))
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
            return in_array($ext,$this->viewResources);
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
                $this->pathLoadedFiles[$key] = $path;
                $this->properties->{$fileKey} = file_get_contents($path.'/'.$fileName);
                $this->fileLoadedNames[$key] = $fileName;

                $this->sanitizeFiles($fileKey);
    
                $this->getReplacer()->parseFile($fileKey);
            }
            catch(\Throwable $th)
            {
                throw $th; 
            }

            return $key;
        }

        public function setViewResources(Array $exts = [])
        {
            foreach($exts as $ext)
            {
                if(!in_array($ext,$this->viewResources) && is_string($ext))
                {
                    array_push($this->viewResources,$ext);
                }
            }
        }

        public function getViewResources()
        {
            return $this->viewResources;
        }

        public function getPaths()
        {
            return $this->paths;
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
                throw new Exception(
                    'Handler {'.$name.'} does not exists!', 
                     500
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

        public function compile($key,$directory)
        {
            $fKey = $this->getTemplateID($key);

            $file = $this->getProperty($fKey);

            $path = sprintf('%s/%s',$this->pathLoadedFiles[$key],$directory);

            if(!is_dir($path))
            {
                mkdir($path);
            }

            file_put_contents(sprintf('%s/%s',$path,$this->fileLoadedNames[$key]),$file);
        }
}