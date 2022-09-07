<?php 
namespace Harp\playh;
class Path
{
    public static function getAppPath()
    {
        return dirname(dirname(dirname(dirname(dirname(__DIR__)))));
    }

    public static function getRootPath()
    {
        return dirname(dirname(dirname(dirname(dirname(dirname(__DIR__))))));
    }
}
