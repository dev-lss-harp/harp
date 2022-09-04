<?php
namespace Harp\lib\HarpCryptography;

class HarpHash 
{
    private $HashLength;
    private $Hash;
        
    public function __construct($HashLength = null) 
    {
        if(isset($this->HashAlgos[$HashLength]))
        {
            $this->HashLength = $HashLength;
        }
        
        $this->Hash = null;
    }

    private function getHashAlg($length,$data,$binary = false,$hashAlgo = null)
    {
     
        $arrPerLength = [];

        foreach(hash_algos() as $v) 
        { 
            $r = hash($v,$data,false); 

            $sLength = mb_strlen($r); 

            if($sLength == $length)
            {
                $arrPerLength[$v] = $r;
            }
        } 
        
        $strhash = '';

        if(!empty($arrPerLength))
        {
            $k = key($arrPerLength);
            $strhash = $arrPerLength[$k];
        }
    
        if(!empty($hashAlgo) && isset($arrPerLength[$hashAlgo]))
        {
            $strhash = $arrPerLength[$hashAlgo];
        }

        return $strhash;
    }



    public function createHash($data,$binary = false,$hashAlgo = null)
    {
        if(is_int($this->HashLength))
        {
            $this->Hash = $this->getHashAlg($this->HashLength,$data,$binary,$hashAlgo);
                
            return true;             
        }
        
        return false;
    }
        
    public function setHashLength($HashLength)
    {
            $this->HashLength = $HashLength;
    }
    
    public function getHashLength($HashName = null)
    {
        $l = $this->HashLength;

        if(!empty($HashName))
        {
            $r = hash($HashName,uniqid(),false); 
            $l = mb_strlen($r);
        }

        return $l;
    }
    
    public function getNameHashByLength($length)
    {
        if(isset($this->HashAlgos[$length]))
        {
            $key = key($this->HashAlgos[$length]);
            
            if(isset($this->HashAlgos[$length][$key]['name']))
            {
                return $this->HashAlgos[$length][$key]['name'];
            }            
        }

        return false;
    }
    
    public function getHash()
    {
        return $this->Hash;
    }
}
