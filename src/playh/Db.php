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
        self::$DotEnv->loadEnv(Path::getProjectPath().'/.env','env_main');
        self::$DotEnv->loadEnv(Path::getProjectPath().'/.env-develop','env-develop');


        if(file_exists(Path::getProjectPath().'/.env-maintainer'))
        {
            self::$DotEnv->loadEnv(Path::getProjectPath().'/.env-maintainer','env-maintainer');
        }
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

    public static function entity($args,$noExit = false)
    {
        if(empty($args[2]))
        {
            Show::showMessage(sprintf(Show::getMessage(1000),'required {app}/{entityName}!'));
        }

        $arg = explode('/',$args[2]);

        $tableName = trim($arg[2]);

        if(isset($args[3]) && preg_match('`--table=(.*)`',$args[3],$matches))
        {
            if(!empty($matches[1]))
            {
                $tableName = trim($matches[1]);
            }
        }

        if(count($arg) != 3)
        {
            Show::showMessage(sprintf(Show::getMessage(1000),'required {app}/{module}/{entityName}!'));
        }

        $path = Path::getProjectPath().DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.$arg[0].DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.$arg[1];

        if(!is_dir($path))
        {
            Show::showMessage(sprintf(Show::getMessage(1000),sprintf('Not found {app}/{module} %s/%s!',$arg[0],$arg[1])));
        }
        
        if(!is_dir($path.DIRECTORY_SEPARATOR.'mapper'))
        {
            mkdir($path.DIRECTORY_SEPARATOR.'mapper',0755,true);
        }

        $model = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'ModelORMBase.playh');

        $model = str_ireplace(['{{app}}','{{module}}','{{nameModel}}','{{tableName}}'],[$arg[0],$arg[1],$arg[2],sprintf('%s%s%s',"'",$tableName,"'")],$model);

        file_put_contents($path.DIRECTORY_SEPARATOR.'mapper'.DIRECTORY_SEPARATOR.$arg[2].'.php',$model);

        Show::showMessage(sprintf(Show::getMessage(200),'Mapper Entity ',$arg[2]),$noExit);
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
            Path::getProjectPath().'%s%s%s%s%s%s%s%s',
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
       
        self::loadEnv();
        self::eloquentManager();
        self::createMigraionTableBaseControl();

        $class = sprintf('\\Harp\playh\%s','Migrate');
   
        if(!\class_exists($class))
        {
            Show::showMessage(sprintf(Show::getMessage(1000),sprintf('Migrate class not found!')));
        }

        $migrate = new Migrate();
        asort($migrate->orders);

        foreach($migrate->orders as $migr => $ord)
        {
            $mtd = sprintf('get%s',$migr);

            if(\method_exists($migrate,$mtd))
            {
            
                $ClsMtd = $migrate->{$mtd}();
                $ClsMtd->up();
            }

        }
        
        Show::showMessage(sprintf(Show::getMessage(1001),sprintf('All migrations were performed successfully!',$path)));
    }

    public static function create_migration($args,$noExit = false)
    {
        if(empty($args[2]) || empty($args[3]) || empty($args[4]))
        {
            Show::showMessage(sprintf(Show::getMessage(1000),'Missed argument {--app} or {--name} or {--table|--create}!'));
        }


        $countArgs = count($args);

        $mName = [];
        $mTable = [];
        $mApp = [];
        $mOrder = [];

        $name = null;
        $table = null;
        $create = null;
        $app = null;
        $order = null;

        for($i = 2; $i < $countArgs;++$i)
        {
            $re1 = '`--name=(.*)?`si';
            $re2 = '`--(?:create|table)=(.*)?`si';
            $re3 = '`--app=(.*)?`si';
            $re4 = '`--order=(.*)`si';

            if(empty($mName[1]) && preg_match($re1,$args[$i],$mName))
            {
                $name = trim($mName[1]);
            }
            else if(empty($mTable[1]) && preg_match($re2,$args[$i],$mTable))
            {
                if(preg_match(sprintf('`%s`','create'),$args[$i]))
                {
                    $create = trim($mTable[1]);
                }
                else 
                {
                    $table = trim($mTable[1]);
                }
               
            }  
            else if(empty($mApp[1]) && preg_match($re3,$args[$i],$mApp))
            {
                $app = trim($mApp[1]);
            }   
            else if(empty($mOrder[1]) && preg_match($re4,$args[$i],$mOrder))
            {
                $order = intval(trim($mOrder[1]));
            }              
        }

        $path = sprintf
        (
            Path::getProjectPath().'%s%s%s%s%s%s%s%s',
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

        if(!empty($create))
        {
            $baseFile = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'MigrationCreateBase.playh');
        }
        else
        {
            $baseFile = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'MigrationTableBase.playh');
        }
       
        $nameClass = preg_replace("/[^A-Za-z0-9]/",'_',!empty($name) ? $name : (!empty($table) ? $table : (!empty($create) ? $create : '')));
        $parts = explode('_',$nameClass);
        $definitiveNameClass = '';
        for($i = 0; $i < count($parts);++$i)
        {
            if(!empty($parts[$i]))
            $definitiveNameClass.= ucfirst($parts[$i]);
        }

        $nameTable = preg_replace("/[^A-Za-z0-9\\_]/",'#',!empty($table) ? $table : $create);
        $parts = explode('#',$nameTable);
        $definitiveNameTable = '';
        for($i = 0; $i < count($parts);++$i)
        {
            if(!empty($parts[$i]))
            $definitiveNameTable .= $parts[$i];
        }

        $namespace = sprintf("Harp\app\%s\storage\migrations",$app);
        $baseFile = str_ireplace(['{{namespace}}','{{class_name}}','{{table_name}}'],[$namespace,$definitiveNameClass,$definitiveNameTable],$baseFile);

        $nameFile = $definitiveNameClass.'.php';

        //Fim criação da classe que define a tabela

        //Início da classe que executa
        if(!file_exists(__DIR__.DIRECTORY_SEPARATOR.'Migrate.php'))
        {
            $base_migrate = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'MigrateBase.playh');
            //$base_migrate = str_ireplace(['{{app}}'],[$app],$base_migrate);
            file_put_contents(__DIR__.DIRECTORY_SEPARATOR.'Migrate.php',$base_migrate);
        }

        $migrate = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'Migrate.php');

        if(preg_match(sprintf('`%s`',$definitiveNameClass),$migrate))
        {
            Show::showMessage(sprintf(Show::getMessage(1000),sprintf('Migration %s exists!',$definitiveNameClass)));
        }


        //Criar nova instância da classe de migração
        $re = '/\/\/start_declare\b(.+)\/\/end_declare\b/is';
        if(!preg_match($re,$migrate,$start_declare))
        {
            Show::showMessage(sprintf(Show::getMessage(1000),'Markup {start_declare} and {end_declare} not found in file base!'));
        }

        $new_migration = sprintf('%s%s%s%s%sprivate $%s = null;%s',PHP_EOL,chr(32),chr(32),chr(32),chr(32),$definitiveNameClass,PHP_EOL); 
        $declaration = trim($start_declare[1]);
        $declaration .= $new_migration; 
      
        $migrate = str_ireplace([$start_declare[0]],['//start_declare'.PHP_EOL.$declaration.'//end_declare'],$migrate);

        //incluir use
        
        $re = '/\/\/start_use\b(.+)\/\/end_use\b/is';
        if(!preg_match($re,$migrate,$start_use))
        {
            Show::showMessage(sprintf(Show::getMessage(1000),'Markup {start_use} and {end_use} not found in file base!'));
        }

        $namespaces = trim($start_use[1]);
        $namespaces .= sprintf('%s%s%s%s%suse%s%s\%s;%s',PHP_EOL,chr(32),chr(32),chr(32),chr(32),chr(32),$namespace,$definitiveNameClass,PHP_EOL);
        
        $migrate = str_ireplace([$start_use[0]],['//start_use'.PHP_EOL.$namespaces.'//end_use'],$migrate);

        //incluir requires

        $re = '/\/\/start_require\b(.+)\/\/end_require\b/is';
        if(!preg_match($re,$migrate,$start_require))
        {
            Show::showMessage(sprintf(Show::getMessage(1000),'Markup {start_require} and {end_require} not found in file base!'));
        }

        $requires = trim($start_require[1]);
        $requires .= sprintf
            (   
                "%srequire_once('%s%s%s');%s",
                PHP_EOL,
                $path,
                DIRECTORY_SEPARATOR,
                $nameFile,
                PHP_EOL
            );

        $migrate = str_ireplace([$start_require[0]],['//start_require'.PHP_EOL.$requires.'//end_require'],$migrate);
    

        //incluir métodos
        $re = '/\/\/start_methods\b(.+)\/\/end_methods\b/is';
        if(!preg_match($re,$migrate,$start_methods))
        {
            Show::showMessage(sprintf(Show::getMessage(1000),'Markup {start_methods} and {end_methods} not found in file base!'));
        }

        $methods  = trim($start_methods[1]);
        $methods .= sprintf
        (
            '%s%s%s%s%spublic function get%s(){%s%s%s%s%s%s%s%s $this->%s = new %s();%s%s%s%s%s%s%s return $this->%s;%s%s%s%s%s}%s%s',
            PHP_EOL,
            PHP_EOL,
            chr(32),
            chr(32),
            chr(32),
            $definitiveNameClass,
            PHP_EOL,
            PHP_EOL,
            chr(32),
            chr(32),
            chr(32),
            chr(32),
            chr(32),
            chr(32),
            $definitiveNameClass,
            $definitiveNameClass,
            PHP_EOL,
            
            chr(32),
            chr(32),
            chr(32),
            chr(32),
            chr(32),
            chr(32),

            $definitiveNameClass,
            PHP_EOL,
            PHP_EOL,
            chr(32),
            chr(32),
            chr(32),
            PHP_EOL,
            PHP_EOL
        );

        $migrate = str_ireplace([$start_methods[0],'{{status}}'],['//start_methods'.PHP_EOL.$methods.'//end_methods',1],$migrate);

        //incluir orders
        $re = '/\/\/start_order\b(.+)\/\/end_order\b/is';
        if(!preg_match($re,$migrate,$start_orders))
        {
            Show::showMessage(sprintf(Show::getMessage(1000),'Markup {start_order} and {end_order} not found in file base!'));
        }

        $orders =  trim($start_orders[1]);

        $re = '/\$orders.+\[(.*?)\]/si';
        preg_match($re,$orders,$orderArray);


        $ord = [];
        if(!empty($orderArray))
        {
            
            $itens = explode(',',$orderArray[1]);
            

            foreach($itens as $item)
            {

                if(preg_match('`=>.*([0-9])`',$item,$valOrder))
                {
                    $mg = trim(preg_replace(['`=>.*`'],'',$item));
                    $mg = str_ireplace(["'"],[""],$mg);
                    $ord[$mg] = intval($valOrder[1]);
                }
            
            }

            $order = !empty($order) && is_int($order) ? $order : count($ord);
            
            if(in_array($order,$ord))
            {
                Show::showMessage(sprintf(Show::getMessage(1000),sprintf('order {%s} already exists in the list!',$order)));
            }
        }

        $ord[$definitiveNameClass] = $order;

        $attrOrder =  'public $orders = ['.PHP_EOL;
        foreach($ord as $key => $v)
        {
            $attrOrder .= sprintf('%s%s%s%s%s%s%s%s%s',chr(32),chr(32),"'",$key,"'",'=>',$v,',',PHP_EOL);
        }
        $attrOrder .= '];'; 

        $migrate = str_ireplace([$start_orders[0]],['//start_order'.PHP_EOL.$attrOrder.'//end_order',1],$migrate);
 
        $methods  = trim($start_methods[1]);

        self::loadEnv();
        self::eloquentManager();
        self::createMigraionTableBaseControl();

        if(CapsuleManager::schema()->hasTable($nameTable))
        {
            Show::showMessage(sprintf(Show::getMessage(1000),sprintf('Table {%s} exists in database!',$nameTable)));
        }

        $max = CapsuleManager::table('migrations')->select([CapsuleManager::raw('MAX(batch) AS batch')])->get()->first();

        $batch = 1;
        if(!empty($max->batch))
        {
            $batch += $max->batch;
        }

        //consultar o batch na base
        $baseFile = str_ireplace(['{{batch}}'],[$batch],$baseFile);
        
        if(file_put_contents($path.DIRECTORY_SEPARATOR.$nameFile,$baseFile) && file_put_contents(__DIR__.DIRECTORY_SEPARATOR.'Migrate.php',$migrate))
        {
            Show::showMessage(sprintf(Show::getMessage(200),'migrate',$nameFile));
        }
    }
}