<?php
namespace Harp\bin;

use Exception;
use Harp\bin\ArgumentException;
use Symfony\Component\Dotenv\Dotenv;

abstract class HarpApplication implements HarpApplicationInterface
{    

    //personal configs for your application
    public abstract function config();

    private $appName;
    private $Application;
    private $pathCertificate;
    private $pathEncryptionKeys;
    protected $operatorModeCryptography = 'AES-256-CBC';
    protected $registeredApps = [];
    protected $dotenv;

    private $properties = [
        'defaultApp'=> false,
        'config' => null,
        'publicKey' => null
    ];
    
    protected function __construct(HarpApplicationInterface &$Application,Array $registeredApps,$pathCertificate = null,$pathEncryptionKey = null)
    {
        $this->dotenv = new Dotenv();
        $this->dotenv->load(dirname(dirname(__DIR__)).'/.env-develop',dirname(dirname(__DIR__)).'/.env');


        $this->Application = $Application;
        
        $this->registeredApps = $registeredApps;

        $this->extractAppName();
        

        $defaultPathCerts = dirname(__DIR__).DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.mb_strtolower($this->appName).DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'certs';
        $defaultPathKeys = dirname(__DIR__).DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.mb_strtolower($this->appName).DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'keys';

        $this->pathCertificate = 
        (empty($pathCertificate) || !is_dir($pathCertificate))
        ? 
        $defaultPathCerts
        :
        $pathCertificate;

        $this->pathEncryptionKeys = 
        (empty($pathEncryptionKey) || !is_dir($pathEncryptionKey))
        ? 
        $defaultPathKeys
        :
        $pathEncryptionKey;

      
        $this->defineEncryptionKey();
        $this->definePrivateKey();
        $this->storageProperties();
    }   

    private function storageProperties()
    {
        $this->Application->setProperty('publicKey',$this->getPublicKey());
        $this->Application->setProperty('privateKey',$this->getPrivateKey());
        $this->Application->setProperty('hashApplication',$this->getHashApplication());
        $this->Application->setProperty('encryptionKey',$this->getEncryptionKey());
    }
    
    public function getDir() : string
    {
        $dir = mb_strtolower($this->appName);

        $dir = is_dir(PATH_APP.'/'.$dir) ? $dir : $this->appName;
   
        return $dir;
    }

    public function setProperty($key,$property)
    {
        if(!empty($key) && !isset($this->properties[$key]))
        {
            $this->properties[$key] = $property;
        }

        return $this;
    }
    
    public function getProperty($name)
    {
        return isset($this->properties[$name]) ? $this->properties[$name] : null;
    }
    
    public function getProperties()
    {
        return $this->properties;
    }
    
    public function isDefault()
    {
        return $this->properties['defaultApp'];
    }

    private function extractAppName()
    {
        $nameSpaceProject = $this->getIdentifier();
        $exp = explode('\\',$nameSpaceProject);
        $this->appName = end($exp);
    }
    
    public function getName()
    {
        return $this->appName;
    }

    private function __clone(){}
    
    public function getPublicKey()
    {
        return $this->properties['publicKey'];
    }
    
    public function getPrivateKey()
    {
        return $this->properties['privateKey'];
    }    

    public function getHashApplication()
    {
        return sha1($this->getIdentifier());
    }

    protected function getEncryptionKey()
    {
        return $this->properties['encryptionKey'];
    }

    private function throwOpenSSLError()
    {
        $error = '';
     
        while ($msg = openssl_error_string())
        {
             $error .= $msg; 
        }

        throw new ArgumentException($error);
    }

    function folder_exist($folder)
{
    // Get canonicalized absolute pathname
    $path = realpath($folder);

    // If it exist, check if it's a directory
    return ($path !== false && is_dir($path)) ? $path : false;
}
    private function defineEncryptionKey()
    {
        if(!is_dir($this->pathEncryptionKeys))
        {
             throw new Exception(sprintf('directory keys not found in {%s}, please check documentation to generate directory and keys!',$this->pathEncryptionKeys));
        }
        else if(!file_exists($this->pathEncryptionKeys.DIRECTORY_SEPARATOR.'encryption.key'))
        {
             throw new Exception(sprintf('encryption.key not found in {%s}, please check documentation to generate keys!',$this->pathEncryptionKeys));
        }

        $this->properties['encryptionKey'] = file_get_contents($this->pathEncryptionKeys.DIRECTORY_SEPARATOR.'encryption.key');
    }
 
    private function definePublicKey($privateKeyResource)
    {
        $this->properties['publicKey'] = '';
        
        $arrPublicKey = openssl_pkey_get_details($privateKeyResource);
     
        if(empty($arrPublicKey['key']))
        {
            $this->throwOpenSSLError();
        }
       
        $this->properties['publicKey'] = $arrPublicKey['key'];

        $publicKeyResource = openssl_pkey_get_public($this->properties['publicKey']);
   
        if
        (
            !($publicKeyResource instanceof \OpenSSLAsymmetricKey) 
            && 
            !is_resource($publicKeyResource)
        )
        {
          
            $this->throwOpenSSLError();
        } 

    }
    
    private function definePrivateKey()
    {
       if(!is_dir($this->pathCertificate))
       {
            throw new Exception(sprintf('directory certificates not found in {%s}, please check documentation to generate directory and certificates!',$this->pathCertificate));
       }
       else if(!file_exists($this->pathCertificate.DIRECTORY_SEPARATOR.'private.key'))
       {
            throw new Exception(sprintf('private.key not found in {%s}, please check documentation to generate certificate!',$this->pathCertificate));
       }

       $this->defineEncryptionKey();

       $strPrivateKey =  file_get_contents($this->pathCertificate.DIRECTORY_SEPARATOR.'private.key');


       $privateKeyResource = openssl_pkey_get_private($strPrivateKey,$this->getEncryptionKey());

       if
       (
           !($privateKeyResource instanceof \OpenSSLAsymmetricKey) 
           && 
           !is_resource($privateKeyResource)
       )
       {
            $this->throwOpenSSLError();
       }
       
       $this->properties['privateKey'] = $strPrivateKey;
       $this->definePublicKey($privateKeyResource);    
    }    
    
    public function getIdentifier() { return get_class($this->Application); }
    
    public function getApplication()
    {
        return $this->Application;
    }

    public function getAppNamespace()
    {
        $nmsp = 'Harp\\app\\'.mb_strtolower($this->getName());
        
        return $nmsp;
    }

    public function setOperatorModeCryptography($mode)
    {
        if(in_array(mb_strtoupper($mode),openssl_get_cipher_methods()))
        {
            $this->operatorModeCryptography = mb_strtoupper($mode);
        }
    }

    public function getOperatorModeCryptography()
    {
        return $this->operatorModeCryptography;
    }    
      
    private function renderView($obj,$ServerRequest)
    {
        try
        {
            if($obj instanceof \Harp\bin\HarpView)
            {
                $RefView = new \ReflectionClass($obj);
                $RefMethod = $RefView->getMethod('renderView');
                $RefMethod->setAccessible(true);
                $RefMethod->invoke($obj,$this->Application,$ServerRequest->getServerConfig());
                $RefMethod->setAccessible(false);                
            }
            else if($obj instanceof Exception)
            {
                throw $obj;
            }
            else
            {
                \Harp\bin\View::DefaultAction($obj);
            }
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
    }
}