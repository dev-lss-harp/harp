<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ServerRequest
 *
 * @author t-lsilva
 */
namespace Harp\bin;

use Harp\bin\HarpServer;
use Harp\bin\HarpRequestHeaders;


class HarpServerRequest extends HarpRequest implements HarpPsr7Interface
{
    protected $GuzzleServerRequest;
     
    public function __construct(HarpServer $HarpServer, HarpRequestHeaders $HarpRequestHeaders)
    {
        parent::__construct($HarpServer,$HarpRequestHeaders); 
        
        $this->GuzzleServerRequest = \GuzzleHttp\Psr7\ServerRequest::fromGlobals();
    }
    
    public function getServerRequest() : \GuzzleHttp\Psr7\ServerRequest
    {
        return $this->GuzzleServerRequest;
    }
}
