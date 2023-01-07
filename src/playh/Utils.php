<?php 
namespace Harp\playh;
class Utils
{
    public static function parseCommand($args)
    {
        if(!isset($args[2]))
        {
            Show::showMessage(sprintf(Show::getMessage('1000'),'Invalid command!'));
        }

        $list = explode('/',$args[2]);

        return $list;
    }
    
    public static function parseExtraArguments($args)
    {
        $extraArgs = [];
        $k = count($args) - 1;    

        while($k >= 2)
        {
            if
                (
                    preg_match('`--(table)=(.*)`',$args[$k],$matches)
                    ||
                    preg_match('`--(conn)=(.*)`',$args[$k],$matches)
                    ||
                    preg_match('`--(type)=(.*)`',$args[$k],$matches)
                    ||
                    preg_match('`--(short)=(.*)`',$args[$k],$matches)
                    ||
                    preg_match('`--(app)=(.*)`',$args[$k],$matches)
                    ||
                    preg_match('`--(name)=(.*)`',$args[$k],$matches)
                )
            {
                $extraArgs[$matches[1]] = trim($matches[2]);
            }

            --$k;
        }

        return $extraArgs;
    }
}
