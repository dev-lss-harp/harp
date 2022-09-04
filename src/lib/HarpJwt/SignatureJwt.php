<?php 
namespace Harp\lib\HarpJwt;

use Harp\bin\ArgumentException;
use Harp\bin\ArgumentException;

class SignatureJwt
{
    private $Header;
    private $Payload;
    private $bs64Signature;

    public function __construct(HeaderJwt $Header,PayloadJwt $Payload)
    {
        $this->Header = $Header;
        $this->Payload = $Payload; 
        $this->signature = '';   
    
    }

    public function sign($passwordOrprivateKey = '')
    {
        $algs = $this->Header->getAlgs();
        $signs = $this->Header->getSigns();
        if(isset($algs[$this->Header->getAlg()]))
        {
            $alg = $algs[$this->Header->getAlg()][$this->Header->getAlg()];
            $signature = hash_hmac($alg,$this->Header->getJwtHeader().'.'.$this->Payload->getJwtPayload(),$passwordOrprivateKey,true);
            $this->bs64Signature = Jwt::safeB64(base64_encode($signature));
        }
        else if(isset($signs[$this->Header->getAlg()]) && !empty($passwordOrprivateKey))
        {
            $signature = '';
            $alg = $signs[$this->Header->getAlg()][$this->Header->getAlg()];
            openssl_sign($this->Header->getJwtHeader().'.'.$this->Payload->getJwtPayload(), $signature,$passwordOrprivateKey,$alg);
            $this->bs64Signature = Jwt::safeB64(base64_encode($signature));
        }
        else
        {
            throw new ArgumentException
            (
                'invalid alg or private key is empty!',
                ArgumentException::WARNING_TYPE_EXCEPTION,
                ArgumentException::WARNING_TYPE_EXCEPTION,
                400
            );
        }
    }

    public function getJwtSignature()
    {
        return $this->bs64Signature;
    }
}