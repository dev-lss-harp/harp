<?php
namespace Harp\lib\HarpCryptography;

use Harp\lib\HarpCryptography\CryptographyInterface;
use Harp\lib\HarpCryptography\HarpHash;

class HarpCryptography implements CryptographyInterface
{
    private $ParameterIV;
    private $Length;
    private $Cipher;
    private $HarpHash;
    private $KeyIV;
    private $hashAlgo;
    public $cryptNumber = false;
    
    //Suported Ciphers In Version
    private $Ciphers;
    
    public function __construct(
        $Cipher = CryptographyInterface::DEFAULT_CIPHER,
        $keyIV = CryptographyInterface::DEFAULT_KEY_PARAMETER_IV,
        $pIV = '',
        $hashAlgo = CryptographyInterface::DEFAULT_ALGO)
    {
        $f = openssl_get_cipher_methods();

        foreach($f as $i => $v)
        {
            $const = mb_strtoupper(str_ireplace('-','_',$v));
            
            if(!defined($const))
            {
                $v = mb_strtolower($v);
                $this->Ciphers[$v] = $v;
                define($const,$v);
            } 
        }
      
        $Cipher = mb_strtolower($Cipher);

        $this->Cipher = (!empty($Cipher) && isset($this->Ciphers[$Cipher])) ? $this->Ciphers[$Cipher] :  AES_256_CFB; 

        $this->Length = openssl_cipher_iv_length($this->Cipher);
        $this->HarpHash = new HarpHash();
        $this->HarpHash->setHashLength($this->Length);

        $this->KeyIV = !empty($keyIV) ? $this->lengthNormalize($keyIV) : $this->lengthNormalize(CryptographyInterface::DEFAULT_KEY_PARAMETER_IV);

        $this->hashAlgo = $hashAlgo;
        
        $this->ParameterIV = $this->lengthNormalize($pIV);

        if(empty($pIV))
        {
            $this->createParameterIV();
        }
    
    }
        
    public function createParameterIV($keyVI = null,$binary = false,$hashAlgo = null)
    {
        $this->Length = openssl_cipher_iv_length($this->Cipher);
   
        $this->HarpHash->setHashLength($this->Length);
        $hashAlgo = !empty($hashAlgo) ? $hashAlgo : $this->hashAlgo;
        $keyVI = !empty($keyIV) ? $keyIV : $this->KeyIV;
       
        $this->HarpHash->createHash($keyVI,$binary,$hashAlgo); 

        $this->ParameterIV  = $this->HarpHash->getHash(); 
 
        return $this->ParameterIV;
    }
    
    public function setCipher($Cipher)
    {
        $this->Cipher = (!empty($Cipher) && isset($this->Ciphers[$Cipher])) ? $this->Ciphers[$Cipher] : AES_256_CFB;
        
        return $this;
    }
    
    public function setKeyParameterIV($keyIV)
    {
        $this->KeyIV = $keyIV;
        
        $this->createParameterIV();
        
        return $this;        
    }   
    
    public function setParameterIV($keyIV)
    {
        $this->KeyIV = $keyIV;
        
        $this->createParameterIV();
        
        return $this;        
    }       
    
    public function setParameterKey($key)
    {
        if(!empty($key))
        {
            $this->defaultKey = $key;
        }
        
        return $this;        
    }      
    
    public function setDefaultEncryptionKey($key)
    {
        if(!empty($key))
        {
            $this->defaultKey = $key;
        }
    }
    
    public function getDefaultEncryptionKey()
    {
        return $this->defaultKey;
    }

    private function base64Encode($str)
    {
         $e = base64_encode($str);

         $e = str_ireplace(array('/','+','='),array('!','_','-'), $e);
   
         return $e;
    }
    
    private function lengthNormalize($key)
    {
        $KeyLength = mb_strlen($key);
 
        if($KeyLength != $this->Length)
        {
            if(($this->Length - $KeyLength) < 0)
            {
                $key = substr($key,0,$this->Length);
            }
            else
            {
                $partKeyLength = ($this->Length - $KeyLength);

                $partKey = str_repeat("\0",$partKeyLength);

                $key .= $partKey;
            }
        }
    
        return $key;
    }
    
    public function encrypt($str,$keyIV = null,$parameterIV = null)
    {
        
        //Alteraçao Feita Em 2015-02-03 Porque nas novas distribuições
        //se a chave for menor que a chave do algoritmo deve-se então completa-la
        //até o tamanho da chave do algoritmo
       mb_internal_encoding("UTF-8");
       
       $key = !empty($keyIV) ? $this->lengthNormalize($keyIV) : $this->lengthNormalize($this->KeyIV);
    
       $parameterIV = !empty($parameterIV) ? $this->createParameterIV($parameterIV) : $this->ParameterIV; 

       if(mb_strlen($key) == $this->Length)
       {
            $e = openssl_encrypt($str,$this->Cipher,$key,OPENSSL_RAW_DATA,$parameterIV);

            $e = $this->base64Encode($e);

            if($this->cryptNumber){ $e = $this->encryptForNumbers($e); }

            return $e;        
        }

        return false;
    }

    private function base64Decode($str)
    {
        $d = str_replace(array('!','_','-'),array('/','+','='),$str);

        $d = base64_decode($d);

        return $d;
    }
    
    public function decrypt($str,$keyIV = null,$parameterIV = null)
    {
        mb_internal_encoding("UTF-8");

        $key = !empty($keyIV) ? $this->lengthNormalize($keyIV) : $this->lengthNormalize($this->KeyIV);
        
        $parameterIV = !empty($parameterIV) ? $this->createParameterIV($parameterIV) : $this->ParameterIV; 
    
        if(mb_strlen($key) <= $this->Length)
        {             
            $d = $str;
  
            if($this->cryptNumber){ $d = $this->decryptingNumbers($d);}

            $d = $this->base64Decode($d);

            if($d === false){ return false; }//lançar exceção depois

            $d = openssl_decrypt($d,$this->Cipher,$key,OPENSSL_RAW_DATA,$parameterIV);

            if(!$d){ return false; }//lançar exceção depois

            return $d;
        }
        
        return false;
    }
    
    public function encryptForNumbers($str)
    {
        $numbers = 0;

        $numbers = implode(array_map(function($n) { return str_pad($n,3,'0',STR_PAD_LEFT);},unpack('C*',$str)));

        return $numbers;      
    }
    
    public function decryptingNumbers($strNumbers)
    {
        $str = null;

        if(!empty($strNumbers))
        {
             $str = join(array_map('chr',str_split($strNumbers,3)));
        }    
       
        return $str;
    }   
}
