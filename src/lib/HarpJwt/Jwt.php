<?php 
namespace Harp\lib\HarpJwt;

class Jwt
{
    private $Header;
    private $Payload;
    private $Signature;
    private $bs64Jwt;

    public function __construct(HeaderJwt $Header,PayloadJwt $Payload,SignatureJwt $Signature)
    {
        $this->Header = $Header;
        $this->Payload = $Payload; 
        $this->Signature = $Signature;
        $this->bs64Jwt = '';   
    }

    public static function unsafeB64($strBase64)
    {
        $strBase64 = trim(str_ireplace(['-','_'],['+','/'],$strBase64));
        return $strBase64;
    }

    public static function safeB64($strBase64)
    {
        $strBase64 = trim(str_ireplace(['+','/','='],['-','_',''],$strBase64));
        return $strBase64;
    }

    public function getJwt()
    {
        $this->bs64Jwt = sprintf(
            '%s.%s.%s', 
            $this->Header->getJwtHeader(),
            $this->Payload->getJwtPayload(),
            $this->Signature->getJwtSignature()
        );

        return $this->bs64Jwt;
    }
}