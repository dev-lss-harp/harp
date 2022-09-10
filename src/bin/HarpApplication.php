<?php
namespace Harp\bin;

use Exception;
use Harp\bin\ArgumentException;
use Harp\enum\AppEnum;
use Symfony\Component\Dotenv\Dotenv;

abstract class HarpApplication implements HarpApplicationInterface
{    

    //personal configs for your application
    public abstract function config();

    private $appName;
    private $Application;
    private $pathCertificate;
    private $pathEncryptionKeys;
    protected $registeredApps = [];
    protected $dotenv;

    protected function __construct(HarpApplicationInterface &$Application,Array $registeredApps,$pathCertificate = null,$pathEncryptionKey = null)
    {
        $this->dotenv = new Dotenv();
        $dtEnvPath = dirname(dirname(dirname(dirname(dirname(__DIR__)))));
        $this->dotenv->loadEnv($dtEnvPath.DIRECTORY_SEPARATOR.AppEnum::ENV_DEVELOP->value);
        $this->dotenv->loadEnv($dtEnvPath.DIRECTORY_SEPARATOR.AppEnum::ENV->value);
 
        if(file_exists($dtEnvPath.DIRECTORY_SEPARATOR.AppEnum::ENV_MAINTAINER->value))
        {
            $this->dotenv->loadEnv($dtEnvPath.DIRECTORY_SEPARATOR.AppEnum::ENV_MAINTAINER->value);
        }

        $this->Application = $Application;
        
        $this->registeredApps = $registeredApps;

        $this->extractAppName();
        

        $defaultPathCerts = 
                        $dtEnvPath.DIRECTORY_SEPARATOR.
                        AppEnum::APP_DIR->value.DIRECTORY_SEPARATOR.
                        mb_strtolower($this->appName).DIRECTORY_SEPARATOR.
                        AppEnum::StorageDir->value.DIRECTORY_SEPARATOR.
                        AppEnum::StorageCertsDir->value;

        $defaultPathKeys = 
                        $dtEnvPath.DIRECTORY_SEPARATOR.
                        AppEnum::APP_DIR->value.DIRECTORY_SEPARATOR.
                        mb_strtolower($this->appName).DIRECTORY_SEPARATOR.
                        AppEnum::StorageDir->value.DIRECTORY_SEPARATOR.
                        AppEnum::StorageKeysDir->value;

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
        $this->Application->setProperty(AppEnum::PublicKey->value,$this->getPublicKey());
        $this->Application->setProperty(AppEnum::PrivateKey->value,$this->getPrivateKey());
        $this->Application->setProperty(AppEnum::HashApp->value,$this->getHashApplication());
        $this->Application->setProperty(AppEnum::EncryptionKey->value,$this->getEncryptionKey());
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
        return $this->properties[AppEnum::PublicKey->value];
    }
    
    public function getPrivateKey()
    {
        return $this->properties[AppEnum::PrivateKey->value];
    }    

    public function getHashApplication()
    {
        return sha1($this->getIdentifier());
    }

    protected function getEncryptionKey()
    {
        return $this->properties[AppEnum::EncryptionKey->value];
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

        $this->properties[AppEnum::EncryptionKey->value] = file_get_contents($this->pathEncryptionKeys.DIRECTORY_SEPARATOR.'encryption.key');
    }
 
    private function definePublicKey($privateKeyResource)
    {
        $this->properties[AppEnum::PublicKey->value] = '';
        
        $arrPublicKey = openssl_pkey_get_details($privateKeyResource);
     
        if(empty($arrPublicKey['key']))
        {
            $this->throwOpenSSLError();
        }
       
        $this->properties[AppEnum::PublicKey->value] = $arrPublicKey['key'];

        $publicKeyResource = openssl_pkey_get_public($this->properties[AppEnum::PublicKey->value]);
   
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
       
       $this->properties[AppEnum::PrivateKey->value] = $strPrivateKey;
       $this->definePublicKey($privateKeyResource);    
    }    
    
    public function getIdentifier() { return get_class($this->Application); }
    
    public function getApplication()
    {
        return $this->Application;
    }

    public function getAppNamespace()
    {
        $nmsp = sprintf('%s\\%s',AppEnum::APP_NAMESPACE->value,mb_strtolower($this->getName()));
      
        return $nmsp;
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