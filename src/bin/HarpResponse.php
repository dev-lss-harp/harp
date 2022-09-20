<?php
namespace Harp\bin;

use Exception;
use GuzzleHttp\Psr7\Response;
use Harp\bin\ArgumentException;
use Harp\lib\HarpGuid\Guid;
use Harp\lib\HarpJson\Json;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use stdClass;
use Throwable;

class HarpResponse extends Response
{

    protected const HTTP_RESPONSE_STATUS = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
        599 => 'Internal Exceptions'
    ];

    //private const RESPONSE = 'RESPONSE';
    public const FAILURE = 'FAILURE';
    public const SEVERITY = 'SEVERITY';

    public const SEVERITY_ERROR = 2;
    public const SEVERITY_WARNING = 1;
    public const SEVERITY_WITHOUT = 0;
    public const FAILURE_POSITIVE = 1;
    public const FAILURE_WITHOUT = 0;
    public $debugException = false;
    
    public const JSON = 1;
    public const STREAM = 0;
    public const NONE = -1;

    public const HTTP_INFO = 0;
    public const HTTP_SUCCESS = 1;
    public const HTTP_REDIRECT = 2;
    public const HTTP_CLIENT_ERROR = 3;
    public const HTTP_SERVER_ERROR = 4;
    public const HTTP_INVALID = 5;


    private $collectionBody = [];
    private $collectionHeader = [];
    private $allResponses = [];
    private $httpVersion; 
    private $msgDebugDisabled = 'hidden by server configuration';
    private $version = '1.1';
    private $reason = 'No Content';
    private $code = 204;


    public function __construct(int $code = 204,Array $headers = [],Array $body = [],int $response = self::STREAM,string $version = '1.1')
    {       

        $this->collectionBody = $body;
        $this->collectionHeader = $headers;
        $this->code = !empty(self::HTTP_RESPONSE_STATUS[$code]) ? (int)$code : $this->code; 
    
        $this->reason = self::HTTP_RESPONSE_STATUS[$code] ?? $this->reason;

        $this->version = $version != '1.1' || $version != '1.0' ? $this->version : $version;

        $this->httpVersion = filter_input(INPUT_SERVER,'SERVER_PROTOCOL');

        if(!empty($body))
        {
            $this->createResponse($response);
        }
        
        return $this;
    }

    private function checkStatusCode(int $code,int $responseType) : bool
    {
        switch($responseType)
        {
            case self::HTTP_SUCCESS:
                return $code >= 200 && $code <= 299;
            break;
            case self::HTTP_INFO:
                return $code >= 100 && $code <= 102;
            break;
            case self::HTTP_REDIRECT:
                return $code >= 300 && $code <= 308;
            break;
            case self::HTTP_CLIENT_ERROR:
                return $code >= 400 && $code <= 499;
            break;
            case self::HTTP_SERVER_ERROR:
                return $code >= 500 && $code <= 599;
            break;
            case self::HTTP_INVALID:
                return $code < 100 || $code > 599;
            break;
            default:
                return false;
        }

    }

    private function putControlHeaders()
    {
        $status = intval($this->code >= 400);

        if(!$this->hasHeader(self::FAILURE))
        {
            $this->putHeader(self::FAILURE,$status);
        }
        
        if(!$this->hasHeader(self::SEVERITY))
        {
            $this->putHeader(self::SEVERITY,$status);
        }
    }

    public function withStatus($code, $reasonPhrase = ''): ResponseInterface
    {
        $this->code = $code;
        $this->reason = !empty($reasonPhrase) ? $reasonPhrase : self::HTTP_RESPONSE_STATUS[$this->code];
        return parent::withStatus($code, $reasonPhrase = '');
    }

    public function withHeader($header, $value): MessageInterface
    {
        $this->putHeader($header, $value);

        $this->putControlHeaders();

        return parent::withHeader($header,$value);
    }

    public function isSuccessCode(int $code = 0) : bool
    {
        return $this->checkStatusCode($code,self::HTTP_SUCCESS);
    }

    public function isInfoCode(int $code = 0) : bool
    {
        return $this->checkStatusCode($code,self::HTTP_INFO);
    }

    public function isRedirectCode(int $code = 0) : bool
    {
        return $this->checkStatusCode($code,self::HTTP_REDIRECT);
    }

    public function isClientErrorCode(int $code = 0) : bool
    {
        return $this->checkStatusCode($code,self::HTTP_CLIENT_ERROR);
    }

    public function isServerErrorCode(int $code = 0) : bool
    {
        return $this->checkStatusCode($code,self::HTTP_SERVER_ERROR);
    }

    public function isInvalidCode(int $code = 0) : bool
    {
        return $this->checkStatusCode($code,self::HTTP_INVALID);
    }   

    public function OK(Array $bodyCollection,$code = 200,$response = self::JSON)
    {
        $this->headerSuccess($code);
        foreach($bodyCollection as $key => $body)
        {
            $this->putBody($key,$body);
        }

        $this->createResponse($response);

        return $this;
    }

    private function headerByCode($code)
    {
        if($this->isSuccessCode($code))
        { 
            $this->headerSuccess($code);
        }
        else if($this->isRedirectCode($code))
        {
            $this->headerRedirection($code);
        }
        else if($this->isClientErrorCode($code))
        {
            $this->headerClientError($code);
        }
        else if($this->isServerErrorCode($code))
        {
            $this->headerServerError($code);
        }
        else if($this->isInfoCode($code))
        {
            $this->headerInfo($code);
        }
        else if($this->isInvalidCode($code))
        { 
            $this->headerServerError(599);
        }
    }

    public function ByStatus(Array $bodyCollection,$code = 200,$response = self::JSON)
    {
        
        $this->headerByCode($code);
    
        foreach($bodyCollection as $key => $body)
        {
            $this->putBody($key,$body);
        }
    
        $this->createResponse($response);

        return $this;
    }   

    public function ServerError(Array $bodyCollection,$code = 500,$response = self::JSON)
    {
        $this->headerServerError($code);
        foreach($bodyCollection as $key => $body)
        {
            $this->putBody($key,$body);
        }

        $this->createResponse($response);

        return $this;
    }


    public function ClientError(Array $bodyCollection,$code = 400,$response = self::JSON)
    {
        $this->headerClientError($code);
        foreach($bodyCollection as $key => $body)
        {
            $this->putBody($key,$body);
        }

        $this->createResponse($response);

        return $this;
    }


    public function Info(Array $bodyCollection,$code = 100,$response = self::JSON)
    {
        $this->headerInfo($code);
        foreach($bodyCollection as $key => $body)
        {
            $this->putBody($key,$body);
        }

        $this->createResponse($response);

        return $this;
    }


    public function Redirect(Array $bodyCollection,$code = 300,$response = self::JSON)
    {
        $this->headerRedirection($code);
        foreach($bodyCollection as $key => $body)
        {
            $this->putBody($key,$body);
        }

        $this->createResponse($response);

        return $this;
    }


    public function headerInfo($code = 100)
    {
        $this->putHeader(self::FAILURE,self::FAILURE_POSITIVE);
        $this->putHeader(self::SEVERITY,self::SEVERITY_ERROR);

        if($code < 100 || $code > 102)
        {
            throw new Exception(sprintf('http code {%s} invalid, valid codes are between 100 and 102',$code));
        }     

        $this->code = $code;
        $this->reason = self::HTTP_RESPONSE_STATUS[$this->code];

        return $this;
    }

    public function headerSuccess($code = 200)
    {
        $this->putHeader(self::FAILURE,self::FAILURE_WITHOUT)
             ->putHeader(self::SEVERITY,self::SEVERITY_WITHOUT);

        if($code < 200 || $code > 299)
        {
            throw new Exception(sprintf('http code {%s} invalid, valid codes are between 200 and 299',$code));
        }     

        $this->code = $code;
        $this->reason = self::HTTP_RESPONSE_STATUS[$this->code];

        return $this;
    }

    public function headerRedirection($code = 300)
    {
        $this->putHeader(self::FAILURE,self::FAILURE_POSITIVE);
        $this->putHeader(self::SEVERITY,self::SEVERITY_ERROR);

        if($code < 300 || $code > 308)
        {
            throw new Exception(sprintf('http code {%s} invalid, valid codes are between 300 and 308',$code));
        }     

        $this->code = $code;
        $this->reason = self::HTTP_RESPONSE_STATUS[$this->code];

        return $this;
    }    

    public function headerClientError($code = 400)
    {
        $this->putHeader(self::FAILURE,self::FAILURE_POSITIVE);
        $this->putHeader(self::SEVERITY,self::SEVERITY_WARNING);

        if($code < 400 || $code > 499)
        {
            throw new Exception(sprintf('http code {%s} invalid, valid codes are between 400 and 499',$code));
        }     

        $this->code = $code;
        $this->reason = self::HTTP_RESPONSE_STATUS[$this->code];

        return $this;
    }


    public function headerServerError($code = 500)
    {
        $this->putHeader(self::FAILURE,self::FAILURE_POSITIVE);
        $this->putHeader(self::SEVERITY,self::SEVERITY_ERROR);

        if($code < 500 || $code > 599)
        {
            throw new Exception(sprintf('http code {%s} invalid, valid codes are between 500 and 599',$code));
        }     

        $this->code = $code;
        $this->reason = self::HTTP_RESPONSE_STATUS[$this->code];

        return $this;
    }

    public function getOneHeader($header)
    {
        $value = null;

        if($this->hasHeader($header))
        {
            $value = $this->getHeader($header)[0];
        }

        return $value;
    }

    public function putHeader($key,$value)
    {
        $this->collectionHeader[$key] = $value;
        return $this;
    }    

    public function putBody($key,$value)
    {
        $this->collectionBody[$key] = $value;

        return $this;
    }

    public function resetHeader()
    {
        $this->collectionHeader = [];
        return $this;
    }

    public function resetBody()
    {
        $this->collectionBody = [];
        return $this;
    }

    public function reset()
    {
        $this->resetHeader();
        $this->resetBody();
        return $this;
    }

    public function createResponse($response = self::STREAM)
    {
        $body = '';

        if($response == self::STREAM)
        {
            $body = $this->createResponseStream();
        }
        else if($response == self::JSON)
        {
            $body = $this->createResponseJson();
        }
     
        parent::__construct(
            $this->code,
            $this->collectionHeader,
            $body,
            $this->version,
            $this->reason
        );

        return $this;
    }

    private function createResponseJson()
    {
        $Json = new Json($this->collectionBody,Json::JSON_ENCODE);
        return $Json->getResponse();  
    }


    public function getResponse($decoded = false)
    {
        $this->getBody()->rewind();
        $contents = $this->getBody()->getContents();

        $result = $contents;

        if($decoded)
        {
            $json = new Json($contents,Json::IS_JSON);

            if($json->getResponse())
            {
                $result = $json->exec($contents,Json::JSON_DECODE);
            }
            else
            {
                parse_str($contents,$result);
            }
        }

        return $result;
    }

    public function saveResponse(string $key,HarpResponse $response)
    {
        $this->allResponses[$key] = $response->getResponse();

        return $this;
    }

    public function getSavedResponse(string $key)
    {
        $result = null;

        if(!empty($this->allResponses[$key]))
        {
            $contents = $this->allResponses[$key];

            $json = new Json($contents,Json::IS_JSON);

            if($json->getResponse())
            {
                $result = $json->exec($contents,Json::JSON_DECODE);
            }
            else
            {
                parse_str($contents,$result);
            }
        }

        return $result;
    }

    private function createResponseStream()
    {
        return $strBody =  http_build_query($this->collectionBody,'','&');
    }

    public static function getHttpResponse($code)
    {
        return self::HTTP_RESPONSE_STATUS[$code] ?? 'Undefined Http Status';
    }

    public function setHttpCode($code)
    {
        if(!isset(self::HTTP_RESPONSE_STATUS[$code]))
        {
            throw new \Exception('Http status code invalid!');
        }

        $this->code = $code;

        return $this;
    }

    public function __sendResponse(Array $headers = [],$result = null)
    {
        try 
        {
            $headers = !empty($headers) ? $headers : $this->collectionHeader;

            header(sprintf('%s %s %s',$this->httpVersion,$this->code,$this->reason));
            foreach($headers as $key => $header)
            {
                header(sprintf('%s:%s',$key,$header));
            }

            if(!empty($result))
            {
                exit(print($result));
            }
        } 
        catch (\Throwable $th) 
        {
            throw $th;
        }
    }

    private function sendResponse($headers,$result)
    {
        try 
        {
            header(sprintf('%s %s %s',$this->httpVersion,$this->code,$this->reason));
            foreach($headers as $key => $header)
            {
                header(sprintf('%s:%s',$key,$header));
            }
    
            exit(print($result));
        } 
        catch (\Throwable $th) 
        {
            throw $th;
        }
    }

    public function json()
    {
        $contents = $this->getResponse();

        $Json = new Json($contents,Json::IS_JSON);
       
        $result = '{}';

        if($Json->getResponse())
        {
            
            //$result = $Json->exec($contents,Json::JSON_DECODE);
            $result = $contents;
        }
        else
        {
            parse_str($contents,$stream);
            $result = json_encode($stream);
        }

        $headers = ['Content-Type' => 'application/json','charset' => 'UTF-8']
        + $this->collectionHeader;

        $this->sendResponse($headers,$result);
    }

    public function xform()
    {
        $contents = $this->getResponse();

        $headers = ['Content-Type' => 'x-www-form-urlencoded']
        + $this->collectionHeader;

        $this->sendResponse($headers,$contents);
    }    

    public function debugException(bool $debug = true)
    {
        $this->debugException = $debug;

        return $this;
    }
    
    public function throwResponseException(Throwable $th,bool $debugException = false)
    {        
        $code = $th->getCode() >= 400 && $th->getCode() <= 599 ? $th->getCode() : 599;

        $this->ByStatus([
            'response' => $th->getMessage(),
            'code' => $th->getCode(),
            'file' => ($debugException || $this->debugException) ? $th->getFile() : $this->msgDebugDisabled,
            'lineNumber' => ($debugException || $this->debugException) ? $th->getLine() : $this->msgDebugDisabled
        ],$code);

        return $this;
    }
}