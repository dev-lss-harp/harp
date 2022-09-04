<?php
namespace Harp\lib\HarpDatabase\orm;

use Exception;
use Harp\lib\HarpDatabase\drivers\DriverInterface;
use Harp\lib\HarpDatabase\orm\ORM;

class ORMUpdate implements IORMWhere 
{
    private $table;
    private $ConfigORM;


    
    public function __construct(ORM $ConfigORM)
    {
        $this->ConfigORM = $ConfigORM;

        $this->table = $this->ConfigORM->getProperty('table');

        $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text = sprintf(
           ' %s ',
           'UPDATE'
        );
    }

    public function where(Array $param)
    {
        $this->ConfigORM->where($param);
    }
    
    public function update($cols = [])
    {        
        try
        {
            $columns = $cols;

            if(in_array('*',$cols))
            {
                $columns = [];

                if(empty($this->table['attributes']['pk']))
                {
                    throw new Exception('To insert with the {*} symbol it is necessary to inform the {pk} attribute in the table!');
                }

                $pk = $this->table['attributes']['pk'];
          
                foreach($this->table['columns'] as $attr => $info)
                {
                    if($pk != $attr)
                    {
                        array_push($columns,$attr);
                    }
                }
            }

            $nameEntity = $this->ConfigORM->getProperty('name');

            $alias = $this->ConfigORM->selectAliases(true,$this->table['attributes']['alias'],$nameEntity);

            $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= sprintf(
                    ' %s.%s as %s',
                    $this->ConfigORM->addSpecialChar($this->table['attributes']['schema']),
                    $this->ConfigORM->addSpecialChar($this->table['attributes']['table']),
                    $this->ConfigORM->addSpecialChar($alias)
             );

            $this->set($columns); 
        }
        catch(\Exception $ex)
        {
            throw $ex;
        }

        return $this;
    }
    
    public function set(Array $columns)
    {
        $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= sprintf(
            '%s',
            ' SET '
        );

        $c = 0;
        foreach($columns as $attr)
        {
            ++$c;
            if(!isset($this->table['columns'][$attr]))
            {
                continue;
            }

            if(empty($this->table['columns'][$attr]['entityAttribute']) && empty($this->table['columns'][$attr]['fk']['entityAttribute']))
            {
                throw new Exception('Attribute {'.$attr.'} does not contains attribute {entityAttribute}!');
            }

            $entityAttr = !empty($this->table['columns'][$attr]['entityAttribute'])   ?
                                    $this->table['columns'][$attr]['entityAttribute'] :
                                    $this->table['columns'][$attr]['fk']['entityAttribute'];

            $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= sprintf(
                '%s %s %s %s',
                $attr, 
                '=', 
                '@'.$entityAttr, 
                    $c < count($columns) ? ',' : ''
            );                        
        }


        return $this;
    } 
    
    public function getInsertId()
    {
       return $this->ConfigORM->getConnectionDriver()->getInsertId();
    }
    
    public function execute($returnColumn = null)
    {
        try
        {

            $nameStatement = $this->ConfigORM->prepareParams($returnColumn);

            $this->ConfigORM->getConnectionDriver()->connect();

            $this->ConfigORM->getConnectionDriver()->executeNonQuery($nameStatement);
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return $this;
    }   
}
