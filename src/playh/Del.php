<?php
namespace Harp\playh;

use stdClass;

require_once(__DIR__.DIRECTORY_SEPARATOR.'Show.php');

class Del
{
    public static function model($args)
    {
        if(empty($args[2]))
        {
            Show::showMessage(sprintf(Show::getMessage(003),'Del::model'));
        }

        $name = preg_replace("/[^A-Za-z0-9\\/]/",'', $args[2]);

        $listArgs = [];

        if(preg_match('`/`is',$name))
        {
            $listArgs = explode('/',$name);
        }
        else if(preg_match('`\\`is',$name))
        {
            $listArgs = explode('\\',$name);
        }

        $path = sprintf
                (
                    Path::getAppPath().'%s%s%s%s%s%s%s%s%s%s%s%s',
                    DIRECTORY_SEPARATOR,
                    'app',
                    DIRECTORY_SEPARATOR,
                    trim($listArgs[0]),
                    DIRECTORY_SEPARATOR,
                    'modules',
                    DIRECTORY_SEPARATOR,
                    trim($listArgs[1]),
                    DIRECTORY_SEPARATOR,
                    'model',
                    DIRECTORY_SEPARATOR,
                    ucfirst($listArgs[2]).'Model.php'
                );

        self::deleteAll($path);

        Show::showMessage(sprintf(Show::getMessage(501),'model',$name));
    }  


    public static function controller($args)
    {
        if(empty($args[2]))
        {
            Show::showMessage(sprintf(Show::getMessage(003),'Del::controller'));
        }

        $name = preg_replace("/[^A-Za-z0-9\\/]/",'', $args[2]);

        $listArgs = [];

        if(preg_match('`/`is',$name))
        {
            $listArgs = explode('/',$name);
        }
        else if(preg_match('`\\`is',$name))
        {
            $listArgs = explode('\\',$name);
        }

        $path = sprintf
                (
                    Path::getAppPath().'%s%s%s%s%s%s%s%s%s%s%s%s',
                    DIRECTORY_SEPARATOR,
                    'app',
                    DIRECTORY_SEPARATOR,
                    trim($listArgs[0]),
                    DIRECTORY_SEPARATOR,
                    'modules',
                    DIRECTORY_SEPARATOR,
                    trim($listArgs[1]),
                    DIRECTORY_SEPARATOR,
                    'controller',
                    DIRECTORY_SEPARATOR,
                    ucfirst($listArgs[2]).'Controller.php'
                );

        self::deleteAll($path);

        Show::showMessage(sprintf(Show::getMessage(501),'controller',$name));
    }  

    public static function view($args)
    {
        if(empty($args[2]))
        {
            Show::showMessage(sprintf(Show::getMessage(003),'Del::view'));
        }

        $name = preg_replace("/[^A-Za-z0-9\\/]/",'', $args[2]);

        $listArgs = [];

        if(preg_match('`/`is',$name))
        {
            $listArgs = explode('/',$name);
        }
        else if(preg_match('`\\`is',$name))
        {
            $listArgs = explode('\\',$name);
        }

        $path = sprintf
                (
                    Path::getAppPath().'%s%s%s%s%s%s%s%s%s%s%s%s',
                    DIRECTORY_SEPARATOR,
                    'app',
                    DIRECTORY_SEPARATOR,
                    trim($listArgs[0]),
                    DIRECTORY_SEPARATOR,
                    'modules',
                    DIRECTORY_SEPARATOR,
                    trim($listArgs[1]),
                    DIRECTORY_SEPARATOR,
                    'view',
                    DIRECTORY_SEPARATOR,
                    ucfirst($listArgs[2]).'View.php'
                );

        self::deleteAll($path);

        Show::showMessage(sprintf(Show::getMessage(501),'view',$name));
    }   

    public static function layout($args)
    {
        if(empty($args[2]))
        {
            Show::showMessage(sprintf(Show::getMessage(003),'Del::layout'));
        }

        $layoutName = mb_strtolower($args[2]);

        $publicFolder = sprintf
        (
            Path::getAppPath().'%s%s%s%s%s%s%s%s',
            DIRECTORY_SEPARATOR,
            'app',
            DIRECTORY_SEPARATOR,
            'public',
            DIRECTORY_SEPARATOR,
            'layouts',
            DIRECTORY_SEPARATOR,
            $layoutName
        );

        self::deleteAll($publicFolder);

        Show::showMessage(sprintf(Show::getMessage(501),'layout',$layoutName));
    }
 
    public static function deleteAll($str) 
    {
        if (is_file($str)) 
        {    
            return unlink($str);
        }
        elseif (is_dir($str))
        {
            $scan = glob(rtrim($str, '/').'/*');
              
            foreach($scan as $index=>$path) {
                  
                self::deleteAll($path);
            }
              
            return @rmdir($str);
        }
    }

    public static function app($args)
    {
        if(empty($args[2]))
        {
            Show::showMessage(sprintf(Show::getMessage(003),'Del::app'));
        }

        $appName = trim(ucfirst($args[2]));

        $fileStart = file_get_contents(Path::getAppPath().DIRECTORY_SEPARATOR.'index.php');

        $startBase = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'StartBase.playh');

        $strApps = '';

        if(preg_match_all('`registerApp\((.*?)\)`',$fileStart,$matches2))
        {
            foreach($matches2[1] as $nameApp)
            {
                $nApp = str_ireplace(['\''],[''],$nameApp);

                if(trim($nApp) != $appName)
                {
                    $strApps .= sprintf('%s->registerApp(%s)%s',str_repeat(chr(32),7),$nameApp,PHP_EOL); 
                }
            }
        }

        $strCode  = '$Route = HarpRoute::load(false)'.PHP_EOL;    
        $strCode .= $strApps;
        $strCode .= str_repeat(chr(32),7).'->runApp();'.PHP_EOL;
        $startBase = str_ireplace(['{{apps}}'],[$strCode],$startBase);

        $pRoutes = sprintf(Path::getAppPath().'%s%s',DIRECTORY_SEPARATOR,'app');

        $routes = json_decode(file_get_contents($pRoutes.DIRECTORY_SEPARATOR.'routes.json'),true);

        unset($routes['apps'][$appName]);

        $appNameLower = mb_strtolower($appName);

        $appPath =  sprintf
        (
            Path::getAppPath().'%s%s%s%s',
            DIRECTORY_SEPARATOR,
            'app',
            DIRECTORY_SEPARATOR,
            $appNameLower
        );

        $publicFolder = sprintf
        (
            Path::getAppPath().'%s%s%s%s%s%s%s%s',
            DIRECTORY_SEPARATOR,
            'app',
            DIRECTORY_SEPARATOR,
            'public',
            DIRECTORY_SEPARATOR,
            'layouts',
            DIRECTORY_SEPARATOR,
            $appNameLower
        );

        self::deleteAll($appPath);
        self::deleteAll($publicFolder);

        file_put_contents($pRoutes.DIRECTORY_SEPARATOR.'routes.json',json_encode($routes,JSON_PRETTY_PRINT));
        file_put_contents(dirname(Path::getAppPath()).DIRECTORY_SEPARATOR.'index.php',$startBase);
     
        Show::showMessage(sprintf(Show::getMessage(501),'app',$appName));
    }

    public static function api($args)
    {
        if(empty($args[2]))
        {
            Show::showMessage(sprintf(Show::getMessage(1000),'Invalid arguments to delete api!'));
        }

        $appName = trim(ucfirst($args[2]));

        $fileStart = file_get_contents(Path::getAppPath().DIRECTORY_SEPARATOR.'index.php');

        $startBase = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'StartBase.playh');

        $strApps = '';

        if(preg_match_all('`registerApp\((.*?)\)`',$fileStart,$matches2))
        {
            foreach($matches2[1] as $nameApp)
            {
                $nApp = str_ireplace(['\''],[''],$nameApp);

                if(trim($nApp) != $appName)
                {
                    $strApps .= sprintf('%s->registerApp(%s)%s',str_repeat(chr(32),7),$nameApp,PHP_EOL); 
                }
            }
        }

        $strCode  = '$Route = HarpRoute::load(false)'.PHP_EOL;    
        $strCode .= $strApps;
        $strCode .= str_repeat(chr(32),7).'->runApp();'.PHP_EOL;
        $startBase = str_ireplace(['{{apps}}'],[$strCode],$startBase);

        $pRoutes = sprintf(Path::getAppPath().'%s%s',DIRECTORY_SEPARATOR,'app');

        $routes = json_decode(file_get_contents($pRoutes.DIRECTORY_SEPARATOR.'routes.json'),true);

        unset($routes['apps'][$appName]);

        $appNameLower = mb_strtolower($appName);

        $appPath =  sprintf
        (
            Path::getAppPath().'%s%s%s%s',
            DIRECTORY_SEPARATOR,
            'app',
            DIRECTORY_SEPARATOR,
            $appNameLower
        );

        self::deleteAll($appPath);

        file_put_contents($pRoutes.DIRECTORY_SEPARATOR.'routes.json',json_encode($routes,JSON_PRETTY_PRINT));
        file_put_contents(Path::getAppPath().DIRECTORY_SEPARATOR.'index.php',$startBase);
     
        Show::showMessage(sprintf(Show::getMessage(501),'api',$appName));
    }

}