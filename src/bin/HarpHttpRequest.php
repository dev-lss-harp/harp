<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Request
 *
 * @author t-lsilva
 */
namespace Harp\bin;


use GuzzleHttp\Psr7\ServerRequest;

class HarpHttpRequest extends ServerRequest
{
    public function __construct(string $method,string $uri,array $headers,$body = null,$version = '1.1',array $serverParams = [])
    {
        parent::__construct($method,$uri,$headers,$body,$version,$serverParams);
    }
}
