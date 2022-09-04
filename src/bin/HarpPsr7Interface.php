<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @author t-lsilva
 */
namespace Harp\bin;

interface HarpPsr7Interface
{
   public function getServerRequest() : \GuzzleHttp\Psr7\ServerRequest;
}
