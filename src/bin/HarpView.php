<?php
namespace Harp\bin;

/**
 * Description of HarpView
 *
 * @author t-lsilva
 */
use Harp\bin\HarpApplicationInterface;
use Harp\bin\HarpServer;
use Harp\enum\RouteEnum;
use Harp\enum\ViewEnum;

abstract class HarpView
{
    private $properties;
    private $Application;
    private $ServerConfig;
    private $viewPaths = [];
    
    protected function __construct($viewName)
    {
        if(empty($viewName))
        {
            throw new \Exception('View is empty or null!',500);
        }
        
        $this->properties = new \stdClass();
        $this->viewPaths[ViewEnum::Action->value] = $viewName;
    }
    
    private function renderView(HarpApplicationInterface $Application, HarpServer $ServerConfig)
    {
        $this->Application = $Application;
        $this->ServerConfig = $ServerConfig;
        $routeCurrent = $this->Application->getProperty(RouteEnum::class);

        $this->viewPaths[ViewEnum::Group->value] = mb_strtolower($routeCurrent[RouteEnum::Group->value]);

        if(!array_key_exists(RouteEnum::Group->value,$routeCurrent) || empty($routeCurrent[RouteEnum::Group->value]))
        {
            throw new \Exception('Group View is not defined!',500);
        }

        $viewGroup = sprintf
        (
            '%s%s%s%s%s%s%s%s',
            $this->Application->getAppNamespace(),
            '\\modules',
            '\\',
            $routeCurrent[RouteEnum::Module->value], 
            '\\view',
            '\\',
            $routeCurrent[RouteEnum::Group->value],
            'View',

        );
        
        $this->setProperty(ViewEnum::ServerVar->value,$this->ServerConfig->getAll());
        $this->setProperty(ViewEnum::Resources->value,$this->Application->getProperty(ViewEnum::Resources->value));
        $this->setProperty(ViewEnum::RouteCurrent->value,$this->viewPaths);

        $ViewObj = new $viewGroup($this);
        $viewName = $this->viewPaths[ViewEnum::Action->value];

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
