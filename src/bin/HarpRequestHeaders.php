<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of HarpRequestHeaders
 *
 * @author t-lsilva
 */

namespace Harp\bin;

class HarpRequestHeaders
{
   private $headers = [];
   
   public function __construct()
   {
        if (function_exists('getallheaders')) 
        { 
            $this->headers = getallheaders();
        }
   }
   
   public function getHeaders()
   {
       return $this->headers;
   }
}
