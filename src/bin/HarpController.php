<?php
namespace Harp\bin;

use Harp\bin\HarpApplicationInterface;

abstract class HarpController
{
    private static $properties;
    protected $Application;
    protected $ServerRequest;

    protected function __construct()
    {   
        $this->Application = self::$properties['Application'];
        $this->ServerRequest = $this->getProperty(HarpHttpMessage::class)->getServerRequest();
    }
    
    protected function getProperty($key)
    {
        return $this->Application->getProperty($key);
    }

    protected function clearBuffer()
    {
        while(ob_get_length() > 0) { ob_clean(); }
    }

    protected function clearHeaders()
    {
        if(!headers_sent()){ header_remove();}
    }

    protected function redirect($url)
    {
        $this->clearHeaders();
        $this->clearBuffer();
        exit(header('location:'.$url));
    }
    
    private static function inject(HarpApplicationInterface $Application)
    {
        self::$properties['Application'] = $Application;
    }    
}
