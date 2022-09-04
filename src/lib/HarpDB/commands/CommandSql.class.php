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

use etc\HarpDatabase\commands\CommandParameter;
use etc\HarpDatabase\commands\CommandText;

abstract class CommandSql
{
    protected $CommandParameter;
    protected $DatabaseHandleException;
    protected $CommandText;
    protected $whereIsSet;
    protected $parameters;
    protected $CommandEnum;

    protected function __construct(CommandParameter &$CommandParameter,CommandText &$CommandText)
    {
        $this->whereIsSet = false;

        $this->parameters = Array();
        
        $this->CommandParameter = &$CommandParameter;
        
        $this->CommandText = &$CommandText;

        $this->CommandEnum = new CommandEnum();
    }
    
    public function setParameters(Array $parameters)
    {
        $this->parameters = $parameters;
    } 

    public function whereIsImplicit()
    {
        $this->whereIsSet = true;
    }
    
    private function optionalWhere($param,$partCommand,$type)
    {
        if(isset($this->parameters[$param]))
        {
            $this->CommandText->getCommand()->text .= $partCommand;
            
            $this->CommandParameter->addParameter($param,$this->parameters[$param],$type);
            
            if($this->CommandParameter->commit($param))
            {
                $this->whereIsSet = true;

                return true;  
            }

            return false;
        }        
    }
    
    private function requiredWhere($value,$partCommand,$type)
    {
        $this->CommandText->getCommand()->text .= $partCommand;
        
        $this->CommandParameter->addParameter('p',$value,$type);
        
        if($this->CommandParameter->commit('p'))
        {
            $this->whereIsSet = true;
            
            return true;  
        }

        return false;        
    }
    
    /**
     * @param type $partCommand
     * @param type $param
     * @param type $type
     * @param type $optional
     * @return boolean
     * @description $param pode assumir um valor ou um parâmetro, será assumido como valor se a flag optional for falsa caso contrário será assumido como parâmetro do array parameters
     */
    public function setWhere($partCommand,$param,$type,$optional = true)
    {
        if($this->whereIsSet){ return false; }
        
        preg_match('#where#i',$partCommand,$r);
        
        if(!empty($r) && count($r) == 1)
        {        
            if(!$optional)
            {
                return $this->requiredWhere($param,$partCommand,$type);
            }
            else
            {
                return $this->optionalWhere($param,$partCommand,$type);
            } 
        }
        
        return false;
    }
    
    private function formatVarchar($p,$partCommand)
    {
        $like = Array();

        $operator = Array();

        $regex = '#(LIKE)[^@](%)?.*'.$p.'(%)?#i';

        $regex2 = '#(<|>)?(=).*(@'.$p.')#i';

        if(preg_match($regex,$partCommand,$like))
        {
            if(isset($like[3]))
            {
                $param = $like[1].chr(32)."'".$like[2].$this->parameters[$p].$like[3]."'";  
            }   
            else if(isset($like[2]))
            {
                $param = $like[1].chr(32)."'".$like[2].$this->parameters[$p]."'";
            }

            $partCommand = str_ireplace($like[0],$param,$partCommand);
        }
        else if(preg_match($regex2,$partCommand,$operator) && isset($operator[3]))
        {
            $param =  "'".$this->parameters[$p]."'";

            $partCommand = str_ireplace($operator[3],$param,$partCommand);
        }

        return $partCommand;        
    }
        
    public function setParameter($partCommand,Array $params)
    {
        foreach($params as $p => $types)
        {
            if(count($types) == 2)
            {
                $tp = array_values($types);

                if(isset($this->parameters[$p]))
                {
                    if($this->CommandEnum->isSupportedType($tp[0]))
                    {
                        if($tp[0] == CommandEnum::VARCHAR)
                        {    
                            $partCommand = $this->formatVarchar($p,$partCommand);
                        }

                        if($this->CommandParameter->validateParameter($tp[0],$this->parameters[$p],$tp[1],false)) 
                        {
                            $this->CommandText->getCommand()->text .= chr(32).$partCommand;

                            $this->CommandParameter->addParameter($p,$this->parameters[$p],$tp[0],$tp[1]);

                            $this->CommandParameter->commit($p);                          
                        }        
                    }                
                }                  
            }                        
        }     
    }
}
