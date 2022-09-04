<?php 
namespace Harp\lib\HarpJson;

use Harp\bin\ArgumentException;

class Json
{
    public const JSON_DECODE = 1;
    public const JSON_ENCODE = 0;
    public const JSON_IS_DECODABLE = 2;
    public const JSON_IS_ENCODABLE = 3;
    public const IS_JSON = 200;
    private const UNKNOWN_ERROR = 100;
    private $data;
    private $type;

    private $responseStatus = 
    [
        JSON_ERROR_NONE => 'No errors',
        JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
        JSON_ERROR_STATE_MISMATCH => 'Underflow or the modes mismatch',
        JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
        JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
        JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded',
        self::UNKNOWN_ERROR => 'Unknown error'
    ];

    public function __construct($data,$type = self::JSON_ENCODE)
    {
        $this->setType($type);

        $this->data = $data;

        return $this;
    }

    private function setType($type)
    {
        $ref = new \ReflectionClass($this);
        $consts = $ref->getConstants();

        if(in_array($type,$consts))
        {
            $this->type = $type;
        }
    }

    public function exec($data,$type = self::JSON_ENCODE)
    {
        $this->setType($type);
        $this->data = $data;
        return $this->getResponse();
    }

    private function isDecodable()
    {
         return !empty($this->jsonDecode($this->data));
    }

    private function isEncodable()
    {
         return !empty($this->jsonEncode($this->data));
    }

    public function getResponse()
    {
        $response = null;

        try
        {
            switch ($this->type) {
                case self::JSON_DECODE:
                    $response = $this->jsonDecode($this->data);
                    break;
                case self::JSON_IS_DECODABLE:
                    $response = $this->isDecodable($this->data);
                    break;     
                case self::JSON_IS_ENCODABLE:
                    $response = $this->isEncodable($this->data);
                    break;    
                case self::IS_JSON:
                    $response = $this->isJson($this->data);
                    break;                                               
                default:
                    $response = $this->jsonEncode($this->data);
                 break;
            }
        }
        catch(\Throwable $th)
        {
            throw $th;
        }

        return $response;
    }

    private function parseError()
    {
        $Sae = null;

        try
        {
            if
            (
                isset($this->responseStatus[json_last_error()]) 
                && json_last_error() != JSON_ERROR_NONE
            )
            {
                
                $Sae = new ArgumentException(
                    $this->responseStatus[json_last_error()], 
                    500, 
                );
            }
            else if(!isset($this->responseStatus[json_last_error()]))
            {
                $Sae = new ArgumentException(
                    $this->responseStatus[self::UNKNOWN_ERROR], 
                    500,  
                );  
            }

           if($Sae instanceof \Exception)
           {
               throw $Sae;
           }
        }
        catch(\Throwable $th)
        {
            throw $th;
        }
    }

    private function isJson($data)
    {
        $s = false;

        try 
        {
            $regex  = '`[{\[]{1}([,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t]|".*?")+[}\]]{1}`';
            $s = preg_match($regex,$data);
        } 
        catch (\Throwable $th) 
        {
           $s = false;
        }

        return $s;
    }

    private function jsonEncode($data)
    {
        $response = null;

        try
        {
            $response = @\json_encode($data);

            $this->parseError();
        }
        catch(\Throwable $th)
        {
            throw $th;
        }

        return $response;
    }

    private function jsonDecode($data)
    {
        $response = null;

        try 
        {
            $response = @\json_decode($data,true);

            $this->parseError();
        } 
        catch (\Throwable $th) 
        {
            throw $th;
        }

        return $response;
    }    
}