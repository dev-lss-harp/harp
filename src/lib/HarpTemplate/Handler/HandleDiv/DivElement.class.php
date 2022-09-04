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
namespace etc\HarpDesignTemplate\components\HandleHtml\HandleDiv;

use Exception;

class DivElement
{
    private $HandleDiv;
    
    public function __construct(HandleDiv $HandleDiv) 
    {                   
        $this->HandleDiv = $HandleDiv;
    }  
    
    private function findByAttr($attr,$valueAttr)
    {
        $this->element = Array();
        
        if(empty($attr) || empty($valueAttr))
        {
            throw new Exception('attr or value attr is empty!'); 
        }

        $elementIdentifier = str_ireplace(Array('Element','Handle'),Array('',''),basename(str_ireplace(Array('\\','/'),DIRECTORY_SEPARATOR,get_class($this))));

        $regex = '`\<'.$elementIdentifier.'*[^>]*'.$attr.'=*[^\>]*"'.preg_quote($valueAttr).'".*?\>(.+?)\</'.$elementIdentifier.'\>`is';

        $result = Array();

        preg_match_all($regex,$this->HandleDiv->getFileDocument()->getFile()->file,$result);

        if(empty($result[0]))
        {
            throw new \Harp\bin\ArgumentException('Element  '.$attr.' = '.$valueAttr.' not found!','Error','error'); 
        }

        return $result;        
    }        
    
    public function replaceContentById($id,$content)
    {
        $s = false;
        
        $element = $this->findByAttr('id',$id);
        
        if(!empty($element[0]) && !empty($element[1]))
        {
            foreach($element[0] as $i => $v)
            {
                $el = str_ireplace($element[1][$i],$content,$v);
                
                $this->HandleDiv->getFileDocument()->getFile()->file = str_ireplace($v, $el,$this->HandleDiv->getFileDocument()->getFile()->file);
            }
            
            $s = true;
        }
        
        return $s;
    }
}
