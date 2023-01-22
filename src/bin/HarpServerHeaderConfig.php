<?php
namespace Harp\bin;

class HarpServerHeaderConfig 
{
    private $headers;
    private static $instance = null;

    private function __construct() 
    {
        $this->headers = [
            'X-Frame-Options' => 'sameorigin',
            'Content-Security-Policy' => "script-src 'self'"
        ];
    }


    public static function getInstance()
    {
        if(!(self::$instance instanceof self))
        {
            self::$instance = new self();
        }

        return self::$instance;
    }
    
    public function add($k,$h)
    {
        if(is_string($k) && !is_null($h))
        {
            $this->headers[trim($k)] = trim($h);
        }

        return $this;
    }

    public function get()
    {
        return $this->headers;
    }

    public function del($k)
    {
        if(is_string($k) && array_key_exists($k,$this->headers))
        {
            unset($this->headers[$k]);
        }

        return $this;
    }

    public function run()
    {
        foreach($this->headers as $k => $h)
        {
            header(sprintf('%s:%s',$k,$h));
        }
    }
}
