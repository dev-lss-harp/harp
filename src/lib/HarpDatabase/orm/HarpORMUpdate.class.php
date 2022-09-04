<?php
namespace Harp\lib\HarpDB;

use Exception;
use etc\HarpDatabase\ORM\XmlORM;
use etc\HarpDatabase\commands\CommandEnum;

class HarpORMUpdate extends AbstractORM
{
    public function __construct(XmlORM $XmlORM)
    {
        parent::__construct($XmlORM);

        $this->XmlObject->ConnectionDriver->CommandText->getCommand()->text = &$this->getCommand();
    }
    
    private function setMappedColumns($columns,$ignoreColumns)
    {
            foreach($columns as $n)
            {
                $autoIncrement = $n->getAttribute('autoIncrement');

                $nValue = trim(str_ireplace(Array(PHP_EOL,"\r\n","\n"),'',$n->nodeValue));

                $attr = $n->getAttribute('entityAttribute');

                if(!in_array($attr,$ignoreColumns) && $autoIncrement != 'true')
                {
                    $nValue = $this->normalizeTableAndColumnsName($nValue);
                    
                    $this->command .= $nValue;
                    $this->command .= ' = ';
                    $this->command .= '@'.$attr;
                    $this->command .= PHP_EOL;
                    $this->command .= ',';    
                }
            } 
    }
    
    private function setChosenColumns($columns,$columnsSchema,$ignoreColumns)
    { 
        foreach($columnsSchema as $n)
        {
            $autoIncrement = $n->getAttribute('autoIncrement');
            $attr = $n->getAttribute('entityAttribute');

            if(!in_array($attr,$ignoreColumns) && in_array($attr,$columns) && $autoIncrement != 'true')
            {
                $nValue = trim(str_ireplace(Array(PHP_EOL,"\r\n","\n"),'',$n->nodeValue));
                $nValue = $this->normalizeTableAndColumnsName($nValue);
                $this->command .= $nValue;
                $this->command .= ' = ';
                $this->command .= '@'.$attr;
                $this->command .= PHP_EOL;
                $this->command .= ',';    
            }

        }  
    }

    public function update(Array $columns = Array(),Array $ignoreColumns = Array())
    {        
        $name = $this->XmlObject->{$this->XmlObject->DataBase}['tables']['xml']->getAttribute('name');

        $name = $this->XmlObject->{$this->XmlObject->DataBase}['tables']['xml']->getAttribute('schema').'.'.$name;
        $name = $this->normalizeTableAndColumnsName($name);
        
        $this->command = sprintf(" UPDATE %s SET ",$name);        
        
        $columnsSchema = $this->XmlObject->{$this->XmlObject->DataBase}['tables']['columns']['xml'];
      
        if(empty($columns))
        {
            $this->setMappedColumns($columnsSchema,$ignoreColumns);
        }
        else
        {
            $this->setChosenColumns($columns,$columnsSchema,$ignoreColumns); 
        }

        $this->command = substr($this->command,0,(mb_strlen($this->command) - 1));
    }
    
    public function setExpression($param,$value,$numParams = 1)
    {       
        try
        {  
            if(is_string($param) && preg_match('`('.$param.')\b`',$this->command))
            {
                $this->command = str_ireplace($param,$value,$this->command);
                //echo $this->command;exit;
                //$this->XmlObject->ConnectionDriver->CommandParameter->addParameter($param,$value,CommandEnum::VARCHAR_NO_QUOTES, CommandEnum::TYPE_VARCHAR,$numParams);  
                //$this->XmlObject->ConnectionDriver->CommandParameter->commit($param);
            }
        }
        catch (Exception $ex)
        { 
            throw $ex;
        }

        return $this;
    }


    public function getTableName()
    {
        return $this->XmlObject->{$this->XmlObject->DataBase}['tables']['xml']->getAttribute('name');
    }
    
    public function executeAndClear()
    {
        try
        {
            if(!$this->XmlObject->ConnectionDriver->isConnected())
            {
                $this->XmlObject->ConnectionDriver->connect();
            }

            $this->XmlObject->ConnectionDriver->executeNonQuery();
            $this->toCleanCommand();
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return $this;
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

    public function where($where,$alias = null,$replaceForAlias = null,$normalizeAlias = false,$normalizeWhere = false)
    {        
        $whereParams = $this->normlizeWhere($where,$alias,$normalizeAlias,$normalizeWhere);
        
        parent::where($whereParams['where'],$whereParams['alias'],$replaceForAlias,$normalizeAlias,$normalizeWhere);
        
        return $this;
    }     
}
