<?php
namespace etc\HarpDesignTemplate\components;

class ComponentSourceString implements ComponentSource
{
    private $string;
    
    public function __construct($string)
    {
        $this->string = $string;
    }
    
    public function getSource()
    {
        return $this->string;
    }
    
    public function setSource($string)
    {
        $this->string = $string;
    }
}
