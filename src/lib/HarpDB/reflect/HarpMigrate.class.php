<?php
namespace etc\HarpDatabase\reflect;

use Exception;

class HarpMigrate
{
    private $ConnDriver;
    private $path;
    
    public function __construct($ConnDriver)
    {
        $this->ConnDriver = &$ConnDriver;
        
        $this->path =  $this->ConnDriver->getXmlORM()->getPath().'/'.$this->ConnDriver->getXmlORM()->getXmlObject()->DataBase.'.xml';
    }
    
    public function changeColumn($field,$props,$namespace,$table)
    {
        try
        {
            $this->ConnDriver->transactionBegin();

                $this->ConnDriver->CommandText->getCommand()->text = 
                sprintf(' 
                    ALTER TABLE %s%s%s.%s%s%s DROP COLUMN "%s"; 
                ',$this->ConnDriver->getCharacterCaseSensitive()
                 ,$namespace
                 ,$this->ConnDriver->getCharacterCaseSensitive()
                 ,$this->ConnDriver->getCharacterCaseSensitive()       
                 ,$table
                 ,$this->ConnDriver->getCharacterCaseSensitive()       
                 ,$field);     

                $this->ConnDriver->executeAnyCommand();
                
                $this->ConnDriver->CommandText->getCommand()->text = 
                sprintf(' 
                    ALTER TABLE %s%s%s.%s%s%s ADD COLUMN %s%s%s %s; 
                ',$this->ConnDriver->getCharacterCaseSensitive()
                 ,$namespace
                 ,$this->ConnDriver->getCharacterCaseSensitive()
                 ,$this->ConnDriver->getCharacterCaseSensitive()       
                 ,$table
                 ,$this->ConnDriver->getCharacterCaseSensitive()
                 ,$this->ConnDriver->getCharacterCaseSensitive()       
                 ,$field
                 ,$this->ConnDriver->getCharacterCaseSensitive()         
                 ,$props); 

                $this->ConnDriver->executeAnyCommand();

            $this->ConnDriver->transactionCommit();
            
            $this->changeXmlOnChangeColumn($namespace,$table,$props,$field);
        }
        catch(Exception $ex)
        {
            $this->ConnDriver->transactionRollback();
            throw $ex;
        }
    }
    
    public function addColumn($field,$props,$namespace,$table)
    {
        try
        {
            $this->ConnDriver->CommandText->getCommand()->text = 
            sprintf(' 
                ALTER TABLE %s%s%s.%s%s%s ADD COLUMN "%s" %s; 
            ',$this->ConnDriver->getCharacterCaseSensitive()
             ,$namespace
             ,$this->ConnDriver->getCharacterCaseSensitive()
             ,$this->ConnDriver->getCharacterCaseSensitive()       
             ,$table
             ,$this->ConnDriver->getCharacterCaseSensitive()       
             ,$field
             ,$props);  

            $this->ConnDriver->executeAnyCommand();
            
            $this->changeXmlAddColumn($namespace,$table,$props,$field); 
            

        }
        catch(Exception $ex)
        {
            throw $ex;
        }
    }  
    
    public function dropColumn($field,$namespace,$table)
    {

        try
        {


            $this->ConnDriver->CommandText->getCommand()->text = 
            sprintf(' 
                ALTER TABLE %s%s%s.%s%s%s DROP COLUMN "%s"; 
            '   ,$this->ConnDriver->getCharacterCaseSensitive()
                ,$namespace
                ,$this->ConnDriver->getCharacterCaseSensitive()
                ,$this->ConnDriver->getCharacterCaseSensitive()       
                ,$table
                ,$this->ConnDriver->getCharacterCaseSensitive()       
                ,$field);  

            $this->ConnDriver->executeAnyCommand();

            
            $this->changeXmlDropColumn($namespace,$table,$field);
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
    }    
    
    private function changeXmlOnChangeColumn($namespace,$tableName,$props,$field)
    {
            try
            {
                $this->changeXmlDropColumn($namespace, $tableName, $field);
                $this->changeXmlAddColumn($namespace, $tableName, $props, $field);                 
            }
            catch(Exception $ex)
            {
                throw $ex;
            }
    }    
    
    public function createTable($namespace,$table,$fields = [])
    {
        try
        {
            $this->ConnDriver->transactionBegin();

            $this->ConnDriver->CommandText->getCommand()->text = 
            sprintf('CREATE TABLE %s%s%s.%s%s%s (',$this->ConnDriver->getCharacterCaseSensitive()
                 ,$namespace
                 ,$this->ConnDriver->getCharacterCaseSensitive()
                 ,$this->ConnDriver->getCharacterCaseSensitive()       
                 ,$table
                 ,$this->ConnDriver->getCharacterCaseSensitive());
            
            foreach($fields as $k =>  $f)
            {
                $this->ConnDriver->CommandText->getCommand()->text .= sprintf('"%s" %s,',$k,$f['props']);
            }
            
            $this->ConnDriver->CommandText->getCommand()->text = rtrim($this->ConnDriver->CommandText->getCommand()->text,',');
            $this->ConnDriver->CommandText->getCommand()->text .= ')';

            $this->ConnDriver->executeAnyCommand();

            $this->ConnDriver->transactionCommit();
            
            $this->changeXmlCreate($namespace,$table,$fields);
        }
        catch(Exception $ex)
        {
            $this->ConnDriver->transactionRollback();
            throw $ex;
        }
    }
        
    
    private function changeXmlAddColumn($namespace,$tableName,$props,$field)
    {
            try
            {
                     $Dom = $this->ConnDriver->getXmlORM()->getXmlObject()->xmlFile;

                     $Xpath = new \DOMXPath($Dom);
        
                     $nodes = $Xpath->query('//table[@name="'.$namespace.'.'.$tableName.'"]');
                     
                     if($nodes->length > 0)
                     {
                        $props = $this->verifyDefinitionAttributes($props);
                        $Column = $Dom->createElement('column',$field);
                        
                        foreach($props as $attr => $value)
                        {
                               $Attr = $Dom->createAttribute($attr);
                               $Attr->value = $value;  
                               $Column->appendChild($Attr);
                        }
                        
                        $Attr = $Dom->createAttribute('entityAttribute');
                        $Attr->value = $field;  
                        $Column->appendChild($Attr);
                        
                        $nodes->item(0)->appendChild($Column);
                        
                        $this->ConnDriver->getXmlORM()->getXmlObject()->xmlFile->saveXml();
                        $this->ConnDriver->getXmlORM()->getXmlObject()->xmlFile->save($this->path);                           
                     }
                                       
            }
            catch(Exception $ex)
            {
                throw $ex;
            }
    }
    
    private function changeXmlDropColumn($namespace,$tableName,$field)
    {
            try
            {
                     $Dom = $this->ConnDriver->getXmlORM()->getXmlObject()->xmlFile;
                                                              
                  
                     $Xpath = new \DOMXPath($Dom);
        
                     $nodes = $Xpath->query('//table[@name="'.$namespace.'.'.$tableName.'"]//column[@entityAttribute="'.$field.'"]');

                     foreach($nodes as $node)
                     {
                          $node->parentNode->removeChild($node);
                     }
                     
                    $this->ConnDriver->getXmlORM()->getXmlObject()->xmlFile->saveXml();
                    $this->ConnDriver->getXmlORM()->getXmlObject()->xmlFile->save($this->path);                     
            }
            catch(Exception $ex)
            {
                throw $ex;
            }

    }
    
    private function verifyDefinitionAttributes($prop)
    {
            $r = [];
            
            $definition = [];
            
            if(preg_match('`primary.*key`i',$prop,$r))
            {
                $definition['primaryKey'] = 'true';
            } 
            
            if(preg_match('`(references)(.*)`i',$prop,$r))
            {
                if(isset($r[2]))
                {
                   $p1 = explode('.',$r[2]);
                   
                   if(isset($p1[1]) && preg_match('`(.*)\(`',$p1[1],$r1))
                   {
                       $definition['references'] = str_ireplace(['"'],[''],$r1[1]);
                   }
                }
            }
            
            if(preg_match('`(serial|int|identity)`i',$prop,$r))
            {
                $definition['type'] = 'int';
                $definition['length'] = $definition['type'];
            }
            else if(preg_match('`(boolean|bit|varchar|text|bit|char|binary|double|float|numeric)`i',$prop,$r))
            {
                $definition['type'] = $r[0];
                $definition['length'] = $definition['type'];
            }
            else if(preg_match('`(timestamp|datetime)`i',$prop,$r))
            {
                $definition['type'] = $r[0];
                $definition['length'] =  'datetime_iso8601'; 
            }     
            
            return $definition;
    }
    
    private function changeXmlCreate($namespace,$table,$fields)
    {
             $Tables = $this->ConnDriver->getXmlORM()->getXmlObject()->xmlFile->getElementsByTagName('tables');
        
             $Table = $this->ConnDriver->getXmlORM()->getXmlObject()->xmlFile->createElement('table');
             //attribute name
             $Attr = $this->ConnDriver->getXmlORM()->getXmlObject()->xmlFile->createAttribute('name');
             $Attr->value = $namespace.'.'.$table;
             
             $Table->appendChild($Attr);

             //attribute alias
             $Attr = $this->ConnDriver->getXmlORM()->getXmlObject()->xmlFile->createAttribute('alias');
             $Attr->value = strtolower(mb_substr($table,0,3));
             
             $Table->appendChild($Attr);
             
             //attribute entity
             $Attr = $this->ConnDriver->getXmlORM()->getXmlObject()->xmlFile->createAttribute('entity');
             $Attr->value = $table;
             
             $Table->appendChild($Attr);     

             foreach($fields as $name => $column)
             {
                    
                    $Column = $this->ConnDriver->getXmlORM()->getXmlObject()->xmlFile->createElement('column',$name);
                    
                    
                    $props = $this->verifyDefinitionAttributes($column['props']);
                 //   print_r($props);exit;
                    foreach($props as $attr => $value)
                    {
                           $Attr = $this->ConnDriver->getXmlORM()->getXmlObject()->xmlFile->createAttribute($attr);
                           $Attr->value = $value;  
                           $Column->appendChild($Attr);
                    }
                     
                    $Attr = $this->ConnDriver->getXmlORM()->getXmlObject()->xmlFile->createAttribute('entityAttribute');
                    $Attr->value = $name;
                    $Column->appendChild($Attr);
                    $Table->appendChild($Column);                 
             }

             $Tables->item(0)->appendChild($Table);
             $this->ConnDriver->getXmlORM()->getXmlObject()->xmlFile->saveXml();
             $this->ConnDriver->getXmlORM()->getXmlObject()->xmlFile->save($this->path);
    }
}
