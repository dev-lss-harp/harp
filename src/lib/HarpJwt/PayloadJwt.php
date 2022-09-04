<?php 
namespace Harp\lib\HarpJwt;

use Harp\bin\ArgumentException;

class PayloadJwt
{
    private $payload;
    private $bs64Payload;

    private $listAttributes = 
    [
        'iss' => ['type' => 'string'],
        'sub' => ['type' => 'string'],
        'aud' => ['type' => 'array'],
        'exp' => ['type' => 'int'],
        'nbf' => ['type' => 'int'],
        'iat' => ['type' => 'int'],
        'jti' => ['type' => 'string'],
    ];

    public function __construct()
    {
        $this->bs64Payload = '';
        $this->payload = [];
    }

    public function addAll(Array $payload)
    {
        foreach($payload as $key => $val)
        {
            $this->add($key,$val);
        }
    }    

    public function add($key,$val)
    {
        if(isset($this->listAttributes[$key]))
        {
            $attr = $this->listAttributes[$key];

            if(($attr['type'] == 'string' && !is_string($val)) 
            || ($attr['type'] == 'int' && !(is_numeric($val) && (int)$val == $val)))
            {
                throw new ArgumentException
                (
                    'invalid type for {'.$key.'}, '.$key.' is of type {'.$attr['type'].'}!',
                    ArgumentException::WARNING_TYPE_EXCEPTION,
                    ArgumentException::WARNING_TYPE_EXCEPTION,
                    400
                );
            }
        }

        if(!empty($key) && !empty($val))
        {
            $this->payload[$key] = $val;
        }
    }

    private function createPayload()
    {
        $jse = json_encode($this->payload);
        $this->bs64Payload = Jwt::safeB64(base64_encode($jse));
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function getJwtPayload()
    {
        $this->createPayload();
        return $this->bs64Payload;
    }
}