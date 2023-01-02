<?php
namespace Harp\lib\HarpValidator;

use DateTime;
use Exception;
use Throwable;

class Mix
{
    public static function pastTime(DateTime $date,string $fraction = "d")
    {
        $result = null;

        try 
        {
            $now = new \DateTime();

            $result = $date->diff($now)->{$fraction};

        } 
        catch (\Throwable $th) 
        {
            throw $th;
        }

        return $result;
    }
}