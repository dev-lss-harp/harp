<?php 
namespace Harp\lib\HarpEntityHandler;

use Exception;
use Harp\lib\HarpValidator\MultiValidator;
use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use ReflectionProperty;
use stdClass;

abstract class EntityHandler
{
    private $props;
    private $entities;
    private $entitiesCopy;

    protected function __construct()
    {          
        $this->extractProperties($this);
    }

    protected function getEntities()
    {
        return $this->entities;
    }

    //Extract all properties recursively 
    private function extractProperties($sub)
    {
        $Entity = new ReflectionClass($sub);

        $this->props[$Entity->getName()] = $Entity->getProperties();
        $this->entities[$Entity->getShortName()] = $Entity->getName();

        $namespace = $Entity->getNamespaceName();

        foreach($this->props[$Entity->getName()] as $prop)
        {
            $EntityComposite = sprintf('%s%s%s',$namespace,'\\',$prop->getName());

            if(\class_exists($EntityComposite))
            {
                $method = sprintf('get%s',$prop->getName());

                if(\method_exists($sub,$method))
                {
                    //it's necessary create a method get to get sub entity 
                    $instance = $sub->{$method}();

                    $this->extractProperties($instance);
                }
            }
        }
    }

    private function putPrefix($data,EntityHandler $obj)
    {
        $newData = [];

        $entities = $obj->getEntities();

        foreach($data as $key => $value)
        {
            $key = sprintf('%sEntity',$key);

            if(!array_key_exists($key,$entities))
            {
                continue;
            }

            $newData[$key] = $value;
        }

        if(empty($newData))
        {
            $intersect = array_intersect(array_keys($entities),array_keys($data));

            if(!empty($intersect))
            {
                $newData = $data;
            }
            else
            {
                $newData[key($entities)] = $data;
            }
        }

        return $newData;
    }

    private function callRecursive(EntityHandler $obj,array $props,string $callback,array $arg)
    {
            $propValue = null;

            foreach($props as $p)
            {
                if(array_key_exists($p->getName(),$this->entitiesCopy))
                {
                    $propValue = $p->getValue($obj);
            
                    if(!($propValue instanceof EntityHandler))
                    {
                        continue;
                    }

                    if($callback == 'setValues')
                    {
                        call_user_func_array([$this,$callback],[$arg,$propValue]);
                    }
                }
            }
    }

    private function setValue($props,$obj,$dt,$fullEntityName)
    {
        foreach($props as $p)
        {
            if(array_key_exists($p->getName(),$dt) && $p->class == $fullEntityName)
            {
                $this->validator($obj,$p,$dt);
                $accessible = !$p->isPublic() ? true : false;
                $p->setAccessible($accessible);
                $p->setValue($obj,$dt[$p->getName()]);
                if($accessible)
                {
                    $p->setAccessible(!$accessible);
                }
            }
        }
    }

    public function setValues($data,$obj = null)
    {
        if(empty($data))
        {
            throw new Exception('Impossible to infer values, because no data was found in the request.',406);
        }

        $obj = empty($obj) ? $this : $obj;

        $data = $this->putPrefix($data,$obj);

        $count = count($obj->getEntities());
       
        if($count != count($data))
        {
            $fullNameEntity = get_class($obj);
            $shortName = substr($fullNameEntity, strrpos($fullNameEntity, '\\') + 1);

            throw new Exception(sprintf('Entity %s is a composite entity, single entity found!',$shortName));
        }

        $this->entitiesCopy = $this->entities;
        
        foreach($this->entities as $shortEntityName => $fullEntityName)
        {
            $props = $this->props[$fullEntityName];
    
            if(array_key_exists($shortEntityName,$data))
            {
          
                $dt = $data[$shortEntityName];
                unset($data[$shortEntityName]);
                unset($this->entitiesCopy[$shortEntityName]);
    
                $this->setValue($props,$obj,$dt,$fullEntityName); 
                $this->callRecursive($obj,$props,'setValues',$data);
            }

            
        }

     
    }

    private function validator(EntityHandler $obj,ReflectionProperty $p,Array $dt)
    {
        $comment = $p->getDocComment();
    
        $validator = [];

        $re = '/\<validator\>(.*?)\<\/validator\>/is';

        if(!empty($comment) && preg_match($re,$comment,$validator))
        {
           $validators = explode(',',$validator[1]);
           
           foreach($validators as $k => $val)
           {
                $validators[$k] = trim($val,"\n\r\t\x0B\t* ");
           }

           $rules[$p->getName()] = array_filter($validators);

           (new MultiValidator($dt,$rules))->throwErrors(400,true);
          
        }
    }

    private function getAttributes(ReflectionProperty $p,string $shortEntityName)
    {
        $comment = $p->getDocComment();
    
        $attr = [$p->name];

        $re = '/\[(.*?)\]/is';

        if(!empty($comment) && preg_match($re,$comment,$attr))
        {
            $attr = explode('|',trim($attr[1]));

            foreach($attr as $prop)
            {
                if(empty($prop))
                {
                    throw new Exception(sprintf('All mapped properties must be reported for the {%s} property declared in {%s}!',$p->name,$shortEntityName),599);
                }
            }
        }

        return $attr;
    }

    private function getEntityShortName($obj)
    {
        $fullNameEntity = get_class($obj);
        $shortName = substr($fullNameEntity, strrpos($fullNameEntity, '\\') + 1);
        return $shortName;
    }

    public function save(Array $data,Model $Mapper)
    {
        $obj =  $this;

        $shortEntityName = $this->getEntityShortName($obj);
        $fullEntityName = $this->entities[$shortEntityName];
        $props = $this->props[$fullEntityName];
        $data = $this->putPrefix($data,$obj);

        foreach($props as $p)
        {
            $attr = $this->getAttributes($p,$shortEntityName);

            $propValue = $p->getValue($obj);

            $destProp = trim($attr[0]);
            $sourceProp = $p->name; 
         
            $dt = $data[$shortEntityName];
            $objGet = $obj;

            if(($propValue instanceof EntityHandler))
            {
                $dt = $data[$sourceProp];

                if(empty($attr[1]))
                {
                    throw new Exception(sprintf('No property relationship found between entities %s and %s!',$fullEntityName,get_class($propValue)),599);
                }

    
                $destProp = trim($attr[0]);
                $sourceProp = trim($attr[1]);  
                
                $objGet = $propValue;
            }

            if(array_key_exists($sourceProp,$dt))
            {
                $Mapper->{$destProp} = $objGet->{$sourceProp}; 
            }
        }

        $Mapper->save();
    }

    public function change(Model $model)
    {
        
    }

    public function remove(Model $model)
    {
        
    }

    public function toArray()
    {
        
        dd($this->props);
    }
}