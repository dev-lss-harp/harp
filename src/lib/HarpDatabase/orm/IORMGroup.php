<?php
namespace Harp\lib\HarpDatabase\orm;

interface IORMGroup
{
    public function startGroup();
    public function endGroup();
}
