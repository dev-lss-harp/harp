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
namespace etc\HarpFilter;

//use etc\HarpFilter\IAnnotationValidate;
use etc\HarpFilter\FilterManagedValidationInterface;
use FilterEnum;
use ReflectionMethod;
include_once(__DIR__.'/FilterManagedValidationInterface.interface.php');
include_once(__DIR__.'/FilterEnum.class.php');
include_once(__DIR__.'/HarpFilterValidate.class.php');



class HarpEntityValidator
{
    public $HarpFilterValidate;
    private $methods;
    private $attributesClass;
    private $attributesForValidation = Array();
    private $Enum;
    private $Object;
        
    public function __construct(FilterManagedValidationInterface $Object)
    {
        $this->HarpFilterValidate = new \HarpFilterValidate();
        
        $this->Object = &$Object;
       
        $this->addAttributesClass();

        $this->Enum = new \FilterEnum();
        
        $this->methods = Array();
    }
    
    private function addAttributesClass()
    {
         $ReflectionClass = new \ReflectionClass($this->Object);
        
         $props   = $ReflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PRIVATE);
         
         foreach($props as $value)
         {
             $this->attributesClass[$value->name] = $value->name;
         }     
    }
    
    private function getAttributesForValidation()
    {
        $filter = array_filter($this->attributesForValidation, function($v){ return isset($this->attributesClass[$v]);});

        if(empty($filter))
        {
            $this->attributesForValidation = $this->attributesClass;
        }
        
        foreach($filter as $i => $name)
        {
            $methodSet = FilterEnum::PREFIX_SET.ucfirst($name);
            $methodGet = FilterEnum::PREFIX_GET.ucfirst($name);

            if(!method_exists($this->Object,$methodSet) || !method_exists($this->Object,$methodGet))
            { 
                unset($filter[$i]);
            }
            else
            {
                $this->methods[$i][FilterEnum::ATTRIBUTE_NAME] = $name;
                $this->methods[$i][FilterEnum::METHOD_SET] = $methodSet;
                $this->methods[$i][FilterEnum::METHOD_GET] = $methodGet;
            }
        }
        
        
    }
    
    private function validate($annotationMethod,$methodGet,$message,$argsMethod)
    {        
        $nameMethod = substr($annotationMethod,1);

        if(!method_exists($this->HarpFilterValidate,$nameMethod))
        {
            throw new Exception($nameMethod.' não é um método válido da classe HarpFilterValidate!'); 
        }
        
        $args = Array('value' => $this->Object->{$methodGet}());
   
        $keyMV = $this->Enum->getRelationshipMessage($annotationMethod);
        
        $this->HarpFilterValidate->setMessageValidation($keyMV,$message);
        
        if(!empty($argsMethod))
        {
            $argsMethod = explode(',',$argsMethod);
            
            $args = array_merge($args,$argsMethod);
        }
      
        return call_user_func_array(Array($this->HarpFilterValidate,$nameMethod),$args);
    }
    
    public function setAttributesForValidation(Array $attributes)
    {
        $this->attributesForValidation = $attributes;
    }   

    public function validationCheck()
    {
        $this->getAttributesForValidation();
     
        foreach($this->methods as $i => $m)
        {            
            $ReflectionMethod = new ReflectionMethod($this->Object,$m[FilterEnum::METHOD_SET]);
             
            $doc = $ReflectionMethod->getDocComment();

            if($doc !== false)
            {
                $doc = str_ireplace(Array("/","*","\n","\t","\r","\r\n"),'',$doc);
                
                $doc = preg_replace('#\s\s+#i','',$doc);
                
                $regex = '#@validate\((.*)\)#i';
                
                if(preg_match($regex,$doc,$r) && isset($r[1]))
                {
                    $v = array_filter(explode(';',$r[1]));

                    foreach($v as $ant)
                    {
                        $mtd = substr($ant,0,strpos($ant,chr(40)));

                        if($this->Enum->get($mtd))
                        {
                            $regexMessage = '#@message\{(.*?)\}#i';
                            
                            $message = Array();
                            
                            if(!preg_match($regexMessage,$ant,$message) || !isset($message[1]))
                            {
                                throw new Exception('Sintáxe inválida informe a mensagem com o seguinte padrão: @message{sua mensagem}!'); 
                            }
                          
                            $regexArgs = '#@args\{(.*?)\}#i';
                            
                            $argsMethod = null;
                            
                            if(preg_match($regexArgs,$ant,$args) && isset($args[1]))
                            {
                                $argsMethod = $args[1];
                            }
                            
                            $this->validate($mtd,$m[FilterEnum::METHOD_GET],$message[1],$argsMethod);
                        }
                    }
                }
            }
            else
            {
                unset($this->methods[$i]);
            }
        }
    }
    
}
