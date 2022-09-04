<?php
/*
 * Copyright 2010 Leonardo Souza da Silva <allezo.lss@gmail.com>.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace Harp\lib\HarpDB;

use etc\HarpDatabase\connection\InfoConnection;
use etc\HarpDatabase\DatabaseEnum;
use etc\HarpDatabase\drivers\ConnectionDriver;
use Harp\bin\ArgumentException;
use Exception;

class ConnectionDriverMysqli extends ConnectionDriver
{   
   
   const DEFAULT_PORT = '3306'; 
    
   private $sourceConnection; 
   private $numRows = 0;
   private $fieldKey;
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
       $error = mysqli_connect_error();
       $error = !empty($error) ? $error : mysqli_error($this->sourceConnection);
       $errno = mysqli_connect_errno();
       $errno = !empty($errno) ? $errno : mysqli_errno($this->sourceConnection);
       
       throw new ArgumentException($error,'An error occurred','error',$errno);
   }


    private function auth()
    {
        $server = $this->InfoConnection->getServerID();
        
        $port = self::DEFAULT_PORT;
        
        if(self::DEFAULT_PORT != $this->InfoConnection->getPort())
        {
            $port = $this->getInfoConnection()->getPort();
        }
        
        try
        {
            $EmptyCommand = new \bin\env\EmptyValue($server);

            if($EmptyCommand->verify())
            {
                throw new ArgumentException($EmptyCommand->formatMessage(new \bin\env\IntegrityCheckMessage('Server name or ip Appears to be empty and returned type {%s}!')),'An error occurred','error');
            }
            
            $c = mysqli_connect($server,$this->InfoConnection->getUserName(),$this->InfoConnection->getPassword(),$this->InfoConnection->getDatabaseName(),$port);

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

                @mysqli_set_charset($this->sourceConnection,'utf8');

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
            $this->numRows = @mysqli_num_rows($result);

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
    
    protected function affectedRows()
    {
        try
        {
            $this->affectedRows = @mysqli_affected_rows($this->sourceConnection);

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
            $this->lastInsertId = mysqli_insert_id($this->sourceConnection);

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
    
    public function transactionBegin()
    { 
        try
        {
            if($this->isConnected())
            {
                $result = @mysqli_query($this->sourceConnection,'START TRANSACTION');

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
    
    public function transactionRollback()
    {
        try
        {
            if($this->isConnected())
            {
                $result = @mysqli_query($this->sourceConnection,'ROLLBACK');

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
    
    protected function executeCommandQuery()
    {
        try
        {
            $allowedCommands = '(SELECT|DESCRIBE|SET|DESC)';
            
            $s = preg_match('#^'.$allowedCommands.'#is',trim($this->CommandText->getCommand()->text));
                        
            if(!$this->isConnected() || empty($this->CommandText->getCommand()->text))
            {
                throw new ArgumentException('To perform this operation you must be logged in and your commandText must contain a sql command','Warning','warning');
            }
            else if(!$s)
            {
                throw new ArgumentException('Currently the allowed commands are {'.$allowedCommands.'}','Warning','warning');
            }
            
            $result = @mysqli_query($this->sourceConnection,$this->CommandText->getCommand()->text);
            
            if(!$result){ $this->handleError();}
            
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
            
            $result = @mysqli_query($this->sourceConnection,$this->CommandText->getCommand()->text);
            
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
        
        $i = 0;

        while($Field = mysqli_fetch_field($result))
        {
              $Fields[$i] = $Field->name;
              $Fields[$Field->name] = $i;

              ++$i;
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
                while($row = mysqli_fetch_row($result)) 
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
                while ($row = mysqli_fetch_row($result)) 
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
            while ($row = mysqli_fetch_row($result)) 
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
            while ($row = mysqli_fetch_row($result)) 
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

    public function executeNonQuery()
    {
        $result = false;
        
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

            $result = @mysqli_query($this->sourceConnection,$this->CommandText->getCommand()->text);

            if(!$result){$this->handleError();}

            if(strtoupper($r[0]) === ConnectionDriverEnum::COMMAND_INSERT)
            {
                $this->lastInsertId();
            }   
            
            $this->affectedRows();
        }
        catch(Exception $ex)
        {
            throw $ex;
        }     
                    
        return $result;
    } 
    
    public function close()
    {
        $r = false;
        
        if($this->isConnected())
        {
             $r = @mysqli_close($this->sourceConnection);
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
        return DatabaseEnum::SGBD_MYSQL;
    }
}
