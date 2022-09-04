<?php
namespace Harp\lib\HarpDB;


class AdapterORM 
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
    
    public function select($columns = ['*'])
    {
        $ObjSelect = new Select($this->ConfigORM);
        
        if(!$ObjSelect->isAggregation())
        {
            $ObjSelect->select($autoJoins);
        }
        
           
       /* if(!empty($columns) && !$ObjSelect->isAggregation())
        {
            $ObjSelect->select($columns,null,null,true,$extraColumns);
        }
        else if(empty($columns) && !$ObjSelect->isAggregation())
        {
            $ObjSelect->select(null,null,null,false,$extraColumns);
        }*/
        
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
