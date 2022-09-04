<?php
namespace Harp\lib\HarpDatabase\orm;

use Harp\lib\HarpDatabase\orm\EntityHandler;

interface MapORMInterface 
{
    public function mapByEntity(EntityHandler $Entity);
    public function load($path);
    public function getPath();
    public function getConnectionDriver();
}
