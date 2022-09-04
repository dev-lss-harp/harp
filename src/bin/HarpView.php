<?php
namespace Harp\bin;

/**
 * Description of HarpView
 *
 * @author t-lsilva
 */
use Harp\bin\HarpApplicationInterface;
use Harp\bin\HarpServer;
use Harp\lib\HarpTemplate\HarpTemplate;

abstract class HarpView
{
    const __SERVER_VARIABLES = '__SERVER_VARIABELS';
    const __APP_PROPERTIES = '__APP_PROPERTIES';

    private $properties;
    private $Application;
    private $ServerConfig;
    
    protected function __construct($viewName)
    {
        if(empty($viewName))
        {
            throw new \Exception('View is empty or null!');
        }
        $this->properties = new \stdClass();
        $this->properties->viewName = $viewName;
        
    }
    
    private function renderView(HarpApplicationInterface $Application, HarpServer $ServerConfig)
    {
        $this->Application = $Application;
        $this->ServerConfig = $ServerConfig;
        $this->setProperty(self::__SERVER_VARIABLES,$this->ServerConfig->getAll());
        $this->setProperty(self::__APP_PROPERTIES,$Application->getProperties());
    
        $routeCurrent = $this->Application->getProperty('routeCurrent');

        $viewGroup = $routeCurrent['group'];

        if(!is_string($viewGroup) || empty($viewGroup))
        {
            throw new \Exception('Group View is not defined!');
        }

        $nameSpace = $this->Application->getAppNamespace();

        $this->setProperty('viewGroup',mb_strtolower($viewGroup));

        $viewGroup = $nameSpace
                    .'\\modules'
                    .'\\'.$routeCurrent['module']
                    .'\\view'
                    .'\\'.$viewGroup.'View';
                 
        $ViewObj = new $viewGroup($this);

        $viewName = $this->properties->viewName;

        if(is_callable([$ViewObj,$viewName]))
        {
            $ViewObj->$viewName();
        }
    }

    public function setProperty($key,$value)
    {
         if(!empty($key))
         {
             $this->properties->{$key} = $value;
         }   
         
         return $this;
    }
    
    public function getProperty($key)
    {
        $prop = null;

        $props = (Array)$this->properties;

        if(isset($props[$key]))
        {
            $prop = $props[$key];
        }

        return $prop;
    }    
}
