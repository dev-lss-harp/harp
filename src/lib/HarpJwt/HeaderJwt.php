<?php 
namespace Harp\lib\HarpJwt;

class HeaderJwt
{
    public static $listAlgs = 
    [
        'HS256' => ['HS256' => 'sha256'],
        'HS384' => ['HS384' => 'sha384'],
        'HS512' => ['HS512' => 'sha512'],
    ];

    public static $listSigns = 
    [
        'RS256' => ['RS256' => 'sha256WithRSAEncryption'],
        'RS384' => ['RS384' => 'sha384WithRSAEncryption'],
        'RS512' => ['RS512' => 'sha512WithRSAEncryption']
    ];

    public static $listTyps = 
    [
        'JWT',
    ];

    private $header;
    private $bs64Header;

    public function __construct($header)
    {
        $this->header = $header;
        $this->createHeader();
    }

    private function createHeader()
    {
        $jse = json_encode($this->header);
        $this->bs64Header = Jwt::safeB64(base64_encode($jse));
    }

    public function getHeader($key)
    {
        return  (!empty($key) && array_key_exists($key,$this->header)) ? $this->header[$key] : null;
    }

    public function getJwtHeader()
    {
        return $this->bs64Header;
    }

    public function getAlgs()
    {
        return self::$listAlgs;
    }

    public function getSigns()
    {
        return self::$listSigns;
    }

    public function getAlg()
    {
        return $this->alg;
    }

    public function getTyp()
    {
        return $this->typ;
    }    
}