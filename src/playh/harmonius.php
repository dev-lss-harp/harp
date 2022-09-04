#!/usr/bin/php
<?php

use Harp\lib\HarpCryptography\HarpCryptography;

class hdb
{
    public $argv;
    public $path;
    private const KEY_IV = '(deliveryday-app-1455655448895)';
    
    public function __construct()
    {
        $this->argv = $_SERVER['argv'];
        $this->path = __DIR__;
    }

    private function getNameMethod($name)
    {
        $method = trim($name);
        $mexp = explode('-',$method);
        $method = $mexp[0];

        if(count($mexp) > 1)
        {
            for($i = 1; $i < count($mexp); ++$i)
            {   
                $pm = ucfirst($mexp[$i]);
                $method.= $pm;
            }
        }

        return $method;
    }

    public function parseCommand()
    {
        try
        {
            if(isset($this->argv[1]))
            { 
                if(preg_match('`(?:([A-z\-0-9]*)\:([A-z0-9\_\.\,]*))`i',$this->argv[1],$res))
                {
                    $method = $this->getNameMethod($res[1]);
                
                    if(!method_exists($this,$method))
                    {
                        throw new Exception("command {".$res[0]."} not found!");
                    }

                    call_user_func([$this,$method],$res);
                 
                }
                else
                {
                    throw new Exception("invalid command {".$this->argv[1]."}!");
                }
            }
            else
            {
                $this->printHelp();
            }
        }
        catch(Exception $ex)
        {
            $this->showMessage($ex->getMessage());
        }

    }

    private function printHelp()
    {
        $this->showMessage(file_get_contents($this->path.DIRECTORY_SEPARATOR.'hdb-helper.txt'));
    }

    private function showMessage($msg)
    {
        print($msg.PHP_EOL);
    }

    private function connectDb($dsn)
    {
        $conn = null;

        try
        {
            $conn = new PDO($dsn);
   
        }catch (PDOException $ex)
        {
            throw $ex;
        }


        return $conn;
    }

    private $dbTypes = [
        'character varying' => 'varchar',
        'integer' => 'int',
        'timestamp without time zone' => 'timestamp',
        'boolean' => 'boolean',
        'smallint' => 'int',
        'bit' => 'bit'
    ];

    private $dbLengths = [
        'character varying' => 'varchar',
        'integer' => 'int',
        'timestamp without time zone' => 'datetime_iso8601',
        'boolean' => 'boolean',
        'smallint' => 'int',
        'bit' => 'bit'
    ];    

    private function getType($dataType)
    {
           if(empty($this->dbTypes[$dataType]))
           {
               throw new Exception('The type {'.$dataType.'} not mapped!');
           } 

           return $this->dbTypes[$dataType];
    }

    private function getLength($dataType)
    {
           if(empty($this->dbLengths[$dataType]))
           {
               throw new Exception('The length {'.$dataType.'} not mapped!');
           } 

           return $this->dbLengths[$dataType];
    }

    private function getNameAttribute($nm)
    {
        $entityAttribute = '';

        if(!empty($nm))
        {
            $exp = explode('_',$nm);

            $entityAttribute = $exp[0];
            if(count($exp) > 1)
            {
                for($h = 1; $h < count($exp);++$h)
                {
                    $entityAttribute .= ucfirst($exp[$h]);
                }
                
            }
        }


        return $entityAttribute;
    }

    private function confConnDb($dbConfig)
    {
        $dir = $this->path.DIRECTORY_SEPARATOR.'hdb-files';

        if(!isset($dbConfig[2]))
        {
            throw new Exception('Invalid command to config connection DB!');
        }

        $dbCnf = explode(',',$dbConfig[2]);

        if(!isset($dbCnf[5]))
        {
            throw new Exception('Expected 5 parameters to config db!');
        }

        $Crypt = $this->getCryptography(self::KEY_IV);

        $connString = $dbCnf[0].":host=".$dbCnf[1].";port=".$dbCnf[2].";dbname=".$dbCnf[3].";user=".$dbCnf[4].";password=".$dbCnf[5];

        $connString = $Crypt->encrypt($connString);

        file_put_contents($dir.DIRECTORY_SEPARATOR.$dbCnf[3],$connString);
    }

    private function mapTb($parsedArgs)
    {
        try
        {
       
            if(!isset($parsedArgs[2]))
            {
                throw new Exception("command not found!");
            }

            $cmdExp = explode('.',$parsedArgs[2]);

            if(!isset($cmdExp[0]))
            {
                throw new Exception("command does not contains db name!"); 
            }
            else if(!isset($cmdExp[1]))
            {
                throw new Exception("command does not  contains table name!");
            }
            else if(!isset($cmdExp[2]))
            {
                throw new Exception("command does not  contains key decryption file {".$cmdExp[0]."}!");
            }
            else if(!isset($cmdExp[3]))
            {
                throw new Exception("command does not contains app name!");
            }

            $db = $cmdExp[0];
            $tableName = $cmdExp[1];
            $appName = $cmdExp[3];
 
            $className = ucfirst($appName);

            $pth = $this->path.DIRECTORY_SEPARATOR.'hdb-files'.DIRECTORY_SEPARATOR.$db;

            if(!file_exists($pth))
            {
                throw new Exception("to use this cli use the method: HarpConnection::createConnectionFile!");
            }

            $readFile = @file_get_contents($pth);

            if(!$readFile)
            {
                throw new Exception("file {".$className.'.php'."} failed to open!");
            }

       

            $crypt = $this->getCryptography(self::KEY_IV);

            $cnf = $crypt->decrypt($readFile);

            if(empty($cnf))
            {
                throw new Exception("invalid password to decrypt db config file!");
            }
         
            $conn = $this->connectDb($cnf);

            $conf = explode(':',$cnf);

            $jsonDb = json_decode(file_get_contents(
                $this->path.DIRECTORY_SEPARATOR.
                'app'.
                DIRECTORY_SEPARATOR.
                $appName.
                DIRECTORY_SEPARATOR.
                $db.'.json'),true);

                $jsonDb['tables'][$tableName]['attributes'] = [

                ];
                $jsonDb['tables'][$tableName]['columns'] = [];

                
            if($conf[0] == 'pgsql')
            {
               // echo $this->getQueryPgsql($tableName);exit;
                $stmt = $conn->query($this->getQueryPgsql($tableName));

                while ($row = $stmt->fetch()) 
                {
                    $columnName = $row['column_name'];
                  
           
                    $entityAttribute = $this->getNameAttribute($columnName);
                    $entityName = $this->getNameAttribute($row['table_name']);

                    if(empty($jsonDb['tables'][$tableName]['attributes']))
                    {
                        $jsonDb['tables'][$tableName]['attributes'] = [
                            'schema' => $row['table_schema'],
                            'alias' =>  mb_substr(str_shuffle($row['table_name']),rand(0,mb_strlen($row['table_name'])),4),
                            'entity' => ucfirst($entityName),
                            'table' => $row['table_name'],
                            'pk' => $row['constraint_type'] == 'PRIMARY KEY' ? $row['column_name'] : null
                        ];
                    }
                    else if(empty($jsonDb['tables'][$tableName]['attributes']['pk']))
                    {
                        $jsonDb['tables'][$tableName]['attributes']['pk'] = 
                                $row['constraint_type'] == 'PRIMARY KEY' ? 
                                $row['column_name'] : 
                                null;
                    }

                    $jsonDb['tables'][$tableName]['columns'][$columnName] = [
                        'type' => $this->getType($row['data_type']),
                        'length' => $this->getLength($row['data_type']),
                        'entityAttribute' => $entityAttribute
                    ];

                    if($row['constraint_type'] == 'FOREIGN KEY')
                    {

                        $entityNameFK = $this->getNameAttribute($row['foreign_table_name']);

                        $jsonDb['tables'][$tableName]['columns'][$columnName]['fk'] = [
                            'entityAttribute' => $entityAttribute,
                            'references' => $row['foreign_table_name'],
                            'relation' => 'inner join',
                            'operator' => '=',
                            'columnName' => $row['foreign_column_name'],
                            'entityName' => ucfirst($entityNameFK)  
                        ];
                    }
                }
            }

            $js = json_encode($jsonDb,JSON_PRETTY_PRINT);

            $s = file_put_contents(
                $this->path.DIRECTORY_SEPARATOR.
                'app'.
                DIRECTORY_SEPARATOR.
                $appName.
                DIRECTORY_SEPARATOR.
                $db.'.json',$js);
               
            if($s)
            {
                $this->showMessage("Table {".$tableName."} mapped!");
            }   
           
            if(isset($cmdExp[4]) && isset($cmdExp[5]))
            {
                $this->createEntity($jsonDb['tables'][$tableName],$cmdExp);
            }

        }
        catch(Exception $ex)
        {
            $this->showMessage($ex->getMessage());
        }
    }

    private function getCryptography($keyIV)
    {
        $pCrypto = $this->path.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'HarpCryptography'.DIRECTORY_SEPARATOR;

        include_once($pCrypto.'CryptographyInterface.php');
        include_once($pCrypto.'HarpHash.php');
        include_once($pCrypto.'HarpCryptography.php');
        return new HarpCryptography('AES-256-CBC',$keyIV);
    }

    private function getCNFFile($nameFile)
    {
        $pth = dirname($this->path).DIRECTORY_SEPARATOR.'cnf'.DIRECTORY_SEPARATOR.$nameFile;

        if(!file_exists($pth))
        {
            throw new Exception("Use HarpConnection::createConnectionFile to create a config encrypted database!");
        }

        $readFile = @file_get_contents($pth);

        return $readFile;
    }

    private function getTextTypeDB($dbCnf)
    {
        $response = 'text null';

        if($dbCnf['dbdriver'] == 'mysqli')
        {
            $response = 'mediumtext null';
        }
        else if($dbCnf['dbdriver'] == 'sqlSrv')
        {
            $response = 'VARCHAR(max) null';
        }

        return $response;
    }

    private function getDateTypeDB($dbCnf)
    {
        $response = 'datetime not null default NOW()';

        if($dbCnf['dbdriver'] == 'pgsql')
        {
            $response = 'timestamp not null default NOW()';
        }
        else if($dbCnf['dbdriver'] == 'sqlSrv')
        {
            $response = 'datetime not null default getdate()';
        }

        return $response;
    } 
    
    private function getBoolTypeDB($dbCnf)
    {
        $response = "bit(1) not null default '1'";

        if($dbCnf['dbdriver'] == 'pgsql')
        {
            $response = 'boolean not null default true';
        }
        else if($dbCnf['dbdriver'] == 'sqlSrv')
        {
            $response = 'bit not null default 1';
        }

        return $response;
    }   
    
    private function getBitTypeDB($dbCnf)
    {
        $response = "bit(1) not null default '1'";

        if($dbCnf['dbdriver'] == 'sqlSrv')
        {
            $response = 'bit not null default 1';
        }

        return $response;
    }       

    private function getConfigJsonDB($dbCnf,$exp)
    {
        $path = $this->path.DIRECTORY_SEPARATOR.
                'app'.
                DIRECTORY_SEPARATOR.
                $exp[3].
                DIRECTORY_SEPARATOR.
                $dbCnf['dbname'].'.json';

        if(!file_exists($path))
        {
            file_put_contents($path,json_encode([]));
        }      

        return json_decode(file_get_contents($path),true);

    }

    private function getSchemaDB($dbCnf)
    {
        $response = '';

        if($dbCnf['dbdriver'] == 'pgsql')
        {
            $response = 'public';
        }
        else if($dbCnf['dbdriver'] == 'sqlSrv')
        {
            $response = 'dbo';
        }

        return $response;
    }

    private function configSession($args)
    {
        try
        { 

            if(!isset($args[2]))
            {
                throw new Exception('incomplete command!');
            }

            $exp = explode('.',$args[2]);

            if(!isset($exp[1]))
            {
                throw new Exception("command does not contains pass to decryption file database config!");
            }
            else if(!isset($exp[2]))
            {
                throw new Exception("command does not contains name database!");
            }

            $file = $this->getCNFFile($exp[2]);
         
            $crypt = $this->getCryptography($exp[1]);
            $cnf = $crypt->decrypt($file);
            $dbCnf = json_decode($cnf,true);
            if(empty($dbCnf))
            {
                throw new Exception("invalid password to decrypt db config file!");
            }
 
            $conn = $this->connectDb($dbCnf);

            $conn->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
            $sql ="CREATE TABLE ".$exp[0]."
            (
                session_id varchar(80) not null primary key,
                session_data ".$this->getTextTypeDB($dbCnf).",
                session_created ".$this->getDateTypeDB($dbCnf).",
                session_updated ".$this->getDateTypeDB($dbCnf).",
                session_last_updated ".$this->getDateTypeDB($dbCnf).",
                session_expired ".$this->getDateTypeDB($dbCnf).",
                session_status ".$this->getBitTypeDB($dbCnf)."
            )";

            try
            {
                $conn->exec('DROP TABLE '.$exp[0]);
            }
            catch(\Exception $ex)
            {
                $this->showMessage('table '.$exp[0].' not exists...');
            }
            
            $conn->exec($sql);

            $this->showMessage("Created ".$exp[0]." Table.");

            $jsonDb = $this->getConfigJsonDB($dbCnf,$exp);  
            
            $epl = explode('_',$exp[0]);
            $entity = ucfirst($epl[0]).(isset($epl[1]) ? ucfirst($epl[1]) : '');

                    
            $jsonDb['tables'][$exp[0]]['attributes'] = [
                'schema' => $this->getSchemaDB($dbCnf),
                'alias' =>  mb_substr(str_shuffle($exp[0]),rand(0,mb_strlen($exp[0])),4),
                'entity' => $entity,
                'table' => $exp[0],
                'pk' => 'session_id'
            ];

            $jsonDb['tables'][$exp[0]]['columns']['session_id'] = 
            [
                "type" => "varchar",
                "length" => "varchar",
                "entityAttribute" => "id"
            ];

            $jsonDb['tables'][$exp[0]]['columns']['session_data'] = 
            [
                "type" => "varchar",
                "length" => "varchar",
                "entityAttribute" => "data"
            ];

            $jsonDb['tables'][$exp[0]]['columns']['session_status'] = 
            [
                "type" => ($dbCnf['dbdriver'] == 'pgsql' || $dbCnf['dbdriver'] == 'mysqli') ? 'bit' : 'bit',
                "length" => ($dbCnf['dbdriver'] == 'pgsql' || $dbCnf['dbdriver'] == 'mysqli') ? 'bit' : 'bit',
                "entityAttribute" => "status"
            ];

            $jsonDb['tables'][$exp[0]]['columns']['session_created'] = 
            [
                "type" => $dbCnf['dbdriver'] == 'pgsql' ? "timestamp" : 'datetime',
                "length" => "datetime_iso8601",
                "entityAttribute" => "created"
            ];   
            
            $jsonDb['tables'][$exp[0]]['columns']['session_updated'] = 
            [
                "type" => $dbCnf['dbdriver'] == 'pgsql' ? "timestamp" : 'datetime',
                "length" => "datetime_iso8601",
                "entityAttribute" => "updated"
            ]; 
            
            $jsonDb['tables'][$exp[0]]['columns']['session_last_updated'] = 
            [
                "type" => $dbCnf['dbdriver'] == 'pgsql' ? "timestamp" : 'datetime',
                "length" => "datetime_iso8601",
                "entityAttribute" => "lastUpdated"
            ];   

            $jsonDb['tables'][$exp[0]]['columns']['session_expired'] = 
            [
                "type" => $dbCnf['dbdriver'] == 'pgsql' ? "timestamp" : 'datetime',
                "length" => "datetime_iso8601",
                "entityAttribute" => "expired"
            ];               

            $js = json_encode($jsonDb,JSON_PRETTY_PRINT);

            $s = file_put_contents(
            $this->path.DIRECTORY_SEPARATOR.
            'app'.
            DIRECTORY_SEPARATOR.
            $exp[3].
            DIRECTORY_SEPARATOR.
            $dbCnf['dbname'].'.json',$js);
           
            if($s)
            {
                $this->showMessage("Table {".$exp[0]."} mapped!");
            }   
        
            if(isset($exp[4]) && isset($exp[5]))
            {
                $this->createEntity($jsonDb['tables'][$exp[0]],$exp);
            }
        }
        catch(Exception $ex)
        {
            $this->showMessage($ex->getMessage());
        }
    }    

    private function createController($args)
    {
        if(isset($args[2]))
        {
            $dir = $this->path.DIRECTORY_SEPARATOR.'hdb-files';

            $exp = explode('.',$args[2]);

            $path = $this->path.
            DIRECTORY_SEPARATOR.
            'app'. 
            DIRECTORY_SEPARATOR.
            $exp[0].
            DIRECTORY_SEPARATOR.
            'modules'. 
            DIRECTORY_SEPARATOR. 
            $exp[1]. 
            DIRECTORY_SEPARATOR.
            'controller';

            if(!file_exists($dir.DIRECTORY_SEPARATOR.'ControllerBase'))
            {
                throw new Exception("base file for crete controller not found!");
            }
    
            $file = file_get_contents($dir.DIRECTORY_SEPARATOR.'ControllerBase');
            $defaultMethod = $this->createMethod('index');
            $file = str_ireplace(
                ['{{appName}}','{{moduleName}}','{{nameController}}','{{attributesConstruct}}','{{defaultMethod}}'],
                [$exp[0],$exp[1],$exp[2],'',$defaultMethod],
                $file
            );

            if(!is_dir($path))
            {
                mkdir( $path, 0775, true );
            }

            $s = file_put_contents($path.DIRECTORY_SEPARATOR.$exp[2].'Controller.php',$file);
            $this->showMessage($s ? 'controller {'.$exp[2].'} created!' : 'failed create controller {'.$exp[2].'}');   
        }
        else
        {
            $this->showMessage('invalid command to create controller!');   
        }

    }

    private function createModel($args)
    {
        if(isset($args[2]))
        {
            $dir = $this->path.DIRECTORY_SEPARATOR.'hdb-files';

            $exp = explode('.',$args[2]);

            $path = $this->path.
            DIRECTORY_SEPARATOR.
            'app'. 
            DIRECTORY_SEPARATOR.
            $exp[0].
            DIRECTORY_SEPARATOR.
            'modules'. 
            DIRECTORY_SEPARATOR. 
            $exp[1]. 
            DIRECTORY_SEPARATOR.
            'model';

            if(!file_exists($dir.DIRECTORY_SEPARATOR.'ModelBase'))
            {
                throw new Exception("base file for crete model not found!");
            }
    
            $file = file_get_contents($dir.DIRECTORY_SEPARATOR.'ModelBase');
            $defaultMethod = $this->createMethod('index');
            $file = str_ireplace(
                ['{{appName}}','{{moduleName}}','{{nameModel}}','{{defaultMethod}}'],
                [$exp[0],$exp[1],$exp[2],$defaultMethod],
                $file
            );

            if(!is_dir($path))
            {
                mkdir( $path, 0775, true );
            }

            $s = file_put_contents($path.DIRECTORY_SEPARATOR.$exp[2].'Model.php',$file);
            $this->showMessage($s ? 'model {'.$exp[2].'} created!' : 'failed create model {'.$exp[2].'}');   
        }
        else
        {
            $this->showMessage('invalid command to create model!');   
        }

    }    

    private function createRepository($args)
    {
        if(isset($args[2]))
        {
            $dir = $this->path.DIRECTORY_SEPARATOR.'hdb-files';

            $exp = explode('.',$args[2]);

            $path = $this->path.
            DIRECTORY_SEPARATOR.
            'app'. 
            DIRECTORY_SEPARATOR.
            $exp[0].
            DIRECTORY_SEPARATOR.
            'modules'. 
            DIRECTORY_SEPARATOR. 
            $exp[1]. 
            DIRECTORY_SEPARATOR.
            'repository';

            if(!file_exists($dir.DIRECTORY_SEPARATOR.'RepositoryBase'))
            {
                throw new Exception("base file for crete repository not found!");
            }
    
            $file = file_get_contents($dir.DIRECTORY_SEPARATOR.'RepositoryBase');

            $file = str_ireplace(
                ['{{appName}}','{{moduleName}}','{{repositoryName}}'],
                [$exp[0],$exp[1],$exp[2]],
                $file
            );

            if(!is_dir($path))
            {
                mkdir( $path, 0775, true );
            }

            $s = file_put_contents($path.DIRECTORY_SEPARATOR.$exp[2].'Repository.php',$file);
            $this->showMessage($s ? 'repository {'.$exp[2].'} created!' : 'failed create repository {'.$exp[2].'}');   
        }
        else
        {
            $this->showMessage('invalid command to create repository!');   
        }

    }      
    
    private function createOnlyEntity($path)
    {
        if(!isset($path[2]))
        {
            throw new Exception('invalid command!');
        }

        $exp = explode('.',$path[2]);

        if(!isset($exp[2]))
        {
            throw new Exception('invalid command!');
        }

        $baseEntityFile = $this->path.DIRECTORY_SEPARATOR.'hdb-files';
        $pathSaveEntity =   $this->path.
                            DIRECTORY_SEPARATOR.
                            'app'.
                            DIRECTORY_SEPARATOR.
                            $exp[0].
                            DIRECTORY_SEPARATOR.
                            'modules'. 
                            DIRECTORY_SEPARATOR.
                            $exp[1].
                            DIRECTORY_SEPARATOR.
                            'entity';

        if(!file_exists($baseEntityFile.DIRECTORY_SEPARATOR.'EntityBase'))
        {
            throw new Exception("base file for crete entity not found!");
        }

        $file = file_get_contents($baseEntityFile.DIRECTORY_SEPARATOR.'EntityBase');

        $classAttrs = '';  
        $constructorAttrs = ''; 
        $constructorsCode = '';
        $geterrsAndSetters = '';
        $attrsArray = [];             

        print_r($exp);exit;
    }

    private function createEntity($tbl,$args)
    {
        $baseEntityFile = $this->path.DIRECTORY_SEPARATOR.'hdb-files';
        $pathSaveEntity =   $this->path.
                            DIRECTORY_SEPARATOR.
                            'app'.
                            DIRECTORY_SEPARATOR.
                            $args[3].
                            DIRECTORY_SEPARATOR.
                            'modules'. 
                            DIRECTORY_SEPARATOR.
                            $args[5].
                            DIRECTORY_SEPARATOR.
                            'entity';
                            
        if(!file_exists($baseEntityFile.DIRECTORY_SEPARATOR.'EntityBase'))
        {
            throw new Exception("base file for crete entity not found!");
        }

        $file = file_get_contents($baseEntityFile.DIRECTORY_SEPARATOR.'EntityBase');

        $classAttrs = '';  
        $constructorAttrs = ''; 
        $constructorsCode = '';
        $geterrsAndSetters = '';
        $attrsArray = [];             

        $i = 0;
        foreach($tbl['columns'] as $column)
        {
            ++$i;
            if($column['entityAttribute'] == $this->getNameAttribute($tbl['attributes']['pk']))
            {
                $classAttrs .= 'private $id;'.PHP_EOL;
                $attrsArray[] = 'id';

                $geterrsAndSetters .= $this->createGettersAndSettersMethods('id',$i);
            }
            else
            {
                if(isset($column['fk']))
                {
                    $classAttrs .= '    public $'.$column['fk']['entityName'].';'.PHP_EOL;
                    $attrsArray[] = $column['fk']['entityName'];
                    if(!empty($constructorAttrs))
                    {
                        $constructorAttrs .= ',';   
                    } 
                  
                    $constructorAttrs .= 'EntityHandlerInterface $'.$column['fk']['entityName'];
                    $constructorsCode .= empty($constructorsCode) ? '$this->'.$column['fk']['entityName'].' = $'.$column['fk']['entityName'].';'.PHP_EOL : '        $this->'.$column['fk']['entityName'].' = $'.$column['fk']['entityName'].';'.PHP_EOL;

                    $geterrsAndSetters .= $this->createGettersAndSettersMethods($column['fk']['entityName'],$i);
                }
                else
                {
                    $classAttrs .= '    private $'.$column['entityAttribute'].';'.PHP_EOL;
                    $attrsArray[] = $column['entityAttribute'];

                    $geterrsAndSetters .= $this->createGettersAndSettersMethods($column['entityAttribute'],$i);
                }
            }

        }
     
        $file = str_ireplace(
            [
                '{{appName}}',
                '{{moduleName}}',
                '{{nameEntity}}',
                '{{entityAttributes}}',
                '{{attributesConstruct}}',
                '{{constructCode}}',
                '{{gettersAndSetters}}',
            ],
            [
                $args[3],
                $args[5],
                $tbl['attributes']['entity'],
                $classAttrs,
                $constructorAttrs,
                $constructorsCode,
                $geterrsAndSetters
            ],$file);

            if(!is_dir($pathSaveEntity))
            {
                mkdir( $pathSaveEntity, 0775, true );
            }

            $s = file_put_contents($pathSaveEntity.DIRECTORY_SEPARATOR.$tbl['attributes']['entity'].'Entity.php',$file);
            $this->showMessage($s ? 'entity {'.$tbl['attributes']['entity'].'} created!' : 'failed create entity {'.$tbl['attributes']['entity'].'}');    

    }

    private function createMethod($name,Array $params = [])
    {
        $nm = $name;
        $ch32 = str_repeat(chr(32),4);
            
            $method  = 'public function '.$nm.'('.(!empty($params) ? implode(',',$params) : '').')'.PHP_EOL;
            $method .= $ch32.'{'.PHP_EOL;
                $method .= $ch32.$ch32.'try'.PHP_EOL;   
                $method .= $ch32.$ch32.'{'.PHP_EOL; 
                $method .= $ch32.$ch32.'}'.PHP_EOL;   
                $method .= $ch32.$ch32.'catch(\Exception $ex)'.PHP_EOL;   
                $method .= $ch32.$ch32.'{'.PHP_EOL; 
                $method .= $ch32.$ch32.'}'.PHP_EOL;     
            $method .= $ch32.'}'.PHP_EOL.PHP_EOL;
      
        return $method;        
    }

    private function createGettersAndSettersMethods($name,$i)
    {
        $nm = ucfirst($name);
        $ch32 = str_repeat(chr(32),4);
        if($i > 1)
        {
            
            $geterrsAndSetters  = $ch32.'public function set'.$nm.'($'.$name.')'.PHP_EOL;
            $geterrsAndSetters .= $ch32.'{'.PHP_EOL;
                $geterrsAndSetters .= $ch32.$ch32.'$this->'.$name.' = $'.$name.';'.PHP_EOL;   
                $geterrsAndSetters .= $ch32.$ch32.'return $this;'.PHP_EOL;  
            $geterrsAndSetters .= $ch32.'}'.PHP_EOL.PHP_EOL;
            $geterrsAndSetters .= $ch32.'public function get'.$nm.'()'.PHP_EOL;
            $geterrsAndSetters .= $ch32.'{'.PHP_EOL;
                $geterrsAndSetters .= $ch32.$ch32.'return $this->'.$name.';'.PHP_EOL;   
            $geterrsAndSetters .= $ch32.'}'.PHP_EOL.PHP_EOL;
        }
        else
        {
            $geterrsAndSetters  =       'public function set'.$nm.'($'.$name.')'.PHP_EOL;
            $geterrsAndSetters .= $ch32.'{'.PHP_EOL;
                $geterrsAndSetters .= $ch32.$ch32.'$this->'.$name.' = $'.$name.';'.PHP_EOL;   
                $geterrsAndSetters .= $ch32.$ch32.'return $this;'.PHP_EOL;  
            $geterrsAndSetters .= $ch32.'}'.PHP_EOL.PHP_EOL;
            $geterrsAndSetters .= $ch32.'public function get'.$nm.'()'.PHP_EOL;
            $geterrsAndSetters .= $ch32.'{'.PHP_EOL;
                $geterrsAndSetters .= $ch32.$ch32.'return $this->'.$name.';'.PHP_EOL;   
            $geterrsAndSetters .= $ch32.'}'.PHP_EOL.PHP_EOL;
        }
        
        return $geterrsAndSetters;
    }

    private function getQueryPgsql($tableName)
    {
         $query =  "     select      
                                cols.table_catalog,
                                cols.table_schema,
                                cols.table_name,
                                cols.column_name,	
                                tbf.constraint_name, 
                                tbf.foreign_table_schema,
                                tbf.foreign_table_name,
                                tbf.constraint_type, 
                                tbf.foreign_column_name,
                                cols.ordinal_position,
                                cols.column_default,
                                cols.is_nullable,
                                cols.data_type,
                                cols.character_maximum_length,
                                cols.character_octet_length,
                                cols.numeric_precision,
                                cols.numeric_precision_radix,
                                cols.numeric_scale,
                                cols.datetime_precision,
                                cols.interval_type,
                                cols.interval_precision,
                                cols.character_set_catalog,
                                cols.character_set_schema,
                                cols.character_set_name,
                                cols.collation_catalog,
                                cols.collation_schema,
                                cols.collation_name,
                                cols.domain_catalog,
                                cols.domain_schema,
                                cols.udt_catalog,
                                cols.udt_schema,
                                cols.udt_name,
                                cols.scope_catalog,
                                cols.scope_schema,
                                cols.scope_name,
                                cols.maximum_cardinality,
                                cols.dtd_identifier,
                                cols.is_self_referencing,
                                cols.is_identity,
                                cols.identity_generation,
                                cols.identity_start,
                                cols.identity_increment, 
                                cols.identity_maximum, 
                                cols.identity_minimum, 
                                cols.identity_cycle, 
                                cols.is_generated,
                                cols.generation_expression,
                                cols.is_updatable
                        FROM 
                                information_schema.columns cols
                        LEFT join
                            (SELECT
                                tc.table_schema, 
                                tc.constraint_name, 
                                tc.table_name, 
                                ccu.table_schema AS foreign_table_schema,
                                ccu.table_name AS foreign_table_name,
                                ccu.column_name AS foreign_column_name,
                                tc.constraint_type
                        FROM 
                                information_schema.table_constraints AS tc 
                                INNER JOIN 
                                information_schema.constraint_column_usage AS ccu
                                ON ccu.constraint_name = tc.constraint_name
                                AND ccu.table_schema = tc.table_schema
                                and (tc.constraint_type = 'FOREIGN KEY' or tc.constraint_type = 'PRIMARY KEY')
                        WHERE 
                                tc.table_name = '".$tableName."') tbf
                        on 
                        tbf.table_name = cols.table_name 
                        and 
                        tbf.foreign_column_name = cols.column_name 
                        where 
                        cols.table_name = '".$tableName."'";

        return $query;
    }

}

$hdb = new hdb();
$hdb->parseCommand();
//print_r($hdb->argv);

