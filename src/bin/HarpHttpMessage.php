<?php
namespace Harp\bin;

use Exception;
use h4cc\Multipart\ParserSelector;
use Harp\bin\HarpApplicationInterface;
use Harp\bin\HarpServerRequest;
use Harp\bin\ArgumentException;
use Harp\enum\RouteEnum;
use Harp\lib\HarpJson\Json;

class HarpHttpMessage
{
    private $HarpServerRequest = null;
    private $Application;
    private $body = [];
    private $uriQuery = [];
    
    public function __construct(HarpServerRequest $ServerRequest,HarpApplicationInterface $Application)
    { 
        $this->Application = $Application;
        $this->HarpServerRequest = $ServerRequest;

        $this->parseBodyParams();
     
        $this->parsedQuery($this->Application);

        if(array_key_exists('X-XSS-Protection',$this->body))
        {
            $protection = (int)$_POST['X-XSS-Protection'];
          
            header("X-XSS-Protection:".$protection);
        }
    }

    private function parseFormData($contentType)
    {
        $ParserSelector = new ParserSelector();
        $parser = $ParserSelector->getParserForContentType($contentType);
        $multipart = $parser->parse($this->HarpServerRequest->getServerRequest()->getBody()->getContents());
        
        if(is_array($multipart))
        {
            foreach($multipart as $obj)
            {
                if(isset($obj['body']))
                {
                    $header = $obj['headers']['content-disposition'][0];
                    preg_match('`name="(.*?)"`',$header,$rs);
                    $key = isset($rs[1]) ? trim($rs[1]) : 'unk_'.mt_rand(0,10000);
                    $this->body[$key] = $obj['body'];
                }
            }
        }
    }

    private function parseBodyParams()
    {
        
        $contentType = '';

        $this->body = $this->HarpServerRequest->getServerRequest()->getParsedBody();

        if
        (
            !$this->HarpServerRequest->getServerRequest()->hasHeader('Content-Type')
            &&
            $this->HarpServerRequest->getServerRequest()->getMethod() != 'GET'
        )
        {
            throw new Exception('Content-Type not found. it was not possible to complete the request!',500);
        }
    
        $contentType = $this->HarpServerRequest->getServerRequest()->getHeader('Content-Type');
        $contentType = isset($contentType[0]) ? $contentType[0] : null;
   
        if(empty($this->body))
        {
            if(!empty($contentType) && preg_match('`\bform-data\b`',$contentType))
            {
                $this->parseFormData($contentType);
            }
            else
            {
                $this->parseFromStream($contentType);
            }
        }

        $this->body = $this->sanitizeDefault($this->body);
    }

    private function sanitizeDefault(&$body)
    {
        foreach($body as $k => $v)
        {
            $body[$k] = is_array($v) ? 
                        $this->sanitizeDefault($v) : 
                        (is_string($v) ? filter_var($v,\FILTER_SANITIZE_ADD_SLASHES) : $v);
        }

        return $body;
    }

    private function parseFromStream($contentType)
    {
        $contents = $this->HarpServerRequest->getServerRequest()->getBody()->getContents();

        if(!empty($contents))
        {
            $Json = new Json($contents,Json::IS_JSON);

            if($Json->getResponse())
            {
                $dtJsonArray = $Json->exec($contents,Json::JSON_DECODE);

                $this->body = $dtJsonArray;
            }
            else if(preg_match('`x-www-form-urlencoded`',$contentType))
            {
                $decode = urldecode($contents);

                parse_str($decode,$this->body);
            }
        }
    }

    private function parseTypeValue(mixed $val) : mixed
    {
  
        if(preg_match('/^\d+$/', $val))
        {
            $val = intval($val);
        }

        return $val;
    }
    
    private function paramsUrlParse($p,$urlParams)
    {
        if(!empty($p))
        {
            $nmRparams = [];

            parse_str($p,$nmRparams);
  
            $valParamsUrl = [];

            foreach($nmRparams as $keyParam => $valParam)
            {
                $val = $this->parseTypeValue($valParam);
                array_push($valParamsUrl,$keyParam,$val);
            }

            $urlParams = array_merge($urlParams,$valParamsUrl);
        } 
 
        return $urlParams;
    }

    private function parseArgsFromRoute($route)
    {
        $args = 
        !empty($route['args']['get']) ? $route['args']['get']
        : (!empty($route['constructor']['args']['get']) ? $route['constructor']['args']['get'] : []);

        if(empty($args) && isset($route['required']['before']))
        {
            foreach($route['required']['before'] as $k => $obj)
            {
               
                if(!empty($obj['args']['get']))
                {
                    $args = array_merge($args,$obj['args']['get']);
                }
            
            }
        }

        return $args;
    }

    //mantém somente parâmetros GET
    private function filterParametersUrl($alias)
    {
        $uri = $this->HarpServerRequest->getServerRequest()->getUri();
        $query = $uri->getQuery();
   
        $urlParams = array_values(array_filter(explode('/',$uri->getPath())));
 
        if(!empty($query) && mb_substr($query, 0,4) != 'url=')
        {
            $urlParams = $this->paramsUrlParse($query,$urlParams);
        }

        //second parameter array_filter to prevent remove zero values {0}.
        $urlParams = array_values(array_filter($urlParams,function($val){ return $val !== null && $val !== false && $val !== '';}));
        $keys[] = array_search(__PROJECT_NAME,$urlParams);
        $keys[] = array_search(mb_strtolower($this->Application->getName()),$urlParams);

        foreach($keys as $key)
        {
            if($key !== false)
            {
                unset($urlParams[$key]);
                continue;
            }
        }
      
        $requestAlias = explode('/',$alias); 

        foreach($requestAlias as $part)
        {
            if(in_array($part,$urlParams))
            {
                $key = array_search($part,$urlParams);
                unset($urlParams[$key]);
                continue;
            }
        }

        $urlParams = array_values($urlParams);
  
        return $urlParams;
    }  
    
    private function getParametersByRouteArgs($urlParams,$routeArgs)
    {
        $urlParamsCopy = $urlParams;

        $uriQuery = [];

        $acceptedAll = !empty($routeArgs[0]) && $routeArgs[0] === '*';

        $cntParams = count($urlParamsCopy);
   
        for($i = 0; $i < $cntParams; ++$i)
        {
            $k = $urlParamsCopy[$i];
    
            if(in_array($k,$routeArgs))
            {   
                $uriQuery[$k] = chr(32);
   
                if(!isset($urlParamsCopy[$i + 1]))
                {
                    continue;  
                }
             
                $uriQuery[$k] = filter_var($urlParamsCopy[$i + 1],FILTER_DEFAULT);
            }
            else if($acceptedAll && $i % 2 === 0)
            {
                $uriQuery[$k] = $urlParamsCopy[$i + 1];
            }
        }
    
        return $uriQuery;
    }

    private function parsedQuery($Application)
    {        
        $routeCurrent = $Application->getProperty(RouteEnum::class);

        $alias = $routeCurrent[RouteEnum::Alias->value];
        $route = $routeCurrent[RouteEnum::Current->value];
        $routeArgs = $this->parseArgsFromRoute($route);

        $urlParams = $this->filterParametersUrl($alias);

        $this->uriQuery = $this->getParametersByRouteArgs($urlParams,$routeArgs);        
    }
    
    public function getBody()
    {
        return $this->body;
    }
    
    public function getQuery()
    {
        return $this->uriQuery;
    }
    
    public function getAll()
    {
        return array_merge_recursive($this->getQuery(),$this->getBody());  
    }

    public function getHarpServerRequest()
    {
        return $this->HarpServerRequest;
    }    
    
    public function getServerRequest()
    {
        return $this->HarpServerRequest->getServerRequest();
    }
}
