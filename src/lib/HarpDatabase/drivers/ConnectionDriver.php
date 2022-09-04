<?php
namespace Harp\lib\HarpDatabase\drivers;



use Harp\lib\HarpDatabase\drivers\DriverInterface;
use Harp\lib\HarpDatabase\cmd\CommandText;
use Harp\lib\HarpDatabase\cmd\CommandParameter;
use Harp\lib\HarpDatabase\HarpConnection;
use Harp\lib\HarpDatabase\cmd\CommandJson;
use Harp\lib\HarpCryptography\HarpCryptography;
//use Harp\bin\ArgumentException;
use Exception;
use Harp\bin\ArgumentException;
use Harp\lib\HarpDatabase\orm\ORM;
use Harp\lib\HarpDatabase\orm\ORMConfig;

abstract class ConnectionDriver implements DriverInterface
{
    
    const PREFIX_CLASS_CMD = 'Command';
    const DIRECTORY_CMD = 'cmd';
    const CMD_BASE_NAMESPACE = 'Harp\lib\HarpDatabase\cmd';
    const CMD_INSERT = 'INSERT';    

  
    protected $encryptionKey;
    protected $encryptionFields;
    /****
     * A depender da seleção do app algum arquivo de configuração da database será instanciado
     */
    protected $CommandXml;
    protected $XmlORM;  
    protected $CommandJson;
    protected $ConfigORM;  
    protected $driversNoSql = [
        'redis',
    ];
    
    protected $InfoConnection;
    public $CommandText;
    public $CommandParameter;
    public $DatabaseHandleException;
    public $CommandHandlerSQL;
    public $HarpCryptography;
    

    public abstract function getSgbdName();
    public abstract function defineInsertOID($returnColumn = null);
    

    protected function __construct(HarpConnection &$InfoConnection)
    {
      
       $this->InfoConnection = &$InfoConnection;

       if(!in_array($this->getDriverName(),$this->driversNoSql))
       {
           
            $this->CommandText = new CommandText(); 
            $this->CommandParameter = new CommandParameter($this->CommandText);
            $this->CommandHandlerSQL = $this->getCommandSql();            
            $this->cleanColumnsToEncrypt();
       }
    }
    
    public function getORM()
    {
        $this->CommandJson = new CommandJson($this->getSgbdName()); 
        $this->ConfigORM = (!$this->ConfigORM instanceof ORM) ? new ORMConfig($this) : $this->ConfigORM;  
        
        
        return $this->ConfigORM;
    }
    
    public function cleanColumnsToEncrypt()
    {
        $this->encryptionFields = [];
    }
    
    public function clearColumnsToEncrypt()
    {
        $this->encryptionFields = Array();
    }  
    
    protected function instanceCryptography()
    {
        if(!$this->HarpCryptography instanceof HarpCryptography)
        {
            $this->HarpCryptography = new HarpCryptography();
        }
    }    
    
    private function getCommandSql()
    {
        $Object = null;
        
        try
        {
            $driver = ucfirst($this->InfoConnection->getDriverName());

            $className = self::PREFIX_CLASS_CMD.$driver;
            
            $path = dirname(__DIR__).DIRECTORY_SEPARATOR.self::DIRECTORY_CMD;

            $fileName = $className.'.php';
                        
            if(!file_exists($path.DIRECTORY_SEPARATOR.$fileName))
            {
                throw new ArgumentException('{'.$className.'} not found in {'.$path.'}'); 
            }  
            
            $ClassNameWithNameSpace = self::CMD_BASE_NAMESPACE.'\\'.$className;
            
            $Object = new $ClassNameWithNameSpace($this->CommandParameter,$this->CommandText); 
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return $Object;
    }

    public function setColumnsToEncrypt(Array $columns)
    {
        if(!empty($columns))
        {
           $this->encryptionFields = array_combine($columns,$columns);
           
           return true;
        }
        
        return false;
    }
    
    public function getInfoConnection()
    {
        return $this->InfoConnection;
    }

    public function getDriverName()
    {
        return $this->InfoConnection->getDriverName();
    }
    
    public function getConnectionName()
    {
        return $this->InfoConnection->getConnectionID();
    }
     
    
    public function getMigrate()
    {
        return new \etc\HarpDatabase\reflect\HarpMigrate($this);
    }    
    
    public function getCharacterCaseSensitive()
    {
        return '';
    }
}
