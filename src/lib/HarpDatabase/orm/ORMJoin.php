<?php
namespace Harp\lib\HarpDatabase\orm;

use Exception;
use lib\pvt\HarpFilterSql\HarpFilterSql;
use etc\HarpDatabase\pagination\PaginationData;
use etc\HarpDatabase\ORM\MapORM;
use Harp\bin\ArgumentException;
use SimpleXMLElement;

class ORMJoin extends ORM
{
    const INNER_JOIN = ' INNER JOIN ';
    const LEFT_JOIN  = ' LEFT JOIN  ';
    const RIGHT_JOIN = ' RIGHT JOIN ';
    const FULL_JOIN  = ' FULL JOIN  ';
    
    private $listJoins = [
        self::INNER_JOIN,
        self::LEFT_JOIN,
        self::RIGHT_JOIN,
        self::FULL_JOIN
    ];

    private $entityRelation;


    private $listOrderBy;
    private $listGroupBy;
    private $aggregation;
    private $FilterSql;
    private $limit = [];
    private $offset = [];
    private $hasPagination = false;
    private $subList = [];
    private $listSelectAsField = [];
    public $PaginationData;


    private $listOfUsedAliases = [];

    
    public function __construct(ORM $ConfigORM,$entityName,$join = self::INNER_JOIN)
    {
        parent::__construct($ConfigORM);

        if(!in_array($join,$this->listJoins))
        {
            throw new ArgumentException('Only {'.implode(',',$this->listJoins).'} clauses are allowed');
        }

        $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text = sprintf(
           ' %s ',
            $join
        );  

        $this->addJoin($entityName);
    }

    public function addJoin($entityName)
    {
        $schema = $this->ConfigORM->getJsonObject();
        $table = $this->ConfigORM->getProperty('table');

        $columns = $table['columns'];

        foreach($columns as $obj)
        {
            if(isset($obj['fk']) && trim($obj['fk']['entityName']) == trim($entityName))
            {
                $this->entityRelation['table'] = $schema[$obj['fk']['references']];
                $this->entityRelation['fkAttribute'] = $obj;
            }
        }

        echo '<pre>';print_r($this->entityRelation);exit;
    }

   
    
    public function getTableName()
    {
        return '';
    }
    
    

 
        
    private function addAutoJoin(Array $attributesTable,$fkInfo,$nameColumn)
    {
        
        $updatedCommand = chr(32);
        
        try
        {
            $fkTable = $this->ConfigORM->getTableByName($fkInfo['references']);

            $tbRelation = mb_strtoupper($fkInfo['relation']);
            $tbOperator = $fkInfo["operator"];
            $tbAlias = $this->normalizeTableAndColumnsName($attributesTable["alias"]);
            $tbColumnFk = $this->normalizeTableAndColumnsName($nameColumn);
            
            $fkSchemaTable = $this->normalizeTableAndColumnsName($fkTable["attributes"]["schema"]);
            $fkNameTable = $this->normalizeTableAndColumnsName($fkTable["attributes"]["table"]);
            $fkAliasTable = $this->normalizeTableAndColumnsName($fkTable["attributes"]["alias"]);            
            $fkColumnName = $this->normalizeTableAndColumnsName($fkInfo["columnName"]);
            
            if(empty($fkTable))
            {
                throw new Exception('{'.$fkInfo['references'].'} configuration not found in file json!');
            }

            $cmdFormat = $updatedCommand." %s \r\n%s.%s as %s \r\n ON \r\n %s.%s %s %s.%s \r\n";
            
            $updatedCommand = sprintf($cmdFormat,
                        $tbRelation,
                        $fkSchemaTable,
                        $fkNameTable,
                        $fkAliasTable,
                        $fkAliasTable,
                        $fkColumnName,
                        $tbOperator,
                        $tbAlias,
                        $tbColumnFk
                        
                    );
        }
        catch(Exception $ex)
        {
            throw $ex;
        }

        return $updatedCommand;
    }    
    
    
    private function setColumnsForFk($table)
    {
        $attributesFk = 
        [
            "entityAttribute",
            "references",
            "relation",
            "operator",
            "columnName"
        ];
        
        try
        {
            foreach($table['columns'] as $column)
            {
                if(isset($column["fk"]))
                {
                    $validateAttributes = array_diff(array_keys($column["fk"]),$attributesFk);

                    if(!empty($validateAttributes))
                    {
                        throw new Exception('Converging Attributes for FK Declaration:{'.(implode(',',$validateAttributes)).'}');
                    }

                    $referenceTable = $this->ConfigORM->getTableByName($column['fk']['references']);

                    if(empty($referenceTable))
                    {
                         throw new Exception('Table {'.$column['fk']['references'].'} was not found in the json declaration.');
                    }  

                    $columns = $referenceTable['columns'];
                    $alias = $this->normalizeTableAndColumnsName($referenceTable['attributes']['alias']);            
                       
                    $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= sprintf(" %s ",$this->setColumns($columns,$alias,","));
                }
            } 
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
    }
    
    private function getAutoJoins($table)
    {
        $strCommand = chr(32);
        
        try
        {
                $attributes = $table['attributes'];
                
                $this->setColumnsForFk($table);
                $this->from($table);
                
                foreach($table['columns'] as $nameColumn => $column)
                {
                    if(isset($column["fk"]))
                    {
                        $strCommand .= $this->addAutoJoin($attributes,$column["fk"],$nameColumn); 
                    }               
                } 
        }
        catch(Exception $ex)
        {
            throw $ex;
        }   
        
        return $strCommand;
    }
    
   private function addJoinExistingRelation(Array $attributesTable,$fkInfo,$nameColumn)
    {
        
        $updatedCommand = chr(32);
        
        try
        {
            $fkTable = $this->ConfigORM->getTableByName($fkInfo['references']);

            $tbRelation = mb_strtoupper($fkInfo['relation']);
            $tbOperator = $fkInfo["operator"];
            
            $tbSchema = $this->normalizeTableAndColumnsName($attributesTable["schema"]);
            $tbName = $this->normalizeTableAndColumnsName($attributesTable["table"]);
            $tbAlias = $this->normalizeTableAndColumnsName($attributesTable["alias"]);
            $tbColumnFk = $this->normalizeTableAndColumnsName($nameColumn);

            $fkAliasTable = $this->normalizeTableAndColumnsName($fkTable["attributes"]["alias"]);            
            $fkColumnName = $this->normalizeTableAndColumnsName($fkInfo["columnName"]);
            
            if(empty($fkTable))
            {
                throw new Exception('{'.$fkInfo['references'].'} configuration not found in file json!');
            }

            $cmdFormat = $updatedCommand." %s ".PHP_EOL." %s.%s as %s ".PHP_EOL." ON ".PHP_EOL." %s.%s %s %s.%s ".PHP_EOL;
            
            $updatedCommand = sprintf($cmdFormat,
                        $tbRelation,
                        $tbSchema,
                        $tbName,
                        $tbAlias,
                        $fkAliasTable,
                        $fkColumnName,
                        $tbOperator,
                        $tbAlias,
                        $tbColumnFk
                    );
        }
        catch(Exception $ex)
        {
            throw $ex;
        }

        return $updatedCommand;
    }     
    
    private function getJoinExistingRelation($table)
    {
        $strCommand = chr(32);
        
        try
        {
                $attributes = $table['attributes'];
                                
                foreach($table['columns'] as $nameColumn => $column)
                {
                    if(isset($column["fk"]))
                    {
                        $strCommand .= $this->addJoinExistingRelation($attributes,$column["fk"],$nameColumn); 
                    }               
                } 
        }
        catch(Exception $ex)
        {
            throw $ex;
        }   
        
        return $strCommand;
    }    
    
    private function setJoinExistingRelation($itens)
    {
        try
        {
            for($i = 0;$i < count($itens);++$i)
            {
                $table = $this->ConfigORM->getTableByName($itens[$i]);
                
                if(!empty($table))
                {
                    $firstFrom = strpos($this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text,'FROM');
                    
                    if($firstFrom !== false)
                    {
                        $firstPart = substr($this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text,0,$firstFrom);
                        $lastPart = substr($this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text,$firstFrom);
                        $columns = $table['columns'];
                        $alias = $this->normalizeTableAndColumnsName($table['attributes']['alias']);
                        
                        $columns = $this->setColumns($columns,$alias,',');
                        $joins = $this->getJoinExistingRelation($table);

                        $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text = sprintf(
                                       $firstPart.' %s '.$lastPart.' %s ',
                                       $columns,
                                       $joins
                           );
                    }
                }
            }
        }
        catch(Exception $ex)
        {
            throw $ex;
        }   
       
    }    
    
    public function from($table)
    {
        $cmdFomat = $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text." FROM ".PHP_EOL." %s.%s AS %s ".PHP_EOL;

        $schema = $this->normalizeTableAndColumnsName($table['attributes']['schema']);
        $nameEntity = $table['attributes']['entity'];

        $nameTable  = $this->normalizeTableAndColumnsName($table['attributes']['table']);
        $alias = $this->normalizeTableAndColumnsName($this->listOfUsedAliases[$nameEntity][count($this->listOfUsedAliases[$nameEntity]) - 1]);

        $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text = sprintf(
                $cmdFomat,
                $schema,
                $nameTable,
                $alias
             );
        
        return $this;
    }

    public function includeTables(Array $fk = [])
    {
       try
       {
           $table = $this->ConfigORM->getCurrentTable();
           
           if(!empty($table['includeTablesFK']))
           {
                $itens = [];
               
                $tablesFk = $table['includeTablesFK'];

                for($i = 0; $i < count($fk); ++$i)
                {
                    if(isset($tablesFk[$i]))
                    {
                        array_push($itens,$tablesFk[$i]);
                    }
                } 
                
                $this->setJoinExistingRelation($itens);
           }
       }
       catch(Exception $ex)
       {
           throw $ex;
       }
       
       return $this;
    }     

    private function selectAliases($incrementAlias,$alias,$nameEntity)
    {
        if(!isset($this->listOfUsedAliases[$nameEntity]))
        {
            $this->listOfUsedAliases[$nameEntity] = [];
        }

        $incr = 1;
        $als = $alias;

        while($incrementAlias && in_array($als,$this->listOfUsedAliases[$nameEntity]))
        {
            $als = $alias.$incr; 
            ++$incr;
        }

        $this->addAliases($nameEntity,$als);

        return $als;
    }

    private function addAliases($nameEntity,$alias)
    {
        array_push($this->listOfUsedAliases[$nameEntity],$alias);
    }

    public function getAliasesByNameEntity($nameEntity)
    {
        return isset($this->listOfUsedAliases[$nameEntity]) ? $this->listOfUsedAliases[$nameEntity] : null;
    }
    
    public function select($columns = [],$autoJoin = false,$incrementAlias = true)
    {       
        try
        {
            $table = $this->ConfigORM->getProperty('table');
            $nameEntity = $this->ConfigORM->getProperty('name');
            
            //$tableName = $table['attributes']['table'];   
            $columns = empty($columns) ? $table['columns'] : array_flip($columns);

            $alias = $this->selectAliases($incrementAlias,$table['attributes']['alias'],$nameEntity);
            $alias = $this->normalizeTableAndColumnsName($alias);
            
            $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text = sprintf("%s %s"," SELECT ",$this->setColumns($columns,$alias));

            if($autoJoin)
            {
                 $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= $this->getAutoJoins($table);
            }
            else
            {
                $this->from($table);
            }    
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
              //echo $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text;echo '<br/>';
        return $alias;
    }    
    
//    public function select($columns = null,$sufix ='',$alias = null,$injected = false,$extraColumns = [])
//    {       
//      
//        if(!$injected)
//        { 
//            if(empty($columns))
//            {
//                $columns = $this->XmlObject->{$this->XmlObject->DataBase}['tables']['columns']['xml'];
//                $alias = is_string($alias) ? $alias : $this->XmlObject->{$this->XmlObject->DataBase}['tables']['xml']->getAttribute('alias').'_'.$this->XmlObject->{$this->XmlObject->DataBase}['tables']['idEntity'];
//                $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text = " SELECT ";
//            }
//            else
//            {
//              
//                $alias = $alias = is_string($alias) ? $alias : $columns->getAttribute('alias').'_'.$this->XmlObject->{$this->XmlObject->DataBase}['tables']['idEntity'];
//                $columns = $columns->getElementsByTagName('column');
//            }
//            
//            $alias = $this->normalizeTableAndColumnsName($alias);
//
//            $this->setColumns(true,$columns,$alias,$sufix,$extraColumns);
//            $this->groupByAll($columns,$alias); 
//        }
//        else if(is_array($columns))
//        { 
//
//            if(!$this->aggregation) { $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text = " SELECT "; }
//            
//            $this->setColumns(false,$columns,'','',$extraColumns);
//        }
//              
//        return $this;
//    }
    
    public function distinctAll()
    {
       if(!empty($this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text))
       {
           $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text = preg_replace('`SELECT`','SELECT DISTINCT ',$this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text,1);
       } 
       
       return $this;
    }
    
    public function count(string $p,Array $options = [],$als = false)
    {
        if($this->aggregation)
        {
            $columns = $this->XmlObject->{$this->XmlObject->DataBase}['tables']['columns']['xml'];
            $alias = $this->XmlObject->{$this->XmlObject->DataBase}['tables']['xml']->getAttribute('alias').'_'.$this->XmlObject->{$this->XmlObject->DataBase}['tables']['idEntity'];
    
            $s = false;

            foreach($columns as $n)
            {
                if($n->getAttribute('entityAttribute') == $p)
                {
                    $nValue = $this->normalizeTableAndColumnsName(trim(str_ireplace(Array(PHP_EOL,"\r\n","\n"),'',$n->nodeValue)));

                    $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text = " SELECT COUNT(".$alias.".".$nValue.") As ".(($als ? $alias : '').$this->normalizeTableAndColumnsName('Count'.ucfirst($p))).',';

                    $s = true;

                    break;
                }
            }
			    //    echo '<pre>';print_r($this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text);exit;
            if(!$s){throw new \Harp\bin\ArgumentException('Attribute {'.$p.'} not mapped!');}       
        }
        
        return $this;  
    } 
    
    public function max(string $p,$als = null)
    {
        if($this->aggregation)
        {
            $columns = $this->XmlObject->{$this->XmlObject->DataBase}['tables']['columns']['xml'];
            $alias = $this->XmlObject->{$this->XmlObject->DataBase}['tables']['xml']->getAttribute('alias').'_'.$this->XmlObject->{$this->XmlObject->DataBase}['tables']['idEntity'];
            
            $s = false;

            foreach($columns as $n)
            {
                if($n->getAttribute('entityAttribute') == $p)
                {
                    $nValue = $this->normalizeTableAndColumnsName(trim(str_ireplace(Array(PHP_EOL,"\r\n","\n"),'',$n->nodeValue)));

                    $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text = " SELECT MAX(".$alias.".".$nValue.") As ".((!empty($als) ? $alias : $als).$this->normalizeTableAndColumnsName('Max'.ucfirst($p))).',';

                    $s = true;

                    break;
                }
            }

            if(!$s){throw new \Harp\bin\ArgumentException('Attribute {'.$p.'} not mapped!');}       
        }
        
        return $this;  
    }     

    public function groupBy(bool $useNumbers = false,Array $columns = array())
    {
        if(empty($columns))
        {
            $columns = $this->XmlObject->{$this->XmlObject->DataBase}['tables']['columns']['xml'];
            $alias = $this->XmlObject->{$this->XmlObject->DataBase}['tables']['xml']->getAttribute('alias').'_'.$this->XmlObject->{$this->XmlObject->DataBase}['tables']['idEntity'];
        }
        
        $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= PHP_EOL.' GROUP BY '.PHP_EOL;

        if(!$useNumbers && empty($columns))
        {            
            $this->groupByAll($columns,(isset($alias) ? $alias : null));
            
            $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= implode(',',array_reverse($this->listGroupBy));
        }
        else if(!$useNumbers && !empty($columns))
        {
            foreach($columns as $i => $n)
            {
                $n = $this->normalizeTableAndColumnsName($n);
                
                $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= $n;
                $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= ',';
            }
            
            $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text = rtrim($this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text,',');
        }
        else if($useNumbers)
        {
            foreach($columns as $i => $n)
            {
                $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= ($i + 1);
                $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= ',';
            }
            
            $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text = rtrim($this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text,',');
        }

        return $this;
    }
    
    public function cIf($column,$cond1,Array $values = [])
    {
        if(!empty($cond1) && !empty($values) && preg_match('`('.$column.')\b`',$this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text,$res))
        {
            $if = ' IF('.$column.chr(32).$cond1.', %s,%s)';
            
            $values = array_values($values);
            
            if(count($values) == 2)
            {
                $if = sprintf($if,$values[0],$values[1]);
            }
            else
            {
                $if = sprintf($if,$res[0],$values[0]);
            }

            $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text  = preg_replace('`('.$res[0].')\b`',$if,$this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text,1);
        }
        
        return $this;
    }
      
    public function inJoin($t1,$condition)
    {
        if(is_string($t1) && is_string($condition))
        {
            $k = md5($t1.$condition);
            
            $alias = '';
            
            $p = explode('.',$t1);
            
            if(count($p) == 2)
            {
                $alias = $p[0];
                $t1 = $p[1];
            }
            
            $this->listJoins[$k]['join']  = ' INNER JOIN '.PHP_EOL;
            $this->listJoins[$k]['join'] .= $t1.PHP_EOL.(!empty($alias) ? ' As '.$alias : $alias);
            $this->listJoins[$k]['join'] .= ' ON ';
            $this->listJoins[$k]['join'] .= $condition;
        }
            
        return $this;
    }

    public function addQueryBuilder(Selectelect $ObjSelect)
    {
            $c = $ObjSelect->getCommand();
            
            if(is_string($c))
            {
                $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= $c;
            }
            
            return $this;
    } 
    
    public function addJoinQuery(Selectelect $ObjSelect,$typeJoin,Array $tablesAliasCondition,$aliasQuery)
    {
            $key = md5($ObjSelect->getCommand());
           
            if($tablesAliasCondition['exprCondition'])
            {
                $this->listJoins[$key]['join'] = 
                         mb_strtoupper($typeJoin).' JOIN '.PHP_EOL
                        .'('.PHP_EOL
                            .$ObjSelect->getCommand()
                        .PHP_EOL.') As '.$aliasQuery.PHP_EOL
                        .' ON '.PHP_EOL
                        .$tablesAliasCondition['exprCondition'];
            }
 
            return $this;
    } 
    
    public function addInnerEquiJoin(Selectelect $ObjSelect)
    {
            $key = md5($ObjSelect->getCommand());
            
            $subList = $ObjSelect->getSubFieldsCondition();
            
            if(count($subList) == 2)
            {
                $alias1 = strstr($subList['field0'],'.',true);

                $this->listJoins[$key]['join'] = 
                         'INNER JOIN '.PHP_EOL
                        .'('
                        .PHP_EOL
                            .$ObjSelect->getCommand()
                        .PHP_EOL
                        .') As '.$alias1.PHP_EOL
                        .' ON '.PHP_EOL
                        .$subList['field0'].' = '.$subList['field1']; 
            }

            
            return $this;
    }
    
    public function addSelectAsField(Selectelect $ObjSelect,$alias)
    {
            $key = md5($ObjSelect->getCommand());

            $this->listSelectAsField[$key] = '('.$ObjSelect->getCommand().') As '.$alias;
            
            return $this;
    }
    
    public function setSubFieldsCondition($field0,$field1)
    {
        if(!empty($field0) && !empty($field1))
        {
            $this->subList['field0'] = $field0;
            $this->subList['field1'] = $field1;
        }
        
        return $this;
    }

    public function getSubFieldsCondition()
    {
        return $this->subList;
    }
    
    /****
     * @todo Adiciona um tabela como join através de uma tabela que possua sua referência, A tabela referência já deve ter sido inserida no comando anteriormente
     * @param type $by
     * @param type $tblJoin
     * @param type $options
     * @return $this 
     */
    public function joinBy($by,$tblJoin,$options)
    {
            $aliasBy = stristr($by,'.',true);
            $uTblBy = stristr($by,'.');
            $uTblBy = mb_substr($uTblBy,1);
            
            $aliasTblJoin = stristr($tblJoin,'.',true);
            $uTblJoin = stristr($tblJoin,'.');
            $uTblJoin = mb_substr($uTblJoin,1);

            if(isset($this->listJoins[$uTblBy]))
            {
                  $Xpath = new DOMXPath($this->XmlObject->xmlFile);  
                  $Node = $Xpath->query('//tables/table[@name="'.$uTblBy.'"]/column[@references="'.$uTblJoin.'"]');
                  if(!empty($Node) && $Node->length > 0)
                  {
                        $columnByReference = trim($Node->item(0)->nodeValue);
                        $Node2 = $Xpath->query('//tables/table[@entity="'.$uTblJoin.'"]/column[@primaryKey="true"]');
                        $columnJoinReference = trim($Node2->item(0)->nodeValue);

                          $NJoin = $Xpath->query('//tables/table[@entity="'.$uTblJoin.'"]');

                          $this->listJoins[$uTblJoin] = [];
                          $this->listJoins[$uTblJoin]['entity'] = $uTblJoin; 
                          $this->listJoins[$uTblJoin]['alias'] = $this->normalizeTableAndColumnsName($aliasTblJoin);
                          $this->listJoins[$uTblJoin]['name'] = $this->normalizeTableAndColumnsName($NJoin->item(0)->getAttribute('schema')).'.'.$this->normalizeTableAndColumnsName($uTblJoin);
                          $this->listJoins[$uTblJoin]['typeJoin'] = mb_strtoupper($options['typeJoin']);
                          $this->listJoins[$uTblJoin]['join'] = 
                                       $this->listJoins[$uTblJoin]['typeJoin'].' JOIN '
                                      .$this->listJoins[$uTblJoin]['name'].' As '
                                      .$aliasTblJoin.PHP_EOL.' ON '
                                      .$this->normalizeTableAndColumnsName($aliasTblJoin)
                                      .'.'    
                                      .$this->normalizeTableAndColumnsName($columnJoinReference).chr(32)
                                      .$options['opr'].chr(32)
                                      .$this->normalizeTableAndColumnsName($aliasBy).'.'
                                      .$this->normalizeTableAndColumnsName($columnByReference);     
                  }
    
            }
            
            return $this;
            
    }
    /****
     * @todo Adiciona um tabela como join, sendo que esta tabela deve possuir alguma outra tabela como referência 
     * @param type $by
     * @param type $tblJoin
     * @param type $options
     * @return $this 
     */
    public function joinByReference($by,$tblJoin,$options)
    {
            $aliasBy = stristr($by,'.',true);
            $uTblBy = stristr($by,'.');
            $uTblBy = mb_substr($uTblBy,1);
            
            $aliasTblJoin = stristr($tblJoin,'.',true);
            $uTblJoin = stristr($tblJoin,'.');
            $uTblJoin = mb_substr($uTblJoin,1);
            
            $Xpath = new DOMXPath($this->XmlObject->xmlFile);
            //verifica se a tabela referência possui realmente uma referência da tabela que será utilizada para join
            $Node = $Xpath->query('//tables/table[@name="'.$uTblJoin.'"]/column[@references="'.$uTblBy.'"]');
        
            if(!empty($Node) && $Node->length > 0)
            {
                        $columnByReference = trim($Node->item(0)->nodeValue);
              
                        //Pega a coluna primary key da tabela candidata ao join,
                        //esta coluna será utilizada para a claúsula ON do JOIN
                        $NodePrimary = $Xpath->query('//tables/table[@entity="'.$uTblJoin.'"]/column[@primaryKey="true"]');
                        $columnJoinReference = trim($NodePrimary->item(0)->nodeValue);
       
                        //Pega a tabela candidata ao join
                          $NodeJoin = $Xpath->query('//tables/table[@entity="'.$uTblJoin.'"]');
                          $this->listJoins[$uTblJoin] = [];
                          $this->listJoins[$uTblJoin]['entity'] = $uTblJoin; 
                          $this->listJoins[$uTblJoin]['alias'] = $this->normalizeTableAndColumnsName($aliasTblJoin);
                          $this->listJoins[$uTblJoin]['name'] = $this->normalizeTableAndColumnsName($NodeJoin->item(0)->getAttribute('schema')).'.'.$this->normalizeTableAndColumnsName($uTblJoin);
                          $this->listJoins[$uTblJoin]['typeJoin'] = mb_strtoupper($options['typeJoin']);
                          $this->listJoins[$uTblJoin]['join'] = 
                                       $this->listJoins[$uTblJoin]['typeJoin'].' JOIN '
                                      .$this->listJoins[$uTblJoin]['name'].' As '
                                      .$aliasTblJoin.PHP_EOL.' ON '
                                      .$this->normalizeTableAndColumnsName($aliasTblJoin)
                                      .'.'    
                                      .$this->normalizeTableAndColumnsName($columnByReference).chr(32)
                                      .$options['opr'].chr(32)
                                      .$this->normalizeTableAndColumnsName($aliasBy).'.'
                                      .$this->normalizeTableAndColumnsName($columnJoinReference);   
                        
            }
            
            return $this; 
    }
    
    public function addByReference($EntityName,$EntityReference,$aliasEntityReference = null,$typeJoin = 'INNER',Array $tablesAliasCondition = [],$useFields = true)
    {           
            $alias = stristr($EntityName,'.',true);
            $eny = stristr($EntityName,'.');
            
            $aliasRef = stristr($EntityReference,'.',true);
            $enyRef = stristr($EntityReference,'.');
            
            $EntityReference = substr($enyRef,1);
            $EntityName = substr($eny,1);
            
            $Xpath = new DOMXPath($this->XmlObject->xmlFile);
            $Node = $Xpath->query('//tables/table[@name="'.$EntityName.'"]/column[@references="'.$EntityReference.'"]');
            
            
            if(!empty($Node) && $Node->length > 0)
            {
                $Node2 = $Xpath->query('//tables/table[@entity="'.$Node->item(0)->getAttribute('references').'"]');
                
                if(!empty($Node2) && $Node2->length > 0)
                {

                    $alias = !empty($alias) ? $alias : $Node2->item(0)->getAttribute('alias').'_'.$this->XmlObject->{$this->XmlObject->DataBase}['tables']['idEntity'];
                  
                    if(!$this->aggregation && $useFields) 
                    {
                        $this->select($Node2->item(0),$EntityName,$alias,false); 
                    }

                    $this->groupByAll($Node2->item(0),$alias);
                    $nValue = $this->normalizeTableAndColumnsName(trim(str_ireplace([PHP_EOL,"\r\n","\n"],'',$Node->item(0)->nodeValue)));
               
                    $N2 = $Xpath->query('//tables/table[@entity="'.$Node->item(0)->getAttribute('references').'"]/column[@primaryKey="true"]');
     
                    $nValue2 = $nValue;
              
                    if($N2->length == 1)
                    {
                        $nValue2 = $this->normalizeTableAndColumnsName(trim($N2->item(0)->nodeValue));
                    }

                    $this->listJoins[$EntityName] = [];
                    $this->listJoins[$EntityName]['entity'] = $EntityName; 
                    $this->listJoins[$EntityName]['alias'] = $this->normalizeTableAndColumnsName($alias);
                    $this->listJoins[$EntityName]['name'] = $this->normalizeTableAndColumnsName($Node2->item(0)->getAttribute('schema')).'.'.$this->normalizeTableAndColumnsName($EntityName);
                    $this->listJoins[$EntityName]['typeJoin'] = mb_strtoupper($typeJoin);

                    if(isset($tablesAliasCondition['opr']))
                    {       
                        $this->listJoins[$EntityName]['join'] = 
                                 mb_strtoupper($typeJoin).' JOIN '
                                .$this->listJoins[$EntityName]['name'].' As '
                                .$this->listJoins[$EntityName]['alias'].PHP_EOL.' ON '
                                .$aliasRef
                                .'.'    
                                .$nValue2.chr(32)
                                .$tablesAliasCondition['opr'].chr(32)
                                .$this->listJoins[$EntityName]['alias'].'.'
                                .$nValue;                                
                    }
                    else if(isset($tablesAliasCondition['exprCondition']))
                    {
                        $this->listJoins[$EntityName]['join'] = 
                                 mb_strtoupper($typeJoin).' JOIN '
                                .$this->listJoins[$EntityName]['name'].' As '
                                .$this->listJoins[$EntityName]['alias'].' ON '
                                .$tablesAliasCondition['exprCondition'];
                    }
                    
                }
            }    
            
     
        return $this;
    }   
    
    public function addWithReference($EntityName,$EntityReference,$aliasEntityReference = null,$typeJoin = 'INNER',Array $tablesAliasCondition = [],$useFields = true)
    {           
            $alias = stristr($EntityName,'.',true);
            $eny = stristr($EntityName,'.');

            if(is_string($alias))
            {
                $EntityName = substr($eny,1);
            }
            
            $Xpath = new DOMXPath($this->XmlObject->xmlFile);
            $Node = $Xpath->query('//tables/table[@name="'.$EntityReference.'"]/column[@references="'.$EntityName.'"]');

            
            if(!empty($Node) && $Node->length > 0)
            {
                $Node2 = $Xpath->query('//tables/table[@entity="'.$EntityName.'"]');
      
                if(!empty($Node2) && $Node2->length > 0)
                {

                    $alias = !empty($alias) ? $alias : $Node2->item(0)->getAttribute('alias').'_'.$this->XmlObject->{$this->XmlObject->DataBase}['tables']['idEntity'];
                  
                    if(!$this->aggregation && $useFields) 
                    {
                        $this->select($Node2->item(0),$EntityName,$alias,false); 
                    }

                    $this->groupByAll($Node2->item(0),$alias);
                    $nValue = $this->normalizeTableAndColumnsName(trim(str_ireplace(Array(PHP_EOL,"\r\n","\n"),'',$Node->item(0)->nodeValue)));
                    
                    $N2 = $Xpath->query('//tables/table[@entity="'.$EntityName.'"]/column[@primaryKey="true"]');
     
                    $nValue2 = $nValue;
                    
                    if($N2->length == 1)
                    {
                        $nValue2 = $this->normalizeTableAndColumnsName(trim($N2->item(0)->nodeValue));
                    }

                    $this->listJoins[$EntityName] = [];
                    $this->listJoins[$EntityName]['entity'] = $Node2->item(0)->getAttribute('entity'); 
                    $this->listJoins[$EntityName]['alias'] = $this->normalizeTableAndColumnsName($alias);
                    $this->listJoins[$EntityName]['name'] = $this->normalizeTableAndColumnsName($Node2->item(0)->getAttribute('schema')).'.'.$this->normalizeTableAndColumnsName($Node2->item(0)->getAttribute('name'));
                    $this->listJoins[$EntityName]['typeJoin'] = mb_strtoupper($typeJoin);
         
                    if(isset($tablesAliasCondition['opr']))
                    {       
           
                        $this->listJoins[$EntityName]['join'] = 
                                 mb_strtoupper($typeJoin).' JOIN '
                                .$this->listJoins[$EntityName]['name'].' As '
                                .$this->listJoins[$EntityName]['alias'].PHP_EOL.' ON '
                                .(empty($aliasEntityReference) ? $this->XmlObject->{$this->XmlObject->DataBase}['tables']['xml']->getAttribute('alias').'_'.$this->XmlObject->{$this->XmlObject->DataBase}['tables']['idEntity'] : $aliasEntityReference)
                                .'.'    
                                .$nValue.chr(32)
                                .$tablesAliasCondition['opr'].chr(32)
                                .$this->listJoins[$EntityName]['alias'].'.'
                                .$nValue2;                                
                    }
                    else if(isset($tablesAliasCondition['exprCondition']))
                    {
                        $this->listJoins[$EntityName]['join'] = 
                                 mb_strtoupper($typeJoin).' JOIN '
                                .$this->listJoins[$EntityName]['name'].' As '
                                .$this->listJoins[$EntityName]['alias'].' ON '
                                .$tablesAliasCondition['exprCondition'];
                    }
                    
                }
            }                    
        
        return $this;
    }    
     
    public function add($EntityName,$typeJoin,Array $tablesAliasCondition,$useFields = true)
    {          
            $alias = stristr($EntityName,'.',true);
            $eny = stristr($EntityName,'.');

            if(is_string($alias))
            {
                $EntityName = substr($eny,1);
            }

            $Xpath = new DOMXPath($this->XmlObject->xmlFile);
            $Node = $Xpath->query('//tables/table[@name="'.$this->XmlObject->{$this->XmlObject->DataBase}['tables']['xml']->getAttribute('name').'"]/column[@references="'.$EntityName.'"]');

            if(!empty($Node) && $Node->length > 0)
            {
                $Node2 = $Xpath->query('//tables/table[@entity="'.$EntityName.'"]');
             
                if(!empty($Node2) && $Node2->length > 0)
                {

                    $alias = !empty($alias) ? $alias : $Node2->item(0)->getAttribute('alias').'_'.$this->XmlObject->{$this->XmlObject->DataBase}['tables']['idEntity'];
                    
                    if(!$this->aggregation && $useFields) 
                    {
                        $this->select($Node2->item(0),$EntityName,$alias,false); 
                    }

                    $this->groupByAll($Node2->item(0),$alias);
                    $nValue = $this->normalizeTableAndColumnsName(trim(str_ireplace(Array(PHP_EOL,"\r\n","\n"),'',$Node->item(0)->nodeValue)));
                    
                    $N2 = $Xpath->query('//tables/table[@entity="'.$EntityName.'"]/column[@primaryKey="true"]');
                  
                    $nValue2 = $nValue;
                    
                    if($N2->length == 1)
                    {
                        $nValue2 = $this->normalizeTableAndColumnsName(trim($N2->item(0)->nodeValue));
                    }

                    $this->listJoins[$EntityName] = [];
                    $this->listJoins[$EntityName]['entity'] = $Node2->item(0)->getAttribute('entity'); 
                    $this->listJoins[$EntityName]['alias'] = $this->normalizeTableAndColumnsName($alias);
                    $this->listJoins[$EntityName]['name'] = $this->normalizeTableAndColumnsName($Node2->item(0)->getAttribute('schema')).'.'.$this->normalizeTableAndColumnsName($Node2->item(0)->getAttribute('name'));
                    $this->listJoins[$EntityName]['typeJoin'] = mb_strtoupper($typeJoin);
  
                    if(isset($tablesAliasCondition['opr']))
                    {
                        $this->listJoins[$EntityName]['join'] = 
                                 mb_strtoupper($typeJoin).' JOIN '
                                .$this->listJoins[$EntityName]['name'].' As '
                                .$this->listJoins[$EntityName]['alias'].PHP_EOL.' ON '
                                .$this->XmlObject->{$this->XmlObject->DataBase}['tables']['xml']->getAttribute('alias').'_'.$this->XmlObject->{$this->XmlObject->DataBase}['tables']['idEntity'].'.'
                                .$nValue.chr(32)
                                .$tablesAliasCondition['opr'].chr(32)
                                .$this->listJoins[$EntityName]['alias'].'.'
                                .$nValue2;                                
                    }
                    else if($tablesAliasCondition['exprCondition'])
                    {
                        $this->listJoins[$EntityName]['join'] = 
                                 mb_strtoupper($typeJoin).' JOIN '
                                .$this->listJoins[$EntityName]['name'].' As '
                                .$this->listJoins[$EntityName]['alias'].' ON '
                                .$tablesAliasCondition['exprCondition'];
                    }
                }
            }                    
       
        return $this;
    }
    
    public function fromDeprecated()
    {
        $table = $this->ConfigORM->getCurrentTable();
        $alias = $this->normalizeTableAndColumnsName($table['attributes']['alias']);
        $tableName = $this->normalizeTableAndColumnsName($table['attributes']['schema']).'.'.$this->normalizeTableAndColumnsName($table['attributes']['table']);
        $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text = substr($this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text,0,(mb_strlen($this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text) - 1));
        $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= ' FROM ';
        $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= $tableName;
        $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= ' As ';
        $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= $alias;

        foreach($this->listJoins as $j)
        {
            $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= PHP_EOL.$j['join'].chr(32);
        }
//echo $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text;exit;
       // $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text = str_ireplace($dinamicAlias,$alias,$this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text);

        return $this;
    }
    
    private function prepareJoin($tableName,Array $tablesAliasCondition,$typeJoin = self::INNER_JOIN)
    {
        $c2 = count($tablesAliasCondition);

        try
        {
            if(!is_string($tableName))
            {
                throw new \Harp\bin\ArgumentException('Table name invalid!');
            }
            else if($c2 != 3)
            {
                throw new \Harp\bin\ArgumentException('invalid parameters condition!');
            }

            $alias = stristr($tablesAliasCondition[0],'.',true);
            
            $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= $typeJoin;
            $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .=  PHP_EOL;
            $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .=  ' '.$tableName.' ';
            $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .=  'As '.$alias;
            $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .=  ' ON ';
            $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= $tablesAliasCondition[0];
            $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= ' '.$tablesAliasCondition[1];
            $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= ' '.$tablesAliasCondition[2];
            
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
    }
    
    public function fullJoin($tableName,Array $tablesAliasCondition)
    {
        try
        {
            $this->prepareJoin($tableName, $tablesAliasCondition,self::FULL_JOIN);
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return $this;
    }     
    
    public function rightJoin($tableName,Array $tablesAliasCondition)
    {
        try
        {
            $this->prepareJoin($tableName,$tablesAliasCondition,self::RIGHT_JOIN);
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return $this;
    }    
    
    public function leftJoin($tableName,Array $tablesAliasCondition)
    {
        try
        {
            $this->prepareJoin($tableName,$tablesAliasCondition,self::LEFT_JOIN);
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return $this;
    }
    
    
    public function innerJoin($tableName,Array $tablesAliasCondition)
    {
        try
        {
            $this->prepareJoin($tableName,$tablesAliasCondition);
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return $this;
    } 
    
    public function &getCommand()
    {
        if(!$this->hasPagination && $this->PaginationData instanceof PaginationData)
        {   
            $this->hasPagination = true;
            $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= PHP_EOL.' LIMIT '.$this->PaginationData->getLimit();
            $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= PHP_EOL.' OFFSET '.$this->PaginationData->getOffset();
        }
        else
        {
            self::$ConnectionDriver->CommandHandlerSQL->offset($this->offset);
            self::$ConnectionDriver->CommandHandlerSQL->limit($this->limit['r'],$this->limit['l']);            
        }
        
        return parent::getCommand();
    }


    public function getResult($nameStatement)
    {
        try
        {
            $this->ConfigORM->getConnectionDriver()->connect();
            
            $result = $this->ConfigORM->getConnectionDriver()->executeQueryFetchResult($nameStatement);
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return $result;
    }

    public function executeReader($clear = true)
    {
        try
        {
            $nameStatement = $this->prepareParams();

            $result = $this->getResult($nameStatement);
            
            if($clear)
            {
                 $this->toCleanCommand();
            }
           
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return $result;
    }
    
    public function getResultAndClear()
    {        
        try
        {
            $nameStatement = parent::prepareParams();

            $result = $this->getResult($nameStatement);

            $this->toCleanCommand();
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return $result;
    }

    public function openParentheses()
    {
        $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= ' ( '.PHP_EOL;
        return $this;
    }
    
    public function closeParentheses()
    {
        $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= ' ) '.PHP_EOL;
        return $this;
    } 
    
    public function setText($text)
    {
        if(!empty($text))
        {
            $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= $text.PHP_EOL;
        }
        
        return $this;
    }
        
    public function where(Array $param = [])
    {        
        try
        {
            parent::where();
            
            $table = $this->ConfigORM->getProperty('table');  

            if(count($param) == 3)
            {               
                $alias = $table['attributes']['alias'];
                $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= sprintf(
                    "%s"."%s"."%s"."."."%s"."%s"."%s"." %s %s", 
                    $this->ConfigORM->getConnectionDriver()->getSpecialChar(),
                    $alias,
                    $this->ConfigORM->getConnectionDriver()->getSpecialChar(),
                    $this->ConfigORM->getConnectionDriver()->getSpecialChar(),
                    $param[0],
                    $this->ConfigORM->getConnectionDriver()->getSpecialChar(),
                    $param[1],
                    $param[2],
                 ); 
            }
            else if(count($param) == 4)
            {
                $alias = $param[0];
                $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= sprintf(
                    "%s"."%s"."%s"."."."%s"."%s"."%s"." %s %s", 
                    $this->ConfigORM->getConnectionDriver()->getSpecialChar(),
                    $alias,
                    $this->ConfigORM->getConnectionDriver()->getSpecialChar(),
                    $this->ConfigORM->getConnectionDriver()->getSpecialChar(),
                    $param[1],
                    $this->ConfigORM->getConnectionDriver()->getSpecialChar(),
                    $param[2],
                    $param[3],
                 );  
            }
            else
            {
                throw new ArgumentException('The number of parameters passed to the WHERE clause are incompatible. Permitted parameter numbers {3} or {4}!');
            }

        }
        catch(\Exception $ex)
        {
            throw $ex;
        }

        return $this;
    }
    
    public function conjAND($and,$alias = null,$replaceForAlias = null,$normalizeAlias = null, $normalizeWhere = null,$startGroup = false)
    {        
        $normalized = $this->normlizeAnd($and, $alias, $normalizeAlias, $normalizeWhere);
        
        parent::conjAND($normalized['and'],$normalized['alias'], $replaceForAlias, $startGroup);
        
        return $this;
    }    
        
    public function useFilter(HarpFilterSql $FilterSql)
    {
        if($FilterSql instanceof HarpFilterSql)
        {

            if(is_string($FilterSql->getBase64String()))
            {
                $this->FilterSql = new HarpFilterSql($FilterSql->getBase64String());
                $this->FilterSql->toDictionary();
               
            }
        }
        
        return $this;
    }
    
    public function prepareParams()
    {
        
       /* if(!empty($this->listOrderBy))
        {
           $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= PHP_EOL.' ORDER BY '.implode(',',$this->listOrderBy); 
        }*/
             
//        if($this->FilterSql instanceof HarpFilterSql && !$this->FilterSql->isEmptyFilter())
//        {   
//            
//            if(!$this->alreadyUsedWhere)
//            {
//                $this->FilterSql->explicitWhereClause();
//            }
//            
//            $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= chr(32).$this->FilterSql->getCommandTextWithParameters().chr(32);
//                        
//            foreach ($this->FilterSql->getParametersValues() as $p)
//            {
//                self::$ConnectionDriver->CommandParameter->addParameter($p->param,$p->value,$p->type,$p->typeLength);
//                self::$ConnectionDriver->CommandParameter->commit($p->param,false); 
//            }
//        }
        
        return parent::prepareParams();
    }
    
    public function isAggregation()
    {
        return $this->aggregation;
    }
    
    public function getIdAlias()
    {
       return $this->XmlObject->{$this->XmlObject->DataBase}['tables']['idEntity'];
    }
}
