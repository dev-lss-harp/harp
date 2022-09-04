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
namespace etc\HarpDesignTemplate\components\HandleHtml\HandleTable;

use etc\HarpDesignTemplate\components\HarpFileComponent;
use etc\HarpDesignTemplate\components\HandleHtml\HandleHtml;
use etc\HarpDesignTemplate\components\HandleHtml\HandleHtmlEnum;
use Exception;

class HandleTable extends HandleHtml
{
    private $tag;
    private $elementTable;
    private $builds;

    public function __construct(HarpFileComponent $HarpFileComponent) 
    {           
        parent::__construct($HarpFileComponent);
        
        $this->elementTable = Array();
        
        $this->builds = Array();
    }  
        
    public function findByTag($tag)
    {
        $this->tag = preg_quote($tag);
        
        $result = Array();
        
        if(!preg_match('#{H:=Table:'.$this->tag.'}(.+?){/H:=Table:'.$this->tag.'}#is',$this->HarpFileComponent->getFile()->file,$result))
        {
            throw new Exception('Não foi possível encontrar o elemento table id: '.$this->tag); 
        }
        
        $this->elementTable = $result;
        
        return $this;
    }
        
    private function identifier()
    {
        $this->currentIdentifier = !empty($this->genericIdentifier) ? $this->genericIdentifier : $this->tag;

        if(empty($this->currentIdentifier))
        {
            throw new Exception('Não foi possível encontrar o identificador da tabela!');
        }
    }
    
    private function generateTFoot(Array $tfoot,Array $tfootStr)
    {
            if(!empty($tfoot) && !empty($tfootStr[1]))
            {
                $tfootHtml = null;

                $replace = Array();

                $baseTfoot = str_ireplace(Array('<tfoot>','</tfoot>'),null,$tfootStr[1]);
               
                $keyFirst = key($tfoot);
                 
                if(is_array($tfoot[$keyFirst]))
                {
                   foreach($tfoot as $t)
                   {
                       foreach($t as $i => $v)
                       {
                          $replace[HandleHtmlEnum::KEY][$i] = '{H:=Table:'.$this->currentIdentifier.':tfoot:'.$i.'}'; 
                          $replace[HandleHtmlEnum::VALUE][$i] = $v;
                       }

                      $tfootHtml .= str_ireplace($replace[HandleHtmlEnum::KEY],$replace[HandleHtmlEnum::VALUE],$baseTfoot);              
                   }                   
                }
                else
                {
                    $replace = Array();
                    
                    foreach($tfoot as $i => $v)
                    {
                       $replace[HandleHtmlEnum::KEY][$i] = '{H:=Table:'.$this->currentIdentifier.':tfoot:'.$i.'}'; 
                       
                       $replace[HandleHtmlEnum::VALUE][$i] = $v;
                    }                    
                    
                    $tfootHtml .= str_ireplace($replace[HandleHtmlEnum::KEY],$replace[HandleHtmlEnum::VALUE],$baseTfoot);
                }
                
                $tfootHtml = '<tfoot>'.$tfootHtml.'</tfoot>';

                $this->currentManipulatedElement = $this->currentIdentifier.HandleHtmlEnum::TABLE_TFOOT;

                $this->builds[$this->currentManipulatedElement][HandleHtmlEnum::KEY] =  $tfootStr[0];

                $this->builds[$this->currentManipulatedElement][HandleHtmlEnum::VALUE] =  $tfootHtml;

                $this->HarpFileComponent->getHandleReplacement()->addByKey($this->currentManipulatedElement,$this->builds[$this->currentManipulatedElement]['key'],$this->builds[$this->currentManipulatedElement]['value']);
            }         
    }        
    
    private function generateTHead(Array $thead,Array $theadStr)
    {
            if(!empty($thead) && !empty($theadStr[1]))
            {
                $theadHtml = null;

                $replace = Array();

                $baseTfoot = str_ireplace(Array('<thead>','</thead>'),null,$theadStr[1]);
               
                $keyFirst = key($thead);
                 
                if(is_array($thead[$keyFirst]))
                {
                   foreach($thead as $t)
                   {
                       foreach($t as $i => $v)
                       {
                          $replace[HandleHtmlEnum::KEY][$i] = '{H:=Table:'.$this->currentIdentifier.':thead:'.$i.'}'; 
                          $replace[HandleHtmlEnum::VALUE][$i] = $v;
                       }

                      $theadHtml .= str_ireplace($replace[HandleHtmlEnum::KEY],$replace[HandleHtmlEnum::VALUE],$baseTfoot);              
                   }                   
                }
                else
                {
                    $replace = Array();
                    
                    foreach($thead as $i => $v)
                    {
                       $replace[HandleHtmlEnum::KEY][$i] = '{H:=Table:'.$this->currentIdentifier.':thead:'.$i.'}'; 
                       
                       $replace[HandleHtmlEnum::VALUE][$i] = $v;
                    }                    
                    
                    $theadHtml .= str_ireplace($replace[HandleHtmlEnum::KEY],$replace[HandleHtmlEnum::VALUE],$baseTfoot);
                }
                
                $theadHtml = '<thead>'.$theadHtml.'</thead>';

                $this->currentManipulatedElement = $this->currentIdentifier.HandleHtmlEnum::TABLE_THEAD;

                $this->builds[$this->currentManipulatedElement][HandleHtmlEnum::KEY] =  $theadStr[0];

                $this->builds[$this->currentManipulatedElement][HandleHtmlEnum::VALUE] =  $theadHtml;

                $this->HarpFileComponent->getHandleReplacement()->addByKey($this->currentManipulatedElement,$this->builds[$this->currentManipulatedElement]['key'],$this->builds[$this->currentManipulatedElement]['value']);
            }         
    } 
    
    private function generateTBody(Array $tbody,Array $tbodyStr)
    {
            if(!empty($tbody) && !empty($tbodyStr[1]))
            {
                $tbodyHtml = null;

                $replace = Array();

                $baseTbody = str_ireplace(Array('<tbody>','</tbody>'),null,$tbodyStr[1]);

                $keyFirst = key($tbody);
                 
                if(is_array($tbody[$keyFirst]))
                {
                   foreach($tbody as $t)
                   {
                       foreach($t as $i => $v)
                       {
                                                        //    $this->conditionField();                       

                          $replace[HandleHtmlEnum::KEY][$i] = '{H:=Table:'.$this->currentIdentifier.':tbody:'.$i.'}'; 
                                               //    print_r($replace[HandleHtmlEnum::KEY][$i]);
                          $replace[HandleHtmlEnum::VALUE][$i] = $v;
                       }
                                              
                      $tbodyHtml .= str_ireplace($replace[HandleHtmlEnum::KEY],$replace[HandleHtmlEnum::VALUE],$baseTbody);       
                      
                   }                   
                }
                else
                {
                    $replace = Array();
                    
                    foreach($tbody as $i => $v)
                    {
                       $replace[HandleHtmlEnum::KEY][$i] = '{H:=Table:'.$this->currentIdentifier.':tbody:'.$i.'}'; 
                       
                       $replace[HandleHtmlEnum::VALUE][$i] = $v;
                    }                    
                    
                    $tbodyHtml .= str_ireplace($replace[HandleHtmlEnum::KEY],$replace[HandleHtmlEnum::VALUE],$baseTbody);
                }
                
                $tbodyHtml = $this->HarpFileComponent->HandleHTags->execExpr($tbodyHtml);
                
                $tbodyHtml = '<tbody>'.$tbodyHtml.'</tbody>';

                $this->currentManipulatedElement = $this->currentIdentifier.HandleHtmlEnum::TABLE_TBODY;

                $this->builds[$this->currentManipulatedElement][HandleHtmlEnum::KEY] =  $tbodyStr[0];

                $this->builds[$this->currentManipulatedElement][HandleHtmlEnum::VALUE] =  $tbodyHtml;

                $this->getFileDocument()->getFile()->file = str_ireplace($this->builds[$this->currentManipulatedElement]['key'],$this->builds[$this->currentManipulatedElement]['value'],$this->getFileDocument()->getFile()->file);
            }         
    }
    
    public function setTHead(Array $thead,$headId)
    {
        $this->identifier();
        
        $theadStr = Array();
        
        if(!empty($this->elementTable[1]))
        {
            if(!preg_match('#{H:=Table:'.$this->currentIdentifier.':thead:'.$headId.'}.+?\<thead\>(.*)\<\/thead\>.+?{/H:=Table:'.$this->currentIdentifier.':thead:'.$headId.'}#is',$this->elementTable[1],$theadStr))
            {
                throw new Exception('thead id '.$headId.' não foi encontrado'); 
            }            
        }
        else if(!empty($this->element))
        {
            if(!preg_match('#{H:=Table:'.$this->currentIdentifier.':thead:'.$headId.'}.+?\<thead\>(.*)\<\/thead\>.+?{/H:=Table:'.$this->currentIdentifier.':thead:'.$headId.'}#is',$this->element,$theadStr))
            {
                throw new Exception('thead id '.$headId.' não foi encontrado'); 
            }   
        }
        
        $this->generateTHead($thead,$theadStr);

        return $this;        
    }    
     
    public function setTBody(Array $tbody,$bodyId)
    {
        $this->identifier();
        
        $tbodyStr = Array();
       
        if(!empty($this->elementTable[1]))
        {
            if(!preg_match('#{H:=Table:'.$this->currentIdentifier.':tbody:'.$bodyId.'}.+?\<tbody\>(.*)\<\/tbody\>.+?{/H:=Table:'.$this->currentIdentifier.':tbody:'.$bodyId.'}#is',$this->elementTable[1],$tbodyStr))
            {
                throw new Exception('tbody id '.$bodyId.' não foi encontrado'); 
            }            
        }
        else if(!empty($this->element))
        {
            if(!preg_match('#{H:=Table:'.$this->currentIdentifier.':tbody:'.$bodyId.'}.+?\<tbody\>(.*)\<\/tbody\>.+?{/H:=Table:'.$this->currentIdentifier.':tbody:'.$bodyId.'}#is',$this->element,$tbodyStr))
            {
                throw new Exception('tbody id '.$bodyId.' não foi encontrado'); 
            }   
        }
        
        $this->generateTBody($tbody,$tbodyStr);

        return $this;        
    }
    
    public function setTFoot(Array $tfoot,$footId)
    {
        $this->identifier();
        
        $tfootStr = Array();
        
        if(!empty($this->elementTable[1]))
        {
            if(!preg_match('#{H:=Table:'.$this->currentIdentifier.':tfoot:'.$footId.'}.+?\<tfoot\>(.*)\<\/tfoot\>.+?{/H:=Table:'.$this->currentIdentifier.':tfoot:'.$footId.'}#is',$this->elementTable[1],$tfootStr))
            {
                throw new Exception('tfoot id '.$footId.' não foi encontrado'); 
            }            
        }
        else if(!empty($this->element))
        {
            if(!preg_match('#{H:=Table:'.$this->currentIdentifier.':tfoot:'.$footId.'}.+?\<tfoot\>(.*)\<\/tfoot\>.+?{/H:=Table:'.$this->currentIdentifier.':tfoot:'.$footId.'}#is',$this->element,$tfootStr))
            {
                throw new Exception('tfoot id '.$footId.' não foi encontrado'); 
            }   
        }
        
        $this->generateTFoot($tfoot,$tfootStr);

        return $this;        
    } 
    
    public function getHandleTD()
    {
       return $this->HarpFileComponent->getHandleHtml('td');
    }
    
    public function getHandleTH()
    {
       return $this->HarpFileComponent->getHandleHtml('th');
    }    
    
    public function getName()
    {
        return HandleHtmlEnum::TABLE;
    }     
}
