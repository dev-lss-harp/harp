<?php
namespace etc\HarpDesignTemplate\components;

class ComponentSourcePath implements ComponentSource
{
    private $path;
    
    public function __construct($path)
    {
        $this->path = $path;
    }
    
    public function getSource()
    {
        return $this->path;
    }
    
    public function setSource($path)
    {
        $this->path = $path;
    }
}
