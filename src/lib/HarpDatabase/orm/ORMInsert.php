<?php
namespace Harp\lib\HarpDatabase\orm;

use Exception;
use Harp\lib\HarpDatabase\drivers\DriverInterface;
use Harp\lib\HarpDatabase\orm\ORM;

class ORMInsert implements IORMWhere
{
    private $table;
    private $ConfigORM;
    
    public function __construct(ORM $ConfigORM)
    {
       //
       // parent::__construct($ConfigORM);

        $this->ConfigORM = $ConfigORM;

        $this->table = $this->ConfigORM->getProperty('table');

        $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text = sprintf(
           '%s %s.%s',
           'INSERT INTO ',  
           $this->ConfigORM->addSpecialChar($this->table['attributes']['schema']),
           $this->ConfigORM->addSpecialChar($this->table['attributes']['table']),
        );
    }

    public function where(Array $param)
    {
        $this->ConfigORM->where($param);
    }
    
    public function insert($cols = [])
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

            $this->ConfigORM->startGroup();

            $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= sprintf
                                                                    (
                                                                        " %s ",
                                                                        implode(',',$columns)
                                                                    );

            $this->ConfigORM->endGroup();      
            
            $this->values($columns); 
        }
        catch(\Exception $ex)
        {
            throw $ex;
        }

        return $this;
    }
    
    public function values(Array $columns)
    {

        $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= sprintf(
            '%s',
            ' VALUES '
        );

        $this->ConfigORM->startGroup();

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
                        '%s %s',
                        '@'.$entityAttr, 
                         $c < count($columns) ? ',' : ''
                    );                        
                }

        $this->ConfigORM->endGroup();

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
