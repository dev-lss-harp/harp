<?php
namespace Harp\lib\HarpDatabase\orm;

interface EntityHandlerInterface
{    
    const METHOD_PREFIX_SET = 'set';
    const METHOD_PREFIX_GET = 'get';
    public function set($data,EntityHandlerInterface $obj = null);
    public function toGenericClass($key = null);
    public function toArray();
    public function serializeArray();
    public function serializeObject();
    public function getNameProperties($a = false);
    public function getNameProperty($name);
    public function setMapperAttributes(Array $attr);
    public function getMapperAttributes();
    public function getEntityName();
    public static function getClassName();
       
}
