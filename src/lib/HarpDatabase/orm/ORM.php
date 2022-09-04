<?php
namespace Harp\lib\HarpDatabase\orm;

use Exception;
use Harp\bin\ArgumentException;

abstract class ORM 
{
    const FLAG_COLUMN = 'column';
    const FLAG_TABLE = 'table';
    protected $TableObject;
    protected $ConfigORM;
    protected $command;
    protected $csToken;
    protected static $sConnectionDriver; 
    protected $alreadyUsedWhere = false;
    public $lastCommand = '';
    protected $flags = [];

    protected $ormInfo = [];
    public $listOfUsedAliases = [];
    
    abstract public  function mapByEntity(EntityHandler $Entity);
    abstract public function load($path = null);
    abstract public function getPath();
    abstract public function getConnectionDriver();

    protected function __construct(ORM $ConfigORM) 
    {      
     
        $this->ConfigORM = $ConfigORM;
        self::$sConnectionDriver = $this->ConfigORM->getConnectionDriver();
    }
    
    public function startGroup()
    {
        $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= ' ( ';
        
        return $this;
    }
    
    public function endGroup()
    {
        $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= ' ) ';
        
        return $this;
    }  
    
    protected function addAliases($nameEntity,$alias)
    {
        array_push($this->listOfUsedAliases[$nameEntity],$alias);
    }
    
    public function selectAliases($incrementAlias,$alias,$nameEntity)
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
    
    public function clearFlags()
    {
        $this->flags = [];
    }

    public function getSelectedColumns($columns,$allColumns,$nameEntity)
    {
        $columnsResult = [];
      
        foreach($allColumns as $prop => $obj)
        {
            $prop = trim($prop);

            foreach($columns as $nmColumn)
            {
                
                $nm = $this->getColumnsByEntity($nmColumn,$nameEntity);

                $nmProp = $nm;
          
                if(!empty($nm))
                {
                    $nmp = explode(chr(32),$nm);
              
                    if(count($nmp) > 1)
                    {
                        $nm = trim($nmp[0]);
                        $nmProp = $nm;
                    }

                    $nmpCast = explode('::',$nm);
         
                    if(count($nmpCast) > 1)
                    {
                        $nmProp = $nmpCast[0];
                    }
                    
                    if($prop == $nmProp)
                    {
                        $key = ((isset($nmp[1]) && mb_strtolower(trim($nmp[1]))) == 'as' && !empty($nmp[2])) ? $nm.chr(32).$nmp[1].chr(32).$nmp[2] : $nm;
                        $columnsResult[$key] = $obj;
                        break;
                    }
   
                }
            }
        }

        return !empty($columnsResult) ? $columnsResult : $allColumns;
    }

    public function getColumnByEntity($entityAttr,Array $columns)
    {
        $attr = null;

        foreach($columns as $c => $column)
        {
            if(trim($column['entityAttribute']) != trim($entityAttr))
            {
                continue;
            }

            $attr = $c;
            break;
        }

        return $attr;
    }

    public function getColumnsByEntity($columnName,$entityName)
    {
        $p = explode('.',$columnName);

        if(count($p) > 1)
        {

            $columnName = null;


            if(trim($p[0]) == trim($entityName))
            {
                $columnName = trim($p[1]);
            }
            /*else
            {
                $tb = $this->ConfigORM->getTableByNameEntity($entityName);

                if($entityName == 'PerfilClienteAcesso')
                {
                    var_dump($tb);exit;
                }
                foreach($tb['columns'] as $column)
                {
                    if($entityName == 'PerfilClienteAcesso')
                    {
                       if(isset($column['fk']))
                       {
   
                          if(trim($column['fk']['entityName']) == trim($p[0]))
                          {
                                $columnName = trim($p[1]);
                          }
                       }
                    }
                }
            }*/
        }

        return $columnName;
    }
    
    public function addSpecialChar($str)
    {
        return $this->ConfigORM->getConnectionDriver()->getSpecialChar().$str.$this->ConfigORM->getConnectionDriver()->getSpecialChar();
    }
    
    
    /*public function normalizeLogicalOperators($lop,$alias,$normalizeAlias,$normalizeLop)
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
    }*/
    
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
    
    public function prepareParams($returnColumn = null)
    {
        $nameStatement = '';
        
        try
        {
            
                $entity = $this->ConfigORM->getProperty('entity');
        
                $toArrayEntity = $entity->toArray();

                $keyParam = 1;
      
                foreach($toArrayEntity as $attribute => $value)
                {
                    $parameter = '@'.$attribute;

            
                    if(!preg_match('`('.$parameter.')\b`m',$this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text))
                    {
                        continue;
                    }

                    $this->ConfigORM->getConnectionDriver()->addToStatements($parameter,$keyParam,$value);

                    ++$keyParam;
                }

                $this->ConfigORM->getConnectionDriver()->defineInsertOID($returnColumn);
                
                $nameStatement = $this->ConfigORM->getConnectionDriver()->prepareStatements();   
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return $nameStatement;
    }


    private function writeOperator($param)
    {
        try
        {
            $table = $this->ConfigORM->getProperty('table'); 
            

            if(count($param) == 3)
            {               
                //$alias = $table['attributes']['alias'];
                $nameEntity = $table['attributes']['entity']; 
                $alias = ($this->ConfigORM->listOfUsedAliases[$nameEntity][count($this->ConfigORM->listOfUsedAliases[$nameEntity]) - 1]);

                $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= sprintf(
                    "%s"."%s"."%s"."."."%s"."%s"."%s"." %s %s"."%s", 
                    $this->ConfigORM->getConnectionDriver()->getSpecialChar(),
                    $alias,
                    $this->ConfigORM->getConnectionDriver()->getSpecialChar(),
                    $this->ConfigORM->getConnectionDriver()->getSpecialChar(),
                    $param[0],
                    $this->ConfigORM->getConnectionDriver()->getSpecialChar(),
                    $param[1],
                    $param[2],
                    PHP_EOL
                    ); 
            }
            else if(count($param) == 4)
            {
                $alias = $param[0];
                $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= sprintf(
                    "%s"."%s"."%s"."."."%s"."%s"."%s"." %s %s"."%s", 
                    $this->ConfigORM->getConnectionDriver()->getSpecialChar(),
                    $alias,
                    $this->ConfigORM->getConnectionDriver()->getSpecialChar(),
                    $this->ConfigORM->getConnectionDriver()->getSpecialChar(),
                    $param[1],
                    $this->ConfigORM->getConnectionDriver()->getSpecialChar(),
                    $param[2],
                    $param[3],
                    PHP_EOL
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

    }
    
    
    public function where(Array $param)
    {
        try
        {

            if(!in_array('where',$this->flags))
            {
                $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= " WHERE ".PHP_EOL;
                array_push($this->flags,'where');
            }
            else
            {
               throw new Exception('where clause already reported!');
            }
  
            $this->writeOperator($param);        
            
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return $this;
    }
    
    public function and(Array $param)
    {
        $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= ' AND '.PHP_EOL;

        if(!empty($param))
        {
            $this->writeOperator($param);  
        }
        
        return $this;
    }

    
    //condition without conj and,or,where
    public function cnd(Array $param)
    {
        $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= ' ';

        $this->writeOperator($param);  
        
        return $this;
    }   

    public function or(Array $param)
    {
        $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text .= ' OR '.PHP_EOL;

        if(!empty($param))
        {
            $this->writeOperator($param);  
        }
        
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
        self::$sConnectionDriver->transactionBegin();
    } 
    /**
     * Comita uma transação para qualquer driver suportado.
     * @author Leonardo Souza(lss.leonardo.dev@gmail.com)
     * @version 1.0
     * @return void
     */
    public static function commit()
    {
         self::$sConnectionDriver->TransactionCommit();
    }         
    /**
     * Desfaz uma transação para qualquer driver suportado.
     * @author Leonardo Souza(lss.leonardo.dev@gmail.com)
     * @version 1.0
     * @return void
     */
    public static function rollback()
    {
         self::$sConnectionDriver->TransactionRollback();
    }       
    /**
     * Retorna o comando atual.
     * @author Leonardo Souza(lss.leonardo.dev@gmail.com)
     * @version 1.0
     * @return String
     */
    public function &getCommand()
    {
        return $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text;
    }
    /**
     * Limpa o comando atual.
     * @author Leonardo Souza(lss.leonardo.dev@gmail.com)
     * @version 1.0
     * @return void
     */
    public function toCleanCommand()
    {
        $this->lastCommand = $this->getCommand();
        $this->ConfigORM->getConnectionDriver()->CommandText->getCommand()->text = sprintf(
            '%s',
            ''
        );

        //$this->flags = [];
    }
}
