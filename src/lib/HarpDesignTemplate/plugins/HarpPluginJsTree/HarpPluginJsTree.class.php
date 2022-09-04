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
class HarpPluginJsTree extends AbstractPluginElement 
{
    private $AuthorizedModules;
    private $keyDisplay;
    private $keyId; 
    private $scriptjQuery;
    private $scriptjQueryBody;
    private $identifierElement;
    private $eventJstreeChecked;
    private $collection;


    public function __construct(HarpEngineTemplatePlugin $HarpEngineTemplatePlugin) 
    {
        parent::__construct($HarpEngineTemplatePlugin);
        
        $this->HarpEngineTemplatePlugin = $HarpEngineTemplatePlugin;
                
        $this->AuthorizedModules = Array();
        
        $this->scriptjQueryBody = null;
        
        $this->eventJstreeChecked = null;
        
        $specificArguments = $HarpEngineTemplatePlugin->getSpecificArguments();

        $this->collection = $specificArguments[0];
        
        $this->keyId = $specificArguments[1];
        
        $this->keyDisplay = $specificArguments[2];      
                
    }
        
    public function setRunnableArguments($collections)
    {
        $this->collections = $collections;
    }
    
    public function getCollection()
    {
        return $this->collection;
    }
    
    public function build($method = 'runnable') 
    {
         $this->HarpEngineTemplatePlugin->build($method);
         
         return $this;
    }
    
    public function run()
    {
        return $this->HarpEngineTemplatePlugin->run();
    }

    private function createJsTreeScript()
    {
        $this->scriptjQuery   = '<script type="text/javascript">';
        $this->scriptjQuery  .= 'jQuery(document).ready(function(){';
            $this->scriptjQuery .= 'jQuery("'.$this->identifierElement.'").jstree({';
             $this->scriptjQuery .= $this->scriptjQueryBody;
             $this->scriptjQuery .= '});';
        $this->scriptjQuery .= '});';
        $this->scriptjQuery .= str_ireplace('@identifier@',$this->identifierElement,$this->eventJstreeChecked);
        $this->scriptjQuery .= '</script>';
    }
    
    public function setEventClicked($identifierElement,$nameElementHtml,$destinationElementId,$preventDefault = false,$consoleLog = false)
    {   
        $this->eventJstreeChecked .= 'jQuery(document).ready(function(){';
            $this->eventJstreeChecked .= 'jQuery("'.$identifierElement.'").on("click",function(event){';
 
                $this->eventJstreeChecked .= 'var x = jQuery("@identifier@ ul li").find("a").filter(function(){ return jQuery(this).attr("rel") == "only-child"}).filter(function(){ return jQuery(this).attr("class").replace(/(jstree-anchor)/gi,"").trim() == "jstree-clicked"});';
                
                $this->eventJstreeChecked .= 'jQuery("#'.$destinationElementId.'").empty();';
                
                $this->eventJstreeChecked .= 'jQuery.each(x,function(f,g){ ';
                       $this->eventJstreeChecked .= 'var itemId = jQuery(g).attr("href");';
                       $this->eventJstreeChecked .= 'var inputHidden = "<input type=\"hidden\" name=\"'.$nameElementHtml.'["+itemId+"]\" value=\""+itemId+"\"/>";'; 
                       $this->eventJstreeChecked .= 'jQuery("#'.$destinationElementId.'").append(inputHidden);';
                $this->eventJstreeChecked .=  '});';
                
                
                
                if($consoleLog)
                {
                    $this->eventJstreeChecked .='console.log(x);';
                }  
                
                if($preventDefault)
                {
                     $this->eventJstreeChecked .= 'event.preventDefault();';
                }    
                
            $this->eventJstreeChecked .= '});';
        $this->eventJstreeChecked .= '});';
        $this->setEventChange($destinationElementId);
    } 
    
    /**
     * @return void previne que elementos pre-existentes continuem a existir no dom
     * quando a árvore é atualizada
     */
    private function setEventChange($destinationElementId)
    {   
        $this->eventJstreeChecked .= 'jQuery(document).ready(function(){';
            $this->eventJstreeChecked .= 'jQuery("@identifier@ ul li").on("click",function(event){';                
            $this->eventJstreeChecked .= 'jQuery("#'.$destinationElementId.'").empty();';
           $this->eventJstreeChecked .= '});';
        $this->eventJstreeChecked .= '});';
    }      
        
    public function setOptionPlugin($option)
    {
        $this->scriptjQueryBody .= $option.PHP_EOL;
    }
        
    public function create(Array $treeNodes = Array(),$rootId = '0.0',$id = null,$class = null) 
    {
        $rootId = (string)  $rootId;
        
        $parentId = $rootId;
        
        $ParentElements = Array();
        
        if(empty($id) && empty($class))
        {
            return false;
        }
        
        $this->identifierElement = !empty($id) ? '#'.$id : '.'.$class;

        $id = empty($id) ? $id : 'id="'.$id.'"';
        
        $class = empty($class) ? $class : 'class="'.$class.'"';
        
        $this->htmlElement   = '<div '.$id.' '.$class.'>';
        $this->htmlElement  .= '<ul>'.PHP_EOL;

        while(($v = each($treeNodes[$parentId])) || ($parentId > $rootId))
        {
           if(!empty($v))
            {
                if(!empty($treeNodes[$v['value'][$this->keyId]]))
                {
                    $this->htmlElement .= '<li>';
                    $this->htmlElement .=      '<a href="'.$v['value'][$this->keyId].'" rel="not-only-child">' . $v['value'][$this->keyDisplay].'</a>'.PHP_EOL;
                    $this->htmlElement .= '<ul>'.PHP_EOL;

                    $ParentElements[] = $parentId;
                    $parentId = $v['value'][$this->keyId];
                    
                }
                else
                {   
                    $this->htmlElement .= '<li>';
                    $this->htmlElement .= '<a href="'.$v['value'][$this->keyId].'" rel="only-child">' . $v['value'][$this->keyDisplay] . '</a>';
                    $this->htmlElement .= PHP_EOL.'</li>';
                }                  
            }
            else
            {
                  $this->htmlElement .= PHP_EOL.'</ul>';

                  $parentId = array_pop($ParentElements);
            }
           
        }
       // exit;
         $this->htmlElement .= PHP_EOL.'</ul>';
         $this->htmlElement .= '</div>';
    }

    public function GetElement() 
    {
        return $this->htmlElement;
    }

    public function GetPlugin($includeJquery = true) 
    {
        
        $this->addLink('js/jstree-3.2.1-0/dist/themes/default','style.min','css','text/css','stylesheet');
        
        if($includeJquery)
        {
            $this->addScript('js','jquery-1.11.1.min','js','text/javascript');
        }    
        
        $this->addScript('js/jstree-3.2.1-0/dist','jstree.min','js','text/javascript');
        
        $this->createJsTreeScript();
        
        return $this->htmlPlugin = $this->getLink().PHP_EOL.$this->getScript().PHP_EOL.$this->scriptjQuery.PHP_EOL.$this->htmlElement;
    }
}
