<?php
namespace Harp\lib\HarpDB;

use etc\HarpDatabase\ORM\MapORM;

class HarpORMQueryBuilder
{
    private $ConfigORM;
    
    public function __construct(MapORM $ConfigORM)
    {
        $this->ConfigORM = $ConfigORM;
    }
    
    public function aggregation()
    {
        return new HarpORMSelect($this->ConfigORM,true);
    }    
    
    public function selectByColumns(Array $columns = [])
    {
        $ObjSelect = new HarpORMSelect($this->ConfigORM);
        
        $ObjSelect->selectByColumns($columns);
        
        return $ObjSelect;
    }
    
    public function select(Array $columns = [],Array $extraColumns = [])
    {
        
        $ObjSelect = new HarpORMSelect($this->ConfigORM);
        
        if(!empty($columns) && !$ObjSelect->isAggregation())
        {
            $ObjSelect->select($columns,null,null,true,$extraColumns);
        }
        else if(empty($columns) && !$ObjSelect->isAggregation())
        {
            $ObjSelect->select(null,null,null,false,$extraColumns);
        }
        
        return $ObjSelect;
    }
    
    public function insert(Array $columns = [])
    {    
        return new HarpORMInsert($this->ConfigORM,$columns);
    }

    public function update(Array $columns = Array(),Array $ignoreColumns = Array())
    {
        $Obj = new HarpORMUpdate($this->ConfigORM);
        
        if(!empty($columns))
        {
            $Obj->update($columns,$ignoreColumns);
        }
        else
        {
            $Obj->update([],$ignoreColumns);
        }
        
        return $Obj;
    }  

    public function delete()
    {
        $Obj = new HarpORMDelete($this->ConfigORM);
        
        return $Obj->delete();
    }     
}
