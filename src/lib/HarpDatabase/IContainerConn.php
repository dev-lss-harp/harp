<?php 
namespace Harp\lib\HarpDatabase;

interface IContainerConn
{
    public function add(string $key, HarpConnection $Conn);
    public function get(string $key);
}


