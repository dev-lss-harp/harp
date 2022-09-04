<?php
namespace Harp\playh;

use Symfony\Component\Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as CapsuleManager;


require_once(__DIR__.DIRECTORY_SEPARATOR.'Show.php');

class Db
{
    private static $DotEnv;
    public static function loadEnv()
    {
        self::$DotEnv = new Dotenv();
        self::$DotEnv->load(dirname(dirname(__DIR__)).'/.env', dirname(dirname(__DIR__)).'/.env-develop');
    }


    public static function eloquentManager()
    {
        $db_config = [
            'driver' => $_ENV['DB_CONNECTION'],
            'host' => $_ENV['DB_HOST_CMD'],
            'port' => $_ENV['DB_PORT'],
            'database' => $_ENV['DB_DATABASE'],
            'username' => $_ENV['DB_USERNAME'],
            'password' => $_ENV['DB_PASSWORD'],
        ];

        $Manager = new CapsuleManager();
        $Manager->addConnection($db_config);
        $Manager->setAsGlobal();
        $Manager->bootEloquent();
    }

    private static function createMigraionTableBaseControl()
    {
        if(!CapsuleManager::schema()->hasTable('migrations'))
        {
            $TBMigration = new MigrationTableBaseControl();
            $TBMigration->up();
        }
    }

    public static function migrate($args,$noExit = false)
    {
        if(empty($args[1]))
        {
            Show::showMessage(sprintf(Show::getMessage(1000),'Missed argument {--app}!'));
        }

        $countArgs = count($args);

        $mApp = [];
        $app = null;

        for($i = 1; $i < $countArgs;++$i)
        {
            $re3 = '`--app=(.*)?`si';

            if(empty($mApp[1]) && preg_match($re3,$args[$i],$mApp))
            {
                $app = trim($mApp[1]);
            }              
        }

        if(empty($app))
        {
            Show::showMessage(sprintf(Show::getMessage(1000),'Missed argument {--app}!'));
        }

        $path = sprintf
        (
            dirname(__DIR__).'%s%s%s%s%s%s%s%s',
            DIRECTORY_SEPARATOR,
            'app',
            DIRECTORY_SEPARATOR,
            $app,
            DIRECTORY_SEPARATOR,
            'storage',
            DIRECTORY_SEPARATOR,
            'migrations'
        );

        if(!is_dir($path))
        {
            Show::showMessage(sprintf(Show::getMessage(1000),sprintf('folder {migrations} not found in %s!',$path)));
        }
        else if(!file_exists($path.DIRECTORY_SEPARATOR.'Migrate.php'))
        {
            Show::showMessage(sprintf(Show::getMessage(1000),sprintf('Migrate class not found in %s!',$path)));
        }

        self::loadEnv();
        self::eloquentManager();
        self::createMigraionTableBaseControl();

        $class = sprintf('\\Harp\app\%s\storage\migrations\\%s',$app,'Migrate');

        if(!\class_exists($class))
        {
            Show::showMessage(sprintf(Show::getMessage(1000),sprintf('Migrate class not found in %s!',$path)));
        }

        $migrate = new $class();
        $migrate->createUP();
        Show::showMessage(sprintf(Show::getMessage(1001),sprintf('All migrations were performed successfully!',$path)));
    }

    public static function create_migration($args,$noExit = false)
    {
        if(empty($args[2]) || empty($args[3]) || empty($args[4]))
        {
            Show::showMessage(sprintf(Show::getMessage(1000),'Missed argument {--app} or {--name} or {--table}!'));
        }

        $countArgs = count($args);

        $mName = [];
        $mTable = [];
        $mApp = [];

        $name = null;
        $table = null;
        $app = null;

        for($i = 2; $i < $countArgs;++$i)
        {
            $re1 = '`--name=(.*)?`si';
            $re2 = '`--table=(.*)?`si';
            $re3 = '`--app=(.*)?`si';

            if(empty($mName[1]) && preg_match($re1,$args[$i],$mName))
            {
                $name = trim($mName[1]);
            }
            else if(empty($mTable[1]) && preg_match($re2,$args[$i],$mTable))
            {
                $table = trim($mTable[1]);
            }   
            else if(empty($mApp[1]) && preg_match($re3,$args[$i],$mApp))
            {
                $app = trim($mApp[1]);
            }              
        }

        $path = sprintf
        (
            dirname(__DIR__).'%s%s%s%s%s%s%s%s',
            DIRECTORY_SEPARATOR,
            'app',
            DIRECTORY_SEPARATOR,
            $app,
            DIRECTORY_SEPARATOR,
            'storage',
            DIRECTORY_SEPARATOR,
            'migrations'
        );

        if(!is_dir($path))
        {
            Show::showMessage(sprintf(Show::getMessage(1000),sprintf('folder {migrations} not found in %s!',$path)));
        }

        $baseFile = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'MigrationCreateBase.playh');
        $nameClass = preg_replace("/[^A-Za-z0-9]/",'#',$name);
        $parts = explode('#',$nameClass);
        $definitiveNameClass = '';
        for($i = 0; $i < count($parts);++$i)
        {
            if(!empty($parts[$i]))
            $definitiveNameClass.= ucfirst($parts[$i]);
        }

        $nameTable = preg_replace("/[^A-Za-z0-9\\_]/",'#',$table);
        $parts = explode('#',$nameTable);
        $definitiveNameTable = '';
        for($i = 0; $i < count($parts);++$i)
        {
            if(!empty($parts[$i]))
            $definitiveNameTable .= $parts[$i];
        }

        $baseFile = str_ireplace(['{{app}}','{{class_name}}','{{table_name}}'],[$app,$definitiveNameClass,$definitiveNameTable],$baseFile);
        
        $nameFile = $definitiveNameClass.'.php';

        $base_migrate = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'MigrateBase.playh');

        if(!file_exists($path.DIRECTORY_SEPARATOR.'Migrate.php'))
        {
            $base_migrate = str_ireplace(['{{app}}'],[$app],$base_migrate);
            file_put_contents($path.DIRECTORY_SEPARATOR.'Migrate.php',$base_migrate);
        }
        else
        {
            $base_migrate = file_get_contents($path.DIRECTORY_SEPARATOR.'Migrate.php');
        }
        
        $re = '`\/\/\@sCreate\b(.*)\/\/\@eCreate\b`is';
        if(!preg_match($re,$base_migrate,$mObjects))
        {
            Show::showMessage(sprintf(Show::getMessage(1000),'Error while parse objects migrate!'));
        }

        $objs = '';

        if(!empty($mObjects[1]))
        {
            $objs = trim($mObjects[1]);
        }

        $p = array_values(explode(';',$objs));

        $chr32 = chr(32);

        $objsRegistered = [];
        $nextBatch = 0;
        $existsBatch = false;
        self::loadEnv();
        self::eloquentManager();
        self::createMigraionTableBaseControl();
        for($i = 0; $i < count($p);++$i)
        {
            $p[$i] = trim($p[$i]);
            if(empty($p[$i]))
            {
                unset($p[$i]);
                continue;
            }
         
            if(!preg_match('`\$this\-\>([A-Za-z0-9]*)[^\=\ ]*`is',$p[$i],$result) || empty($result[1]))
            {
                Show::showMessage(sprintf(Show::getMessage(1000),'Error while parse objects migrate!'));
            }

            $class = sprintf('\\Harp\app\%s\storage\migrations\\%s',$app,trim($result[1]));

            if(!\class_exists($class))
            {
                unset($p[$i]);
                continue;
            }

            $vBatch = constant($class.'::BATCH');
            $vTable = constant($class.'::TABLE_NAME');

            $nextBatch = $vBatch > $nextBatch ? $vBatch : $nextBatch;

            if(CapsuleManager::schema()->hasTable($vTable))
            {
                $existsBatch = true;
            }

            $p[$i] = sprintf('%s%s',str_repeat($chr32,10),$p[$i]);
            $objsRegistered[] = '$this->'.trim($result[1]);
        }
        //dump(++$nextBatch);exit;
        $classExists = \class_exists(sprintf('\\Harp\app\%s\storage\migrations\\%s',$app,$definitiveNameClass));
                
        if($classExists)
        {
            Show::showMessage(sprintf(Show::getMessage(1000),sprintf('Migrate {%s} já existe!',$definitiveNameClass)));
        }

        if($existsBatch)
        {
            $nextBatch = CapsuleManager::table('migrations')->max('batch') + 1;
        }

        $baseFile = str_ireplace(['{{batch}}'],[$nextBatch],$baseFile);
        $currentObj = sprintf('%s$this->%s = new %s();%s',str_repeat($chr32,10),$definitiveNameClass,$definitiveNameClass,PHP_EOL);
        array_push($p,$currentObj);
        $objsRegistered[] = sprintf('%s%s','$this->',$definitiveNameClass);
        $p = array_values($p);

        $objs = implode(';'.PHP_EOL,$p);

        $strMethods = '';
        $strInstances = '';

        foreach($objsRegistered as $strVar)
        {
            $strMethods .= sprintf('%s%s%s%s%s%s',str_repeat($chr32,9),$strVar,'->','up()',';',PHP_EOL);
            $strInstances .= sprintf('%s%s%s%s%s%s',str_repeat($chr32,4),'public',$chr32,str_ireplace(['this->'],[''],$strVar),';',PHP_EOL);
        }

        $re = '`\/\/\@sCreateUP(.*)\/\/\@eCreateUP`is';
    
        $mMethods = [];
        if(!preg_match($re,$base_migrate,$mMethods))
        {
            Show::showMessage(sprintf(Show::getMessage(1000),'Error while parse methods migrate!'));
        }


        $re = '`\/\/\@sInstances(.*)\/\/\@eInstances`is';
    
        $mInstances = [];
        if(!preg_match($re,$base_migrate,$mInstances))
        {
            Show::showMessage(sprintf(Show::getMessage(1000),'Error while parse instances migrate!'));
        }

        $base_migrate = str_ireplace
        (
                [
                    $mObjects[0],
                    '{{status}}',
                    $mMethods[0],
                    $mInstances[0]
                ],
                [
                    sprintf('%s//@sCreate%s%s%s//@eCreate',str_repeat($chr32,1),PHP_EOL,$objs,str_repeat($chr32,9)),
                    (count($p) > 0 ? 'true' : 'false'),
                    sprintf('%s//@sCreateUP%s%s%s//@eCreateUP',str_repeat($chr32,1),PHP_EOL,$strMethods,str_repeat($chr32,9)),
                    sprintf('%s//@sInstances%s%s%s//@eInstances',str_repeat($chr32,1),PHP_EOL,$strInstances,str_repeat($chr32,7))
                ],
                $base_migrate
            );

       // $base_migrate = str_ireplace([$mMethods[0],'{{count}}'],[sprintf('%s//@sMethod%s%s//@eMethod',],$base_migrate);

        if(file_put_contents($path.DIRECTORY_SEPARATOR.$nameFile,$baseFile) && file_put_contents($path.DIRECTORY_SEPARATOR.'Migrate.php',$base_migrate))
        {
            Show::showMessage(sprintf(Show::getMessage(200),'migrate',$nameFile));
        }
    }
}