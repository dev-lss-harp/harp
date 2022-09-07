<?php
namespace Harp\bin;

use Exception;

use Harp\bin\HarpProcess;
use Harp\bin\HarpHttpMessage;
use Harp\bin\HarpRequestHeaders;
use Harp\bin\HarpServer;
use Harp\bin\HarpServerRequest;
use Throwable;

class HarpRoute
{

    private const __NAMESPACE = '\\App';
    const NAME_JSON_ROUTES = 'routes.json';
    const APP_NAME = 'APP_NAME';

    const PATH_FRAMEWORK = 'PATH_FRAMEWORK';
    const PATH_PROJECT = 'PATH_PROJECT';
    const PATH_APP = 'PATH_APP';
    const PATH_PUBLIC = 'PATH_PUBLIC';
    const PATH_PUBLIC_LAYOUTS = 'PATH_PUBLIC_LAYOUTS';
    const PATH_PUBLIC_TEMPLATES = 'PATH_PUBLIC_TEMPLATES';
    const PATH_PUBLIC_LAYOUTS_APP = 'PATH_PUBLIC_LAYOUTS_APP';
    const PATH_PUBLIC_TEMPLATES_APP = 'PATH_PUBLIC_TEMPLATES_APP';
    const PATH_VIEW_APP = 'PATH_VIEW_APP';

    const PATH_STORAGE = 'PATH_STORAGE';

    const __URL = '__URL';
    const __URL_BASE = '__URL_BASE';
    const __URL_PUBLIC = '__URL_PUBLIC';
    const __URL_PUBLIC_LAYOUTS = '__URL_PUBLIC_LAYOUTS';
    const __URL_PUBLIC_TEMPLATES = '__URL_PUBLIC_TEMPLATES';
    const __URL_PUBLIC_LAYOUTS_APP = '__URL_PUBLIC_LAYOUTS_APP';
    const __URL_APP = '__URL_APP';
    const __URL_APP_MODULE = '__URL_APP_MODULE';
    const __BASE_URL_REQUEST = '__BASE_URL_REQUEST';
    const __URL_REQUEST = '__URL_REQUEST';

    const PROJECT_NAME = 'PROJECT_NAME';
    
    const POST = 'post';
    const GET = 'get';
    const PUT = 'put';
    const DELETE = 'delete';
    const PATCH = 'patch';

    private static $instance;
    private $ServerRequest;
    private $routeCurrent;
    private $translateDomain;
    private $apps;
    private $app;
    private $basePath;
    private $resources = 
            [
                'css' => 'text/css',
                'js' => 'text/javascript',
                'svg'=> 'image/svg+xml',
                'jpg' => 'image/jpg',
                'png' => 'image/png',
                'ico' => 'image/x-icon'
            ];

    private function __construct($translateDomain)
    {
        try 
        {
            $this->translateDomain = $translateDomain;

            $this->HarpServer = new HarpServer();
            $this->HarpRequestHeaders = new HarpRequestHeaders();
            $this->ServerRequest = new HarpServerRequest($this->HarpServer,$this->HarpRequestHeaders);
            $this->apps = [];

            $this->basePath = dirname(dirname(dirname(dirname(dirname(__DIR__)))));
        } 
        catch (\Throwable $th) 
        {
            throw $th;
        }
    }

    public function registerApp($app)
    {
        try
        {
            $app = trim($app);

            if(empty($app))
            {
                throw new Exception("!Application name can not be empty!");
            }

            $nameSpaceApp = self::__NAMESPACE.'\\'.mb_strtolower($app).'\\'.$app;

            if(!class_exists($nameSpaceApp))
            {
                throw new Exception('Could not find class app {'.$app.'}!');
            }

            $lowerNameApp = mb_strtolower($app);

            $this->apps[$lowerNameApp] = $nameSpaceApp;   
        }
        catch(Throwable $th)
        {
            throw $th;
        }

        return $this;
    }    

    private function renderView($Response,$ServerRequest)
    {
        if($Response instanceof HarpView)
        {
            $RefView = new \ReflectionClass($Response);
            $RefMethod = $RefView->getMethod('renderView');
            $RefMethod->setAccessible(true);
            $RefMethod->invoke($Response,$this->app,$this->ServerRequest->getServerConfig());
            $RefMethod->setAccessible(false);                
        }
        else if($Response instanceof Exception)
        {
            throw $Response;
        }
        else
        {
            \Harp\bin\View::DefaultAction($Response);
        }
    }    

    public function runApp()
    {
            //define base constants
            $this->baseConstants();
            //define current app
            $this->requestApp();
            //define constants to use in public folder
            $this->publicConstants();
            //define url constants
            $this->urlConstants();
            //utils constants
            $this->utilsConstants();
            //HttpMessage capture all requests POST, GET, DELETE, PUT e etc.
            $this->app->setProperty('HttpMessage',(new HarpHttpMessage($this->ServerRequest,$this->app)));
        
            //call personal config for app
            $this->app->config();

            $Process = new HarpProcess($this->app);
            $Response = $Process->run();
            $this->renderView($Response,$this->ServerRequest);
    }

    private function publicConstants()
    {
        $this->ServerRequest
                ->getServerConfig()->set(self::PATH_PUBLIC,PATH_PROJECT.DIRECTORY_SEPARATOR.'public');                
        $this->ServerRequest
                ->getServerConfig()->set(self::PATH_PUBLIC_LAYOUTS,PATH_PUBLIC.DIRECTORY_SEPARATOR.'layouts');
        $this->ServerRequest
                ->getServerConfig()->set(self::PATH_PUBLIC_TEMPLATES,PATH_PUBLIC.DIRECTORY_SEPARATOR.'templates');                  
        $this->ServerRequest
                ->getServerConfig()->set(self::PATH_PUBLIC_LAYOUTS_APP,PATH_PUBLIC_LAYOUTS.DIRECTORY_SEPARATOR.$this->app->getDir());
        $this->ServerRequest
                ->getServerConfig()->set(self::PATH_PUBLIC_TEMPLATES_APP,PATH_PUBLIC_TEMPLATES.DIRECTORY_SEPARATOR.$this->app->getDir());
        
        $pathView = PATH_APP.
                            DIRECTORY_SEPARATOR.
                            $this->app->getDir().
                            DIRECTORY_SEPARATOR.
                            'modules'.
                            DIRECTORY_SEPARATOR.
                            $this->routeCurrent['module'].
                            DIRECTORY_SEPARATOR.
                            'view';

            $this->ServerRequest
                ->getServerConfig()
                    ->set(self::PATH_VIEW_APP,$pathView);

    }    

    private function baseConstants()
    {
        $basePath = dirname(dirname(dirname(dirname(dirname(__DIR__)))));

        $pathProject =  str_ireplace(['\\'],[DIRECTORY_SEPARATOR],realpath($basePath));

        $this->ServerRequest
                ->getServerConfig()->set(self::PATH_PROJECT,$pathProject);
        $this->ServerRequest
                ->getServerConfig()->set(self::PATH_FRAMEWORK,PATH_PROJECT.DIRECTORY_SEPARATOR.'Harp');
        $this->ServerRequest
                ->getServerConfig()->set(self::PATH_APP,$basePath.DIRECTORY_SEPARATOR.'app');
        $this->ServerRequest
                ->getServerConfig()->set(self::PROJECT_NAME,(DOCUMENT_ROOT != PATH_PROJECT) ? trim(basename($pathProject)) : '');

    }

    private function utilsConstants()
    {
         $this->ServerRequest
            ->getServerConfig()->set(self::PATH_STORAGE,PATH_APP.DIRECTORY_SEPARATOR.$this->routeCurrent['app'].DIRECTORY_SEPARATOR.'storage');
    }


    private function urlConstants()
    {
        $this->ServerRequest->getServerConfig()->set(self::__URL, 
            str_ireplace(['\\'],['/'],REQUEST_PROTOCOL.'://'.HTTP_HOST)
        );

        //remove slash from last
        $urlBase =  (strrpos(__URL.'/'.PROJECT_NAME,'/') === mb_strlen(__URL.'/'.PROJECT_NAME) - 1) 
                    ? 
                    mb_substr(__URL.'/'.PROJECT_NAME,0,-1) 
                    : __URL.'/'.PROJECT_NAME;

        $this->ServerRequest->getServerConfig()->set(self::__URL_BASE, $urlBase);

        $Uri = $this->ServerRequest->getServerRequest()->getUri();

        $this->ServerRequest->getServerConfig()->set(self::__URL_PUBLIC,__URL_BASE.'/public');
        $this->ServerRequest->getServerConfig()->set(self::__URL_PUBLIC_LAYOUTS,__URL_PUBLIC.'/layouts');
        $this->ServerRequest->getServerConfig()->set(self::__URL_PUBLIC_TEMPLATES,__URL_PUBLIC.'/templates');
        $this->ServerRequest->getServerConfig()->set(self::__URL_PUBLIC_LAYOUTS_APP,__URL_PUBLIC.'/layouts/'.$this->app->getDir());
        $this->ServerRequest->getServerConfig()->set(self::__URL_APP,__URL_BASE.'/'.str_ireplace(['\\'],['/'],$this->app->getDir()));
        $this->ServerRequest->getServerConfig()->set(self::__URL_APP_MODULE,__URL_APP.'/'.$this->routeCurrent['module']);
        $this->ServerRequest->getServerConfig()->set(self::__BASE_URL_REQUEST,__URL.(mb_substr($Uri->getPath(),0,1) == '/' ? $Uri->getPath() : '/'.$Uri->getPath()));
        $this->ServerRequest->getServerConfig()->set(self::__URL_REQUEST,__URL.REQUEST_URI);
    }

    private function getRegisteredApp($appName)
    {

        $appInstance = null;

        if(!empty($this->apps[$appName]))
        {
            $nameSpaceApp = $this->apps[$appName];

            $appInstance = new $nameSpaceApp($this->apps);

            $this->ServerRequest
            ->getServerConfig()->set(self::APP_NAME,$appInstance->getName()); 
        }

        return $appInstance;
    }

    private function getDefaultApp()
    {
        $objApp = null;

        foreach($this->apps as $app)
        {
            if(!$app->isDefaultApp())
            {
                continue;
            }

            $objApp = $app;

            break;
        }

        return $objApp;
    }

    private function verifyIsApp($appName)
    {
        $isApp = false;

        $appName = mb_strtolower($appName);

        foreach($this->apps as $app)
        {
            if(mb_strtolower($app->getName()) != $appName)
            {
                continue;
            }

            $isApp = true;

            break;
        }

        return $isApp;
    }

    private function defineAppArgs()
    {

        $appArgs = [];

        try
        {
            $path = $this->routeCurrent['path'];

            $p = explode('/',$path);

            if(count($p) != 4)
            {
                throw new ArgumentException
                (
                    'Malformed route path in routes.json, path: {'.$path.'} is invalid!',
                    500, 
                    [
                        ArgumentException::KEY_TITLE_EXCEPTION => ArgumentException::INTERNAL_SERVER_ERROR_TITLE,
                        ArgumentException::KEY_TYPE_EXCEPTION => ArgumentException::ERROR_TYPE_EXCEPTION
                    ]
                );
            }

            $p[2] = ucfirst($p[2]);
            
            $appArgs = [
                'module' => trim($p[1]),
                'group' => trim($p[2]),
                'controller' => trim($p[2]).'Controller',
                'action' => trim($p[3]),
            ];

            $appArgs['controllerPath'] =  
                        $this->app->getAppNamespace()
                        .'\\modules'
                        .'\\'.$appArgs['module']
                        .'\\controller'
                        .'\\'.$appArgs['controller'];                            

        }
        catch(\Exception $ex)
        {
            throw $ex; 
        }

        return $appArgs;
    }

    private function partsWithParamsUrl($p,$parts)
    {        
        $cccount = count($p);

        $pparams = '';

        if(!empty($p[$cccount - 1]))
        {
            $pparams = $p[$cccount - 1];
        }

        $rparams = stristr($pparams,'?',true);

        if($rparams !== false)
        {
            $parts[count($parts) - 1] = $rparams;
        } 

        return $parts;
    }

    private function getDefaultRoute($routes)
    {
        $aliasDefault = null;

        foreach($routes as $alias => $rts)
        {
            if(!$rts['default'])
            {
                continue;
            }

            $aliasDefault = $alias;

            break;
        }

        return $aliasDefault;
    }

    private function getBySimilarity($routes,$alias)
    {
        $als = null;
        $listPercentSimilarity = [];

        foreach($routes as $nAlias => $rts)
        {
            similar_text($nAlias,$alias,$percent);

            array_push($listPercentSimilarity,['alias' => $nAlias,'percent' => $percent]);
        }

        $pc = 0;

        foreach($listPercentSimilarity as $obj)
        {
            if($obj['percent'] > $pc)
            {
                $als = $obj['alias'];
                $pc = $obj['percent'];
            }
        }

        return $als;
    }    

    private function getAliasRoute($routes,$parts)
    {
        $p = $parts;
     
        if(count($parts) > 1)
        {
            unset($parts[0]);
            $parts = array_filter(array_values($parts));
        }
       
        $parts = $this->partsWithParamsUrl($p,$parts);
   
        $alias = implode('/',$parts);
    
        if(!isset($routes[$alias]))
        {    

            $als = $this->getDefaultRoute($routes);
            $alias = empty($als) ? $alias : $als;

            if(empty($als))
            {
                $alias = $this->getBySimilarity($routes,$alias);
            }  
        }

        return $alias;
    }

    private function getDefaultRouteByOnTranslateDomain($appsRoutes,$searchTerm)
    {
        $response = [];
       
        if($this->translateDomain &&  empty($searchTerm))
        {
   
            if(!isset($appsRoutes[$this->routeCurrent['app']]))
            {
                throw new ArgumentException('app {'.$this->routeCurrent['app'].'} is not defined in {'.self::NAME_JSON_ROUTES.'}!',500);
            }
    
            $appRoute = $appsRoutes[$this->routeCurrent['app']];
    
            if(!isset($appRoute[$this->routeCurrent['app']]))
            {
                throw new ArgumentException('default route {'.$this->routeCurrent['app'].'} not found in {'.self::NAME_JSON_ROUTES.'}!',404);
            }
        
            $response = [
                'app' => $this->routeCurrent['app'],
                'path' => $appRoute[$this->routeCurrent['app']]['path'],
                'alias' => $this->routeCurrent['app'],
                'current' => $appRoute,
            ];
        }

        return $response;
    }

    private function getRouteByOnTranslateDomain($appsRoutes,$searchTerm)
    {
        $response = [];
     
        if($this->translateDomain &&  !empty($searchTerm))
        {
  
            if(!isset($appsRoutes[$this->routeCurrent['app']]))
            {
                throw new ArgumentException('app {'.$this->routeCurrent['app'].'} is not defined in {'.self::NAME_JSON_ROUTES.'}!',500);
            }
    
            $appRoute = $appsRoutes[$this->routeCurrent['app']];
    
            if(!isset($appRoute[$searchTerm]))
            {
                throw new ArgumentException('route {'.$searchTerm.'} not found in {'.self::NAME_JSON_ROUTES.'}!',404);
            }
        
            $response = [
                'app' => $this->routeCurrent['app'],
                'path' => $appRoute[$searchTerm]['path'],
                'alias' => $searchTerm,
                'current' => $appRoute,
            ];
        }

        return $response;
    }  

    private function standardizeDefaultRoute($appRoute,$app)
    {
        $routes = array_keys($appRoute);
        $route = [];
        $c = count($routes);
        

        for($i = 0; $i < $c; ++$i)
        {
            $k = mb_strtolower($routes[$i]);
         
            if($k != $app)
            {
                continue;
            }

            $default = $appRoute[$routes[$i]];
            unset($appRoute[$routes[$i]]);
            $appRoute[$app] = $default;
            break;
        }
    }

    private function getAppRoutesTroughJson(Array $appsRoutes,string $app)
    {
        $apps = array_keys($appsRoutes);
        $c = count($apps);

        $appRoute = [];

        for($i = 0; $i < $c; ++$i)
        {
            $k = mb_strtolower($apps[$i]);
         
            if($k === $app)
            {
                $appRoute = $appsRoutes[$apps[$i]];
                break;
            }

            continue;
        }
        
        $this->standardizeDefaultRoute($appRoute,$app);

        return $appRoute;
    }

    private function getDefaultRouteByOffTranslateDomain($appsRoutes,$partsSearchTerm)
    {
        $response = [];

        $countPartsSearchTerm = count($partsSearchTerm);
  
        if($countPartsSearchTerm < 1 && !$this->translateDomain)
        {
            throw new ArgumentException('translate domain is off, is not possible determinate app!',403);
        }
        else if($countPartsSearchTerm == 1)
        {
            $app = mb_strtolower(trim($partsSearchTerm[0]));

            $appRoute = $this->getAppRoutesTroughJson($appsRoutes,$app);
           
            if(empty($appRoute))
            {
                throw new ArgumentException('app {'.$partsSearchTerm[0].'} is not defined in {'.self::NAME_JSON_ROUTES.'}!',500);
            }

            $appKeyDefaultRoute = isset($appRoute[$app]) ? $app : mb_strtolower($app);
            
            if(!isset($appRoute[$appKeyDefaultRoute]))
            {
                throw new ArgumentException('route {'.$app.'} not found in {'.self::NAME_JSON_ROUTES.'}!',404);
            }
        
            $response = [
                'app' => $app,
                'app_key_default_route' => $appKeyDefaultRoute,
                'path' => $appRoute[$appKeyDefaultRoute]['path'],
                'alias' => implode('/',$partsSearchTerm),
                'current' => $appRoute[$appKeyDefaultRoute],
            ];
        }

        return $response;
    }
    
    private function getRouteByOffTranslateDomain($appsRoutes,$partsSearchTerm)
    {
        $response = [];

        $countPartsSearchTerm = count($partsSearchTerm);

        if($countPartsSearchTerm < 1 && !$this->translateDomain)
        {
            throw new ArgumentException('translate domain is off, is not possible determinate app!',403);
        }
        else if($countPartsSearchTerm > 1)
        {
            $app = mb_strtolower(trim($partsSearchTerm[0]));

            if(!isset($appsRoutes[$app]))
            {
                throw new ArgumentException('app {'.$partsSearchTerm[0].'} is not defined in {'.self::NAME_JSON_ROUTES.'}!',500);
            }
    
            $appRoute = $appsRoutes[$app];
  
            unset($partsSearchTerm[0]);

            $searchTerm = implode('/',$partsSearchTerm);

            if(!isset($appRoute[$searchTerm]))
            {
                throw new ArgumentException('route {'.$searchTerm.'} not found in {'.self::NAME_JSON_ROUTES.'}!',404);
            }
    
            $response = [
                'app' => $app,
                'path' => $appRoute[$searchTerm]['path'],
                'alias' => implode('/',$partsSearchTerm),
                'current' => $appRoute[$searchTerm],
            ];
        }

        return $response;
    }  
    
    private function getRoute($appsRoutes,$searchTerm)
    {
        $response = [];

        if($this->translateDomain)
        {
            $response = $this->getDefaultRouteByOnTranslateDomain($appsRoutes,$searchTerm);

            if(empty($response))
            {
                $response = $this->getRouteByOnTranslateDomain($appsRoutes,$searchTerm);
            }
        }
        else
        {
            $partsSearchTerm = array_filter(array_values(explode('/',$searchTerm)));
           
            $response = $this->getDefaultRouteByOffTranslateDomain($appsRoutes,$partsSearchTerm);
     
            if(empty($response))
            {
                $response = $this->getRouteByOffTranslateDomain($appsRoutes,$partsSearchTerm);
            }
        }

        return $response;
    }

    private function getRouteBySearchTerm($appsRoutes,$searchTerm)
    {

        $response = [];

        $searchTerm = trim($searchTerm);
      
        $response = $this->getRoute($appsRoutes,$searchTerm);

        return $response;
    }

   

    public function getAppRoutes()
    {
        $response = [];

        try
        {

            $routesJsonPath =  PATH_APP.DIRECTORY_SEPARATOR.self::NAME_JSON_ROUTES;

            if(!file_exists($routesJsonPath))
            {
                throw new ArgumentException
                (
                    sprintf('file {%s} was not found at path: {%s}.',
                            self::NAME_JSON_ROUTES, 
                            PATH_APP
                        ),
                    404
                );
            }     

            $strFile = file_get_contents($routesJsonPath);

            $routes = \json_decode($strFile,true);
     
            if(!isset($routes['apps']))
            {
                throw new ArgumentException('define apps in {'.self::NAME_JSON_ROUTES.'}!',404);
            }

            //to lower all primary keys keys
            $response = array_change_key_case($routes['apps']);    
        }
        catch(\Throwable $th)
        {
            throw $th;
        }

        return $response;
    } 

    private function getAppBySearchUrl($appsRoutes)
    {
        $appName = null;

        if($this->translateDomain)
        {
            foreach($appsRoutes as $app => $routes)
            {
                if(!preg_match('`\b'.$app.'\b`',SERVER_NAME))
                {
                    continue;
                }
    
                $appName = $app;
    
                break;
            }    

            if(empty($appName))
            {
                throw new ArgumentException
                (
                    'Url does not contain a term identifying the application, are you sure you want to use translate domain as true?!',
                     404, 
                     [
                         ArgumentException::KEY_TITLE_EXCEPTION => ArgumentException::NOT_FOUND_TITLE,
                         ArgumentException::KEY_TYPE_EXCEPTION => ArgumentException::ERROR_TYPE_EXCEPTION
                     ]
                );
            }
        }

        return $appName;
    }

    private function appTranslate($appsRoutes)
    {
        if($this->translateDomain)
        {
            $this->routeCurrent['app'] = $this->getAppBySearchUrl($appsRoutes);
        }
    }

    public function publicResources(Array $resources = [])
    {
        foreach($resources as $ext => $mediatype)
        {
            if(!array_key_exists($ext,$this->resources) && !empty($mediatype) && preg_match('`([A-z]*\/[A-z]*)`',$mediatype))
            {
                $this->resources[$ext] = $mediatype;
            }
        }
        
        return $this;
    }

    private function translateResource($searchTerm)
    {
        $ext = trim(pathinfo($searchTerm, PATHINFO_EXTENSION));
        $folder = \stristr($searchTerm,'public',true);


        if(array_key_exists($ext,$this->resources) && $folder !== false)
        { 
            $path = $this->basePath.DIRECTORY_SEPARATOR.ltrim($searchTerm,DIRECTORY_SEPARATOR);
            if(file_exists($path))
            {
                header("Content-type:".$this->resources[$ext], true);
                exit(print(file_get_contents($path)));
            }
        }
    }

    private function requestApp()
    {
        $searchTerm = preg_replace('`\?.*`','',REQUEST_URI);

        $this->translateResource($searchTerm);

        if(!empty(PROJECT_NAME))
        {
            $searchTerm = preg_replace
            (
                [
                    '`\/'.PROJECT_NAME.'\/\b`is',
                    '`\b'.PROJECT_NAME.'\/\b`is',
                    '`\b'.PROJECT_NAME.'\b`is'
                ],
                ['','',''],
                $searchTerm
            );
        }
      
        $searchTerm = strpos($searchTerm,DIRECTORY_SEPARATOR) === 0 ? substr($searchTerm,1) : $searchTerm;

        $appsRoutes = $this->getAppRoutes();

        $this->appTranslate($appsRoutes);
   
        $this->routeCurrent = $this->getRouteBySearchTerm($appsRoutes,$searchTerm);

        if(empty($this->routeCurrent))
        {
            throw new ArgumentException
            (
                'Route not found or not configured!',
                 404, 
                 [
                     ArgumentException::KEY_TITLE_EXCEPTION => ArgumentException::NOT_FOUND_TITLE,
                     ArgumentException::KEY_TYPE_EXCEPTION => ArgumentException::ERROR_TYPE_EXCEPTION
                 ]
            );
        }

        $this->app = $this->getRegisteredApp($this->routeCurrent['app']);

        if(empty($this->app))
        {
            throw new ArgumentException
            (
                'app {'.(!empty($this->routeCurrent['app']) ? $this->routeCurrent['app'] : '').'} not found, configuration in routes.json is correct?!',
                 500
            );
        }

        $appArgs = $this->defineAppArgs();  

        $this->routeCurrent = array_merge($this->routeCurrent,$appArgs);

        $this->app->setProperty('routeCurrent',$this->routeCurrent);     
      
        if(empty($this->app))
        {
            throw new ArgumentException
            (
                'Route not found!',
                404,
                 [
                     ArgumentException::KEY_TITLE_EXCEPTION => ArgumentException::NOT_FOUND_TITLE,
                     ArgumentException::KEY_TYPE_EXCEPTION => ArgumentException::ERROR_TYPE_EXCEPTION
                 ]
            );
        } 
    }

    public static function load($translateDomain = false)
    {
        $class = __CLASS__;

        return empty(self::$instance) ? self::$instance = new $class($translateDomain) : self::$instance;
    }
}