<?php
namespace Harp\playh;

use stdClass;

require_once(__DIR__.DIRECTORY_SEPARATOR.'Show.php');

class Build
{ 
    private static function createMethod($name,$code = '',Array $params = [])
    {
        $nm = $name;
        $ch32 = str_repeat(chr(32),4);
            
            $method  = 'public function '.$nm.'('.(!empty($params) ? implode(',',$params) : '').')'.PHP_EOL;
            $method .= $ch32.'{'.PHP_EOL;
                $method .= $ch32.$ch32.'try'.PHP_EOL;   
                $method .= $ch32.$ch32.'{'.PHP_EOL; 
                $method .= $ch32.$ch32.$ch32.$ch32.$code.PHP_EOL;   
                $method .= $ch32.$ch32.'}'.PHP_EOL;   
                $method .= $ch32.$ch32.'catch(\Throwable $th)'.PHP_EOL;   
                $method .= $ch32.$ch32.'{'.PHP_EOL; 
                $method .= $ch32.$ch32.$ch32.$ch32.'throw $th;'.PHP_EOL;     
                $method .= $ch32.$ch32.'}'.PHP_EOL;     
            $method .= $ch32.'}'.PHP_EOL;
      
        return $method;        
    }

    public static function checkPortInUse($port) 
    {
        $conn = @stream_socket_client("tcp://127.0.0.1:{$port}", $errno, $errstr);

        return is_resource($conn) ? fclose($conn) : false;
    } 

    public static function server($args)
    {
        $port = 8088;

        while(self::checkPortInUse($port))
        {
            $port += 1;
        }

        if(!empty($args[2]))
        {
            $port = preg_replace('`\D`','',$args[2]);
        }

        $port = is_numeric($port) ? $port : '8088';

        Show::showMessage(sprintf(Show::getMessage(004),'004',sprintf('localhost:%s',$port)),true);

        shell_exec(sprintf('php -S localhost:%s %s',$port,'index.php'));
    }

    public static function key($args,$noExit = false)
    {

        //dump($args);exit;
        if(empty($args[2]))
        {
            Show::showMessage(Show::getMessage(1001),'parameter --app not found!');
        }

        $re = '`--app=(.*)?`si';

        preg_match($re,$args[2],$matchesArg);  

        if(empty($matchesArg[1]))
        {
            Show::showMessage(Show::getMessage(1001),'parameter --app not found or empty!');
        }   
       
        $args[3] = !empty($args[3]) ? $args[3] : '';

        $re = '`--force=(.*)?`si';

        preg_match($re,$args[3],$matchesArg2);  

        $force = false;

        if(!empty($matchesArg2[1]))
        {
            $force = mb_strtolower(trim($matchesArg2[1])) == 'true' ? true : false;
        }

        $app = trim($matchesArg[1]);
        //Create keys folder if not exists
        self::storage([
            'play-h',
            'build::storage',
                '--app='.sprintf("%s",$app),
                '--subfolder='.sprintf("%s",'keys')
        ],true);

        $path =  sprintf
        (
            Path::getProjectPath().'%s%s%s%s%s%s%s%s',
            DIRECTORY_SEPARATOR,
            'app',
            DIRECTORY_SEPARATOR,
            $app,
            DIRECTORY_SEPARATOR,
            'storage',
            DIRECTORY_SEPARATOR,
            'keys'
        );

        if($force || !file_exists($path.DIRECTORY_SEPARATOR."encryption.key"))
        {
            $key = base64_encode(random_bytes(32));

            file_put_contents($path.DIRECTORY_SEPARATOR."encryption.key",$key);

            chmod($path.DIRECTORY_SEPARATOR."encryption.key",0644);
            
            $msg = sprintf(Show::getMessage(1001),'generated key: {'.$key.'}');
    
            Show::showMessage($msg,$noExit);
        }
        else
        {
                
            $msg = sprintf(Show::getMessage(1001),'key file exists to force regenerated use parameter --force=true!');
    
            Show::showMessage($msg,$noExit);
        }
    }

    public static function cert($args,$noExit = false)
    {
        if(empty($args[2]))
        {
            Show::showMessage(Show::getMessage(1001),'parameter --app not found!');
        }

        $re = '`--app=(.*)?`si';

        preg_match($re,$args[2],$matchesArg);  

        if(empty($matchesArg[1]))
        {
            Show::showMessage(Show::getMessage(1001),'parameter --app not found or empty!');
        }  
        
        $args[3] = !empty($args[3]) ? $args[3] : '';

        $re = '`--force=(.*)?`si';

        preg_match($re,$args[3],$matchesArg2);  

        $force = false;

        if(!empty($matchesArg2[1]))
        {
            $force = mb_strtolower(trim($matchesArg2[1])) == 'true' ? true : false;
        }

        $app = trim($matchesArg[1]);

        //Create certs folder
        self::storage([
            'play-h',
            'build::storage',
             sprintf("--app=%s",$app),
             '--subfolder='.sprintf("%s",'certs')
        ],true);

        self::key([
            'play-h',
            'build::key',
            sprintf('--app=%s',$app)
        ],true);

        $pathKeys =  sprintf
        (
            Path::getProjectPath().'%s%s%s%s%s%s%s%s%s%s',
            DIRECTORY_SEPARATOR,
            'app',
            DIRECTORY_SEPARATOR,
            $app,
            DIRECTORY_SEPARATOR,
            'storage',
            DIRECTORY_SEPARATOR,
            'keys',
            DIRECTORY_SEPARATOR,
            'encryption.key'
        );

        $pathCerts = sprintf
        (
            Path::getProjectPath().'%s%s%s%s%s%s%s%s%s%s',
            DIRECTORY_SEPARATOR,
            'app',
            DIRECTORY_SEPARATOR,
            $app,
            DIRECTORY_SEPARATOR,
            'storage',
            DIRECTORY_SEPARATOR,
            'certs',
            DIRECTORY_SEPARATOR,
            'private.key'
        );

        $cmd = escapeshellcmd(sprintf("openssl genrsa -aes128 -passout file:%s -out %s 2048",$pathKeys,$pathCerts));
        exec($cmd." 2>&1",$output);

        if(file_exists($pathCerts))
        {
            chmod($pathCerts,0644);
        }

        $msg = sprintf(Show::getMessage(1001),$output[0]);
    
        Show::showMessage($msg,$noExit);
    }

    public static function controller($args,$noExit = false)
    {
        if(empty($args[2]))
        {
            exit(print(sprintf(Show::getMessage(1),1,'controller name')));
        }
        else if(count($args) < 3)
        {
            exit(print(sprintf(Show::getMessage(2),'build::controller')));
        }

        $re = '/[^A-Za-z0-9]/is';

        $name = preg_replace("/[^A-Za-z0-9\\/]/",'_', $args[2]);
 
        $listArgs = [];
        
        if(preg_match('`/`is',$name))
        {
            $listArgs = explode('/',$name);
        }
        else if(preg_match('`\\`is',$name))
        {
            $listArgs = explode('\\',$name);
        }

        if(empty($listArgs))
        {
            Show::showMessage(sprintf(Show::getMessage(2),'build::controller'));
        }

        $path = sprintf
                (
                    Path::getProjectPath().'%s%s%s%s%s%s%s%s%s%s',
                    DIRECTORY_SEPARATOR,
                    'app',
                    DIRECTORY_SEPARATOR,
                    trim($listArgs[0]),
                    DIRECTORY_SEPARATOR,
                    'modules',
                    DIRECTORY_SEPARATOR,
                    trim($listArgs[1]),
                    DIRECTORY_SEPARATOR,
                    'controller'

                );
      
         $controller = preg_replace("/[^A-Za-z0-9]/",'_',$listArgs[2]);   
    
         $ctrArray = explode('_',$controller);

         $controller = '';
         foreach($ctrArray as $ctr)
         {
            $controller .= ucfirst($ctr);
         }
         
         $fileControllerBase = \file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'ControllerBase.playh');

         $args[3] = !empty($args[3]) ? $args[3] : '';
         $args[4] = !empty($args[4]) ? $args[4] : '';

         $re = '`--api=(.*)?`si';

         preg_match($re,$args[3],$matchesArg);  

         $isApi = false;

         if(!empty($matchesArg[1]))
         {
            $isApi = mb_strtolower($matchesArg[1]) == 'true' ? true : false;
         }

         $listArgs[3] = !empty($listArgs[3]) ? $listArgs[3] : 'index';
         $listArgs[3] = preg_replace("/[^A-Za-z0-9]/",'',$listArgs[3]);

         $responseReturn = "return (new View('index'));";
         $nameSpaceReturnView = "use Harp\bin\View;";

         if($isApi)
         {
            $responseReturn = 
            "return (new HarpResponse())->OK
            (
                ['response' => sprintf('Controller Api {%s} is working...','".$controller."')],
                200
            )->json();";

            $nameSpaceReturnView = '';
         }

         $defaultMethod = self::createMethod($listArgs[3],
                                                       $responseReturn
                                            );
         $file = str_ireplace(
             ['{{appName}}','{{moduleName}}','{{nameController}}','{{attributesConstruct}}','{{defaultMethod}}','{{namespace_view}}'],
             [$listArgs[0],$listArgs[1],$controller,'',$defaultMethod,$nameSpaceReturnView],
             $fileControllerBase
         );

         if(!is_dir($path))
         {
             mkdir($path,0755,true);
         }

         $s = file_put_contents($path.DIRECTORY_SEPARATOR.$controller.'Controller.php',$file);

        $re = '`--short=(.*)?`si';
        preg_match($re,$args[4],$mShort);  

        if(empty($mShort[1]))
        {
            $mShort[1] = sprintf('%s%s%s',mb_strtolower($controller),'/',mb_strtolower($listArgs[3]));
        }
    
        self::route([
            'playh',
            'build::route',
            implode('/',$listArgs),
            sprintf('--short=%s',$mShort[1]) 
         ],$noExit);

         if($s){ Show::showMessage(sprintf(Show::getMessage(200),'controller',$controller),$noExit); }
         else{ Show::showMessage(sprintf(Show::getMessage(500),'controller',$controller)); }
    }

    public static function route($args,$noExit = false)
    {
        if(count($args) != 4)
        {
            $msg = sprintf(Show::getMessage(1000),'build::route Expected exactly {4} parameters, found: {'.count($args).'}!');
    
            Show::showMessage($msg);
        }

        $pathRoute = explode('/',$args[2]);
        
        if(count($pathRoute) != 4)
        {
            $msg = sprintf(Show::getMessage(1000),'route Expected exactly {4} arguments separated by slash, found: {'.count($pathRoute).'}!');
    
            Show::showMessage($msg);
        }

        $appPath =  sprintf
        (
            Path::getProjectPath().'%s%s%s%s',
            DIRECTORY_SEPARATOR,
            'app',
            DIRECTORY_SEPARATOR,
            trim($pathRoute[0])
        );

        if(!is_dir($appPath))
        {
            $msg = sprintf(Show::getMessage(1000),'app {'.$pathRoute[0].'} does not exists!');
    
            Show::showMessage($msg);
        }

        $re = '`--short=(.*)?`si';
        preg_match($re,$args[3],$mShort);  

        if(empty($mShort[1]))
        {
            $msg = sprintf(Show::getMessage(1000),'short name route args not found, please check syntax add: --short={short name route}!');
    
            Show::showMessage($msg);
        }

        $shortName = mb_strtolower(preg_replace("/[^A-Za-z0-9\\/]/",'', $mShort[1]));
  
        $pathRoutes = sprintf(Path::getProjectPath().'%s%s%s%s',DIRECTORY_SEPARATOR,'app',DIRECTORY_SEPARATOR,'routes.json');
        
        if(!file_exists($pathRoutes))
        {
            $msg = sprintf(Show::getMessage(1000),'routes.json not found in!');
    
            Show::showMessage($msg);
        }

        $routes = json_decode(file_get_contents($pathRoutes),true);

        $appKey = ucfirst($pathRoute[0]);

            $routes['apps'][$appKey][$shortName] = [];
            $routes['apps'][$appKey][$shortName] = [
                "path" => trim($args[2]),
                "condition" => "=== true",
                "constructor" => [],        
                "args" => [],
                "requestMethod" => "GET",
                "required" => 
                [
                    "before" => new stdClass(),  
                    "after" => new stdClass()
                ] 
            ];

        if(file_put_contents($pathRoutes,json_encode($routes,JSON_PRETTY_PRINT)))
        {
            $msg = sprintf(Show::getMessage(1001),'route {'.$args[2].'} created sucessfull!');
    
            Show::showMessage($msg,$noExit);
        }
        else
        {
            $msg = sprintf(Show::getMessage(1001),'failed create route {'.$args[2].'}!');
    
            Show::showMessage($msg);
        }

    }

    public static function entity($args,$noExit = false)
    {
        if(empty($args[2]))
        {
            Show::showMessage(sprintf(Show::getMessage(1000),'required {app}/{module}/{entityName}!'));
        }

        $arg = explode('/',$args[2]);

        if(count($arg) != 3)
        {
            Show::showMessage(sprintf(Show::getMessage(1000),'required {app}/{module}/{entityName}!'));
        }

        $extraArguments = Utils::parseExtraArguments($args);

        $path = Path::getProjectPath().DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.$arg[0].DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.$arg[1];

        if(!is_dir(Path::getProjectPath().DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.$arg[0]))
        {
            Show::showMessage(sprintf(Show::getMessage(1000),sprintf('Not found {app} %s!',$arg[0])));
        }

        if(!is_dir($path.DIRECTORY_SEPARATOR.'entity'))
        {
            mkdir($path.DIRECTORY_SEPARATOR.'entity',0755,true);
        }

        $props = [];

        if(!empty($extraArguments['table']))
        {
            $args2 = [
                 $args[0],
                'db::entity',
                $args[2],
                '--table='.$extraArguments['table'],
            ];

            if(!empty($extraArguments['conn']))
            {
                $args2[] = '--conn='.$extraArguments['conn'];
            }

            Db::entity($args2,true);

            Db::loadEnv();
            Db::eloquentManager();
            $Model = sprintf("App\\%s\\modules\\%s\\mapper\\%s",$arg[0],$arg[1],$arg[2]);
            $objModel = new $Model();

            $props = $objModel->getTypesColumns();
        }

        $propsEntity = "";

        foreach($props as $prop => $type)
        {
            $propsEntity .= sprintf('%spublic ?%s $%s = null;%s',str_repeat(chr(32),8),$type,$prop,PHP_EOL);
        }

        $entity = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'EntityBase.playh');

        $entity = str_ireplace(['{{app}}','{{module}}','{{nameEntity}}','{{properties}}'],[$arg[0],$arg[1],$arg[2],$propsEntity],$entity);

        file_put_contents($path.DIRECTORY_SEPARATOR.'entity'.DIRECTORY_SEPARATOR.$arg[2].'Entity.php',$entity);

        Show::showMessage(sprintf(Show::getMessage(200),'Entity ',$arg[2]),$noExit);
    }


    public static function repository($args,$noExit = false)
    {
        if(empty($args[2]))
        {
            Show::showMessage(sprintf(Show::getMessage(1000),'required {app}/{module}/{repositoryName}!'));
        }
    
        $arg = explode('/',$args[2]);

        if(count($arg) != 3)
        {
            Show::showMessage(sprintf(Show::getMessage(1000),'required {app}/{module}/{repositoryName}!'));
        }

        $path = Path::getProjectPath().DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.$arg[0].DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.$arg[1];

        if(!is_dir($path))
        {
            Show::showMessage(sprintf(Show::getMessage(1000),sprintf('Not found {app}/{module} %s/%s!',$arg[0],$arg[1])));
        }

        if(!is_dir($path.DIRECTORY_SEPARATOR.'repository'))
        {
            mkdir($path.DIRECTORY_SEPARATOR.'repository',0755,true);
        }

        $repositoryFile = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'RepositoryBase.playh');

        $repository = str_ireplace(['{{app}}','{{module}}','{{nameRepository}}'],[$arg[0],$arg[1],$arg[2]],$repositoryFile);

        file_put_contents($path.DIRECTORY_SEPARATOR.'repository'.DIRECTORY_SEPARATOR.$arg[2].'Repository.php',$repository);

        Show::showMessage(sprintf(Show::getMessage(200),'Repository ',$arg[2]),$noExit);
    }

    public static function model($args,$noExit = false)
    {
        if(empty($args[2]))
        {
            exit(print(sprintf(Show::getMessage(1),1,'model name')));
        }
        else if(count($args) != 3)
        {
            exit(print(sprintf(Show::getMessage(2),'build::model')));
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

        if(empty($listArgs))
        {
            Show::showMessage(sprintf(Show::getMessage(2),'build::model'));
        }

        $path = sprintf
                (
                    Path::getProjectPath().'%s%s%s%s%s%s%s%s%s%s',
                    DIRECTORY_SEPARATOR,
                    'app',
                    DIRECTORY_SEPARATOR,
                    trim($listArgs[0]),
                    DIRECTORY_SEPARATOR,
                    'modules',
                    DIRECTORY_SEPARATOR,
                    trim($listArgs[1]),
                    DIRECTORY_SEPARATOR,
                    'model'

                );

         $model = ucfirst(preg_replace("/[^A-Za-z0-9]/",'',$listArgs[2]));   
         
         $fileModelBase = \file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'ModelBase.playh');
 
         $defaultMethod = self::createMethod(!empty($listArgs[3]) ? $listArgs[3] : 'index');
         $file = str_ireplace(
             ['{{appName}}','{{moduleName}}','{{nameModel}}','{{attributesConstruct}}','{{defaultMethod}}'],
             [$listArgs[0],$listArgs[1],$model,'',$defaultMethod],
             $fileModelBase
         );

         if(!is_dir($path))
         {
             mkdir($path,0775,true);
         }

         $s = file_put_contents($path.DIRECTORY_SEPARATOR.$model.'Model.php',$file);

         if($s){ Show::showMessage(sprintf(Show::getMessage(200),'model',$model),$noExit); }
         else{ Show::showMessage(sprintf(Show::getMessage(500),'model',$model)); }
    }


    public static function view($args,$noExit = false)
    {
        $countArgs = count($args);

        if(empty($args[2]))
        {
            exit(print(sprintf(Show::getMessage(1),1,'view name')));
        }

        $layout = 'default';

        if($countArgs > 3)
        {
            $re = '`--layout=(.*)?`si';
            preg_match($re,$args[3],$matchesLayout);  
            $layout = isset($matchesLayout[1]) ? $matchesLayout[1] : $layout;
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

        if(empty($listArgs))
        {
            Show::showMessage(sprintf(Show::getMessage(2),'build::view'));
        }

        $path = sprintf
                (
                    Path::getProjectPath().'%s%s%s%s%s%s%s%s%s%s',
                    DIRECTORY_SEPARATOR,
                    'app',
                    DIRECTORY_SEPARATOR,
                    trim($listArgs[0]),
                    DIRECTORY_SEPARATOR,
                    'modules',
                    DIRECTORY_SEPARATOR,
                    trim($listArgs[1]),
                    DIRECTORY_SEPARATOR,
                    'view'

                );
           
         $view = ucfirst(preg_replace("/[^A-Za-z0-9]/",'',$listArgs[2]));   
         
         $fileViewBase = \file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'ViewBase.playh');
 
         $defaultMethod = self::createMethod('index');
         $file = str_ireplace(
             ['{{appName}}','{{moduleName}}','{{nameView}}','{{nameLayout}}','{{attributesConstruct}}','{{defaultMethod}}'],
             [$listArgs[0], $listArgs[1], $view, $layout, $defaultMethod],
             $fileViewBase
         );

         if(!is_dir($path))
         {
             mkdir($path,0775,true);
         }

         $s = file_put_contents($path.DIRECTORY_SEPARATOR.$view.'View.php',$file);

         if($s){ Show::showMessage(sprintf(Show::getMessage(200),'view',$view),$noExit); }
         else{ Show::showMessage(sprintf(Show::getMessage(500),'view',$view)); }
    }


    public static function layout_file($args)
    {
        $countArgs = count($args);

        if($countArgs != 4)
        {
            exit(print(sprintf(Show::getMessage(002),'build::layout_file')));
        }

        $re = '`--file=(.*)?`si';

        preg_match($re,$args[2],$matchesFile);  

        if(empty($matchesFile[1]))
        {
            Show::showMessage(sprintf(Show::getMessage(003),'build::layout_file'));
        }

        $re = '`--path=(.*)?`si';

        preg_match($re,$args[3],$matchesFolder);  

        if(empty($matchesFolder[1]))
        {
            Show::showMessage(sprintf(Show::getMessage(003),'build::layout_file'));
        }

        $path = sprintf
        (
            Path::getProjectPath().'%s%s%s%s%s%s',
            DIRECTORY_SEPARATOR,
            'public',
            DIRECTORY_SEPARATOR,
            'layouts',
            DIRECTORY_SEPARATOR,
            $matchesFolder[1]
        );

        if(!is_dir($path))
        {
            mkdir($path,0775,true);
        }

        file_put_contents($path.DIRECTORY_SEPARATOR.$matchesFile[1].'.html',file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'HtmlBase.html'));
 
        Show::showMessage(sprintf(Show::getMessage(200),'layout file',$matchesFile[1]));
    }      

    public static function layout($args,$noExit = false)
    {
        $countArgs = count($args);

        if(empty($args[2]))
        {
            exit(print(sprintf(Show::getMessage(001),1,'layout name')));
        }

        $name = trim(preg_replace("`[^A-Za-z0-9]`",'', $args[2]));

        if(empty($name))
        {
            Show::showMessage(sprintf(Show::getMessage(002),'build::layout'));
        }

        
        $f = 'index';
        $t = 'default';
        $l = 'default';
        $fc = 'hello_world';

        $matchesFile = [];
        $matchesLayout = [];
        $matchesTemplate = [];
        $matchesContent = [];

        if($countArgs > 3)
        {
            for($i = 3; $i < $countArgs;++$i)
            {
                $re1 = '`--file=(.*)?`si';
                $re2 = '`--layout=(.*)?`si';
                $re3 = '`--template=(.*)?`si';
                $re4 = '`--file_content=(.*)?`si';

                if(empty($matchesFile[1]) && preg_match($re1,$args[$i],$matchesFile))
                {
                    $f = $matchesFile[1];
                }
                else if(empty($matchesLayout[1]) && preg_match($re2,$args[$i],$matchesLayout))
                {
                    $l = $matchesLayout[1];
                }  
                else if(empty($matchesTemplate[1]) && preg_match($re3,$args[$i],$matchesTemplate))
                {
                    $t = $matchesTemplate[1];
                } 
                else if(empty($matchesContent[1]) && preg_match($re4,$args[$i],$matchesContent))
                {
                    $fc = $matchesContent[1];
                }             
            }
        }

        $path = sprintf
                (
                    Path::getProjectPath().'%s%s%s%s%s%s%s%s',
                    DIRECTORY_SEPARATOR,
                    'public',
                    DIRECTORY_SEPARATOR,
                    'layouts',
                    DIRECTORY_SEPARATOR,
                    $name,
                    DIRECTORY_SEPARATOR,
                    $t
                );

        if(!is_dir($path))
        {
            mkdir($path,0775,true);
        }

        mkdir($path.DIRECTORY_SEPARATOR.'assets',0775,true);
        mkdir($path.DIRECTORY_SEPARATOR.'shared',0775,true);
        mkdir($path.DIRECTORY_SEPARATOR.$l,0775,true);

        $content = '';

        if(file_exists(__DIR__.DIRECTORY_SEPARATOR.$fc.'.html'))
        {
            $content = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.$fc.'.html');
        }
        
        $htmlBase = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'HtmlBase.html');
        $htmlBase = str_ireplace(['{{content}}'],[$content],$htmlBase);

        file_put_contents($path.DIRECTORY_SEPARATOR.$l.DIRECTORY_SEPARATOR.$f.'.html',$htmlBase);

        Show::showMessage(sprintf(Show::getMessage(200),'layout',$name),$noExit);
    } 
    
    public static function template($args,$noExit = false)
    {
        if(empty($args[2]))
        {
            exit(print(sprintf(Show::getMessage(001),1,'template name')));
        }

        $name = trim(preg_replace("`[^A-Za-z0-9]`",'', $args[2]));

        if(empty($name))
        {
            Show::showMessage(sprintf(Show::getMessage(002),'build::template'));
        }

        $path = sprintf
                (
                    Path::getProjectPath().'%s%s%s%s%s%s',
                    DIRECTORY_SEPARATOR,
                    'public',
                    DIRECTORY_SEPARATOR,
                    'templates',
                    DIRECTORY_SEPARATOR,
                    $name
                );

        if(!is_dir($path))
        {
            mkdir($path,0775,true);
        }

        Show::showMessage(sprintf(Show::getMessage(200),'template',$name),$noExit);
    }      

    private static function getPathsGeneratedApp($list)
    {
        $paths = new stdClass();

        $appFolder = trim(mb_strtolower($list[0]));

        $appPath =  sprintf
        (
            Path::getProjectPath().'%s%s%s%s',
            DIRECTORY_SEPARATOR,
            'app',
            DIRECTORY_SEPARATOR,
            $appFolder
        );

        $basePath = sprintf
        (
            Path::getProjectPath().'%s%s%s%s%s%s%s%s',
            DIRECTORY_SEPARATOR,
            'app',
            DIRECTORY_SEPARATOR,
            $appFolder,
            DIRECTORY_SEPARATOR,
            'modules',
            DIRECTORY_SEPARATOR,
            trim($list[1])
        );

        $paths->start = Path::getProjectPath();

        $paths->routes = sprintf(Path::getProjectPath().'%s%s',DIRECTORY_SEPARATOR,'app');

        $paths->app = $appPath;

        $paths->controller =  sprintf
                (
                    $basePath.'%s%s',
                    DIRECTORY_SEPARATOR,
                    'controller'
                );
        $paths->model =  sprintf
                (
                    $basePath.'%s%s',
                    DIRECTORY_SEPARATOR,
                    'model'
                ); 

        $paths->view =  sprintf
        (
            $basePath.'%s%s',
            DIRECTORY_SEPARATOR,
            'view'
        );   
        
        return $paths;
    }

    public static function storage($args,$noExit = false)
    {

        $countArgs = count($args);

        $app = []; $subfolder = [];
        $a = null;  $subfld = null;

        for($i = 2; $i < $countArgs;++$i)
        {
            $re1 = '`--app=(.*)?`si';
            $re3 = '`--subfolder=(.*)?`si';

            if(empty($app[1]) && preg_match($re1,$args[$i],$app))
            {
                $a = $app[1];
            }
            else if(empty($subfolder[1]) && preg_match($re3,$args[$i],$subfolder))
            {
                $subfld  = $subfolder[1];
            }           
        }

      
        if(empty($a))
        {
            Show::showMessage(sprintf(Show::getMessage(003),'Build::storage app not informed!'));
        }

        $nameApp = trim($a);

        $path =  sprintf
        (
            Path::getProjectPath().'%s%s%s%s',
            DIRECTORY_SEPARATOR,
            'app',
            DIRECTORY_SEPARATOR,
            $nameApp
        );


        $path =  $path.DIRECTORY_SEPARATOR.'storage';

        if(!empty($subfld))
        {
            $path =  $path.DIRECTORY_SEPARATOR.$subfld;
        }

        if(!is_dir($path))
        {
            mkdir($path,0755,true);

            Show::showMessage(sprintf(Show::getMessage(200),'storage','storage/'.$subfld),$noExit);
        }
        else 
        {
            $msg = sprintf(Show::getMessage(1001),'the folder {'.$subfld.'} exists in: {'.$path.'}!');
    
            Show::showMessage($msg,$noExit);
        }
    }

    public static function app($args)
    {

        clearstatcache();

        $countArgs = count($args);

        if($countArgs != 3)
        {
            Show::showMessage(sprintf(Show::getMessage(003),'Build::app'));
        }

        $re = '`--args=(.*)?`si';

        preg_match($re,$args[2],$matches);  

        if(empty($matches[1]))
        {
            Show::showMessage(sprintf(Show::getMessage(003),'build::app'));
        }

        $listArgs = [];

        if(preg_match('`/`is',$matches[1]))
        {
            $listArgs = explode('/',$matches[1]);
        }
        else if(preg_match('`\\`is',$matches[1]))
        {
            $listArgs = explode('\\',$matches[1]);
        }

        $listArgs = array_values($listArgs);

        if(count($listArgs) != 2)
        {
            Show::showMessage(sprintf(Show::getMessage(003),'build::app'));
        }
        

        $paths = self::getPathsGeneratedApp($listArgs);

        $pIndexFile = $paths->start.DIRECTORY_SEPARATOR.'index.php';

        $startBase = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'StartBase.playh');
        
        if(!file_exists($pIndexFile))
        {
            file_put_contents($pIndexFile,$startBase);
        }

        $fileStart = file_get_contents($paths->start.DIRECTORY_SEPARATOR.'index.php');

        $name = ucfirst($listArgs[0]);

        if(!preg_match('`[A-z]`',mb_substr($name,0,1)))
        {
            Show::showMessage(sprintf(Show::getMessage(1000),'Application names must start with letters from [A-z]!'));
        }

        $matches = [];

        $strApps = sprintf('%s->registerApp(%s%s%s)%s',str_repeat(chr(32),7),"'",$name,"'",PHP_EOL);

        if(preg_match_all('`registerApp\((.*?)\)`',$fileStart,$matches2))
        {
            foreach($matches2[1] as $nameApp)
            {
                $nApp = str_ireplace(['\''],[''],$nameApp);
                $strApps .= sprintf('%s->registerApp(%s%s%s)%s',str_repeat(chr(32),7),"'",$nApp,"'",PHP_EOL);  
            }
        }

        $strCode  = '$Route = HarpRoute::load(false)'.PHP_EOL;    
        $strCode .= $strApps;
        $strCode .= str_repeat(chr(32),7).'->runApp();'.PHP_EOL;

        $startBase = str_ireplace(['{{apps}}'],[$strCode],$startBase);

        $nameLower = mb_strtolower($name);

        $routes = json_decode(file_get_contents($paths->routes.DIRECTORY_SEPARATOR.'routes.json'),true);
        $routes['apps'][$name] = [
            $nameLower => [
                "path" => sprintf("%s/%s/%s/%s",$nameLower,$listArgs[1],'home','index'),
                "condition" => "=== true",
                "constructor" => [],        
                "args" => [],
                "requestMethod" => "GET",
                "required" => 
                [
                    "before" => [],  
                    "after" => []
                ] 
            ]
        ];

        $appBase = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'AppBase.playh');
        $appBase = str_ireplace(['{{__name}}','{{__namespace}}'],[$name,$nameLower],$appBase);
       
        if(!is_dir($paths->app))
        {
            mkdir($paths->app,0775,true);
        }

        if(!is_dir($paths->controller))
        {
            mkdir($paths->controller,0775,true);
        }

        if(!is_dir($paths->model))
        {
            mkdir($paths->model,0775,true);
        }

        if(!is_dir($paths->view))
        {
            mkdir($paths->view,0775,true);
        }

        self::controller([
            'play-h',
            'build::controller',
            sprintf("%s/%s/%s",$nameLower,$listArgs[1],'home')
        ],true);

        self::model([
            'play-h',
            'build::model',
            sprintf("%s/%s/%s",$nameLower,$listArgs[1],'home')
        ],true);

        self::view([
            'play-h',
            'build::view',
            sprintf("%s/%s/%s",$nameLower,$listArgs[1],'home'),
            '--layout='.$listArgs[1]
        ],true);

        self::layout([
            'play-h',
            'build::layout',
             sprintf("%s",$nameLower),
             '--template='.$listArgs[1],
             '--layout=home',
             '--file=index',
             '--file_content=hello_world'
        ],true);

        self::template([
            'play-h',
            'build::template',
             sprintf("%s",$listArgs[1]),
        ],true);

        //Create keys folder
        self::storage([
            'play-h',
            'build::storage',
             '--app='.sprintf("%s",$nameLower),
             '--subfolder='.sprintf("%s",'keys')
        ],true);

     
        //Create certs folder
        self::storage([
            'play-h',
            'build::storage',
                '--app='.sprintf("%s",$nameLower),
                '--subfolder='.sprintf("%s",'certs')
        ],true);

        //Create migration folder
        self::storage([
            'play-h',
            'build::storage',
                '--app='.sprintf("%s",$nameLower),
                '--subfolder='.sprintf("%s",'migrations')
        ],true);

        //Create key
        self::key([
            'play-h',
            'build::key',
            '--app='.sprintf("%s",$nameLower),
            '--force=true'
        ],true);

        //Create cert
        self::cert([
            'play-h',
            'build::cert',
            '--app='.sprintf("%s",$nameLower),
            '--force=true'
        ],true);


        file_put_contents($paths->start.DIRECTORY_SEPARATOR.'index.php',$startBase);
        file_put_contents($paths->routes.DIRECTORY_SEPARATOR.'routes.json',json_encode($routes,JSON_PRETTY_PRINT));
        file_put_contents($paths->app.DIRECTORY_SEPARATOR.$name.'.php',$appBase);
     
        Show::showMessage(sprintf(Show::getMessage(200),'app',$name));
    }

    public static function group($args)
    {
        $params = Utils::parseCommand($args);

        if(empty($params))
        {
            Show::showMessage(sprintf(Show::getMessage(1001),'Create a group required parameter after build::group!'));
        }
        else if(count($params) < 3)
        {
            Show::showMessage(sprintf(Show::getMessage(1001),'The params to create a group required 3 arguments {app}/{module}/{controller}. !'));   
        }

        $app = mb_strtolower($params[0]);
        $module = mb_strtolower($params[1]);

        $pathApp =  sprintf
        (
            Path::getProjectPath().'%s%s%s%s',
            DIRECTORY_SEPARATOR,
            'app',
            DIRECTORY_SEPARATOR,
            $app
        );

        $pathModule =  sprintf
        (
            '%s%s%s%s%s',
             $pathApp,
             DIRECTORY_SEPARATOR,
            'modules',
            DIRECTORY_SEPARATOR,
            $module 
        );

        if(!is_dir($pathApp))
        {
            Show::showMessage(sprintf(Show::getMessage(1000),'The app {%s} does not exists!',$app));   
        }

        if(!is_dir($pathModule))
        {
            mkdir($pathModule,0775,true);
        }

        $extraArgs = Utils::parseExtraArguments($args);

        $nameLower = mb_strtolower($params[2]);

      
        $ctrPath =  sprintf("%s/%s/%s%s",$app,$module,$params[2],isset($params[3]) ? sprintf('/%s',$params[3]) : '');
        $argsController = [
            'play-h',
            'build::controller',
            $ctrPath
        ];

        if(!empty($extraArgs['type']) && $extraArgs['type'] = 'api')
        {
            array_push($argsController,'--api=true');
        }

        if(!empty($extraArgs['short']))
        {
            if(preg_match(sprintf('`%s`',$app),$extraArgs['short']))
            {
                Show::showMessage(sprintf(Show::getMessage(1000),sprintf('The string app name {%s} is not allowed to be part of the short name!.',$app))); 
            }

            array_push($argsController,'--short='.$extraArgs['short']);
        }

        self::controller($argsController,true);
 
        self::model([
            'play-h',
            'build::model',
            $ctrPath
        ],true);

        if($extraArgs['type'] != 'api')
        {
            $vwPath = sprintf("%s/%s/%s%s",$app,$module,'home',isset($params[3]) ? sprintf('/%s',$params[3]) : '');
            self::view([
                'play-h',
                'build::view',
                $vwPath,
                '--layout='.$module
            ],true);
    
            self::layout([
                'play-h',
                'build::layout',
                 sprintf("%s",$nameLower),
                 '--template='.$module,
                 '--layout=home',
                 '--file=index',
                 '--file_content=hello_world'
            ],true);
        }

 
        $msg = sprintf(Show::getMessage(1001),'group {'.$args[2].'} created sucessfull!');
    
        Show::showMessage($msg);
    }

    public static function api($args)
    {
        if(empty($args[2]))
        {
            Show::showMessage(sprintf(Show::getMessage(1001),'Invalid command to create api!'));
        }

        $args[2] = str_ireplace("\\","/",$args[2]);
        $listArgs = explode('/',$args[2]);

        $countArgs = count($listArgs);

        if($countArgs < 3)
        {
            Show::showMessage(sprintf(Show::getMessage(1001),'the minimum number of arguments for path is 3 in the format: {app}/{module}/{controller} the fourth argument is optional and is equivalent to the name of the method.!'));
        }

        $listArgs = array_values($listArgs);

        $paths = self::getPathsGeneratedApp($listArgs);

        $fileStart = file_get_contents($paths->start.DIRECTORY_SEPARATOR.'index.php');

        $name = ucfirst($listArgs[0]);

        $matches = [];

        $startBase = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'StartBase.playh');

        $strApps = sprintf('%s->registerApp(%s%s%s)%s',str_repeat(chr(32),7),"'",$name,"'",PHP_EOL);

        if(preg_match_all('`registerApp\((.*?)\)`',$fileStart,$matches2))
        {
            foreach($matches2[1] as $nameApp)
            {
                $nApp = str_ireplace(['\''],[''],$nameApp);
                $strApps .= sprintf('%s->registerApp(%s%s%s)%s',str_repeat(chr(32),7),"'",$nApp,"'",PHP_EOL);  
            }
        }

        $listArgs[3] = !empty($listArgs[3]) ? $listArgs[3] : 'index';

        $strCode  = '$Route = HarpRoute::load(false)'.PHP_EOL;    
        $strCode .= $strApps;
        $strCode .= str_repeat(chr(32),7).'->runApp();'.PHP_EOL;

        $startBase = str_ireplace(['{{apps}}'],[$strCode],$startBase);

        $nameLower = mb_strtolower($name);

        $routes = json_decode(file_get_contents($paths->routes.DIRECTORY_SEPARATOR.'routes.json'),true);
        $routes['apps'][$name] = [
            $nameLower => [
                "path" => sprintf("%s/%s/%s/%s",$nameLower,$listArgs[1],$listArgs[2],$listArgs[3]),
                "condition" => "=== true",
                "constructor" => [],        
                "args" => [],
                "requestMethod" => "GET",
                "required" => 
                [
                    "before" => [],  
                    "after" => []
                ] 
            ]
        ];

        $appBase = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'AppBase.playh');
        $appBase = str_ireplace(['{{__name}}','{{__namespace}}'],[$name,$nameLower],$appBase);
        
        if(!is_dir($paths->app))
        {
            mkdir($paths->app,0775,true);
        }

        if(!is_dir($paths->controller))
        {
            mkdir($paths->controller,0775,true);
        }

        if(!is_dir($paths->model))
        {
            mkdir($paths->model,0775,true);
        }

        if(!is_dir($paths->view))
        {
            mkdir($paths->view,0775,true);
        }

        self::controller([
            'play-h',
            'build::controller',
            sprintf("%s/%s/%s/%s",$nameLower,$listArgs[1],$listArgs[2],$listArgs[3]),
            '--api=true'
        ],true);

        self::model([
            'play-h',
            'build::model',
            sprintf("%s/%s/%s/%s",$nameLower,$listArgs[1],$listArgs[2],$listArgs[3])
        ],true);

        //Create keys folder
        self::storage([
            'play-h',
            'build::storage',
             '--app='.sprintf("%s",$nameLower),
             '--subfolder='.sprintf("%s",'keys')
        ],true);

        //Create certs folder
        self::storage([
            'play-h',
            'build::storage',
                '--app='.sprintf("%s",$nameLower),
                '--subfolder='.sprintf("%s",'certs')
        ],true);

        //Create migrations folder
        self::storage([
            'play-h',
            'build::storage',
                '--app='.sprintf("%s",$nameLower),
                '--subfolder='.sprintf("%s",'migrations')
        ],true);

        self::key([
            'play-h',
            'build::key',
            sprintf('--app=%s',$nameLower)
        ],true);

        self::cert([
            'play-h',
            'build::cert',
            sprintf('--app=%s',$nameLower)
        ],true);


        file_put_contents($paths->start.DIRECTORY_SEPARATOR.'index.php',$startBase);
        file_put_contents($paths->routes.DIRECTORY_SEPARATOR.'routes.json',json_encode($routes,JSON_PRETTY_PRINT));
        file_put_contents($paths->app.DIRECTORY_SEPARATOR.$name.'.php',$appBase);
     
        Show::showMessage(sprintf(Show::getMessage(200),'api',$name));
    }

}