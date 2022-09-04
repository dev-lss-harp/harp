<?php
namespace Harp\lib\HarpDatabase\cmd;

use stdClass;

class CommandText
{
    private $Command;
    
    public function __construct()
    {
        $this->Command = new stdClass();
        $this->Command->text = '';
    }
    
    public function getCommand()
    {
        return $this->Command;
    }
}
