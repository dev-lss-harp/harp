<?php
namespace Harp\lib\HarpDatabase\orm;

use Exception;
use lib\pvt\HarpFilterSql\HarpFilterSql;
use etc\HarpDatabase\pagination\PaginationData;
use etc\HarpDatabase\ORM\MapORM;
use Harp\bin\ArgumentException;

class ORMSelect implements IORMWhere, IORMAnd, IORMOr, IORMGroup 
{
    const INNER_JOIN = ' INNER JOIN ';
    const LEFT_JOIN = ' LEFT JOIN ';
    const RIGHT_JOIN = ' RIGHT JOIN ';
    const FULL_JOIN  = ' FULL JOIN  ';

    private $listAllowedJoins = [
        self::INNER_JOIN,
        self::LEFT_JOIN,
        self::RIGHT_JOIN,
        self::FULL_JOIN
    ];
    
    private $listJoins;
    private $listOrderBy = [];
    private $listGroupBy;
    private $aggregation;
    private $FilterSql;
    private $limit = [];
    private $subList = [];
    private $listSelectAsField = [];
    public $PaginationData;

    private $table;
    private $ConfigORM;



    
    public function __construct(ORM $ConfigORM)
    {
        $this->ConfigORM = $ConfigORM;
        
        $this->table =  $this->ConfigORM->getProperty('table');

        $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text = sprintf(
           '%s ',
           'SELECT ',     
        );
  
    }

    public function where(Array $param)
    {
        $this->ConfigORM->where($param);

        return $this;
    }

    public function and(Array $param)
    {
        $this->ConfigORM->and($param);

        return $this;
    }

    public function or(Array $param)
    {
        $this->ConfigORM->or($param);

        return $this;
    }

    public function startGroup()
    {
        return $this->ConfigORM->startGroup(); 
    }

    public function endGroup()
    {
        return $this->ConfigORM->endGroup();
    }

    public function limit(Array $sLimits,Array $eLimits = [])
    {        
        $this->limit['r'] = $sLimits;
        $this->limit['l'] = $eLimits;

        return $this;
    }

    public function offset(Array $offsets)
    {
        $this->offset = $offsets;

        return $this;
    }  
  

    public function orderByAdd($orderClause,$orderType,$alias = null)
    {
        $type = !empty($orderType) ? strtoupper($orderType) : $orderType;
        
        $alias = empty($alias) ? ($this->XmlObject->{$this->XmlObject->DataBase}['tables']['xml']->getAttribute('alias').'_'.$this->XmlObject->{$this->XmlObject->DataBase}['tables']['idEntity']) : $alias;
        
        if(!empty($orderClause) && ($type == 'ASC' || $type == 'DESC'))
        {
            $this->listOrderBy[] =  $alias.'.'.$orderClause.chr(32).$type;
        }

        return $this;
    }
    
    public function groupByAdd($column,$alias = null)
    {        
        if(!empty($column))
        {
            $alias = empty($alias) ? '' : '.'.$alias;
            
            $this->listGroupBy[] =  $alias.$column.chr(32);
        }
        
        return $this;        
    }    
    
    private function groupByAll($columns,$alias = null)
    {        
        $alias = empty($alias) ? $this->XmlObject->{$this->XmlObject->DataBase}['tables']['xml']->getAttribute('alias').'_'.$this->XmlObject->{$this->XmlObject->DataBase}['tables']['idEntity'] : $alias;

        if(is_object($columns))
        {
            if($columns instanceof \DOMElement)
            {
                $columns = $columns->getElementsByTagName('column');
            }

            foreach($columns as $n)
            {
                $nValue = (trim(str_ireplace(Array(PHP_EOL,"\r\n","\n"),'',$n->nodeValue)));

                $this->listGroupBy[] =  $alias.'.'.$nValue.chr(32);
            }     
        }
        else
        {
            foreach($columns as $n)
            {

                $this->listGroupBy[] =  $alias.'.'.$n.chr(32);
            }     
        }

        return $this;        
    }
    
    public function selectByColumns(Array $columns = [])
    {
        if(empty($columns))
        { 
            throw new ArgumentException('Columns not specified for the select!'); 
        }
        
        $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text = " SELECT "; 

        $this->setColumns(false,$columns,'','',[]);
        
        return $this;
 
    }
    

    private function defineColumns($columns)
    {
        $cols = $columns;

        if(isset($columns['*']))
        {
            $cols =[];
            foreach($this->table['columns'] as $key => $val)
            {
                array_push($cols,$key);
            }

            $cols = array_flip($cols);
        }

        return $cols;
    }

    private function setColumns($columns,$alias,$nameEntity,$firstComa = '')
    {      
        $strColumns = chr(32);

        if(is_array($columns))
        {
            $columns = $this->defineColumns($columns);

            $k = 0;    
 
            foreach($columns as $nameColumn => $attributes)
            {  
                $nameColumn = $this->ConfigORM->getColumnsByEntity($nameColumn,$nameEntity);

                $as = '';
                $onlyColumn = $nameColumn;
                $posAs = '';
                $spc = true;
                
                if(preg_match('`\b as \b`i',$nameColumn,$res))
                {
                    $as = ' as ';
                    $rx = explode($res[0],$nameColumn);
                    $onlyColumn = trim($rx[0]);
                    $posAs = trim($rx[1]);

                    foreach($this->ConfigORM->getConnectionDriver()->listFunctions as $func)
                    {
                        if(preg_match('`'.$func.'`i',$onlyColumn))
                        {
                            $spc = false;
                        }
                    }
                }

                if(!empty($nameColumn))
                {
                    if(!empty($as) && !empty($posAs))
                    {
                        $strFormat = "%s %s.%s %s %s".PHP_EOL;
                        $strColumns .= sprintf(
                                           $strFormat, 
                                           $k > 0 ? ',' : $firstComa, 
                                           $this->ConfigORM->addSpecialChar($alias),
                                           $spc ? $this->ConfigORM->addSpecialChar($onlyColumn) : $onlyColumn,
                                           $as,
                                           $this->ConfigORM->addSpecialChar($posAs)
                                ); 
                    }
                    else
                    {
                        $strFormat = "%s %s.%s".PHP_EOL;
                        $strColumns .= sprintf(
                                           $strFormat, 
                                           $k > 0 ? ',' : $firstComa, 
                                           $this->ConfigORM->addSpecialChar($alias),
                                           $spc ? $this->ConfigORM->addSpecialChar($onlyColumn) : $onlyColumn
                                ); 
                    }
            
                    ++$k;
                }

            }  
        }

        return $strColumns;
        
    }    
        
    private function addAutoJoin(Array $attributesTable,$fkInfo,$nameColumn)
    {
        
        $updatedCommand = chr(32);
 
        try
        {
            $fkTable = $this->ConfigORM->getTableByName($fkInfo['references']);

       
            $tbRelation = mb_strtoupper($fkInfo['relation']);
            $tbOperator = $fkInfo["operator"];
            $tbAlias = ($attributesTable["alias"]);
            $tbColumnFk = ($nameColumn);
            
            $fkSchemaTable = ($fkTable["attributes"]["schema"]);
            $fkNameTable = ($fkTable["attributes"]["table"]);
            $fkAliasTable = ($fkTable["attributes"]["alias"]);            
            $fkColumnName = ($fkInfo["columnName"]);
            
            if(empty($fkTable))
            {
                throw new Exception('{'.$fkInfo['references'].'} configuration not found in file json!');
            }

            $cmdFormat = $updatedCommand." %s ".PHP_EOL."%s.%s as %s ".PHP_EOL." ON ".PHP_EOL." %s.%s %s %s.%s".PHP_EOL;

            $updatedCommand = sprintf($cmdFormat,
                        $tbRelation,
                        $this->ConfigORM->addSpecialChar($fkSchemaTable),
                        $this->ConfigORM->addSpecialChar($fkNameTable),
                        $this->ConfigORM->addSpecialChar($fkAliasTable),
                        $this->ConfigORM->addSpecialChar($fkAliasTable),
                        $this->ConfigORM->addSpecialChar($fkColumnName),
                        $tbOperator,
                        $this->ConfigORM->addSpecialChar($tbAlias),
                        $this->ConfigORM->addSpecialChar($tbColumnFk) 
                    );
        }
        catch(Exception $ex)
        {
            throw $ex;
        }

        return $updatedCommand;
    }   
    
    
    private function includeTablesInRelation($table)
    {
        if(isset($table['includeInJoin']))
        {
            foreach($table['includeInJoin'] as $include)
            {
                foreach($include as $prop => $obje)
                {
                    $table['columns'][$prop] = $obje;    
                }
              
            }
        }

        return $table;
    }

   
    
    
    private function setColumnsForFk($table,Array $cols,$nameEntity)
    {
        $attributesFk = 
        [
            "entityAttribute",
            "references",
            "relation",
            "operator",
            "columnName",
            "entityName"
        ];


        $schema =  $this->ConfigORM->getJsonObject();

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
                    else if(!isset($schema[$column['fk']['references']]))
                    {
                        throw new Exception('Invalid fk reference: {'.$column['fk']['references'].'}');
                    }
    
                    $referenceTable = $schema[$column['fk']['references']];
      
                    $nameEntity = $column['fk']['entityName'];


                    if(empty($referenceTable))
                    {
                         throw new Exception('Table {'.$column['fk']['references'].'} was not found in the json declaration.');
                    }  
   
         
                    $columns = $this->ConfigORM->getSelectedColumns($cols,$referenceTable['columns'],$nameEntity);  

                    $alias = $this->ConfigORM->selectAliases(true,$referenceTable['attributes']['alias'],$nameEntity);

                    $alias = ($referenceTable['attributes']['alias']);            
                    
                    //checks if it is necessary to put a comma, 
                    //in cases where fields were not selected from the main table, 
                    //it is not necessary.
                    $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= 
                    sprintf
                    (   
                        " %s ",
                        trim($this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text)
                        ==
                        'SELECT'
                        ?
                        $this->setColumns($columns,$alias,$nameEntity,"")
                        :
                        $this->setColumns($columns,$alias,$nameEntity,",")
                    );
                }
            } 
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
    }
    
   /**
     * Verifica no arquivo json as relações definidas para determinada tabela e gera os joins de forma automática.
     * @param Array informações da tabela com metadados e colunas.
     * @param Array colunas específicas passadas quando não se deseja fazer join com todas as colunas da tabela.
     * @param String nome da entidade, quando o parâmetro 2 for passado ela servirá para verificar de qual tabela pertence a coluna passada.
     * @author Leonardo Souza(lss.leonardo.dev@gmail.com)
     * @version 1.0
     * @return String
     */    
    private function getAutoJoins($table,$cols,$nameEntity)
    {
        $strCommand = chr(32);
        
        try
        {           
                $attributes = $table['attributes'];

                $table = $this->includeTablesInRelation($table);

                $this->setColumnsForFk($table,$cols,$nameEntity);    

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
     
    public function from($table)
    {
        $cmdFomat = $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text." FROM ".PHP_EOL." %s.%s AS %s ".PHP_EOL;

        $schema = ($table['attributes']['schema']);
        $nameEntity = $table['attributes']['entity'];

        $nameTable  = ($table['attributes']['table']);

        $alias = ($this->ConfigORM->listOfUsedAliases[$nameEntity][count($this->ConfigORM->listOfUsedAliases[$nameEntity]) - 1]);

        $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text = sprintf(
                $cmdFomat,
                $schema,
                $nameTable,
                $alias
             );

        return $this;
    }

    public function orderBy(Array $order)
    {
        try 
        {
            if(count($order) === 3)
            {
                $entity = $order[0];
                $entityAttr = $order[1];
                $ord = $order[2];

                $alias = $this->ConfigORM->selectAliases(false,$this->table['attributes']['alias'],$entity);

                $columnName = $this->ConfigORM->getColumnByEntity($entityAttr,$this->table['columns']);

                $order = '';

                if(empty($this->listOrderBy))
                {
                    $order = sprintf(
                        ' %s ', 
                        'ORDER BY'
                    );
                }
                else
                {
                    $order = sprintf(
                        ' %s ', 
                        ','
                    );
                }

                $order .= sprintf(
                    ' %s.%s %s ', 
                    $alias,
                    $columnName, 
                    $ord
                ); 

                $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= $order;

                array_push($this->listOrderBy,$order);
            }
        }
        catch (\Throwable $th) 
        {
            throw $th;
        }


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

    public function getAliasesByNameEntity($nameEntity,$index = 0)
    {
        $alias = null;

        if(isset($this->ConfigORM->listOfUsedAliases[$nameEntity]))
        {
            $it = 0;

            foreach($this->ConfigORM->listOfUsedAliases[$nameEntity] as $k => $name)
            {
                if($k === $index)
                {
                    $alias = $name;
                    break;
                }
            }
        }

        return $alias;
    }

    private function join($columns)
    {
        $strCommand = chr(32);
        $strColumns = chr(32);
        
        try
        {
            foreach($this->listJoins as $pkReference => $obj)
            {
                $aliasRef = $this->ConfigORM->selectAliases(true,$obj['tbref']['attributes']['alias'],$obj['tbref']['attributes']['entity']);
                $aliasRef  = ($aliasRef);

                $strColumns .= $this->setColumns($columns,$aliasRef,$obj['tbref']['attributes']['entity'],$firstComa = ',');

                $alias = $this->ConfigORM->selectAliases(true,$obj['tb']['attributes']['alias'],$obj['tb']['attributes']['entity']);
                $alias  = ($alias);

                $strCommand .= sprintf(
                                       '%s '.PHP_EOL.' %s.%s as %s'.PHP_EOL.'ON'.PHP_EOL.' %s.%s = %s.%s'.PHP_EOL,
                                        $obj['rel'],
                                        $this->ConfigORM->addSpecialChar($obj['tbref']['attributes']['schema']),
                                        $this->ConfigORM->addSpecialChar($obj['tbref']['attributes']['table']),
                                        $this->ConfigORM->addSpecialChar($aliasRef),
                                        $this->ConfigORM->addSpecialChar($aliasRef),
                                        $this->ConfigORM->addSpecialChar($pkReference),
                                        $this->ConfigORM->addSpecialChar($alias),
                                        $this->ConfigORM->addSpecialChar($obj['fk']['columnName']),
                );
            }
                
        }
        catch(Exception $ex)
        {
            throw $ex;
        }   
        
        return ['cols' => $strColumns,'joins' => $strCommand];

    }

    public function selectCount(Array $cols = [],String $as = '')
    {
        try
        {
            $nameEntity = $this->ConfigORM->getProperty('name');

            reset($cols);
            $k = key($cols);
            $cols = (count($cols) > 1) ? [$cols[$k]] : $cols;
            $columns = empty($cols) ? $this->table['columns'] : array_flip($cols);
        
            $alias = $this->ConfigORM->selectAliases(true,$this->table['attributes']['alias'],$nameEntity);

            if($cols[0] != '*')
            {
                $strCols = $this->setColumns($columns,$alias,$nameEntity);
            }
            else
            {
                $strCols = '*';
            }

            $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= sprintf(" %s%s%s ","COUNT(",$strCols,") ".(!empty($as) ? 'as '.$as : $as) );
            
            $this->from($this->table); 
        }
        catch(\Exception $ex)
        {
            throw $ex;
        }
        
        return $this;
    }
    
    public function select($cols = [],$autoJoin = false,$incrementAlias = true)
    {       
        try
        {
            $nameEntity = $this->ConfigORM->getProperty('name');

            $columns = empty($cols) ? $this->table['columns'] : array_flip($cols);

            $alias = $this->ConfigORM->selectAliases($incrementAlias,$this->table['attributes']['alias'],$nameEntity);

            $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= sprintf(" %s ",$this->setColumns($columns,$alias,$nameEntity));

            $lj = [];
            if(!empty($this->listJoins))
            {
                $lj = $this->join($columns);
                $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= sprintf(
                    ' %s ', 
                    $lj['cols']
                );
            }

            if($autoJoin)
            {
                
                 $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= sprintf(
                    ' %s %s ', 
                    $this->getAutoJoins($this->table,$cols,$nameEntity),
                    !empty($lj['joins']) ? $lj['joins'] : chr(32)
                 );

            }
            else
            {
      
                $this->from($this->table);
            }    
        }
        catch(\Exception $ex)
        {      // echo $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text;echo '<br/>';
            throw $ex;
        }
              //echo $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text;echo '<br/>';
        return $alias;
    }    
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
                    $nValue = (trim(str_ireplace(Array(PHP_EOL,"\r\n","\n"),'',$n->nodeValue)));

                    $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text = " SELECT COUNT(".$alias.".".$nValue.") As ".(($als ? $alias : '').('Count'.ucfirst($p))).',';

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
                    $nValue = (trim(str_ireplace(Array(PHP_EOL,"\r\n","\n"),'',$n->nodeValue)));

                    $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text = " SELECT MAX(".$alias.".".$nValue.") As ".((!empty($als) ? $alias : $als).('Max'.ucfirst($p))).',';

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
                $n = ($n);
                
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

    public function addQueryBuilder(Select $ObjSelect)
    {
            $c = $ObjSelect->getCommand();
            
            if(is_string($c))
            {
                $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= $c;
            }
            
            return $this;
    }
    
    
    
    public function addJoin(EntityHandlerInterface $entity,EntityHandlerInterface $entityReference,$rel = self::INNER_JOIN)
    {

            if(!in_array($rel,$this->listAllowedJoins))
            {
                throw new Exception('this relation {'.$rel.'} is not allowed!');
            }

            $nameEntity = $entity->getEntityName();
            $table = $this->ConfigORM->getTableByNameEntity($nameEntity);

            $nameEntityReference = $entityReference->getEntityName();
            $tableReference = $this->ConfigORM->getTableByNameEntity($nameEntityReference);

            if(empty($table))
            {
                throw new Exception('table not found for entity {'.$nameEntity.'}!');
            }
            else if(empty($tableReference))
            {
                throw new Exception('table not found for entity {'.$nameEntityReference.'}!');
            }
            else if(empty($tableReference['attributes']['pk']))
            {
                throw new Exception('json file does not contains definition for PK attribute, table: {'.$tableReference['attributes']['name'].'}!');
            }

            $referencePk = $tableReference['attributes']['pk'];

            $fk = [];

            foreach($table['columns'] as $prop)
            {

                if(empty($prop['fk']))
                {
                  
                    continue;
                }
                else if(trim($prop['fk']['columnName']) != $referencePk)
                {
                    continue;
                }

                $fk = $prop;
            }

            $this->listJoins[$referencePk] = ['fk' => $fk['fk'],'tb' => $table,'tbref' => $tableReference,'rel' => $rel];

            return $this;
    } 
    
    public function addInnerEquiJoin(Select $ObjSelect)
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
    
    public function addSelectAsField(Select $ObjSelect,$alias)
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
    

    private function clearCommands()
    {
        $this->listOrderBy = [];
        $this->listGroupBy = [];
        $this->ConfigORM->clearFlags();
    }

    public function getResult($nameStatement)
    {
        try
        {
            $this->ConfigORM->getConnectionDriver()->connect();
            
            $result = $this->ConfigORM->getConnectionDriver()->executeQueryFetchResult($nameStatement);

            $this->clearCommands();
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
            $nameStatement = $this->ConfigORM->prepareParams();

            $result = $this->getResult($nameStatement);
            
            if($clear)
            {
                 $this->ConfigORM->toCleanCommand();
            }
           
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return $result;
    }

    public function getLastCommand()
    {
        return $this->ConfigORM->lastCommand;
    }
    
    public function getResultAndClear()
    {        
        try
        {
            $nameStatement = $this->ConfigORM->prepareParams();

            $result = $this->getResult($nameStatement);

            $this->ConfigORM->toCleanCommand();
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
}
