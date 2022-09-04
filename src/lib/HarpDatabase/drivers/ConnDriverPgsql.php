<?php
namespace Harp\lib\HarpDatabase\drivers;


use Harp\lib\HarpDatabase\DriverInterface;

use Harp\lib\HarpDatabase\HarpConnection;
use etc\HarpDatabase\DatabaseEnum;
use Exception;
use Harp\bin\ArgumentException;
use Throwable;

class ConnDriverPgsql extends ConnectionDriver
{   
   
   const DEFAULT_PORT = '5432'; 
    
   private $sourceConnection; 
   private $numRows = 0;
   private $fieldKey;
   private $affectedRows;
   private $lastInsertId;
   private $columnInsertOID = null;
   private $currentColumnInsertOID = null;
   private $listStatements = [];
   public $listFunctions = [
       'cast',
       '::'
   ];

   public function __construct(HarpConnection &$InfoConnection) 
   {
       parent::__construct($InfoConnection);

       $this->numRows = 0;
       
       $this->fieldKey = null;
       
       $this->instanceCryptography();
   }
      
   protected function handleError($resultSet = null)
   {       
       $error = pg_last_error($this->sourceConnection);
       $error = !empty($error) ? $error : (!is_resource($resultSet) ? null : pg_result_error($resultSet));       

       throw new ArgumentException($error);
   }

    private function auth()
    {
        $server = $this->InfoConnection->getServerName();
        
        $port = self::DEFAULT_PORT;
        
        if(self::DEFAULT_PORT != $this->InfoConnection->getPort())
        {
            $port = $this->getInfoConnection()->getPort();
        }
        
        $c = false;

        try
        {
            if(empty($server))
            {
                throw new ArgumentException('Server name or ip Appears to be empty and returned type {%s}!',500);
            }

            $strConnect = "host=".$server." port=".$port." dbname=".$this->InfoConnection->getDatabaseName()." user=".$this->InfoConnection->getUserName()." password=".$this->InfoConnection->getPassword();
           
            $c = \pg_connect($strConnect);
          
            if($c === false)
            {
                $this->handleError();
            }
        }
        catch(Throwable $th)
        {
            throw $th;
        }

        return $c;
    }
       
    public function connect()
    {	
        try
        {
            if(empty($this->sourceConnection) || (is_object($this->sourceConnection) && get_class($this->sourceConnection) != $this->getDriverName()))
            {
                $this->sourceConnection = null;
                $this->sourceConnection = $this->auth();
                return true;          
            }         
        }
        catch(Exception $e)
        {
            throw $e;
        }
        
        return false;
    }

    public function numRows($result)
    {
        try
        {
            $this->numRows = pg_num_rows($result);

            if($this->numRows === false)
            {
                $this->handleError();
            } 
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
    }
    
    protected function affectedRows($resultSet)
    {
        try
        {
            $this->affectedRows = pg_affected_rows($resultSet);

            if($this->affectedRows === false)
            {
                $this->handleError();
            } 
        }
        catch(Exception $ex)
        {
            throw $ex;
        }        
    } 
    
    protected function lastInsertId()
    {
        $s = false;

        if(!empty($this->columnInsertOID))
        {
            $this->CommandText->getCommand()->text .= sprintf(
                ' %s %s',
                'RETURNING ',
                $this->columnInsertOID
            );  

            $s = true;
            $this->currentColumnInsertOID = $this->columnInsertOID;
            $this->columnInsertOID = null;
        }

        return $s;
    }
  
    public function beginTran()
    { 
        try
        {
            $this->transactionBegin();
        }
        catch(Exception $ex)
        {
            throw $ex;
        } 
    }

    protected function isInsertCommand()
    {
        $resp = $this->verifyCommand();

        $cm = isset($resp[0]) ? mb_strtoupper(trim($resp[0])) : '';

        return $cm === self::CMD_INSERT;
    }

    public function defineInsertOID($returnColumn = null)
    {
            $s = false;
           
            if($this->isInsertCommand())
            {
                if(empty($returnColumn))
                {
                    $table = $this->ConfigORM->getProperty('table');
                    if(!empty($table['attributes']['pk']))
                    {
                        $this->columnInsertOID = $table['attributes']['pk'];
    
                        $s = true;
                    } 
                }
                else
                {
                    if(!empty($table['columns'][$returnColumn]))
                    {
                        $this->columnInsertOID = $returnColumn;
                        
                        $s = true;
                    }
                }        
            }

            return $s;   
    }
    
    public function addToStatements($param,$key,$value)
    {
        $this->listStatements[] = $value;
        
        $this->CommandText->getCommand()->text = 
        preg_replace(['`'.$param.'\b`'],['\$'.$key],$this->CommandText->getCommand()->text);    
    }
    
    public function prepareStatements()
    {
        $nameStatement = '';

        if($this->isInsertCommand())
        {
            $this->lastInsertId();
        } 

        if(!empty($this->listStatements))
        {
            $nameStatement = uniqid();

            $result = @pg_prepare
                            (
                        $this->sourceConnection,
                        $nameStatement,
                        $this->CommandText->getCommand()->text
                    );
          
            if(!$result)
            {
                $this->handleError();
            }       
        }
        
        return $nameStatement;
    }
    
    public function commit()
    { 
        try
        {
            $this->transactionCommit();
        }
        catch(Exception $ex)
        {
            throw $ex;
        } 
    }   
    
    public function rollback()
    { 
        try
        {
            $this->transactionRollback();
        }
        catch(Exception $ex)
        {
            throw $ex;
        } 
    }    
    
    public function transactionBegin()
    { 
        try
        {
            if($this->isConnected())
            {
                $result = pg_query($this->sourceConnection,'START TRANSACTION');

                if(!$result)
                {
                    $this->handleError();
                }
            }
        }
        catch(Exception $ex)
        {
            throw $ex;
        } 
    }
    
    public function transactionCommit()
    {
        try
        {
            if($this->isConnected())
            {
                $result = pg_query($this->sourceConnection,'COMMIT');

                if(!$result)
                {
                    $this->handleError();
                }
            }
        }
        catch(Exception $ex)
        {
            throw $ex;
        }               
    }
    
    public function transactionRollback()
    {
        try
        {
            if($this->isConnected())
            {
                $result = pg_query($this->sourceConnection,'ROLLBACK');

                if(!$result)
                {
                    $this->handleError();
                }
            }
        }
        catch(Exception $ex)
        {
            throw $ex;
        }         
    } 
        
    public function addFieldAsKey($FieldName)
    {
        $this->fieldKey = $FieldName;
    } 
    
    protected function executeCommandQuery($nameStatement = '')
    {
        try
        {
            $allowedCommands = '(SELECT|DESCRIBE|SET|DESC)';
            
            $s = preg_match('#^'.$allowedCommands.'#is',trim($this->CommandText->getCommand()->text));
  
            if(!$this->isConnected() || empty($this->CommandText->getCommand()->text))
            {
                throw new ArgumentException('To perform this operation you must be logged in and your commandText must contain a sql command');
            }
            else if(!$s)
            {
                throw new ArgumentException('Currently the allowed commands are {'.$allowedCommands.'}');
            }

            if(!empty($this->listStatements) && !empty($nameStatement))
            {
                $result = @pg_execute($this->sourceConnection,$nameStatement,$this->listStatements);
        
                $this->listStatements = [];
            }
            else
            {
                $result = @pg_query($this->sourceConnection,$this->CommandText->getCommand()->text);
            }

            if(!$result){ $this->handleError($result);}
            
            $this->numRows($result);
                
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return $result;
    }
    
    public function executeAnyCommand()
    {
        try
        {
            if(!$this->isConnected() || empty($this->CommandText->getCommand()->text))
            {
                throw new ArgumentException('To perform this operation you must be logged in and your commandText must contain a sql command','Warning','warning');
            }

            $result = @pg_query($this->sourceConnection,$this->CommandText->getCommand()->text);
    
            if(!$result){ $this->handleError();}   
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return $result;
    }    
    
    private function fetchResultField($result)
    {
        $Fields = Array();
        
        $j =  $i = pg_num_fields($result);

        for($i = 0; $i < $j;++$i)
        {
            $fieldName =  pg_field_name($result,$i);
            
            $Fields[$i] = $fieldName;
            $Fields[$fieldName] = $i;
        }
        
        if($this->fieldKey && isset($Fields[$this->fieldKey]))
        {
            $this->fieldKey = $Fields[$this->fieldKey];
        }
        else
        {
            $this->fieldKey = false;
        }
        
        return $Fields;
    }   
    
    private function fetchEncrypted($result)
    {
            $Fields = $this->fetchResultField($result);
            
            $DataTable = Array();
            
            $this->numRows = 0;

            if($this->fieldKey !== false)
            {
                while($row = pg_fetch_row($result)) 
                {
                    foreach($row as $x => $v)
                    {                            
                        if(isset($this->encryptionFields[$Fields[$x]]) && !empty($row[$x]))
                        {  
                            $row[$x] = $this->HarpCryptography->encrypt($row[$x]);
                        }
                        
                        $DataTable[$row[$this->fieldKey]][$Fields[$x]] = $row[$x];
                    }

                    ++$this->numRows;
                }                      
            }
            else
            {
                while ($row = pg_fetch_row($result)) 
                {
                    foreach($row as $x => $v)
                    {
                        if(isset($this->encryptionFields[$Fields[$x]]) && !empty($row[$x]))
                        {  
                            $row[$x] = $this->HarpCryptography->encrypt($row[$x]);

                        }

                        $DataTable[$this->numRows][$Fields[$x]] = $row[$x];
                    }

                    ++$this->numRows;     
                                       
                }                 
            }
            
            $this->fieldKey = false;
            
            return $DataTable;   
    }
    
    private function fetchUnecrypted($result)
    {
        $Fields = $this->fetchResultField($result);

        $DataTable = Array();

        $this->numRows = 0;
        
        if($this->fieldKey !== false)
        {
            while ($row = pg_fetch_row($result)) 
            {
                foreach($row as $x => $v)
                {
                    $DataTable[$row[$this->fieldKey]][$Fields[$x]] = $row[$x];
                }  

                ++$this->numRows;
            }                      
        } 
        else
        {
            while ($row = pg_fetch_row($result)) 
            {
                foreach($row as $x => $v)
                {
                    $DataTable[$this->numRows][$Fields[$x]] = $row[$x];
                }

                ++$this->numRows;                       
            }                     
        }
        
        $this->fieldKey = false;
        
        return $DataTable;
    }
    
    protected function fetchResult($result,Array $encryptionFields = Array())
    {
        $this->encryptionFields = !empty($encryptionFields) ? $encryptionFields : $this->encryptionFields;

        if(!empty($this->encryptionFields))
        {
            $DataTable = $this->fetchEncrypted($result);
            
            return $DataTable;
        }
        else
        {
            $DataTable = $this->fetchUnecrypted($result);    
            
            return $DataTable;
        }
        
        $this->encryptionFields = Array();
        
        return null;
    }    

    public function executeQueryfetchResult($nameStatement = '')
    {
        try
        {
            $result = $this->executeCommandQuery($nameStatement);

            return $this->fetchResult($result);
        }
        catch(Exception $ex)
        {
            throw $ex;
        }            
    } 

    protected function verifyCommand()
    {
        $res  = [];

        preg_match('`^INSERT\b|^UPDATE\b|^DELETE\b|^DECLARE\b|^USE\b`',ltrim($this->CommandText->getCommand()->text),$res);

        return $res;
    }

    public function executeNonQuery($nameStatement = '')
    {
        $result = false;
        
        try
        {
            $allowedCommands = [
                'INSERT',
                'DELETE',
                'UPDATE',
                'DECLARE',
                'USE'
            ];

            $resp = $this->verifyCommand();

            $cm = isset($resp[0]) ? mb_strtoupper(trim($resp[0])) : '';
         
            
            if(!$this->isConnected()|| empty($this->CommandText->getCommand()->text))
            {
                throw new ArgumentException('To perform this operation you must be logged in and your commandText must contain a sql command','Warning','warning');
            }
            else if(!in_array($cm,$allowedCommands))
            {
                throw new ArgumentException('Currently the allowed commands are {'.implode(',',$allowedCommands).'}','Warning','warning');
            }

            if(!empty($this->listStatements) && !empty($nameStatement))
            {
                $result = @pg_execute($this->sourceConnection,$nameStatement,$this->listStatements);
        
                $this->listStatements = [];
            }
            else
            {
                $result = @pg_query($this->sourceConnection,$this->CommandText->getCommand()->text);
            }
            
            if(!$result){$this->handleError();}
            
            $this->setlastInsertedId($result);
            $this->affectedRows($result);
        }
        catch(Exception $ex)
        {
            throw $ex;
        }     
                    
        return $result;
    } 
    
    private function setlastInsertedId($result)
    {
        $r = pg_fetch_array($result);

        if(!empty($this->currentColumnInsertOID) && isset($r[$this->currentColumnInsertOID]))
        {
            $this->lastInsertId = $r[$this->currentColumnInsertOID];
        }
    }
    
    public function close()
    {
        $r = false;
        
        if($this->isConnected())
        {
             $r = pg_close($this->sourceConnection);
             $this->sourceConnection = null;
        }

        return $r;
    } 
            
    public function isConnected()
    {  

        return 
            (
                !empty($this->sourceConnection)
                    ||
                (is_object($this->sourceConnection) 
                    && 
                get_class($this->sourceConnection) == $this->getDriverName())
            );
    }
    
    public function getNumRows()
    {
        return $this->numRows;
    }    
    
    public function getAffectedRows()
    {
        return $this->affectedRows;
    }
    
    public function getInsertId()
    {
        return $this->lastInsertId;
    } 

    public function getLastInsertId()
    {
        return $this->lastInsertId;
    }
    
    public function __destruct()
    {
        $this->close();
    }

    public function getSgbdName()
    {
        return self::SGBD_POSTGRE_SQL;
    }
    
    public function getSpecialChar()
    {
        return '"';
    }
}
