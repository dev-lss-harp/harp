<?php
namespace Harp\bin;

//use Harp\bin\HarpServerRequest;
use Harp\bin\HarpController;
use Harp\bin\ArgumentException;
use Harp\bin\View;
use Harp\enum\RouteEnum;
use Throwable;

class HarpProcess
{
    private $Application;
    private $plainExecution;
    private $ReflectionClass;
    private $beforeReturn;
    private $routeCurrent;
    
    
    public function __construct(HarpApplicationInterface $Application)
    {
        $this->Application = $Application;

        $this->routeCurrent = $this->Application->getProperty(RouteEnum::class);

        $this->plainExecution = [
            'construct' => [],
            'before' => [],
            'after' => [],
            'main' => []
        ];
        
        $this->extractArgsMethods();
        
        return $this;
    }
    
    
    private function parseLiteralArgs($values,&$plainExecution)
    {
            $idx = count($plainExecution['arguments']);
            foreach($values as $val)
            {
                $plainExecution['arguments'][$idx] = $val; 
                ++$idx;
            }
    }
    
    private function parseGetArgs($keys,&$plainExecution)
    {
            $params = $this->Application->getProperty(HarpHttpMessage::class)->getQuery();

            $idx = count($plainExecution['arguments']);
            if(isset($keys[0]) && $keys[0] == '*')
            {
                $plainExecution['arguments'][$idx] = $params;
                ++$idx;
            }
            else
            {
                foreach($keys as $key)
                {
                    $plainExecution['arguments'][$idx] = null;

                    if(isset($params[$key]))
                    {
                        $plainExecution['arguments'][$idx] = $params[$key];
                    }

                    ++$idx;
                }
            }
    }

    private function parsePostArgs($keys,&$plainExecution)
    {
            $params = $this->Application->getProperty(HarpHttpMessage::class)->getBody();
     
            $idx = count($plainExecution['arguments']);

            if(isset($keys[0]) && $keys[0] == '*')
            {
                $plainExecution['arguments'][$idx] = $params;
                ++$idx;
            }
            else
            {
                foreach($keys as $key)
                {
                    $plainExecution['arguments'][$idx] = null;

                    if(isset($params[$key]))
                    {
                        $plainExecution['arguments'][$idx] = $params[$key];
                    }

                    ++$idx;
                }
            }    
    }    

    private function parsePropertiesArgs($args,&$plainExecution)
    {
        $idx = count($plainExecution['arguments']);
        foreach($args as $arg)
        {
            $arg = trim($arg);
            $prop = $this->Application->getProperty($arg);
            $plainExecution['arguments'][$idx] = $prop;
            ++$idx;
        }     
    }

    private function parseArgs($args,&$plainExecution)
    {

        foreach($args as $type => $arguments)
        {
           
            if($type == 'literal')
            {
                $this->parseLiteralArgs($arguments,$plainExecution);
            }
            else if($type == 'post')
            {
                $this->parsePostArgs($arguments,$plainExecution);
            }
            else if($type == 'get')
            {
                 $this->parseGetArgs($arguments,$plainExecution);
            }
            else if($type == 'property')
            { 
                $this->parsePropertiesArgs($arguments,$plainExecution);
            }
        }
    }

    
    private function extractTo($required,&$plainExecution)
    {

        foreach($required as $methodName => $attributes)
        {
            $plainExecution[$methodName]['method'] = $methodName;
            $plainExecution[$methodName]['expected'] = isset($attributes['expected']) ? $attributes['expected'] : '';
            $plainExecution[$methodName]['callback']["success"] = isset($attributes['callback']['success']) ? $attributes['callback']['success'] : '';
            $plainExecution[$methodName]['callback']["failed"] = isset($attributes['callback']['failed']) ? $attributes['callback']['failed'] : '';
            $plainExecution[$methodName]['arguments'] = isset($attributes['arguments']) ? $attributes['arguments'] : [];

            if(isset($attributes['args']))
            {  
                $this->parseArgs($attributes['args'],$plainExecution[$methodName],'args');
            }
        }
    }

    private function extractMainAction(Array $route)
    {
        if(!isset($route['path']))
        {
            throw new ArgumentException('Path not found, impossible determinate the action method!',404);
        }
        
        $action = mb_substr(strrchr($route['path'],"/"),1);
        
        return $action;
    }
    
    
    private function makeConstructor(Array $route)
    {
        $constructorArgs = isset($route['constructor']['args']) ? $route['constructor']['args'] : [];

        $constructor = [
            '__construct' => [
                    'expected' => '',
                    'args' => [],
            ]
        ];
        
        if(!empty($constructorArgs))
        {
            $constructor['__construct']['args'] = $constructorArgs;                  
        }

        $this->extractTo($constructor,$this->plainExecution['construct']); 
    }
    
    private function makeMainAction(Array $route)
    {
        $name = $this->extractMainAction($route);
        $args = isset($route['args']) ? $route['args'] : [];
        $return = isset($route['expected']) ? $route['expected'] : '';  

        $main = [
            $name =>[
                'expected' => $return,
                'args' => $args
            ]
        ];
     
        $this->extractTo($main,$this->plainExecution['main']);
    }
    
    //extract arguments to call constructor 
    //and method action
    private function extractArgsMethods()
    {
        $route = $this->routeCurrent[RouteEnum::Current->value];

        $this->makeConstructor($route);
  
        if(isset($route['required']['before']))
        {

            $this->extractTo($route['required']['before'],$this->plainExecution['before']);
        }
        
        if(isset($route['required']['after']))
        {
            $this->extractTo($route['required']['after'],$this->plainExecution['after']);
        }
    
        $this->makeMainAction($route);
   }
   
   private function expectedStatusResponseAvaliate($expected,&$Response)
   {
        if($Response->getStatusCode() != $expected)
        {
            $resp = $Response->getResponse(true);
            $message = $resp['message'] ?? 'Expected response code {%s}, found {%s}';
            $code = $Response->getStatusCode();
            throw new \Exception($message,$code);
        }
   }
      
   private function evaluateReturn($plain,$return)
   {
        $expected = $plain['expected'];
    
       if($expected != 'void' && !empty($expected))
       {
  
               if(!($return instanceof HarpResponse) && !($return instanceof View))
               {
                   throw new ArgumentException("Expected object of type {".(HarpResponse::class)."}");
               }
               else if($return instanceof HarpResponse)
               {
                    $this->expectedStatusResponseAvaliate($expected,$return);
               }
       }
       
       return $return;
   }
   
   private function getMethodParameters(\ReflectionMethod $Ref,Array $arguments)
   {    
       $cp = count($Ref->getParameters());
       $ca = count($arguments);

       if($cp != $ca)
       { 
           for($i = 0; $i < $cp; ++$i)
           {
                if(!isset($arguments[$i]))
                {
                    $arguments[$i] = null;
                }
           }
       }

       return $arguments;
   }
   
   private function executeMethod($instance,$method,$arguments)
   {
        $return = null;

        try
        {
                if(!method_exists($instance,$method))
                {
                    throw new \Exception('Method {'.$method.'} is not defined for class {'.(get_class($instance)).'}');
                }
     
                $ReflectionMethod = $this->ReflectionClass->getMethod($method);

                $arguments = $this->getMethodParameters($ReflectionMethod,$arguments);
  
                $ReflectionMethod->setAccessible(true);
               
                $return = $ReflectionMethod->invokeArgs($instance,$arguments); 
        }
        catch(Throwable $th)
        {
            throw $th;
        }

        return $return;
   }

   private function executeCallback($instance,$plain,$prevReturn,$type = null)
   {
       $response = ['statusCallback' => false,'return' => null, 'type' => $type];

       if(!empty($type) && ($type == 'success' || $type == 'failed'))
       {

            if(!empty($plain['callback'][$type]))
            {
                $method = key($plain['callback'][$type]);

                $callbackPlain = $plain['callback'][$type][$method];
                $callbackPlain['method'] = $method;

               
                $callbackPlain['args']['literal'][] = $prevReturn;
           
                if(isset($callbackPlain['args']))
                {
                    $this->parseArgs($callbackPlain['args'],$callbackPlain);
                }

                $return = $this->executeMethod($instance,$callbackPlain['method'],$callbackPlain['arguments']);

                $response['statusCallback'] = true;
                $response['return'] = $return;
            } 
       }

       return $response;
   }

   /*
   *
   */
   private function storeReturn($return)
   {
        $this->beforeReturn = $return;
   }

   private function injectBeforeReturn($plain)
   {
        if(!is_null($this->beforeReturn))
        {
            array_push($plain['arguments'],$this->beforeReturn);
        }
        
        return $plain;
   }

   private function verifyHttpMethod($plainExecution)
   {
        $route = $this->routeCurrent[RouteEnum::Current->value];
        $requestMethod = !empty($route['requestMethod']) ? $route['requestMethod'] : 'GET';
        $method = $this->Application->getProperty(HarpHttpMessage::class)->getServerRequest()->getMethod();

        if($method != $requestMethod)
        {
            (new HarpResponse())->ClientError(
                [
                    'message' => sprintf('This resource does not accept the {%s} request method!',$method),
                ],
                405
            )->json();
        }
   }

   private function executePlain($plainExecution,$instance)
   {

        $this->verifyHttpMethod($plainExecution);

        $return = null;
     
        foreach($plainExecution as $key => $groupPlain)
        {                    
            foreach($groupPlain as $plain)
            {
                if(!empty($plain))
                {
                    $plain = $this->injectBeforeReturn($plain);
                    
                    $return = $this->executeMethod($instance,$plain['method'],$plain['arguments']);
                    //Guarda o retorno do método para injetar no próximo método
                    $this->storeReturn($return); 
                 
                    if($key != 'construct')
                    {              
                        $response = $this->evaluateReturn($plain,$return);

                        if($response instanceof View)
                        {
                            return $return;
                        }
                        else if($response instanceof HarpResponse)
                        {
                            $response->saveResponse($plain['method'],$response);

                            if(!$response->hasHeader(HarpResponse::FAILURE))
                            {
                                throw new \Exception('The response does not have the mandatory {FAILURE} header.',500);
                            }

                            if($response->getOneHeader(HarpResponse::FAILURE) == 1)
                            {
                                $response = $this->executeCallback($instance,$plain,$return,'failed');
                                if($response['statusCallback']){ $return = $response['return']; }
                                break 2;
                            }
                            else
                            {
                                $response = $this->executeCallback($instance,$plain,$return,'success');

                                if($response['statusCallback']){ $return = $response['return']; } 
                            }
                        }
                    }
                }        
            }
        }
        
        return $return;
   }
   
   private function instanceAbstractController()
   {
            $RefController = new \ReflectionClass('Harp\bin\HarpController');
            $RefMethod = $RefController->getMethod('inject');
            $RefMethod->setAccessible(true);
            $RefMethod->invokeArgs(null,[$this->Application]);
            $RefMethod->setAccessible(false);
   }   
   
   private function instanceAbstractModel()
   {
        $Reflection =  new \ReflectionClass('Harp\bin\HarpModel');
        $RefMethod = $Reflection->getMethod('inject');
        $RefMethod->setAccessible(true);
        $RefMethod->invokeArgs(null,[$this->Application]);
        $RefMethod->setAccessible(false);
   }
   
   public function run()
   {

       $return = null;

       try
       {
            $this->instanceAbstractController();
            $this->instanceAbstractModel();

            $controller = $this->routeCurrent[RouteEnum::ControllerPath->value];
     
            $this->ReflectionClass = new \ReflectionClass($controller);
          
            $instance = $this->ReflectionClass->newInstanceWithoutConstructor();
          
            if(!$instance instanceof HarpController)
            {
                throw new ArgumentException('The controller must be an instance of HarpController class.',500);
            }

            $return = $this->executePlain($this->plainExecution,$instance);            
       }
       catch(\Throwable $th)
       {
            throw $th;
       }
       
       return $return;
   }
}