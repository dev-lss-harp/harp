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
namespace etc\HarpDesignTemplate\plugins\HarpFilter\ext;

class HarpFilterSql
{
   private $strFilterBase64;
   private $Dictionary;
   
    const KEY_FILTER = "filter";
    const KEY_PARAMETERS = "parameters";
    const KEY_COMMAND_TEXT = "commandText";
    const KEY_COMMAND_TEXT_PARAMETERS = "commandParameter";
    const CLAUSE_AND = 'AND';
    const CLAUSE_WHERE = 'WHERE';
    const STRING_EMPTY = "";

   public function __construct($strFilterBase64)
   {
       $this->strFilterBase64 = $strFilterBase64;
       
       $this->Dictionary = Array();
   }
   
   public function toDictionary()
   {
        if(is_string($this->strFilterBase64))
        {
            $json = @base64_decode($this->strFilterBase64);
        
            if(!preg_match('//u',$json))
            {
                $json = utf8_encode($json);
            }

            if(!empty($json))
            {
                $obj = json_decode($json);

                if(is_object($obj) && isset($obj->filter) && count(((array)$obj->filter)) > 0)
                { 
                    $this->Dictionary = (Array)$obj;

                    return true;               
                }    

            }            
            
        }

        return false;       
   }
   
   public function explicitWhereClause()
   {
        if(isset($this->Dictionary[self::KEY_COMMAND_TEXT_PARAMETERS]) && isset($this->Dictionary[self::KEY_COMMAND_TEXT]))
        {
            $clauseP = substr(trim($this->Dictionary[self::KEY_COMMAND_TEXT_PARAMETERS]),0,3);
            $clauseV = substr(trim($this->Dictionary[self::KEY_COMMAND_TEXT]),0,3);

            if((trim($clauseP) == self::CLAUSE_AND) && (trim($clauseV) == self::CLAUSE_AND))
            {
                $this->Dictionary[self::KEY_COMMAND_TEXT] = self::CLAUSE_WHERE.' '.substr(trim($this->Dictionary[self::KEY_COMMAND_TEXT]),3);
                $this->Dictionary[self::KEY_COMMAND_TEXT_PARAMETERS] = self::CLAUSE_WHERE.' '.substr(trim($this->Dictionary[self::KEY_COMMAND_TEXT_PARAMETERS]),3);

                return true;
            }
        } 

       return false;
   }

    public function getCommandTextWithParameters()
    {
        return isset($this->Dictionary[self::KEY_COMMAND_TEXT_PARAMETERS]) ? $this->Dictionary[self::KEY_COMMAND_TEXT_PARAMETERS] : self::STRING_EMPTY;
    }
    
    public function getCommandTextWithValues() 
    {
        return isset($this->Dictionary[self::KEY_COMMAND_TEXT]) ? $this->Dictionary[self::KEY_COMMAND_TEXT] : self::STRING_EMPTY;
    }  
    
    public function getSeparatedFilter() 
    {
        return isset($this->Dictionary[self::KEY_FILTER]) ? $this->Dictionary[self::KEY_FILTER] : Array();
    }

    public function getParametersValues() 
    {
       return isset($this->Dictionary[self::KEY_PARAMETERS]) ? $this->Dictionary[self::KEY_PARAMETERS] : Array();    
    }   
    
    public function getDictionary()
    {
        return $this->Dictionary;
    }
    
    public function getBase64String()
    {
        return $this->strFilterBase64;
    }
    
    public function isEmptyFilter()
    {
        return empty($this->Dictionary);
    }
}
