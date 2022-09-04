<?php
namespace Harp\lib\HarpValidator;

use Harp\lib\HarpDatabase\orm\EntityHandlerInterface;
use Harp\lib\HarpDatabase\orm\EntityHandler;

use Lib\pvt\HarpValidator\AnnotationValidatorEnum;
use ReflectionMethod;
use Exception;

class HarpEntityValidator
{
    public $HarpValidator;
    public $HarpSanitization;
    public $HarpFormatter;
    private $methods;
    private $attributesClass;
    private $attributesForValidation = Array();
    private $Enum;
    private $Object;
        
    public function __construct(EntityHandlerInterface $Object)
    {
        $this->HarpValidator = new HarpValidator();
        $this->HarpValidator->disabledDefaultMessages(true);
        
        $this->HarpSanitization = new HarpSanitization();
        
        $this->HarpFormatter = new HarpFormatter();
        
        $this->Object = &$Object;
       
        $this->addAttributesClass();

        $this->Enum = new AnnotationValidatorEnum();
        
        $this->methods = Array();
        
        $this->attributesForValidation = $this->Object->getNameProperties(true);
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
    
    private function getAttributesForValidation($nameAttr = null)
    {
        $methodsToExecute = Array();
        
        if(!empty($nameAttr))
        {
            $key = array_search($nameAttr,$this->Object->getNameProperties(true));

            if($key !== false)
            {
                $methodsToExecute = Array($this->attributesForValidation[$key]);
            }
        } 

        $filter = array_filter($methodsToExecute,function($v){ return isset($this->attributesClass[$v]);});

        if(empty($filter) && !empty($this->attributesForValidation))
        {
            $filter = $this->attributesForValidation;
        }
        else if(empty($filter) && empty($this->attributesForValidation))
        {
            $filter = $this->attributesClass;
        }
        
        foreach($filter as $i => $name)
        {
            $methodSet = AnnotationValidatorEnum::PREFIX_SET.ucfirst($name);
            $methodGet = AnnotationValidatorEnum::PREFIX_GET.ucfirst($name);

            if(!method_exists($this->Object,$methodSet) || !method_exists($this->Object,$methodGet))
            { 
                unset($filter[$i]);
            }
            else
            {
                $this->methods[$i][AnnotationValidatorEnum::ATTRIBUTE_NAME] = $name;
                $this->methods[$i][AnnotationValidatorEnum::METHOD_SET] = $methodSet;
                $this->methods[$i][AnnotationValidatorEnum::METHOD_GET] = $methodGet;
                $this->methods[$i][AnnotationValidatorEnum::MSG_TO_VALUE_NOT_VALIDATED] = null;
            }
        }
        
        return $this->methods;
    }
    
    private function applyOthersActions($s,&$m,$keyMethods)
    {     
        if($s)
        { 
            $CurrentObj =  $m[AnnotationValidatorEnum::ENTITY_OBJECT];
            
            $mtdGet = $m[AnnotationValidatorEnum::METHOD_GET][$keyMethods];
            $mtdSet = $m[AnnotationValidatorEnum::METHOD_SET][$keyMethods];
            
            $value = $CurrentObj->{$mtdGet}();
            $value = $this->applySanitization($m,$value); 
            $value = $this->applyFormatter($m,$value);
            $CurrentObj->{$mtdSet}($value); 
        }
    }

    private function validate(Array &$m,$keyMethods)
    {        
        $s = false;
        
        try
        {
            $nameMethod = substr($m[AnnotationValidatorEnum::ANNOTATION_METHOD],1);

            if(!method_exists($this->HarpValidator,$nameMethod))
            {
                throw new Exception($nameMethod.'is not a valid method for class {'.get_class($this->HarpValidator).'}'); 
            }

            $CurrentObj =  $m[AnnotationValidatorEnum::ENTITY_OBJECT];

            $mtd = $m[AnnotationValidatorEnum::METHOD_GET][$keyMethods]; 

            $args = Array('value' => $CurrentObj->{$mtd}());

            $argsMethod = $m[AnnotationValidatorEnum::PARAMS_METHOD];

            if(!empty($argsMethod))
            {
                $argsMethod = explode(',',$argsMethod);

                $args = array_merge($args,$argsMethod);
            }

            $s = call_user_func_array(Array($this->HarpValidator,$nameMethod),$args);

            if(!$s && !empty($m[AnnotationValidatorEnum::MSG_TO_VALUE_NOT_VALIDATED]))
            {
                $this->HarpValidator->ExceptionValidator->addMessage($m[AnnotationValidatorEnum::MSG_TO_VALUE_NOT_VALIDATED]);
            }
            else if($s)
            {
                 $this->applyOthersActions($s,$m,$keyMethods);
            }

        }
        catch(Exception $ex)
        {
            throw $ex;
        }

        return $s;
    }
    
    public function setAttributesForValidation(Array $attributes)
    {
        $this->attributesForValidation = $attributes;
    } 
    
    
    private function applySanitization(&$m,$value)
    {    
        if(!empty($m[AnnotationValidatorEnum::ANNOTATION_SANITIZATION]))
        {
            foreach($m[AnnotationValidatorEnum::ANNOTATION_SANITIZATION] as $mt => $t)
            {   
                if(method_exists($this->HarpSanitization,$mt))
                {
                   $t['params'] = str_ireplace('@value',$value,$t['params']);
                   
                   $paramsSanitize = explode('|',$t['params']);
                   
                   $value = call_user_func_array(Array($this->HarpSanitization,$mt),$paramsSanitize);
                }
            }       
        }   
        
        unset($m[AnnotationValidatorEnum::ANNOTATION_SANITIZATION]);
        
        return $value;
    }
    
    private function applyFormatter(&$m,$value)
    {
        if(!empty($m[AnnotationValidatorEnum::ANNOTATION_FORMATTER]))
        {
            foreach($m[AnnotationValidatorEnum::ANNOTATION_FORMATTER] as $mt => $t)
            {
                if(method_exists($this->HarpFormatter,$mt))
                {
                   $t['params'] = str_ireplace('@value',$value,$t['params']);
   
                   $paramsFormatter = explode('|',$t['params']);
                   
                   $value = call_user_func_array(Array($this->HarpFormatter,$mt),$paramsFormatter);
                }
            }          
        }   
        
        unset($m[AnnotationValidatorEnum::ANNOTATION_FORMATTER]);
      
        return $value;
    }    
        
    
    
    private function toSanitization(&$v)
    {   
        $sanit = preg_grep('#'.AnnotationValidatorEnum::ANNOTATION_SANITIZATION.'#',$v);
        
        $methodsToSanitization = Array();
        
        if(!empty($sanit))
        {    
            $tagSanit = reset($sanit);

            $regex = '#'.AnnotationValidatorEnum::ANNOTATION_SANITIZATION.'\((.*)\)#i';

            $config = Array();

            preg_match($regex,$tagSanit,$config);

            if(empty($config) || !isset($config[1]))
            {
                throw new Exception('Sintax error for: '.AnnotationValidatorEnum::ANNOTATION_SANITIZATION); 
            }

            $strMethodsToSanitization = str_ireplace(Array("/","\n","\t","\r","\r\n"),'',$config[1]);

            $regex = '#(.*)\((.*)\)#i';

            while(!empty($strMethodsToSanitization))
            {
                $pos = strpos($strMethodsToSanitization,'),');
                
                if($pos !== false)
                {                   
                    $d = substr($strMethodsToSanitization,0,($pos + 1));

                    preg_match($regex, $d,$p);

                    if(isset($p[1],$p[2]))
                    {
                        $methodsToSanitization[$p[1]] =  Array('name' => $p[1],'params' => $p[2]);
                    }

                    $strMethodsToSanitization = substr($strMethodsToSanitization,($pos + 2));
                }
                else
                {                   
                    preg_match($regex,$strMethodsToSanitization,$p);

                    if(isset($p[1],$p[2]))
                    {
                        $methodsToSanitization[$p[1]] =  Array('name' => $p[1],'params' => $p[2]);
                    }

                    $strMethodsToSanitization = null;
                }
            }
            
            unset($v[key($sanit)]);
        }
 
        return $methodsToSanitization;
    }
    
    private function toFormatter(&$f)
    {
        $fmt = preg_grep('#'.AnnotationValidatorEnum::ANNOTATION_FORMATTER.'#',$f);
   
        $methodsToFormatter = Array();
        
        if(!empty($fmt))
        {
                $tagFormatter = reset($fmt);

                $methodsToFormatter = Array();

                $regex = '#'.AnnotationValidatorEnum::ANNOTATION_FORMATTER.'\((.*)\)#i';
                $config = Array();

                preg_match($regex,$tagFormatter,$config);

                if(empty($config) || !isset($config[1]))
                {
                    throw new Exception('Sintax error for: '.AnnotationValidatorEnum::ANNOTATION_FORMATTER); 
                }
                
                $strMethodsToFormatter = str_ireplace(Array("/","\n","\t","\r","\r\n",'chr(42)','chr(27)'),Array('','','','','','*','/'),$config[1]);

                $regex = '#(.*)\((.*)\)#i';

                while(!empty($strMethodsToFormatter))
                {
                    $pos = strpos($strMethodsToFormatter,'),');
                    
                    if($pos !== false)
                    {                   
                        $d = substr($strMethodsToFormatter,0,($pos + 1));

                        preg_match($regex, $d,$p);

                        if(isset($p[1],$p[2]))
                        {
                            $methodsToFormatter[$p[1]] =  Array('name' => $p[1],'params' => $p[2]);
                        }

                        $strMethodsToFormatter = substr($strMethodsToFormatter,($pos + 2));
                    }
                    else
                    {                   
                        preg_match($regex,$strMethodsToFormatter,$p);

                        if(isset($p[1],$p[2]))
                        {
                            $methodsToFormatter[$p[1]] =  Array('name' => $p[1],'params' => $p[2]);
                        }

                        $strMethodsToFormatter = null;
                    }
                }

            unset($f[key($fmt)]);     
        }
   
        return $methodsToFormatter;
    }
   private function formatToExcution($attrs,$data,$object,$methodsToExecution,$attrParentClass = null)
   { 
        foreach($data as $p)
        {
            $p2 = explode('->',$p);

            if(!isset($p2[1]))
            {
                $p2[1] = $p2[0];
                $p2[0] = $object->getEntityName();
            }
                              
            if($object->getEntityName() == $p2[0])
            { 
                $methodSet = AnnotationValidatorEnum::PREFIX_SET.ucfirst($p2[1]);
                $methodGet = AnnotationValidatorEnum::PREFIX_GET.ucfirst($p2[1]);
            }
            else
            {
                $methodSet = AnnotationValidatorEnum::PREFIX_SET.ucfirst($p2[0]);
                $methodGet = AnnotationValidatorEnum::PREFIX_GET.ucfirst($p2[0]);
            }

            if(method_exists($object,$methodGet))
            {
                $value = $object->{$methodGet}();

                if($value instanceof EntityHandler)
                { 
                    $attrs = $value->toArray();

                    $methodsToExecution = $this->formatToExcution($attrs,$data,$value,$methodsToExecution,$p2[0]);
                }
                else 
                {
                    if(method_exists($object,$methodSet) || !method_exists($object,$methodGet))
                    {
                        $uid = !empty($attrParentClass) ? $attrParentClass : $object->getEntityName();
                        $methodsToExecution[$uid][AnnotationValidatorEnum::ENTITY_NAME] = $object->getEntityName();
                        $methodsToExecution[$uid][AnnotationValidatorEnum::ENTITY_OBJECT] = $object;
                        $methodsToExecution[$uid][AnnotationValidatorEnum::ATTRIBUTE_NAME] = $p2[1];
                        
                        usleep(1);
                        $keyMethods = uniqid();
                        $methodsToExecution[$uid][AnnotationValidatorEnum::METHOD_SET][$keyMethods] = $methodSet;
                        $methodsToExecution[$uid][AnnotationValidatorEnum::METHOD_GET][$keyMethods] = $methodGet;
                        $methodsToExecution[$uid][AnnotationValidatorEnum::MSG_TO_VALUE_NOT_VALIDATED] = null;
                    }
                }
            }            
        }

        return $methodsToExecution;
    }

    private function getAttrValidation(Array $attributes)
    {
        $methodsToExecution = Array();
                
        if(is_array($attributes) || is_object($attributes))
        {
            $attrs = $this->Object->toArray();
            
            $methodsToExecution = $this->formatToExcution($attrs,$attributes,$this->Object,$methodsToExecution);
            
        }
                
       return $methodsToExecution;
    }    
    
      
    public function verifyMany(Array $attributes)
    {
        $methodsToPerform = $this->getAttrValidation($attributes);

        $this->performValidation($methodsToPerform);
    }
    
    public function verify($nameAttr = null)
    {
        $methodsToPerform = $this->getAttrValidation(Array($nameAttr));
        
        $this->performValidation($methodsToPerform);        
    }  
    
    private function performValidation(Array $methodsToPerform)
    {
        try
        {
                foreach($methodsToPerform as $i => $m)
                {                 
                   
                    foreach($m[AnnotationValidatorEnum::METHOD_SET] as $keyMethods => $methodSet)
                    {
                        $ReflectionMethod = new ReflectionMethod($m[AnnotationValidatorEnum::ENTITY_OBJECT],$methodSet);
                       
                        $doc = $ReflectionMethod->getDocComment();
                        
                        $m2 = $m;
                        
                        if($doc !== false)
                        {
                            $doc = str_ireplace(Array("/","*","\n","\t","\r","\r\n"),'',$doc);

                            $doc = preg_replace('#\s\s+#i','',$doc);
                           // print_r($doc);
                            $regex = '#@validate\((.*)\)#i';
                        
                            if(preg_match($regex,$doc,$r) && isset($r[1]))
                            {
                                $v = array_filter(explode(';',$r[1]));

                                $m2[AnnotationValidatorEnum::ANNOTATION_SANITIZATION] = $this->toSanitization($v);

                                $m2[AnnotationValidatorEnum::ANNOTATION_FORMATTER] = $this->toFormatter($v);
                                
                                foreach($v as $ant)
                                {
                                    $mtd = substr($ant,0,strpos($ant,chr(40)));

                                    $regexMessage = '#@msg\{(.*?)\}#i';

                                    $message = Array();

                                    if(preg_match($regexMessage,$ant,$message) && isset($message[1]))
                                    {
                                        $m2[AnnotationValidatorEnum::MSG_TO_VALUE_NOT_VALIDATED] = $message[1];
                                    }

                                    //Pega somente a primeira parte da string
                                    //útil quando deseja-se passar uma string que é uma regex
                                    $pAnts = explode(',',$ant);

                                    $regexArgs = '#@param\{(.*)\}#i';

                                    $argsMethod = null;

                                    if(isset($pAnts[0]) && preg_match($regexArgs,$pAnts[0],$args) && isset($args[1]))
                                    {
                                        $argsMethod = $args[1];
                                    }
                                  
                                    $m2[AnnotationValidatorEnum::ANNOTATION_METHOD] = $mtd;
                                    $m2[AnnotationValidatorEnum::PARAMS_METHOD] = $argsMethod;
                                    
                                    $this->validate($m2,$keyMethods);
                                    
                                    $sValidate = $this->validate($m2,$keyMethods);            

                                    if(!$sValidate)
                                    {
                                        break 3;
                                    }
                                }
                            }
                        }
                        else
                        {
                            unset($methodsToPerform[$i]);
                        }                        
                    }
                    
                }                 
        }
        catch(Exception $ex)
        {
            throw new \Harp\bin\ArgumentException($ex->getMessage(),'Error','error');
        }
    }
}
