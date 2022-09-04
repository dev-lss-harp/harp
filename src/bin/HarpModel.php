<?php
namespace Harp\bin;

abstract class HarpModel
{
    private static $properties;
    protected $Application;
    
    protected function __construct()
    {
        $this->Application = self::$properties['Application'];
    }
    
    private static function inject(\Harp\bin\HarpApplicationInterface $Application)
    {
        self::$properties['Application'] = $Application;
    }   
}
