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
    protected $HarpServerConfig;
    protected $HarpRequestHeaders;
    
    protected function __construct(HarpServerConfig $HarpServerConfig,HarpRequestHeaders $HarpRequestHeaders)
    {
        $this->HarpServerConfig = $HarpServerConfig;
        $this->HarpRequestHeaders = $HarpRequestHeaders;
    }
    
    public function getServerConfig() : HarpServerConfig
    {
        return $this->HarpServerConfig;
    }

    public function getServerHeaderConfig()
    {
        return $this->HarpRequestHeaders->getServerHeaderConfig();
    }
}
