<?php 
namespace Harp\lib\HarpJwt;

use DateTime;
use JsonSerializable;
use Throwable;
use Exception;

class ParseJwt implements JsonSerializable
{
    private $token;
    private $header = [];
    private $payload = [];
    private $bs64Header;
    private $bs64Payload;
    private $bs64Signature;
    public $HeaderJwt;
    public $PayloadJwt;

    public function __construct($jwt,$passwordOrPublicKey = null)
    {
        $this->token = $jwt;

        if($jwt instanceof Jwt)
        {
            $this->token = $jwt->getJwt();
        }
 
        $this->parse($passwordOrPublicKey);
    }

    private function jwtParts()
    {
        $p  = explode('.',$this->token);
     
        if(count($p) != 3)
        {
            throw new Exception
            (
                'Malformed string invalid jwt!',
                400
            );
        }

        $p[0] = trim(str_ireplace(['Bearer'],[''],$p[0]));
      
        $this->bs64Header = Jwt::unsafeB64($p[0]);
      
        $this->bs64Payload = Jwt::unsafeB64($p[1]);

        $this->bs64Signature = Jwt::unsafeB64($p[2]);
    }

    

    private function verifySignatureSecret($secret,$alg)
    {
        $signature = base64_encode(hash_hmac($alg,$this->bs64Header.'.'.$this->bs64Payload,$secret,true));
        if($this->bs64Signature != $signature)
        {
            throw new Exception
            (
                'Invalid signature jwt!',
                401
            );  
        }

        return true;
    }

    private function verifySignatureRSA($publicKey,$alg)
    {
        $verify = @openssl_verify($this->bs64Header.'.'.$this->bs64Payload,base64_decode($this->bs64Signature),$publicKey,$alg);

        if(!$verify || $verify == -1)
        {
            $error = 'Invalid signature jwt!'.PHP_EOL;
            while($msg = openssl_error_string()){ $error .= $msg.';'; }
            $Except = new Exception
            (
                $error,
                401
            );  

            throw $Except;
        }

        return $verify;
    }

    public function verifySignature($secretOrPublicKey)
    {
        
        $alg = isset(HeaderJwt::$listAlgs[$this->header['alg']]) ? 
        HeaderJwt::$listAlgs[$this->header['alg']][$this->header['alg']] :
        HeaderJwt::$listSigns[$this->header['alg']][$this->header['alg']]; 

        if(isset(HeaderJwt::$listAlgs[$this->header['alg']]))
        {
            $this->verifySignatureSecret($secretOrPublicKey,$alg);
        }
        else 
        {          
            $this->verifySignatureRSA($secretOrPublicKey,$alg);
        }
    }

    private function parse($secretOrPublicKey)
    {
        try
        {
            $this->jwtParts();

            $this->header = @json_decode(@base64_decode($this->bs64Header),true);
            $this->payload = @json_decode(@base64_decode($this->bs64Payload),true);
   
            if(!is_array($this->header) || !is_array($this->payload))
            {
                throw new Exception
                (
                    'Malformed string invalid jwt!',
                    400
                );
            }
    
            if(!isset(HeaderJwt::$listAlgs[$this->header['alg']]) && !isset(HeaderJWt::$listSigns[$this->header['alg']]))
            {
                throw new Exception
                (
                    'Invalid alg: {'.$this->header['alg'].'}!',
                    400
                );            
            }
            else if(!in_array($this->header['typ'],HeaderJwt::$listTyps))
            {
                throw new Exception
                (
                    'Invalid token type: {'.$this->header['typ'].'}!',
                    400
                );              
            }
    
            if(!empty($secretOrPublicKey))
            {
                $this->verifySignature($secretOrPublicKey);
            }
            
            $this->HeaderJwt = new HeaderJWt($this->header);
            $this->PayloadJwt = new PayloadJwt();
            $this->PayloadJwt->addAll($this->payload);
        }
        catch(Throwable $th)
        {
            throw $th;
        }
       
    }

    public function isExpired() : bool
    {
        $dataExpiracao = new DateTime();
        $dataExpiracao->setTimestamp($this->payload['exp']);
        $dataAtual = new DateTime();

        return $dataExpiracao < $dataAtual;
    }

    public function jsonSerialize() : Array
    {
        return [
            'header' => $this->header,
            'payload' => $this->payload,
            'expired' => $this->isExpired()
        ];
    }
}
