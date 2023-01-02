<?php
namespace Harp\lib\HarpMix;

use DateTime;
use Exception;
use Throwable;

class Mix
{
    public static function currentDateDiff(DateTime $date,string $fraction = "d")
    {
        $result = null;

        try 
        {
            $now = new \DateTime();

            $diff = $date->diff($now);

            switch($fraction)
            {
                case 'd':
                    $result = $diff->days;
                break;
                case 'm':
                    $result = floor($diff->days / 30);
                break;
                case 'y':
                    $result = $diff->y;
                break;
                case 'h':
                    $result = $diff->days * 24;
                break;
                default:
                    throw new Exception('You must enter one of the {d,m,y,h} parameters for {fraction}!',500);
            }

            $result *= ($now >= $date) ? -1 : 1;
        } 
        catch (\Throwable $th) 
        {
            throw $th;
        }

        return $result;
    }
}