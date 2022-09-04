<?php
namespace Harp\lib\HarpDB;

use Exception;
use DOMXPath;
use etc\HarpDatabase\ORM\HarpORMSelect;
use etc\HarpDatabase\ORM\MapORM;

abstract class AbstractORM 
{
    protected $TableObject;
    protected $ConfigORM;
    protected $command;
    protected $csToken;
    protected static $ConnectionDriver; 
    protected $alreadyUsedWhere = false;
    protected $flags = [];
    
    abstract public function getTableName();

    protected function __construct(MapORM $ConfigORM) 
    {
        $this->ConfigORM = $ConfigORM;
        
      //  $this->TableObject = $ConfigORM->getTables;
      
        self::$ConnectionDriver = $this->ConfigORM->getConnectionDriver();
        
        $this->csToken = self::$ConnectionDriver->getCharacterCaseSensitive();
    }
    
    public function startGroup()
    {
        $this->command .= ' ( ';
        
        return $this;
    }
    
    public function endGroup()
    {
        $this->command .= ' ) ';
        
        return $this;
    }    
    
    private function explicitAlias($clause)
    {
            $vAlias = explode('.',$clause);
            
            $vAlias = array_values($vAlias);
            
            $cls = null;
                               
            if(count($vAlias) == 2)
            {
                $cls .= $vAlias[0].'_'.$this->TableObject->{$this->TableObject->DataBase}['tables']['idEntity'];
                $cls .= '.'.$vAlias[1];
            }
            else if(count($vAlias) > 2)
            {
                $cls = $clause;
            }
            
            return $cls;
    }
    
    public function conjAND($and,$alias = null,$replaceForAlias = null,$startGroup = false)
    {
        if(is_string($and) && $this->alreadyUsedWhere)
        {   
            $this->command .=  PHP_EOL;
            $this->command .= ' AND '.($startGroup ? ' ( ' : '');
            $this->command .=  PHP_EOL;
            
            if(!empty($replaceForAlias))
            {
                $Xpath = new DOMXPath($this->TableObject->xmlFile);
                $Node = $Xpath->query('//tables/table[@name="'.$this->TableObject->{$this->TableObject->DataBase}['tables']['xml']->getAttribute('name').'"]/column[@entityAttribute="'.$replaceForAlias.'"]');
                
                if($Node->length > 0)
                {
                    $and = preg_replace('`'.$replaceForAlias.'`',trim($Node->item(0)->nodeValue),$and,1); 
                }
            }
            
            $p = $this->explicitAlias($and);
            
            if(!empty($p))
            {
                 $this->command .= $p;
            }
            else
            {
                $this->command .=  !is_string($alias) ? $this->TableObject->{$this->TableObject->DataBase}['tables']['xml']->getAttribute('alias').'_'.$this->TableObject->{$this->TableObject->DataBase}['tables']['idEntity'].'.'.$and : $alias.'.'.$and;
            }
        }
        
        return $this;        
    }     

    public function conjANDbyObjectSelect($column,$condition,HarpORMSelect $ObjSelect,$startGroup = false)
    {
        $this->command .=  PHP_EOL;
        $this->command .= ' AND '.($startGroup ? ' ( ' : '');
        $this->command .=  PHP_EOL;
        $this->command .=  $column.' '.$condition.' ('.$ObjSelect->getCommand().')';
        
        return $this;
    } 
    
    public function conjOR($or,$alias = null,$startGroup = false)
    {
        if(is_string($or))
        {
            $this->command .=  PHP_EOL;
            $this->command .= ' OR '.($startGroup ? ' ( ' : '');
            $this->command .=  PHP_EOL;
            
            $p = $this->explicitAlias($or);
            
            if(!empty($p))
            {
                 $this->command .= $p;
            }
            else
            {            
                 $this->command .=  !is_string($alias) ? $this->TableObject->{$this->TableObject->DataBase}['tables']['xml']->getAttribute('alias').'_'.$this->TableObject->{$this->TableObject->DataBase}['tables']['idEntity'].'.'.$or : $alias.'.'.$or;
            }
        }
        
        return $this;
    }   
    
    
    
    private function setColumns($useAliasColumns,$columns,$alias = '',$sufix = '')
    {
        if(is_object($columns))
        {
            if($columns instanceof \DOMElement)
            {
                $columns = $columns->getElementsByTagName('column');
            }
            
            foreach($columns as $n)
            {
                
                $nValue = trim(str_ireplace(Array(PHP_EOL,"\r\n","\n"),'',$n->nodeValue));

                if(!empty($alias))
                {            
                    $this->command .= $alias;
                    $this->command .= '.';   
                }

                $this->command .= $nValue;

                if($useAliasColumns)
                {
                    $this->command .= ' As ';   
                    $this->command .= !empty($sufix) ? $n->getAttribute('entityAttribute').$sufix : $n->getAttribute('entityAttribute');                
                }

                $this->command .= PHP_EOL;
                $this->command .= ',';
            }  
        }
        else if(is_array($columns))
        {
            foreach($columns as $n)
            {
                $this->command .= $n;
                $this->command .= PHP_EOL;
                $this->command .= ',';
            }  
        }
        
        if(!empty($this->listSelectAsField))
        {
            foreach($this->listSelectAsField as $n)
            {
                $this->command .= $n;
                $this->command .= PHP_EOL;
                $this->command .= ',';
            }          
        }
    }    
    
    /**
     * Alguns Sgbd's só interpretam case-sensitive se o atributo ou nome da tabela estiver entre caractéres especiais. Esta função realiza a normalização colocando caractéres especiais específico do sgbd ao parâmetro
     * @param String a ser normalizada
     * @author Leonardo Souza(lss.leonardo.dev@gmail.com)
     * @version 1.0
     * @return String
     */
    protected function normalizeTableAndColumnsName($paramName)
    { 
        if(trim($paramName) != '*')
        {
            $paramExplode = $paramName;
            
            $r = [];
            //Caso a coluna possua alias (AS)
            if(preg_match('`(.*[ ])(as)?([ ].*)`i',$paramName,$r))
            {//print_r($r);
                $paramExplode = $r[1];
            }
            
            $tbl = explode('.',$paramExplode);
            
            $tbl[0] = trim($tbl[0]);
            
            $paramName = $this->csToken.$tbl[0].$this->csToken;

            if(count($tbl) > 1)
            {
               $castParam = explode('::',$tbl[1]); 
             //  var_dump($castParam);
               $tbl[1] = trim($castParam[0]);
               $paramName .= ".".$this->csToken.$tbl[1].$this->csToken;

               if(isset($castParam[1]))
               { 
                   $paramName .= '::'.$castParam[1]; 
               }
            }
            
            if(count($r) === 4)
            {                
                $paramName.= chr(32).$r[2].chr(32).$this->csToken.trim($r[3]).$this->csToken;
            }
        }    
        
        return $paramName;  
    }  
    
    
    public function normalizeLogicalOperators($lop,$alias,$normalizeAlias,$normalizeLop)
    {
        if(!empty($alias) && $normalizeAlias)
        { 
            $alias = $this->normalizeTableAndColumnsName($alias);
        }
        
        if(is_array($lop) && $normalizeLop)
        {            
            if(isset($lop[0]))
            {
                $lop[0] = $this->normalizeTableAndColumnsName($lop[0]);
            }
            
            $lop = implode(chr(32),$lop);
        }  
        
        return ['lop' => $lop,'alias' => $alias];
    }
    
    public function normlizeWhere($where,$alias,$normalizeAlias,$normalizeWhere)
    {        
        $response = $this->normalizeLogicalOperators($where,$alias,$normalizeAlias,$normalizeWhere);
        
        return ['where' => $response['lop'],'alias' => $response['alias']];
    }
    
    public function normlizeAnd($and,$alias,$normalizeAlias,$normalizeWhere)
    {
        $response = $this->normalizeLogicalOperators($and,$alias,$normalizeAlias,$normalizeWhere);
        
        return ['and' => $response['lop'],'alias' => $response['alias']];
    }    


    /**
     * Adiciona o comando where para o comando atual.
     * @param mixed $where [o comando where.] se string será concatenado como passado senão será passado como array
     * @param String $alias [Alias da tabela não obrigatório, se não informado será utilizado um alias personalizado.]
     * @param String $replaceForAlias [Não obrigatório. Se for informado, este item será substituido pelo alias informado no XML.]
     * @author Leonardo Souza(lss.leonardo.dev@gmail.com)
     * @version 1.0
     * @return $this
     */
//    public function where($where,$alias = null,$replaceForAlias = null,$normalizeAlias = false,$normalizeWhere = false)
//    {
//        if(is_string($where) && !$this->alreadyUsedWhere)
//        {   
//            $this->command .=  PHP_EOL;
//            $this->command .= ' WHERE ';
//            $this->command .=  PHP_EOL;
//            
//            if(!empty($replaceForAlias))
//            {
//                $Xpath = new DOMXPath($this->TableObject->xmlFile);
//                $Node = $Xpath->query('//tables/table[@name="'.$this->TableObject->{$this->TableObject->DataBase}['tables']['xml']->getAttribute('name').'"]/column[@entityAttribute="'.$replaceForAlias.'"]');
//                
//                if($Node->length > 0)
//                {
//                    $name = trim($Node->item(0)->nodeValue);
//
//                    $where = preg_replace('`'.$replaceForAlias.'`',$name,$where,1); 
//                    
//                }
//            }
//
//            $p = $this->explicitAlias($where);
//
//            if(!empty($p))
//            {
//                 $this->command .= $p;
//            }
//            else
//            {
//                $this->command .=  !is_string($alias) ? $this->TableObject->{$this->TableObject->DataBase}['tables']['xml']->getAttribute('alias').'_'.$this->TableObject->{$this->TableObject->DataBase}['tables']['idEntity'].'.'.$where : $alias.'.'.$where;
//            }
//
//            $this->alreadyUsedWhere = true;
//        }
//        
//        return $this;
//    }
    
    
    public function prepareParams()
    {
        $nameStatement = '';
        
        try
        {
            
           $table = $this->ConfigORM->getCurrentTable();
            
           $toArrayEntity = $table['instanceEntity']->toArray();
           
           if(count($toArrayEntity) > 6)
           {
                $keyParam = 1;
                
                foreach($toArrayEntity as $attribute => $value)
                {
                    $parameter = '@'.$attribute;
                    
                    if(strpos($this->command,$parameter) === false)
                    {
                        continue;
                    }
                    
                    self::$ConnectionDriver->addToStatements($parameter,$keyParam,$value);
                    
                    ++$keyParam;
                }
                
                $nameStatement = self::$ConnectionDriver->prepareStatements();     
           }
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return $nameStatement;
    }
    
    /**
     * Realiza validaçõese e substituições de parâmetros por valores no comando atual.
     * @author Leonardo Souza(lss.leonardo.dev@gmail.com)
     * @version 1.0
     * @return $this
     */
    public function prepareParamsOld()
    {       
        try
        { 
            $table = $this->ConfigORM->getCurrentTable();
            
            $toArrayEntity = $table['instanceEntity']->toArray();
            

            //echo '<pre>';print_r($toArrayEntity);exit;
            
            //$Xpath = new DOMXPath($this->TableObject->xmlFile);

            foreach($toArrayEntity as $k => $v)
            {
                $k2 = '@'.$k;

                $Node = $Xpath->query('//tables/table[@name="'.$this->TableObject->{$this->TableObject->DataBase}['tables']['xml']->getAttribute('name').'"]/column[@entityAttribute="'.$k.'"]');
                $FKey = $Xpath->query('//tables/table[@name="'.$this->TableObject->{$this->TableObject->DataBase}['tables']['xml']->getAttribute('name').'"]/column[@entityAttribute="'.$k.'"]/@references');

                if($Node->length == 1 && preg_match('`('.$k2.')\b`',$this->command,$resultParameters))
                {  
                    $c = substr_count($this->command,$k2);
                    
                    if(!is_array($v) && !is_object($v))
                    {
                        $type = $Node->item(0)->getAttribute('type');
                        $length = strtoupper('LENGTH_'.$Node->item(0)->getAttribute('length'));
                        $length = constant('etc\\HarpDatabase\\commands\\CommandEnum::'.$length);
                        $this->TableObject->ConnectionDriver->CommandParameter->addParameter($k2,$v,$type,$length,$c);  
                        $this->TableObject->ConnectionDriver->CommandParameter->commit($k2);
                    }
                }
                else if($FKey->length > 0)
                {   

                    $entityName = $FKey->item(0)->nodeValue;
                    $NodeTable = $Xpath->query('//tables/table[@entity="'.$entityName.'"]');

                    $Node2 = null;
                   
                    if($NodeTable->length == 1)
                    {
                       $Node2 = $Xpath->query('//tables/table[@name="'.$NodeTable->item(0)->getAttribute('name').'"]/column'); 
                    }

                    if(!empty($Node2) && $Node2->length > 0)
                    {

                            foreach($Node2 as $n)
                            {  
                                $attr = $n->getAttribute('entityAttribute');
                                $attr2 = $attr.$entityName;
                                
                                if(!isset($toArrayEntity[$attr]) || (is_null($toArrayEntity[$attr])))
                                {
                                    $attr = $attr2;
                                }

                                $k2 = '@'.$n->getAttribute('entityAttribute');

                                if(isset($toArrayEntity[$attr]) && preg_match('`('.$k2.')\b`',$this->command,$r))
                                {
                                    if(!is_array($toArrayEntity[$attr]) && !is_object($toArrayEntity[$attr]))
                                    {                                        
                                        $type = $n->getAttribute('type');    
                                        $length = strtoupper('LENGTH_'.$n->getAttribute('length'));
                                        $length = constant('etc\\HarpDatabase\\commands\\CommandEnum::'.$length);                                   
                                        $this->TableObject->ConnectionDriver->CommandParameter->addParameter($k2,$toArrayEntity[$attr],$type,$length,1);  
                                        $this->TableObject->ConnectionDriver->CommandParameter->commit($k2);
                                    }
                                }
                            }                            
                    }
                }
          
            }
        }
        catch (Exception $ex)
        { 
            throw $ex;
        }

        return $this;
    }
    
    
    public function where()
    {
        try
        {
            if(!in_array('where',$this->flags))
            {
                $this->command .= " WHERE\r\n";
                array_push($this->flags,'where');
            }
            else
            {
               throw new Exception('where clause already reported!');
            }
            
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return $this;
    }
    
    public function and()
    {
        $this->command .= ' AND '.PHP_EOL;

        return $this;
    }
    
    /**
     * Abre uma transação para qualquer driver suportado.
     * @author Leonardo Souza(lss.leonardo.dev@gmail.com)
     * @version 1.0
     * @return void
     */
    public static function beginTran()
    {
    
        self::$ConnectionDriver->transactionBegin();
    } 
    /**
     * Comita uma transação para qualquer driver suportado.
     * @author Leonardo Souza(lss.leonardo.dev@gmail.com)
     * @version 1.0
     * @return void
     */
    public static function commit()
    {
         self::$ConnectionDriver->TransactionCommit();
    }         
    /**
     * Desfaz uma transação para qualquer driver suportado.
     * @author Leonardo Souza(lss.leonardo.dev@gmail.com)
     * @version 1.0
     * @return void
     */
    public static function rollback()
    {
         self::$ConnectionDriver->TransactionRollback();
    }       
    /**
     * Retorna o comando atual.
     * @author Leonardo Souza(lss.leonardo.dev@gmail.com)
     * @version 1.0
     * @return String
     */
    public function &getCommand()
    {
        return $this->command;
    }
    /**
     * Limpa o comando atual.
     * @author Leonardo Souza(lss.leonardo.dev@gmail.com)
     * @version 1.0
     * @return void
     */
    public function toCleanCommand()
    {
        $this->command = '';
    }
}
