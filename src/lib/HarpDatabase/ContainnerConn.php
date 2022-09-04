<?php 
namespace Harp\lib\HarpDatabase;

use Harp\bin\ArgumentException;

class ContainnerConn implements IContainerConn
{
    private $connList;
    public function __construct()
    {
        $this->connList = [];
    }

    public function add(string $key, HarpConnection $Conn)
    {
        if(!empty($key) && isset($this->connList[$key]))
        {
            throw new ArgumentException(sprintf("key {%s} exists in the list of connections already added.",$key));
        }

        $this->connList[$key] = $Conn;

        return $this;
    }

    public function get(string $key)
    {
        if(empty($key) || !isset($this->connList[$key]))
        {
            throw new ArgumentException(sprintf("key does not {%s} exists in the list of connections already added.",$key));
        }

        return $this->connList[$key];
    }
}