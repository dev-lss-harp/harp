<?php
namespace Harp\lib\HarpDB;

use Exception;
use etc\HarpDatabase\ORM\XmlORM;
use etc\HarpDatabase\commands\CommandEnum;

class HarpORMDelete extends AbstractORM
{
    public function __construct(XmlORM $XmlORM)
    {
        parent::__construct($XmlORM);

        $this->XmlObject->ConnectionDriver->CommandText->getCommand()->text = &$this->getCommand();
    }

    public function delete()
    {        
        $name = $this->XmlObject->{$this->XmlObject->DataBase}['tables']['xml']->getAttribute('name');
        $name = $this->XmlObject->{$this->XmlObject->DataBase}['tables']['xml']->getAttribute('schema').'.'.$name;
        $this->normalizeTableAndColumnsName($name);
        $this->command = sprintf(" DELETE FROM %s",$name); 
        
        return $this;
    }

    public function getTableName()
    {
        return $this->XmlObject->{$this->XmlObject->DataBase}['tables']['xml']->getAttribute('name');
    }
        
    public function execute()
    {
        try
        {
            if(!$this->XmlObject->ConnectionDriver->isConnected())
            {
                $this->XmlObject->ConnectionDriver->connect();
            }

            $this->XmlObject->ConnectionDriver->executeNonQuery();
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return $this;
    }     
}
