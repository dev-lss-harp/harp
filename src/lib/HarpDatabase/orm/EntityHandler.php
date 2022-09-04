<?php
namespace Harp\lib\HarpDatabase\orm;

use Harp\lib\HarpCryptography\HarpCryptography;
use ReflectionClass;
use stdClass;
use Exception;
use Harp\app\api\modules\oauth2\entity\CodigoAutorizacaoEntity;
use Harp\app\apitorskovia\modules\utility\entity\CadastroTipoEntity;
use Harp\bin\ArgumentException;
use ReflectionMethod;

abstract class EntityHandler implements EntityHandlerInterface
{
    private $subClass;
    private $Attributes = Array();
    private $NameProperties;
    private $mapAttributes = Array();
    public  $Cryptography;
    
    protected function __construct($subClass)
    {            
        $this->subClass = $subClass;
                
        $this->setProperties();
    }

    private function defineAllProperties()
    {
        $this->NameProperties = new stdClass();

        $Obj = new ReflectionClass($this->subClass);

        $properties = $Obj->getProperties();

        foreach ($properties as $property)
        {
            $this->NameProperties->{$property->name} = $property->name; 
        }

        while ($ObjParent = $Obj->getParentClass()) 
        {
            if($ObjParent->getNamespaceName() != 'Harp\lib\HarpDatabase\orm\EntityHandler')
            {
                $properties = $ObjParent->getProperties();
                foreach ($properties as $property)
                {
                    $this->NameProperties->{$property->name} = $property->name; 
                }
            }

            $Obj = $ObjParent;
        }
    }
    
    private function setProperties()
    {       
        $properties = $this->defineAllProperties();

        if(is_array($properties))
        {
            foreach ($properties as $property)
            {
                $this->NameProperties->{$property->name} = $property->name; 
            }
        } 

        if(!empty($this->mapAttributes))
        {
            foreach($this->mapAttributes as $attr)
            {
                $method = self::METHOD_PREFIX_SET.ucfirst($attr);
             
                if(method_exists($this->subClass,$method))
                {
                    $this->NameProperties->{$attr} = $attr; 
                }
            }
        }
        
        return $this->NameProperties;
    }
    
    private function attributesToArray($object = null,Array $prevAttributes = Array())
    {
        if(!$object instanceof EntityHandler)
        {
            $object = $this;
        }

        $attrParentClass = null;

        if(!empty($prevAttributes))
        {
           $attrParentClass = end($prevAttributes); 
        }    

        $properties = (array)$object->setProperties();

        reset($properties);

        $properties = (object)$properties;

        foreach($properties as $property)
        {
            $method = self::METHOD_PREFIX_GET.ucfirst($property);

            if(method_exists($object,$method))
            {
                $value = $object->{$method}();

                if(!is_object($value))
                {
                  
                    if(!array_key_exists($property,$prevAttributes) && empty($attrParentClass))
                    {                    
                        $prevAttributes[$property] = $value;
                    }
                    else
                    { 
                        if(!empty($attrParentClass))
                        {
                           $prevAttributes[$property.$attrParentClass] = $value;
                             
                        }
                    }
                }
                else
                {
                    $prevAttributes[$property] = $property;

                    if($value instanceof EntityHandlerInterface)
                    { 
                        $toArray = $this->attributesToArray($value,$prevAttributes);
                        
                        $prevAttributes = array_merge($prevAttributes,$toArray);
                    }
                }
            }            
        }

        return $prevAttributes;
    }
    
    public function entityValidate(Array $attr = Array())
    {
        try
        {
            include_once(PATH_LIB_PRIVATE.'/HarpValidator/HarpEntityValidator.class.php');         
           
            $ValidatorEntity = new HarpEntityValidator($this);

            $ValidatorEntity->verifyMany($attr); 

            if($ValidatorEntity->HarpValidator->ExceptionValidator->count() > 0)
            {
                throw new ArgumentException($ValidatorEntity->HarpValidator->ExceptionValidator->toString(),'Warning','warning');
            }   
            
            return $this;
            
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
    }    
    

    private function removeEmptyAttrs()
    {
        $attrs = [];

        foreach($this->Attributes as $key => $val)
        {
            if(empty($val) || $val == $key)
            {
                continue;
            }

            $attrs[$key] = $val;
        }

        $this->Attributes = $attrs;
    }

    public function toArray($object = null,Array $prevAttributes = [],$noEmpty = false)
    {
        $this->Attributes = $this->attributesToArray($object,$prevAttributes);

        if($noEmpty)
        {
            $this->removeEmptyAttrs();
        }

        return $this->Attributes;
    }


    public function toArrayClear()
    {
        return $this->clearAndGet();
    }

    public function clearAndGet()
    {
        $this->toArray();
        $this->removeEmptyAttrs();
        
        return $this->Attributes;
    }
      
    public function toGenericClass($key = null)
    {
        if(is_string($key))
        {
            $GenericClass = new \stdClass();
            $GenericClass->{$key} = (Object)$this->toArray();
        }
        
        return isset($GenericClass) ? $GenericClass : (Object) $this->toArray();
    }
    
    public function setMapperAttributes(Array $attr)
    {
        $this->mapAttributes = $attr;
    }
    
    public function getMapperAttributes()
    {
        return $this->mapAttributes;
    }
    
    public function getNameProperties($a = false)
    {           
        return !$a ? $this->NameProperties : (array)$this->NameProperties;
    }
    
    public function getNameProperty($name)
    {        
        if(isset($this->NameProperties->{$name}))
        {
            return $this->NameProperties->{$name};
        }
        
        throw new Exception('Requested property {'.$name.'} does not exist in class {'.$this->getObjectName().'}'); 
    }

    public function serializeObject()
    {
        return serialize($this->subClass);
    }
    
    public function serializeArray()
    {
        return serialize($this->toArray());
    }
    
     
    /*private function setData($attrs,$data,$mapkeysData,$object,$attrSubClass = null)
    {
        foreach($attrs as $p => $v)
        {      
            $method = self::METHOD_PREFIX_GET.ucfirst($p);

            if(method_exists($object,$method))
            {
                $value = $object->{$method}();
                
                if($value instanceof EntityHandler)
                { 
                    $attrs = $value->toArray();
              
                    $this->setData($attrs, $data, $mapkeysData, $value,$p);
                }
                else 
                {
                    
                    $method = !isset($mapkeysData[$p]) ? self::METHOD_PREFIX_SET.ucfirst($p) : self::METHOD_PREFIX_SET.ucfirst($mapkeysData[$p]);

                    if(method_exists($object,$method))
                    {
                        $dataSet = (Array)$data;
                         
                        $k = trim($p.$attrSubClass);
                        
                        $k2 = basename(str_ireplace(Array('Entity','\\'),Array('','/'),get_class($object)).'->'.$p);
                        
                        if(array_key_exists($k,$dataSet))
                        {
                           $object->{$method}($dataSet[$k]);
                        }
                        else if(array_key_exists($k2,$dataSet))
                        {
                            $object->{$method}($dataSet[$k2]);
                        }
                    }
                }
            }            
        }
    }*/
    
    private function normalizeData($data)
    {
        $data = (Array)$data;
       
        $entity = $this->getEntityName();

        foreach($data as $i => $v)
        {
            $prop = str_ireplace($entity,'',$i);
            
            if(array_key_exists($prop,$data) && !empty($v))
            {
                $data[$prop] = $v;
            }
            else if(!array_key_exists($prop,$data))
            {
                $data[$prop] = $v;
            }
        }

        return $data;
    }

    private function dataAttrsNormalize($dataAttrs)
    {
        $newDataAttrs = [];

        foreach($dataAttrs as $k => $v)
        {
            $expK = explode('_',$k);
            $attr = $expK[0];

            if(count($expK) < 2)
            {
                continue;
            }  

            for($i = 1; $i < count($expK);++$i)
            {
                $attr .= ucfirst($expK[$i]);
            }

            $newDataAttrs[$attr] = $v;
        }

        $allAttrs = array_merge($dataAttrs,$newDataAttrs);

        return $allAttrs;
    }
    
    public function set($dataAttrs,EntityHandlerInterface $obj = null,$normalize = true)
    {
        if($normalize)
        {
            $dataAttrs = $this->dataAttrsNormalize($dataAttrs);
        }

        $obj = ($obj instanceof EntityHandlerInterface) ? $obj : $this;



        $attrs = $obj->toArray();
    
        foreach($attrs as $attr => $at)
        {

            $rc = new ReflectionClass($obj);

            $method = 'get'.ucfirst($attr);

            if($rc->hasMethod($method))
            {
                $ob = $obj->{$method}();

                if($ob instanceof EntityHandlerInterface)
                { 
                    $this->set($dataAttrs,$ob,false); 
                }
                else
                {
                    $setMethod = 'set'.ucfirst($attr);

                    $attrChildren = trim(str_ireplace(['Entity'],[''],$rc->getShortName()));
                  
                    if($rc->hasMethod($setMethod) && isset($dataAttrs[$attr]))
                    {
                        $obj->{$setMethod}($dataAttrs[$attr]);
                        unset($dataAttrs[$attr]);
                    }
                    else if($rc->hasMethod($setMethod) && isset($dataAttrs[$attr.$attrChildren]))
                    {
                        $obj->{$setMethod}($dataAttrs[$attr.$attrChildren]);
                        unset($dataAttrs[$attr]);
                    }
                }
                                                
            }
        }

        return $this;
    }

    public static function getClassName()
    {
        return get_called_class();
    } 

    public function getEntityName(EntityHandler $object = null)
    {
        $nameClass = is_null($object) ? self::getClassName() : get_class($object);
        
        $entityName = basename(str_ireplace(Array('Entity','\\'),Array('','/'),$nameClass));
        
        return $entityName;
    } 
    
    public function cloneThis()
    {
        return clone $this;
    }

    private function instanceCryptography()
    {        
        if(!$this->Cryptography instanceof HarpCryptography)
        {
            $this->Cryptography = new HarpCryptography();
        }
    }
    
    public function encrypt(Array $fields,&$object = null)
    {  
        $this->instanceCryptography();
      
        $object = is_null($object) ? $this : $object;
        
        if(!empty($fields))
        {
            foreach($fields as $i => $f)
            {
                if(substr_count($f,'->') == 1)
                {
                    $p = explode('->',$f);
                    
                    $method = self::METHOD_PREFIX_GET.ucfirst($p[0]);
                    $method2 = self::METHOD_PREFIX_GET.ucfirst($p[1]);
                    
                    if(method_exists($object,$method))
                    {
                        $EntityHandlerObject = $object->{$method}();

                        if(is_object($EntityHandlerObject) && $EntityHandlerObject instanceof EntityHandler)
                        {   

                            $typeClass = get_class($EntityHandlerObject);
                          
                            if(!$object instanceof  $typeClass)
                            {
                                $this->encrypt($fields,$EntityHandlerObject);
                            }
                        }
                    }
                    else if(method_exists($object,$method2))
                    {
                        
                        $currentEntityName = $this->getEntityName($object);
                   
                        $attribute = $currentEntityName.'->'.$p[1];

                        if(in_array($attribute,$fields))
                        {    
                  
                                    $val = $object->{$method2}();
                                    $val = $this->Cryptography->encrypt($val);
                                    $methodSet = self::METHOD_PREFIX_SET. ucfirst($p[1]);
                                    $object->{$methodSet}($val);
                                    unset($fields[$i]);
                        }    

                    }
                }

            }
            
        }
        
        return $this;
    }   
    
    private function __decrypt($object,$key,$method,$fields,$p)
    {
        $currentEntityName = $this->getEntityName($object);

        $attribute = $currentEntityName.'->'.$p[1];

        if(in_array($attribute,$fields))
        {    
                    $val = $object->{$method}();
                    $dVal = $this->Cryptography->decrypt($val);
                    $methodSet = self::METHOD_PREFIX_SET. ucfirst($p[1]);
                    $object->{$methodSet}($dVal);
                    unset($fields[$key]);
        }          
    }
        
    public function decrypt(Array $fields,&$object = null)
    {  
        $this->instanceCryptography();
      
        $object = is_null($object) ? $this : $object;

        if(!empty($fields))
        {
            foreach($fields as $i => $f)
            {
                if(substr_count($f,'->') == 1)
                {
                    $p = explode('->',$f);
                    
                    $method = self::METHOD_PREFIX_GET.ucfirst($p[0]);
                    $method2 = self::METHOD_PREFIX_GET.ucfirst($p[1]);
                    
                    if(method_exists($object,$method))
                    {
                        $EntityHandlerObject = $object->{$method}();

                        if(is_object($EntityHandlerObject) && $EntityHandlerObject instanceof EntityHandler)
                        {   

                            $typeClass = get_class($EntityHandlerObject);
                         
                            if(!$object instanceof  $typeClass)
                            {
                                $this->decrypt($fields,$EntityHandlerObject);
                            }
                        }
                        else
                        {
                            $this->__decrypt($object, $i, $method2, $fields, $p);
                        }
                    }
                    else if(method_exists($object,$method2))
                    {  
                        $this->__decrypt($object, $i, $method2, $fields, $p);
                    }
                }

            }
            
        }
        
        return $this;
    }
    
}
