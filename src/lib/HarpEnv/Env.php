<?php 
namespace Harp\lib\HarpEnv;

use Exception;

class Env
{
    public static function exists(string $key)
    {
        $exists = false;
        
        if(!empty($key))
        {
            $exists = \getenv($key);
        }
        
        return $exists;
    }

    public static function get(string $key)
    {
        if(!self::exists($key))
        {
            throw new Exception(sprintf('Key {%s} does not exists in enviroment variables!',$key));
        }

        return getenv($key);
    }
}