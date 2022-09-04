<?php
/*
 * Copyright 2010 Leonardo Souza da Silva <allezo.lss@gmail.com>.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace Harp\lib\HarpDB;

include_once(__DIR__.'/CommandSql.class.php');

use etc\HarpDatabase\commands\CommandSql;
use etc\HarpDatabase\commands\CommandParameter;
use etc\HarpDatabase\commands\CommandText;
use Exception;

class CommandSqlsrv extends CommandSql
{
    public function __construct(CommandParameter &$CommandParameter,CommandText &$CommandText)
    {
        parent::__construct($CommandParameter,$CommandText);
    }
    
    public function limit(Array $sLimits,Array $eLimits = Array())
    {
        try
        {
            if(!empty($sLimits))
            {
                foreach($sLimits as $key => $limit)
                {
                    $this->CommandParameter->addParameter($key,(int)$limit,CommandEnum::INT,CommandEnum::TYPE_BIGINT);
                }              
            }

            if(!empty($eLimits))
            {
                foreach($eLimits as $key => $limit)
                {
                    $this->CommandParameter->addParameter($key,(int)$limit,CommandEnum::INT,CommandEnum::TYPE_BIGINT);
                }              
            }

            $this->CommandParameter->commitAll();
        }
        catch (Exception $ex)
        {
            throw $ex;
        }
    }    
      
     public function offset(Array $offsets)
     {       
        try
        {
            foreach($offsets as $key => $offset)
            {
                $this->CommandParameter->addParameter($key,(int) $offset,CommandEnum::INT,CommandEnum::TYPE_BIGINT);
            }    

            $this->CommandParameter->commitAll();
        }
        catch (Exception $ex)
        {
            throw $ex;
        }         
     }         
}
