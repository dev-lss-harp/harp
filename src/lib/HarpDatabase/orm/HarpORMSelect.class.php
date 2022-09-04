<?php
namespace Harp\lib\HarpDB;

use Exception;
use DOMXPath;
use etc\HarpDatabase\ORM\XmlORM;
use lib\pvt\HarpFilterSql\HarpFilterSql;
use etc\HarpDatabase\pagination\PaginationData;
use etc\HarpDatabase\ORM\MapORM;

class HarpORMSelect extends AbstractORM
{
    const INNER_JOIN = ' INNER JOIN ';
    const LEFT_JOIN = ' LEFT JOIN ';
    const RIGHT_JOIN = ' RIGHT JOIN ';
    const FULL_JOIN  = ' FULL JOIN  ';
    
    private $listJoins;
    private $listOrderBy;
    private $listGroupBy;
    private $aggregation;
    private $FilterSql;
    private $limit = Array();
    private $offset = Array();
    private $hasPagination = false;
    private $subList = Array();
    private $listSelectAsField = Array();
    public $PaginationData;
    
    public function __construct(MapORM $ConfigORM,$aggregation = false)
    {
        parent::__construct($ConfigORM);

        $this->listJoins = Array();
        $this->listOrderBy = Array();
        $this->aggregation = $aggregation;
        $this->PaginationData = null;
        $this->limit = ['r' => [],'l' => []];
        $this->XmlObject->ConnectionDriver->CommandText->getCommand()->text = &$this->getCommand();
  
    }
    
    public function getTableName()
    {
        return $this->XmlObject->{$this->XmlObject->DataBase}['tables']['xml']->getAttribute('name');
    }    
    
    public function addFieldsForEncrypt(Array $fields)
    {
        $this->XmlObject->ConnectionDriver->setColumnsToEncrypt($fields);
        
        return $this;
    }
    
    public function addFieldAsKey($FieldName)
    {
       $this->XmlObject->ConnectionDriver->addFieldAsKey($FieldName); 
       
       return $this;
    }

    public function limit(Array $sLimits,Array $eLimits = Array())
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
        $type = !empty($orderClause) ? strtoupper($orderType) : $orderType;
        
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
                $nValue = $this->normalizeTableAndColumnsName(trim(str_ireplace(Array(PHP_EOL,"\r\n","\n"),'',$n->nodeValue)));

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
    
    private function setColumns(bool $useAliasColumns,$columns,$alias = '',$sufix = '',Array $extraColumns = Array())
    {     
        if(is_object($columns))
        {
            if($columns instanceof \DOMElement)
            {
                $columns = $columns->getElementsByTagName('column');
            }
            
            foreach($columns as $n)
            {
                
                $nValue = trim(str_ireplace([PHP_EOL,"\r\n","\n"],['','',''],$n->nodeValue));

                $nValue = $this->normalizeTableAndColumnsName($nValue);
             
                if(!empty($alias))
                {            
                    $this->command .= $alias;
                    $this->command .= '.';   
                }

                $this->command .= $nValue;

                if($useAliasColumns)
                {
                    $this->command .= ' As ';   
                    $this->command .= !empty($sufix) ? $this->csToken.$n->getAttribute('entityAttribute').$sufix.$this->csToken : $this->csToken.$n->getAttribute('entityAttribute').$this->csToken;                
                }

                $this->command .= PHP_EOL;
                $this->command .= ',';
            }  
        }
        else if(is_array($columns))
        {
            foreach($columns as $n)
            {  
        
                $n = $this->normalizeTableAndColumnsName($n);
               
                $this->command .= $n;
                $this->command .= PHP_EOL;
                $this->command .= ',';
            }  
        }
        //echo '<pre>';print_r($this->listSelectAsField);
        if(!empty($this->listSelectAsField))
        {
            foreach($this->listSelectAsField as $ix => $n)
            {
                $n = $this->normalizeTableAndColumnsName($n);
                
                $this->command .= $n;
                $this->command .= PHP_EOL;
                $this->command .= ',';
                unset($this->listSelectAsField[$ix]);
            }   
   
        }

        if(!empty($extraColumns))
        {
            foreach($extraColumns as $n)
            {   
                $n = $this->normalizeTableAndColumnsName($n);
                
                $this->command .= $n;
                $this->command .= PHP_EOL;
                $this->command .= ',';
            }          
        } 
        
    }

    public function selectByColumns(Array $columns = [])
    {
        if(empty($columns))
        { 
            throw new \Harp\bin\ArgumentException('Columns not specified for the select!'); 
        }
        
        $this->command = " SELECT "; 

        $this->setColumns(false,$columns,'','',[]);
        
        return $this;
 
    }
    
    public function select($columns = null,$sufix ='',$alias = null,$injected = false,$extraColumns = [])
    {       
      
        if(!$injected)
        { 
            if(empty($columns))
            {
                $columns = $this->XmlObject->{$this->XmlObject->DataBase}['tables']['columns']['xml'];
                $alias = is_string($alias) ? $alias : $this->XmlObject->{$this->XmlObject->DataBase}['tables']['xml']->getAttribute('alias').'_'.$this->XmlObject->{$this->XmlObject->DataBase}['tables']['idEntity'];
                $this->command = " SELECT ";
            }
            else
            {
              
                $alias = $alias = is_string($alias) ? $alias : $columns->getAttribute('alias').'_'.$this->XmlObject->{$this->XmlObject->DataBase}['tables']['idEntity'];
                $columns = $columns->getElementsByTagName('column');
            }
            
            $alias = $this->normalizeTableAndColumnsName($alias);

            $this->setColumns(true,$columns,$alias,$sufix,$extraColumns);
            $this->groupByAll($columns,$alias); 
        }
        else if(is_array($columns))
        { 

            if(!$this->aggregation) { $this->command = " SELECT "; }
            
            $this->setColumns(false,$columns,'','',$extraColumns);
        }
              
        return $this;
    }
    
    public function distinctAll()
    {
       if(!empty($this->command))
       {
           $this->command = preg_replace('`SELECT`','SELECT DISTINCT ',$this->command,1);
       } 
       
       return $this;
    }
    
    public function count(string $p,Array $options = Array(),$als = false)
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

                    $this->command = " SELECT COUNT(".$alias.".".$nValue.") As ".(($als ? $alias : '').$this->normalizeTableAndColumnsName('Count'.ucfirst($p))).',';

                    $s = true;

                    break;
                }
            }
			    //    echo '<pre>';print_r($this->command);exit;
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

                    $this->command = " SELECT MAX(".$alias.".".$nValue.") As ".((!empty($als) ? $alias : $als).$this->normalizeTableAndColumnsName('Max'.ucfirst($p))).',';

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
        
        $this->command .= PHP_EOL.' GROUP BY '.PHP_EOL;

        if(!$useNumbers && empty($columns))
        {            
            $this->groupByAll($columns,(isset($alias) ? $alias : null));
            
            $this->command .= implode(',',array_reverse($this->listGroupBy));
        }
        else if(!$useNumbers && !empty($columns))
        {
            foreach($columns as $i => $n)
            {
                $n = $this->normalizeTableAndColumnsName($n);
                
                $this->command .= $n;
                $this->command .= ',';
            }
            
            $this->command = rtrim($this->command,',');
        }
        else if($useNumbers)
        {
            foreach($columns as $i => $n)
            {
                $this->command .= ($i + 1);
                $this->command .= ',';
            }
            
            $this->command = rtrim($this->command,',');
        }

        return $this;
    }
    
    public function cIf($column,$cond1,Array $values = Array())
    {
        if(!empty($cond1) && !empty($values) && preg_match('`('.$column.')\b`',$this->command,$res))
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

            $this->command  = preg_replace('`('.$res[0].')\b`',$if,$this->command,1);
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

    public function addQueryBuilder(HarpORMSelect $ObjSelect)
    {
            $c = $ObjSelect->getCommand();
            
            if(is_string($c))
            {
                $this->command .= $c;
            }
            
            return $this;
    } 
    
    public function addJoinQuery(HarpORMSelect $ObjSelect,$typeJoin,Array $tablesAliasCondition,$aliasQuery)
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
    
    public function addInnerEquiJoin(HarpORMSelect $ObjSelect)
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
    
    public function addSelectAsField(HarpORMSelect $ObjSelect,$alias)
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

                    $this->listJoins[$EntityName] = Array();
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

                    $this->listJoins[$EntityName] = Array();
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
    
    public function from($alias = null)
    {

        $dinamicAlias = $this->XmlObject->{$this->XmlObject->DataBase}['tables']['xml']->getAttribute('alias').'_'.$this->XmlObject->{$this->XmlObject->DataBase}['tables']['idEntity'];
        $alias = !is_string($alias) ? $dinamicAlias : $alias;
        $tableName = $this->XmlObject->{$this->XmlObject->DataBase}['tables']['xml']->getAttribute('name');
        $tableName = $this->XmlObject->{$this->XmlObject->DataBase}['tables']['xml']->getAttribute('schema').'.'.$tableName;
        $tblName = $this->normalizeTableAndColumnsName($tableName);
            
        $this->command = substr($this->command,0,(mb_strlen($this->command) - 1));
        $this->command .= ' FROM ';
        $this->command .= $tblName;
        $this->command .= ' As ';
        $this->command .= $alias;

        foreach($this->listJoins as $j)
        {
            $this->command .= PHP_EOL.$j['join'].chr(32);
        }

        $this->command = str_ireplace($dinamicAlias,$alias,$this->command);

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
            
            $this->command .= $typeJoin;
            $this->command .=  PHP_EOL;
            $this->command .=  ' '.$tableName.' ';
            $this->command .=  'As '.$alias;
            $this->command .=  ' ON ';
            $this->command .= $tablesAliasCondition[0];
            $this->command .= ' '.$tablesAliasCondition[1];
            $this->command .= ' '.$tablesAliasCondition[2];
            
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
            $this->command .= PHP_EOL.' LIMIT '.$this->PaginationData->getLimit();
            $this->command .= PHP_EOL.' OFFSET '.$this->PaginationData->getOffset();
        }
        else
        {
            $this->XmlObject->ConnectionDriver->CommandHandlerSQL->offset($this->offset);
            $this->XmlObject->ConnectionDriver->CommandHandlerSQL->limit($this->limit['r'],$this->limit['l']);            
        }
        
        return parent::getCommand();
    }


    public function getResult()
    {
        try
        {
            $this->XmlObject->ConnectionDriver->connect();
            
            $result = $this->XmlObject->ConnectionDriver->executeQueryFetchResult();
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
            $result = $this->getResult();
            
            $this->toCleanCommand();
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return $result;
    }
        
    public function where($where,$alias = null,$replaceForAlias = null,$normalizeAlias = false,$normalizeWhere = false)
    {        
        $whereParams = $this->normlizeWhere($where,$alias,$normalizeAlias,$normalizeWhere);
        
        if($this->FilterSql instanceof HarpFilterSql)
        {
            if(!$this->alreadyUsedWhere)
            {
                $this->FilterSql->explicitWhereClause();
                $this->command .= $this->FilterSql->getCommandTextWithParameters();
            }
            else
            {
                $this->command .= $this->FilterSql->getCommandTextWithParameters();
                parent::where($whereParams['where'],$whereParams['alias'],$replaceForAlias,$normalizeAlias,$normalizeWhere);
            }
        }
        else if(!$this->alreadyUsedWhere && preg_match('`LIKE`is',$whereParams['where'],$r))
        {

                $this->command .= chr(32).' WHERE '.$where.chr(32);

                $pos = strrpos($where, "@");

                if($pos !== false)
                {
                    $w = trim(substr($whereParams['where'],$pos + 1));
                    $pos = strrpos($w,"%");

                    if($pos !== false)
                    {
                       $w = trim(substr($w,0,$pos)); 
                    }

                    $toArrayEntity = $this->XmlObject->{$this->XmlObject->DataBase}['tables']['entity']->toArray();

                    if(isset($toArrayEntity[$w]))
                    {
                       $this->XmlObject->ConnectionDriver->CommandParameter->addParameter($w,$toArrayEntity[$w], \etc\HarpDatabase\commands\CommandEnum::VARCHAR_NO_QUOTES,\etc\HarpDatabase\commands\CommandEnum::LENGTH_VARCHAR);
                       $this->XmlObject->ConnectionDriver->CommandParameter->commit($w,false);    
                    }
                    
                    $this->alreadyUsedWhere = true;
                }          
        }
        else
        {
             parent::where($whereParams['where'],$whereParams['alias'],$replaceForAlias,$normalizeAlias,$normalizeWhere);
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
        
        if(!empty($this->listOrderBy))
        {
           $this->command .= PHP_EOL.' ORDER BY '.implode(',',$this->listOrderBy); 
        }
             
        if($this->FilterSql instanceof HarpFilterSql && !$this->FilterSql->isEmptyFilter())
        {   
            
            if(!$this->alreadyUsedWhere)
            {
                $this->FilterSql->explicitWhereClause();
            }
            
            $this->command .= chr(32).$this->FilterSql->getCommandTextWithParameters().chr(32);
                        
            foreach ($this->FilterSql->getParametersValues() as $p)
            {
                $this->XmlObject->ConnectionDriver->CommandParameter->addParameter($p->param,$p->value,$p->type,$p->typeLength);
                $this->XmlObject->ConnectionDriver->CommandParameter->commit($p->param,false); 
            }
        }
        
        parent::prepareParams();
        
        return $this;
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
