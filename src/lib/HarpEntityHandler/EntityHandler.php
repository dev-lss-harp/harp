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
    private $propsArray;
    private $data = [];

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

    private function getParams(&$data)
    {
        $params = array_key_exists('params',$data) ? $data['params'] : null;

        if(!empty($params))
        {
            unset($data['params']);
        }

        return $params;
    }

    private function removePrefix($data)
    {
        $newData = [];

        foreach($data as $shortName => $values)
        {
            $key = str_ireplace('Entity','',$shortName);
            $newData[$key] = $values;    
        }

        return $newData;
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

    public function setValues($data,$obj = null)
    {
        $obj = !($obj instanceof EntityHandler) ? $this : $obj;

        $data = $this->putPrefix($data,$obj);

        $this->data = empty($this->data) ? $data : $this->data;

        $shortEntityName = $this->getEntityShortName($obj);
        $fullEntityName = $this->entities[$shortEntityName];
        $props = $this->props[$fullEntityName];
        $dt = $data[$shortEntityName];
        $this->propsArray[$shortEntityName] = [];

        foreach($props as $p)
        {
            $propValue = $p->getValue($obj);

            if($propValue instanceof EntityHandler)
            {
                $this->setValues($data,$propValue);
            }
            else if(array_key_exists($p->name,$dt))
            {
                $this->validator($p,$dt);
                $p->setValue($obj,$dt[$p->name]);
                $this->propsArray[$shortEntityName][$p->name] = $dt[$p->name];
            }
        }

        return $this;
    }

    private function validator(ReflectionProperty $p,Array $dt)
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

    private function getCompositeProps
    (
        $objGet,
        $propValue,
        $destProp,
        $sourceProp,
        $attr,
        $data,
        $shortEntityName,
        $fullEntityName
    )
    {
        $dt = $data[$shortEntityName];

        $composite = [
            'destProp' => $destProp,
            'sourceProp' => $sourceProp,
            'objGet' => $objGet,
            'dt' => $dt
        ];

        if(($propValue instanceof EntityHandler))
        {
            if(array_key_exists($sourceProp,$data))
            {
                if(empty($attr[1]))
                {
                    throw new Exception(sprintf('No property relationship found between entities %s and %s!',$fullEntityName,get_class($propValue)),599);
                }

                $composite = [
                    'destProp' => trim($attr[0]),
                    'sourceProp' => trim($attr[1]),
                    'objGet' => $propValue,
                    'dt' => $data[$sourceProp]
                ];
            }
        }

        return $composite;
    } 

    public function putPrimaryKey(Model $Mapper, EntityHandler $obj, string $shortEntityName,string $action)
    {
        $primaryKey = $Mapper->getKeyName();

        if(!empty($primaryKey))
        {
            $obj->{$primaryKey} = 
                    $action == 'insert' ?  
                    $Mapper->{$primaryKey} : 
                    (
                        !empty($this->data[$shortEntityName]['params'][$primaryKey]) 
                        ? 
                        $this->data[$shortEntityName]['params'][$primaryKey] 
                        : null
                    );

            $this->data[$shortEntityName][$primaryKey] = $obj->{$primaryKey};
        }
    }

    public function save(Model $Mapper)
    {
        $data = $this->data;

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

            if(array_key_exists($shortEntityName,$data))
            {
                $composite = $this->getCompositeProps
                            (
                                $obj,
                                $propValue,
                                $destProp,
                                $sourceProp,
                                $attr,
                                $data,
                                $shortEntityName,
                                $fullEntityName,
                            );

                if(array_key_exists($composite['sourceProp'],$composite['dt']))
                {
                    $objGet = $composite['objGet'];
                    $Mapper->{$composite['destProp']} = $objGet->{$composite['sourceProp']}; 
                }
            }

        }

        $Mapper->save();

        $this->putPrimaryKey($Mapper,$obj,$shortEntityName,'insert');

        return $this;
    }

    private function validateParams(&$data,$props)
    {
        $params = $this->getParams($data);

        foreach($props as $p)
        {
            if(array_key_exists($p->name,$params))
            {
                $this->validator($p,$params);
            }
        } 

        return $params;
    }


    public function change(Model $Mapper)
    {        

        $data = $this->data;
        $obj =  $this;

        $shortEntityName = $this->getEntityShortName($obj);
        $fullEntityName = $this->entities[$shortEntityName];
        $props = $this->props[$fullEntityName];
        $data = $this->putPrefix($data,$obj);

        $StaticMapper = get_class($Mapper);

        $list =  $this->toArray();
       
        $params = $this->validateParams($data[$shortEntityName],$props);

        $values = [];
        foreach($props as $p)
        {
            $attr = $this->getAttributes($p,$shortEntityName);

            $propValue = $p->getValue($obj);

            $dt = $list[$shortEntityName];

            $destProp = trim($attr[0]);
            $sourceProp = $p->name; 

            if(array_key_exists($shortEntityName,$data))
            {
                $composite = $this->getCompositeProps
                            (
                                $obj,
                                $propValue,
                                $destProp,
                                $sourceProp,
                                $attr,
                                $data,
                                $shortEntityName,
                                $fullEntityName,
                            );

                if(array_key_exists($composite['sourceProp'],$composite['dt']))
                {
                    $values[$composite['destProp']] =  $dt[$composite['sourceProp']];
                }            
            }
        }

        $StaticMapper::where($params)->update($values);

        $this->putPrimaryKey($Mapper,$obj,$shortEntityName,'update');

        return $this;
    }

    public function remove(Model $model)
    {
        
    }

    public function removeParams($data)
    {

        foreach($data as $shortName => $values)
        {
            if(array_key_exists('params',$values))
            {
                unset($data[$shortName]['params']);
            }
    
        }

        return $data;
    }

    public function toArray()
    {
        return $this->propsArray;
    }

    public function data()
    {
        $data = $this->removeParams($this->data);
        return $this->removePrefix($data);
    }
}