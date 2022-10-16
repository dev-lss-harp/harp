<?php 
namespace Harp\lib\HarpEntityHandler;

use Exception;
use Harp\lib\HarpValidator\MultiValidator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use ReflectionProperty;

abstract class EntityHandler
{
    private $props;
    private $entities;
    private $propsArray;
    private $pagination = [];
    private $data = [];
    private $mandatoryProperties = [];
    private $rotateToParams = [];
    private $entityWhere = [];
    private $resultSet = 
    [
        'model' => false, 
        'first' => false
    ];

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



    private function getPagination(&$data)
    {
        $pagination = array_key_exists('pagination',$data) ? $data['pagination'] : [];

        if(!empty($pagination))
        {
            unset($data['pagination']);
        }

        return $pagination;
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

    private function normalizeWithPrefix($entities,&$data)
    {
        foreach($entities as $shortNameEntity => $fullNameEntity)
        {
             $short = str_ireplace('Entity','',$shortNameEntity);

             if(array_key_exists($short,$data))
             {
                $data[$shortNameEntity] = $data[$short];
                unset($data[$short]);
             }
        }

        $withoutPrefixData = [];
        foreach($data as $key => $value)
        {
            if(!array_key_exists($key,$entities) && is_scalar($value))
            {
                $withoutPrefixData[$key] = $value;
            }
        }

        return $withoutPrefixData;
    }

    private function normalizeWithEmpty($shortNamePrefix,$shortName,&$data,$withoutPrefixData)
    {
   
        if(!array_key_exists($shortNamePrefix,$data) && !array_key_exists($shortName,$data))
        {
            $data[$shortNamePrefix] = !empty($withoutPrefixData) ? $withoutPrefixData : [];
        }

        return $data;
    }


    private function traverseData($data,$entities,&$newData = [])
    {
        if(!empty($data))
        {
            foreach($data as $key => $value)
            {
                if(!preg_match(sprintf('`%s`is','Entity'),$key))
                {
                    $key = sprintf('%sEntity',$key);
                }
                
                if(!array_key_exists($key,$entities))
                {
                    continue;
                }
              
                if(is_array($value) && array_key_exists($key,$data))
                {
                    $this->traverseData($data[$key],$entities,$newData);
                }
    
                $newData[$key] = $value;
            }
        }

        return $newData;

    }

    private function putPrefix(&$data,EntityHandler $obj)
    {
        $newData = [];
      
        $entities = $obj->getEntities();

        $shortName = $this->getEntityShortName($obj);
        $shortNamePrefix = str_ireplace('Entity','',$shortName);
        //para casos onde existem prefixos da entidade
        //retorna dados fora de um prefixo válido que sejam valores escalares
        $withoutPrefixData = $this->normalizeWithPrefix($entities,$data);

        $this->normalizeWithEmpty($shortNamePrefix,$shortName,$data,$withoutPrefixData);

        $newData = $this->traverseData($data,$entities,$newData);
        
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

    private function rotateToParams()
    {
        $transform = [];

        foreach($this->data as $entity => $values)
        {
            $transform[$entity]['params'] = [];

            if(is_array($values))
            {
                $transform[$entity]['params'] = $values;
            }
        }
      
        return $transform;
    }

    private function keyEntity($name)
    {
        return sprintf('%sEntity',$name);
    }

    public function useWhere(Array $entityWhere)
    {
        foreach($entityWhere as $name => $where)
        {
            $entity = $this->keyEntity($name);

            if(array_key_exists($entity,$this->rotateToParams))
            {
                $this->entityWhere[$entity]['params'] = [];

                foreach($where as $key)
                {
                    if(array_key_exists($key,$this->rotateToParams[$entity]['params']))
                    {
                        $this->entityWhere[$entity]['params'][$key] = $this->rotateToParams[$entity]['params'][$key];
                    }
                }
            }
        }

        return $this;
    }

    public function setValues($data,&$obj = null)
    {
        $obj = !($obj instanceof EntityHandler) ? $this : $obj;

        $data = $this->putPrefix($data,$obj,$this->getEntityShortName($obj));

        $this->data = empty($this->data) ? $data : $this->data;
        
        $shortEntityName = $this->getEntityShortName($obj);
        $fullEntityName = $this->entities[$shortEntityName];
        $props = $this->props[$fullEntityName];
        $dt = $data[$shortEntityName];
        $this->propsArray[$shortEntityName] = [];
        $mandatoryProps = $this->mandatoryProperties[$shortEntityName] ?? []; 
        $this->rotateToParams = $this->rotateToParams($data,$props);

        //$this->rotateToParams = array_merge($this->rotateToParams,$this->parseParams($rotate,$props));
        //dump($this->rotateToParams);
        foreach($props as $p)
        {
            $propValue = $p->getValue($obj);

            if($propValue instanceof EntityHandler)
            {
               $nameProp = $this->getEntityShortName($propValue);
               
               $obj->{$nameProp} =  $this->setValues($data,$propValue);
            }
            else if(array_key_exists($p->name,$dt))
            {
                $this->validator($p,$dt);
                $p->setValue($obj,$dt[$p->name]);
                $this->propsArray[$shortEntityName][$p->name] = $dt[$p->name];
            }
            else if(!array_key_exists($p->name,$dt) && in_array($p->name,$mandatoryProps))
            {
                throw new Exception(sprintf("Mandatory property {%s} for entity {%s} does not exist in the request!",$p->name,$shortEntityName),400);
            }
        }

        return $obj;
    }

    public function setMandatoryProperties(Array $mandatoryProperties)
    {
        foreach($mandatoryProperties as $entity => $props)
        {

            $ent = sprintf('%sEntity',$entity);
            $this->mandatoryProperties[$ent] = [];

            foreach($props as $prop)
            {
                array_push($this->mandatoryProperties[$ent],$prop);
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


    private function getEntityShortName(EntityHandler $obj = null,ReflectionProperty $p = null)
    {
        if(empty($obj) && empty($p))
        {
            throw new Exception(sprintf('Expected instance {%s} or instance {%s}!',EntityHandler::class,ReflectionProperty::class));
        }

        $fullNameEntity = $obj instanceof EntityHandler ?  get_class($obj) : $p->class;
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
           
            if(empty($attr))
            {
                throw new Exception(sprintf('No property relationship found between entities %s and %s!',$fullEntityName,get_class($propValue)),599);
            }

            if(array_key_exists($sourceProp,$data))
            {
                $composite = [
                    'destProp' => $destProp,
                    'sourceProp' => $sourceProp,
                    'objGet' => $propValue,
                    'dt' => $data[$sourceProp]
                ];
            }
            else if(array_key_exists($sourceProp,$dt))
            {
                $dt = $dt[$sourceProp];

                $composite = [
                    'destProp' => $attr[$destProp],
                    'sourceProp' => key($dt),
                    'objGet' => $propValue,
                    'dt' => $dt
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

            $sProp = trim(key($attr));
            $dProp = trim($attr[$sProp]);
    
            if(array_key_exists($shortEntityName,$data))
            {
                $composite = $this->getCompositeProps
                            (
                                $obj,
                                $propValue,
                                $dProp,
                                $sProp,
                                $attr,
                                $data,
                                $shortEntityName,
                                $fullEntityName,
                            );
                       
                if(array_key_exists($composite['sourceProp'],$composite['dt']))
                {
                    $objGet = $composite['objGet'];
                    $objGet->setValues($composite['dt']);
                    $Mapper->{$composite['destProp']} = $objGet->{$composite['sourceProp']}; 
                }
            }

        }

        $Mapper->save();

        $this->putPrimaryKey($Mapper,$obj,$shortEntityName,'insert');

        return $this;
    }


    private function getAttributes(ReflectionProperty $p,string $shortEntityName = null)
    {
        $comment = $p->getDocComment();
 
        $attr = [
            preg_replace('`Entity`is','',$p->name) => $p->name
        ];

        if(!empty($comment) && preg_match('`\<mapper\>(.*?)\<\/mapper\>`is',$comment,$mapperTag))
        {
            if(!empty($mapperTag[1]))
            {
                preg_match_all('`\[(.*?)\]`is',$mapperTag[1],$mapperAll);

                if(!empty($mapperAll[1]))
                {
                   
                    foreach($mapperAll[1] as $map)
                    {
                        $m = explode('|',$map);
                        $db_prop = !empty($m[1]) ? $m[1] : $m[0];
                        $attr[$p->name] = $db_prop; 
                    }
                }
            }
        }

        return $attr;
    }

    private function shortNameClass($nameClass)
    {
        $name = explode('\\',$nameClass);
        return end($name);
    }

    private function parseParams(&$data,$props)
    {
        $params = [];

        foreach($props as $prop)
        {
            $short = $this->shortNameClass($prop->class);

            $p = 
            (\array_key_exists($prop->name,$data) && \array_key_exists('params',$data[$prop->name]))
            ?  $data[$prop->name]['params'] 
            : (
                (\array_key_exists($short,$data) && \array_key_exists('params',$data[$short])) 
                ?
                $data[$short]['params'] 
                :
                []
              );

             $aggregate = (\array_key_exists($prop->name,$data) && \array_key_exists('params',$data[$prop->name]));
  
             if(!empty($p))
             {
                $comment = $prop->getDocComment();

                if(!empty($comment) && preg_match('`\<mapper\>(.*?)\<\/mapper\>`is',$comment,$mapperTag))
                {
                    if(!empty($mapperTag[1]))
                    {
                        preg_match_all('`\[(.*?)\]`is',$mapperTag[1],$mapperAll);

                        if(!empty($mapperAll[1]))
                        {
                            foreach($mapperAll[1] as $map)
                            {
                                $m = explode('|',$map);

                                if(array_key_exists($m[0],$p))
                                {
                                    $this->validator($prop,$p);
                                    $db_prop = !empty($m[1]) ? $m[1] : $m[0];
                                    $params[$db_prop] = $p[$m[0]]; 
                                }
                            }
                        }
                    }
                }
                else
                {      
                    if(!$aggregate && array_key_exists($prop->name,$p))
                    {
                        $this->validator($prop,$p);
                        $params[$prop->name] = $p[$prop->name];
                    }
                }
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

        $params = $this->parseParams($data,$props);
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

        $StaticMapper::where($params['db'])->update($values);

        $this->putPrimaryKey($Mapper,$obj,$shortEntityName,'update');

        return $this;
    }

    private function paginator(&$data,$total)
    {
        $pagination = $this->getPagination($data);

        if(!empty($pagination))
        {
    
            $pagination['page'] = (!empty($pagination['page']) && is_int($pagination['page'])) 
            ? $pagination['page'] 
            : 1;

            $pagination['per_page'] = (!empty($pagination['per_page']) && is_int($pagination['per_page'])) 
            ? $pagination['per_page'] 
            : 10;

            $pagination['per_page'] <= 30 ? $pagination['per_page'] : 30;

            $pagination['limit'] = $pagination['per_page'];
            $pagination['offset'] = ($pagination['page'] * $pagination['per_page']) - $pagination['per_page'];

            $pagination['first'] = 1;
            $pagination['last'] = ceil(floatval($total) / floatval($pagination['per_page']));
            $pagination['next'] = (($pagination['page'] + 1) * $pagination['per_page']) > ($total + $pagination['per_page'] - 1)  ? 0 :  ($pagination['page'] + 1);
            $pagination['prev'] = ($pagination['page'] - 1) > 0 ? ($pagination['page'] - 1) : 0;
        }

        return $pagination;
    }


    public function model(bool $s = true)
    {
        $this->resultSet['model'] = $s;

        return $this;
    }

    public function first(bool $s = true)
    {
        $this->resultSet['first'] = $s;

        return $this;
    }

    public function get(Model $Mapper)
    {
        $obj =  $this;
  
        $data = $this->data;

        $StaticMapper = get_class($Mapper);

        $response = [];

        if(!empty($data))
        {
            $this->putPrefix($data,$obj);
   
            $shortEntityName = $this->getEntityShortName($obj);
            $fullEntityName = $this->entities[$shortEntityName];
            $props = $this->props[$fullEntityName];

        
            $params = $this->parseParams($data,$props,$shortEntityName);
            $params = !empty($params) ? $params : $this->parseParams($this->entityWhere,$props,$shortEntityName);

            $this->pagination = $this->paginator($data[$shortEntityName],$StaticMapper::count());  
          
            if(!empty($params) && !empty($this->pagination))
            { 
                $response = $StaticMapper::where($params)->skip($this->pagination['offset'])->take($this->pagination['limit']);
            }
            else if(!empty($params))
            {
                $response = $StaticMapper::where($params);
            }
            else if(!empty($this->pagination))
            {
                $response = $StaticMapper::skip($this->pagination['offset'])->take($this->pagination['limit']);
            } 
        }

        if(empty($response))
        {
            $response = $StaticMapper::all();
        }
        else
        {
            $response = $response->get();
        }

  
        if($this->resultSet['model'])
        {
            return $response;
        }
        else if($this->resultSet['first'])
        {
            return $response->first();
        }

        $count = $response->count();

        return ($count == 0 || empty($this->pagination)) ? 
                                    ['data' => $response,'total' => $count]  : 
                                    ['data' => $response, 'pagination' => $this->pagination,'total' => $count];
          
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

    public function toArray($key = null)
    {
        if(!empty($key))
        {
            $key = sprintf('%sEntity',$key);
        }

        return (!empty($key) && isset($this->propsArray[$key])) ? $this->propsArray[$key] : $this->propsArray;
    }

    public function data()
    {
        $data = $this->removeParams($this->data);
        return $this->removePrefix($data);
    }

    public function getPaginator()
    {
        return $this->pagination;
    }
}