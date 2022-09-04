<?php
/*
 * Copyright 2010 Leonardo Souza da Silva <allezo.lss@gmail.com>.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace Harp\lib\HarpService;

use Exception;

class ServiceBuilder implements ServiceBuilderInterface
{
    public $SplAutoload;
    private $ServiceContainer;
    private $dependencies = Array();
    /**
     * Inicializa instâncias de ServiceSplAutoload e ArrayObject ServiceContainer
     * @author Leonardo Souza(lss.leonardo.dev@gmail.com)
     * @version 1.0
     */
    public function __construct()
    {
       $this->SplAutoload =  ServiceSplAutoload::getInstance();
       
       $this->ServiceContainer = new \ArrayObject(Array(),\ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * 
     * @param String $serviceName Nome do serviço ao qual deseja-se obter
     * @return Mixed(String|null)
     * @author Leonardo Souza(lss.leonardo.dev@gmail.com)
     * @version 1.0
     */
    public function get($serviceName)
    {
        $GenericService = null;
        
        if(!empty($serviceName))
        {
            if($this->ServiceContainer->offsetExists($serviceName))
            {
                $GenericService = $this->ServiceContainer->offsetGet($serviceName);
            }
        }
        
        return $GenericService;
    }
    /**
     * Instância uma classe, resolvendo suas dependências.
     * @param String $class nome da classe que deve ser instânciada
     * @return Object
     * @throws Exception
     */
    private function resolveClass($class)
    {
        $reflector = new \ReflectionClass($class);

        if(!$reflector->isInstantiable())
        {
                throw new \Exception("[$class] não pode ser instanciada!");
        }

        $constructor = $reflector->getConstructor();

        if(is_null($constructor))
        {
                return new $class;
        }

        $parameters = $constructor->getParameters();
        $dependencies = $this->getDependencies($parameters);

        return $reflector->newInstanceArgs($dependencies);
    }    
  
    private function resolveNonClass(\ReflectionParameter $parameter)
    {
            if($parameter->isDefaultValueAvailable())
            {
                return $parameter->getDefaultValue();
            }

            throw new Exception("Não foi possível resolver a dependência!");
    }    
    
    private function getDependencies(Array $parameters)
    {
        $dependencies = [];

        if(!empty($parameters))
        {		
            foreach($parameters as $parameter)
            {
                $dependency = $parameter->getType();

                if(is_null($dependency))
                {
                    $dependencies[] = !isset($this->dependencies[$parameter->name]) ? $this->resolveNonClass($parameter) : $this->dependencies[$parameter->name];
                }
                else
                {      
                            
                    if(!isset($this->dependencies[$parameter->name]))
                    {
                        $obj = $this->resolveClass($dependency->getName());
                        
                        $dependencies[] = $obj;
                    }
                    else
                    {

                        $obj = $this->dependencies[$parameter->getName()];
                            
                        $dependencies[] = $obj;
                    }
                }
                            
            }           
        }

        return $dependencies;
    }
    
    private function getGenericService($nameService)
    {

        if(!$this->ServiceContainer->offsetExists($nameService))
        {
            $GenericService = new GenericService($nameService);
            $this->ServiceContainer->offsetSet($nameService,$GenericService);
        }

        $GenericService = $this->ServiceContainer->offsetGet($nameService);

        return $GenericService;
    }
 
    public function register($nameService,$name,$namespace = false)
    {   
        try
        {        

            if(!is_object($name) && $name != null && !$this->ServiceContainer->offsetExists($name))
            {
                $name = str_ireplace(['/'],['\\'],$name);
                
                $baseName = basename(str_ireplace(Array('\\','/'),DIRECTORY_SEPARATOR,$name));

                $Reflection = new \ReflectionClass($name);
               
                $constructorParams = $Reflection->getConstructor()->getParameters();
 
                $dependencies = $this->getDependencies($constructorParams);
            
                $GenericService = $this->getGenericService($nameService);

                if(!$namespace)
                {
                    $GenericService->set($baseName,$Reflection->newInstanceArgs($dependencies));
                }
                else
                {

                    $GenericService->set($name,$Reflection->newInstanceArgs($dependencies),$namespace);
                }  
            }
        }
        catch (\Exception $e)
        {
            throw $e;
        }

        return $this->ServiceContainer->offsetExists($nameService);  
    }
    
    public function registerInstance($nameService,$object,$nameInstance = null)
    {
        $nameInstance = !is_string($nameInstance) ? (is_object($object) ? get_class($object) : null) : $nameInstance;

        if(!empty($nameInstance))
        {
            $nameInstance = basename(str_ireplace(Array('\\','/'),DIRECTORY_SEPARATOR,$nameInstance));
                        
            if(!$this->ServiceContainer->offsetExists($nameInstance))
            {
               $GenericService = $this->getGenericService($nameService);
               $GenericService->set($nameInstance,$object);             
            }    

        }
        
        return $this->ServiceContainer->offsetExists(basename($nameInstance));
    }
    
    public function addDependency(Array $dependencies = [])
    {
        $this->dependencies = $dependencies;
    }
}
