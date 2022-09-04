<?php
namespace Harp\lib\HarpDatabase\drivers;
use etc\HarpDatabase\connection\InfoConnection;
use etc\HarpDatabase\DatabaseEnum;
use etc\HarpDatabase\drivers\ConnectionDriver;
use Harp\bin\ArgumentException;
use Exception;

class ConnDriverSqlsrv extends ConnectionDriver
{   
   private $sourceConnection; 
   private $numRows = 0;
   private $fieldKey = null;
   private $affectedRows;
   private $lastInsertId;

   public function __construct(InfoConnection &$InfoConnection) 
   {
       parent::__construct($InfoConnection);

       $this->numRows = 0;
       
       $this->fieldKey = null;
       
       $this->instanceCryptography();
   }
      
   protected function handleError()
   {
       $errors = sqlsrv_errors();

       $error = isset($errors[0]['message']) ? $errors[0]['message'] : null;
       $errno = !empty($errors[0]['code']) ? $errors[0]['code'] : null;  
       
       throw new ArgumentException($error,'An error occurred','error',$errno);
   }
   
//   protected function generateError()
//   {
//        $errors = sqlsrv_errors();
//
//        $this->Exceptions[IADBCException::ERROR_STATUS] = true;
//        
//        $this->Exceptions[IADBCException::ERROR_MESSAGE] = isset($errors[0]['message']) ? $errors[0]['message'] : null;
//     
//        $this->Exceptions[IADBCException::ERROR_CODE] = !empty($errors[0]['code']) ? $errors[0]['code'] : null;      
//   }
   
    private function auth()
    {
        $server = $this->InfoConnection->getServerID().','.$this->InfoConnection->getPort();
        
        try
        {
            $EmptyCommand = new \bin\env\EmptyValue($server);

            if($EmptyCommand->verify())
            {
                throw new ArgumentException($EmptyCommand->formatMessage(new \bin\env\IntegrityCheckMessage('Server name or ip Appears to be empty and returned type {%s}!')),'An error occurred','error');
            }
            
            $c = @sqlsrv_connect($server,array('Database' => $this->InfoConnection->getDatabaseName(),'UID' => $this->InfoConnection->getUserName(),'PWD' => $this->InfoConnection->getPassword(),'ReturnDatesAsStrings' => true,'MultipleActiveResultSets' => false, 'CharacterSet' => 'UTF-8'));

            if($c === false)
            {
                $this->handleError();
            }
        }
        catch(Exception $ex)
        {
            throw $ex;
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
   
//    private function Authenticate($ServerName = null,$User = null,$Password = null,$DataBaseName = null)
//    {
//        return @sqlsrv_connect($ServerName,array('Database' => $DataBaseName,'UID' => $User,'PWD' => $Password,'ReturnDatesAsStrings' => true,'MultipleActiveResultSets' => false, 'CharacterSet' => 'UTF-8'));
//    }
//   
//    public function Connect()
//    {	 
//         $ServerName = ($this->DataConnection->GetPort() == null) ? $this->DataConnection->GetServerName() : $this->DataConnection->GetServerName().':'.$this->HarpADBCGeneric->GetPort();
//
//         $User = $this->DataConnection->GetUser();
//
//         $Password = $this->DataConnection->GetPassword();
//
//         $DataBaseName = $this->DataConnection->GetDataBaseName();
//
//         $this->Connection = $this->Authenticate($ServerName,$User,$Password,$DataBaseName);
//
//         if(!$this->Connection)
//         {
//             $this->generateError();
//
//             return false;
//         }
//
//         return true;
//    }

    public function numRows($result)
    {
        try
        {
            $this->numRows = @sqlsrv_num_rows($result);

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
    
    protected function affectedRows($result = null)
    {
        try
        {
            $this->affectedRows = @sqlsrv_rows_affected($result);

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
        try
        {
            sqlsrv_next_result($this->sourceConnection); 
            
            sqlsrv_fetch($this->sourceConnection); 
            
            $this->lastInsertId = sqlsrv_get_field($this->sourceConnection, 0);

            if($this->lastInsertId === false)
            {
                $this->handleError();
            } 
        }
        catch(Exception $ex)
        {
            throw $ex;
        } 
    }
//    
//    public function lastInsertId()
//    {
//         $this->CommandText->getCommand()->text = " SELECT SCOPE_IDENTITY() As lastInsertId ";
//        
//         $this->lastInsertId = $this->ExecuteQueryFetchResult();
//
//         if($this->lastInsertId === false)
//         {
//             $this->generateError();
//             
//             return false;
//         }
//         else
//         {
//             $this->lastInsertId = $this->lastInsertId[0]['lastInsertId'];
//         }
//    }
//    
    
    
    public function transactionBegin()
    { 
        try
        {
            if($this->isConnected())
            {
                $result = @sqlsrv_query($this->sourceConnection,'BEGIN TRANSACTION');

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
                $result = @mysqli_query($this->sourceConnection,'COMMIT');

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
    
//    public function TransactionCommit()
//    {
//        if($this->Connection)
//        {
//            $result = @sqlsrv_query($this->Connection,'COMMIT');
//  
//            if($result)
//            {
//                return true;
//            }
//        }
//        
//        $this->generateError();
//        
//        return false;        
//    }
//    
    
    public function transactionRollback()
    {
        try
        {
            if($this->isConnected())
            {
                $result = @sqlsrv_query($this->sourceConnection,'ROLLBACK');

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
    
//    public function TransactionRollback()
//    {
//        if($this->Connection)
//        {
//            $result = @sqlsrv_query($this->Connection,'ROLLBACK');
//  
//            if($result)
//            {
//                return $this;
//            }
//        }
//        
//        $this->generateError();
//        
//        return false;
//    } 
        
    public function addFieldAsKey($FieldName)
    {
        $this->fieldKey = $FieldName;
    } 
    
    public function addRowNumberResult($status = true)
    {
        $this->addRowNumberResult = (bool) $status;
    } 
    
    public function addSequence($status = true)
    {
        $this->addSequence = (bool) $status;
    }      
       
    protected function executeCommandQuery()
    {
        try
        {
            $allowedCommands = '(SELECT|WITH|DESCRIBE|DECLARE|SET)';
            
            $s = preg_match('#^'.$allowedCommands.'#is',trim($this->CommandText->getCommand()->text));
   
            if(!$this->isConnected() || empty($this->CommandText->getCommand()->text))
            {
                throw new ArgumentException('To perform this operation you must be logged in and your commandText must contain a sql command','Warning','warning');
            }
            else if(!$s)
            {
                throw new ArgumentException('Currently the allowed commands are {'.$allowedCommands.'}','Warning','warning');
            }
            
            $result = @sqlsrv_query($this->sourceConnection,$this->CommandText->getCommand()->text,Array(),Array('Scrollable' => 'buffered'));

            if(!$result){ $this->handleError();}
            
            $this->numRows($result);
                
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return $result;
    }    
    
//    protected function executeCommandQuery()
//    {
//        if($this->Connection && !empty($this->CommandText->getCommand()->text) && !$this->ErrorStatusExists())
//        {
//            $s = preg_match('#^(SELECT|WITH|DESCRIBE|DECLARE|SET)#is',trim($this->CommandText->getCommand()->text));
//           // echo $this->CommandText->getCommand()->text;exit;
//            if($s)
//            {
//                $result = @sqlsrv_query($this->Connection,$this->CommandText->getCommand()->text,Array(),Array('Scrollable' => 'buffered'));
//
//                if(!$result)
//                {
//                    $this->generateError();
//
//                    return false;
//                }
//                
//               
//                $this->CountRows($result);
//
//                return $result;
//            }
//            else
//            {
//                $this->GenerateGenericError('01');
//            }
//        }
//        
//        return false;
//    }
    
    
    public function executeAnyCommand()
    {
        try
        {
            if(!$this->isConnected() || empty($this->CommandText->getCommand()->text))
            {
                throw new ArgumentException('To perform this operation you must be logged in and your commandText must contain a sql command','Warning','warning');
            }
            
            $result = @sqlsrv_query($this->sourceConnection,$this->CommandText->getCommand()->text,Array(),Array('Scrollable' => 'buffered'));
            
            if(!$result){ $this->handleError();}   
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return $result;
    }      
    
//    public function ExecuteCommandFree()
//    {
//        if($this->Connection && !empty($this->CommandText->getCommand()->text) && !$this->ErrorStatusExists())
//        {
//             $result = @sqlsrv_query($this->Connection,$this->CommandText,Array(),Array('Scrollable' => 'buffered'));
//             
//             if(!$result)
//             {
//                 $this->generateError();
//                 
//                 return false;
//             }
//             
//             return $result;
//        }
//        
//        return false;
//    }    
    
    
    private function fetchResultField($result)
    {
        $metadata = sqlsrv_field_metadata($result);
        
        $Fields = Array();
        
        foreach($metadata as $i => $data)
        {
              $Fields[$i] = $data['Name'];
              $Fields[$data['Name']] = $i;
        }
    
        if($this->fieldKey !== false && isset($Fields[$this->fieldKey]))
        {            
            $this->fieldKey = $Fields[$this->fieldKey];
        }
        else
        {
            $this->fieldKey = false;
        }

        return $Fields;   
    }       
    
    
//    private function FetchResultField($result)
//    {
//        
//        $metadata = sqlsrv_field_metadata($result);
//       
//        $Fields = Array();
//        
//        foreach($metadata as $i => $data)
//        {
//              $Fields[$i] = $data['Name'];
//              $Fields[$data['Name']] = $i;
//        }
//   
//        if($this->fieldKey !== false && isset($Fields[$this->fieldKey]))
//        {            
//            $this->fieldKey = $Fields[$this->fieldKey];
//        }
//        else
//        {
//            $this->fieldKey = false;
//        }
//
//        return $Fields;         
//       
//    }   
    
    private function fetchEncrypted($result)
    {
            $Fields = $this->fetchResultField($result);
            
            $DataTable = Array();
            
            $this->numRows = 0;
           
            if($this->fieldKey !== false)
            {
                while($row = sqlsrv_fetch_array($result,SQLSRV_FETCH_NUMERIC)) 
                {                     
                    $c = count($row);
                    
                    for($i = 0;$i < $c;++$i)
                    {
                        if(isset($this->encryptionFields[$Fields[$i]])  && !empty($row[$i]))
                        {
                            $row[$x] = $this->HarpCryptography->encrypt($row[$x]);
                        } 
                        
                        $DataTable[$row[$this->fieldKey]][$Fields[$i]] = $row[$i];
                    }
                                    
                    ++$this->numRows;
                }                                     
            }
            else
            {                
                while ($row = sqlsrv_fetch_array($result,SQLSRV_FETCH_NUMERIC)) 
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
    
//    private function FetchEncrypted($result)
//    {        
//            $Fields = $this->FetchResultField($result);
//
//            $DataTable = Array();
//            
//            $this->CountRows = 0;
//                       
//            if($this->fieldKey !== false)
//            {          
//                while($row = sqlsrv_fetch_array($result,SQLSRV_FETCH_NUMERIC)) 
//                {                     
//                    $c = count($row);
//                    
//                    for($i = 0;$i < $c;++$i)
//                    {
//                        if(isset($this->EncryptionFields[$Fields[$i]]))
//                        {
//                            $row[$i] = $this->Encrypt($row[$i],$this->EncryptionKey);
//                        } 
//                        
//                        $DataTable[$row[$this->fieldKey]][$Fields[$i]] = $row[$i];
//                        
//                    }
//                    
//                    if($this->addRowNumberResult)
//                    {
//                       $DataTable[$row[$this->fieldKey]]['RowNumber'] = ($this->CountRows + 1);
//
//                    } 
//
//                    if($this->addSequence)
//                    {
//                        $DataTable[$row[$this->fieldKey]]['RowSequence'] = $this->CountRows;
//                    }
//                                        
//                    ++$this->CountRows;
//                } 
// 
//            }
//            else
//            {
//                while ($row = sqlsrv_fetch_array($result,SQLSRV_FETCH_NUMERIC)) 
//                {
//                    foreach($row as $x => $v)
//                    {
//                        if(isset($this->EncryptionFields[$Fields[$x]]) && !empty($row[$x]))
//                        {
//                            $row[$x] = $this->Encrypt($row[$x],$this->EncryptionKey);
//                        }
//                        
//                        $DataTable[$this->CountRows][$Fields[$x]] = $row[$x];                                   
//                    }
//                    
//                    if($this->addRowNumberResult)
//                    {
//                       $DataTable[$this->CountRows]['RowNumber'] = ($this->CountRows + 1);
//
//                    }
//                    
//                    if($this->addSequence)
//                    {
//                        $DataTable[$this->CountRows]['RowSequence'] = $this->CountRows;
//                    }
//                    
//                    ++$this->CountRows;  
//                } 
//            }
//            
//            $this->fieldKey = false;
//            
//            $this->addRowNumberResult = false;
//
//            return $DataTable;  
//    }
    
    
    private function fetchUnecrypted($result)
    {
        $Fields = $this->fetchResultField($result);

        $DataTable = Array();

        $this->numRows = 0;
        
        if($this->fieldKey !== false)
        {
            while ($row = sqlsrv_fetch_array($result,SQLSRV_FETCH_NUMERIC)) 
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
            while ($row = sqlsrv_fetch_array($result,SQLSRV_FETCH_NUMERIC)) 
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
    
//    private function FetchUnenCrypted($result)
//    {
//        $Fields = $this->FetchResultField($result);
//
//        $DataTable = Array();
//
//        $this->CountRows = 0;
//       
//        if($this->fieldKey !== false)
//        {
//            while ($row = sqlsrv_fetch_array($result,SQLSRV_FETCH_NUMERIC)) 
//            {
//                foreach($row as $x => $v)
//                {
//                    $DataTable[$row[$this->fieldKey]][$Fields[$x]] = $row[$x];
//                }
//                                 
//                if($this->addRowNumberResult)
//                {
//                   $DataTable[$row[$this->fieldKey]]['RowNumber'] = ($this->CountRows + 1);
//                }
//                
//                if($this->addSequence)
//                {
//                    $DataTable[$row[$this->fieldKey]]['RowSequence'] = $this->CountRows;
//                }                  
//                
//
//                ++$this->CountRows;
//            }                      
//        } 
//        else
//        {
//            while ($row = sqlsrv_fetch_array($result,SQLSRV_FETCH_NUMERIC)) 
//            {
//                foreach($row as $x => $v)
//                {
//                    $DataTable[$this->CountRows][$Fields[$x]] = $row[$x];
//                }
//                
//                if($this->addRowNumberResult)
//                {
//                   $DataTable[$this->CountRows]['RowNumber'] = ($this->CountRows + 1);
//                }
//                
//                if($this->addSequence)
//                {
//                    $DataTable[$this->CountRows]['RowSequence'] = $this->CountRows;
//                } 
//
//                ++$this->CountRows;                       
//            }                     
//        }
//        
//        $this->fieldKey = false;
//        
//        $this->addRowNumberResult = false;
//        
//        $this->addSequence = false;
//        
//        return $DataTable;
//    }
    
    
    protected function fetchResult($result)
    {
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
        
        return null;
    }      
    
//    protected function FetchResult($result)
//    {
//        if(!empty($this->EncryptionFields))
//        {
//            $DataTable = $this->FetchEncrypted($result);
//            
//            return $DataTable;
//        }
//        else
//        {
//            $DataTable = $this->FetchUnenCrypted($result);    
//            
//            return $DataTable;
//        }
//        
//        return false;
//    }    
//   

    
    public function executeQueryfetchResult()
    {
        try
        {
            $result = $this->executeCommandQuery();

            return $this->fetchResult($result);
        }
        catch(Exception $ex)
        {
            throw $ex;
        }            
    }     
   
//    public function ExecuteQueryFetchResult()
//    {
//        $result = $this->ExecuteCommandQuery();
//
//        if($result)
//        {
//            return $this->FetchResult($result);
//        }
//
//        return false;            
//    } 

    
    public function executeNonQuery()
    {
        try
        {
            $allowedCommands = '(INSERT|DELETE|UPDATE|DECLARE|USE)';
            
            $s = preg_match('#^'.$allowedCommands.'#is',trim($this->CommandText->getCommand()->text),$r);

            if(!$this->isConnected()|| empty($this->CommandText->getCommand()->text))
            {
                throw new ArgumentException('To perform this operation you must be logged in and your commandText must contain a sql command','Warning','warning');
            }
            else if(!$s)
            {
                throw new ArgumentException('Currently the allowed commands are {'.$allowedCommands.'}','Warning','warning');
            }

            $result = @sqlsrv_query($this->sourceConnection,$this->CommandText->getCommand()->text);

            if(!$result){$this->handleError();}

            if(strtoupper($r[0]) === ConnectionDriverEnum::COMMAND_INSERT)
            {
                $this->lastInsertId();
            }   
            
            $this->affectedRows($result);
            
            return $result;
        }
        catch(Exception $ex)
        {
            throw $ex;
        }                    
    }     
    
//    public function ExecuteNonQuery()
//    {
//        $s = preg_match('#^(INSERT|DELETE|UPDATE|DECLARE|USE)#is',trim($this->CommandText->getCommand()->text),$r);
//
//        if($s && !empty($this->CommandText->getCommand()->text) && !$this->ErrorStatusExists())
//        {
//            $result = @sqlsrv_query($this->Connection,$this->CommandText->getCommand()->text);
//          
//            if(!is_resource($result))
//            {
//                $this->generateError();
//            
//                return false;
//            }
//            
//            
//            if(strtoupper($r[0]) === IADBCDriver::COMMAND_INSERT)
//            {
//                $this->lastInsertId();
//            }   
//            
//            $this->AffectedRows($result);
//            
//            return $result;
//        }
//        else
//        {
//           
//            $this->GenerateGenericError('02');
//        }
//        
//        return false;         
//    } 
    
//    public function &getParameter()
//    {
//        return $this->HarpADBCParameter;
//    }
    
    public function close()
    {
        $r = false;
        
        if($this->isConnected())
        {
             $r = @sqlsrv_close($this->sourceConnection);
             $this->sourceConnection = null;
        }

        return $r;
    }     
    
    public function isConnected()
    {
        if(empty($this->sourceConnection) || (is_object($this->sourceConnection) && get_class($this->sourceConnection) != $this->getDriverName()))
        {    
            return false;
        }
  
        return true;
    }    
   
//    public function Close()
//    {      
//        $result = @sqlsrv_close($this->Connection);
//
//        if(!$result)
//        {
//            $this->generateError();
//
//            return false;
//        }
//
//        return $result;
//    } 
    
//    public function isRestrictionForeignKey()
//    {
//        $codesForeignKeyRestriction = Array
//        (
//            547 => true,
//        );
//        
//        if(isset($codesForeignKeyRestriction[$this->Exceptions[IADBCException::ERROR_CODE]]))
//        {
//            return true;
//        }    
//        
//        return false;
//    }
//        
//    public function IsConnected()
//    {
//        if(!$this->Connection)
//        {
//            return false;
//        }
//        
//        return true;
//    }
//    
//    public function GetNumRows()
//    {
//        return $this->CountRows;
//    }    
//    
//    public function GetAffectedRows()
//    {
//        return $this->AffectedRows;
//    }
//    
//    public function getInsertId()
//    {
//        return $this->lastInsertId;
//    }

    
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
        return DatabaseEnum::SGBD_SQL_SERVER;
    }

}
