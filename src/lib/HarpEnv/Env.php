<?php 
namespace Harp\lib\HarpEnv;

use Exception;

class Env
{
    public static function exists(string $key)
    {
        $exists = false;
        
        if(array_key_exists($key,$_ENV))
        {
            $exists = true;
        }
        
        return $exists;
    }

    public static function get(string $key)
    {
        if(!self::exists($key))
        {
            throw new Exception(sprintf('Key {%s} does not exists in enviroment variables!',$key));
        }

        return $_ENV[$key];
    }
}