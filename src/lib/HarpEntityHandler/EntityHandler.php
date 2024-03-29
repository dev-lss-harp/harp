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
    private $orderBy = [];
    private $columns = ["*"];
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

    public function orderBy(Array $order = [])
    {
        $this->orderBy = $order;
        return $this;
    }

    public function useWhere(Array $entityWhere)
    {
        foreach($entityWhere as $name => $where)
        {
            $entity = $this->keyEntity($name);
           
            if(array_key_exists($entity,$this->rotateToParams))
            {

                $this->entityWhere[$entity]['params'] = [];
           
                foreach($where as $k => $v)
                {     

                    if(is_scalar($v))
                    {
                        if(array_key_exists($v,$this->rotateToParams[$entity]['params']))
                        {
                            $this->entityWhere[$entity]['params'][$v] = $this->rotateToParams[$entity]['params'][$v];
                        }
                    }
                    else if(is_array($v) || is_object($v))
                    {
                        foreach($v as $k2 => $v2)
                        {
                            if(array_key_exists($k2,$this->rotateToParams[$entity]['params'][$k]))
                            {
                                $this->entityWhere[$entity]['params'][$k2] = $this->rotateToParams[$entity]['params'][$k][$k2];
                            }
                        }
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
        $values = $this->mapperInsertOrUpdate($data,$obj,$shortEntityName,$fullEntityName);

        foreach($values as $key => $value)
        {
            $Mapper->{$key} = $value;
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
                        $attr[!empty($m[0]) ? $m[0] : $p->name] = $db_prop; 
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

    private function getNamespaceByPropEntity(ReflectionProperty $p)
    {
        $pos = mb_strrpos($p->class,"\\");
        return mb_substr($p->class,0,$pos);
    }

    private function getParamsWithParent(Array $data,string $shortName,string $shortParentName)
    {
        $params = [];

        if(
            array_key_exists($shortParentName,$data) 
            && 
            array_key_exists('params',$data[$shortParentName]))
        {
  
            $shortWithoutPrefix = str_ireplace('Entity','',$shortName);
            $params =  array_key_exists($shortName,$data[$shortParentName]['params']) ? $data[$shortParentName]['params'] : $params;

            if(empty($params) && array_key_exists($shortWithoutPrefix,$data[$shortParentName]['params']))
            {
                $params = $data[$shortParentName]['params'][$shortWithoutPrefix];
            }
        }

        return $params;
    }

    private function getParamsWithoutParent(Array $data,string $shortName,string $shortParentName)
    {
        $params = [];

        if
        (
            array_key_exists($shortName,$data) 
            && 
            array_key_exists('params',$data[$shortName])
        )
        {
            $params = $data[$shortName]['params'];
        }

        return $params;
    }

    private function getParamsExists(Array $data,string $shortName,string $shortParentName)
    {
        $params = $this->getParamsWithParent($data,$shortName,$shortParentName);
        if(empty($params))
        {
            $params = $this->getParamsWithoutParent($data,$shortName,$shortParentName);
        }

        return $params;
    }

    private function parseParams(&$data,$props,$obj)
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

                $propValue = $prop->getValue($obj);

                $subParam = [];

                if($propValue instanceof EntityHandler)
                {
                    $shortSub = $this->getEntityShortName($propValue);
             
                    $subParam = $this->getParamsExists($data,$shortSub,$short);
             
                    if(empty($subParam)){ continue; }

                    $p = $subParam;
                }
              
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

    private function mapperInsertOrUpdate(Array $data,EntityHandler $obj,string $shortEntityName,string $fullEntityName,ReflectionProperty $propParent = null)
    {
        $values = [];
        foreach($this->props[$fullEntityName] as $prop)
        {
            $pValue = $prop->getValue($obj);
            
            if($pValue instanceof EntityHandler)
            {
                $subShort = $this->getEntityShortName($pValue);

                if(array_key_exists($subShort,$data))
                {
                    $subFullEntityName = $this->entities[$subShort];

                    $val = $this->mapperInsertOrUpdate($data,$pValue,$subShort,$subFullEntityName,$prop);

                    $values = array_merge($values,$val);
                }                
            }
            else
            {
                if(array_key_exists($prop->name,$data[$shortEntityName]))
                {
                    $attrs = $this->getAttributes(!empty($propParent) ? $propParent : $prop);

                    foreach($attrs as $key => $attr)
                    {

                        if(array_key_exists($key,$data[$shortEntityName]))
                        {
                            $values[$attr] = $prop->getValue($obj);
                        }
                        
                    }
                }

            }
        }
        
        return $values;
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

        $params = $this->parseParams($data,$props,$obj);
        $params = !empty($params) ? $params : $this->parseParams($this->entityWhere,$props,$obj);
        $values = $this->mapperInsertOrUpdate($data,$obj,$shortEntityName,$fullEntityName);

        if(!empty($params))
        {
            $StaticMapper::where($params)->update($values);
            $this->putPrimaryKey($Mapper,$obj,$shortEntityName,'update');
        }
        else
        {
            $StaticMapper::update($values);
        }      

        

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

    public function setColumns(Array $columns)
    {
        $this->columns = $columns;

        return $this;
    }

    private function addOrderBy($StaticMapper,$response)
    {
        if(!empty($this->orderBy))
        {
            foreach($this->orderBy as $column => $order)
            {
                $response = $StaticMapper::orderBy($column,$order);
            }
        }
 
        return $response;
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
            $params = !empty($this->entityWhere) ? $this->parseParams($this->entityWhere,$props,$obj) : $this->parseParams($data,$props,$obj);

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

        $response = $this->addOrderBy($StaticMapper,$response);

        if(empty($response))
        {
            $response = $StaticMapper::all($this->columns);
        }
        else
        { 
            $response = $response->get($this->columns);
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