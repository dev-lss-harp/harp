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

use Harp\bin\HarpRequestHeaders;

class HarpRequest
{
    protected $HarpServer;
    protected $HarpRequestHeaders;
    
    protected function __construct(HarpServer $HarpServer,HarpRequestHeaders $HarpRequestHeaders)
    {
        $this->HarpServer = $HarpServer;
        $this->HarpRequestHeaders = $HarpRequestHeaders;
    }
    
    public function getServerConfig() : HarpServer
    {
        return $this->HarpServer;
    }
}
