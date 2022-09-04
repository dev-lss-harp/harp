<?php 
namespace Harp\lib\HarpJwt;

class HeaderJwt
{
    private const DEFAULT_ALG = 'HS256';
    private const DEFAULT_TYP = 'JWT';

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

    private $alg;
    private $typ;
    private $header;
    private $bs64Header;

    public function __construct($alg = 'HS256',$typ = 'JWT')
    {
        $this->alg = isset(self::$listAlgs[$alg]) ? $alg :  (isset(self::$listSigns[$alg]) ? $alg : self::DEFAULT_ALG);         
        $this->typ = in_array($alg,self::$listTyps) ? $typ : self::DEFAULT_TYP; 
        $this->createHeader();
    }

    private function createHeader()
    {
        $this->header = [
            'alg' => $this->alg,
            'typ' => $this->typ
        ];

        $jse = json_encode($this->header);
        $this->bs64Header = Jwt::safeB64(base64_encode($jse));
    }

    public function getHeader()
    {
        return $this->header;
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