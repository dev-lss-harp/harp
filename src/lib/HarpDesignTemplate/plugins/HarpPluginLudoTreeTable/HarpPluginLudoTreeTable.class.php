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
class HarpPluginLudoTreeTable extends AbstractPluginElement 
{
    private $AuthorizedModules;
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

    private function createLudoTreeTableScript()
    {
        $this->scriptjQuery   = '<script type="text/javascript">';
        $this->scriptjQuery  .= 'jQuery(document).ready(function(){';
            $this->scriptjQuery .= 'jQuery("'.$this->identifierElement.'").agikiTreeTable({';
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
    
    public function create(Array $columns = Array(),Array $keyLines = Array(),Array $collectionArray = Array(),$class = NULL,$id = NULL)
    {
           $this->identifierElement = !empty($id) ? '#'.$id : '.PluginTreeTable-1';
        
           $Levels = Array(0 => 0); 
            
           $this->htmlElement  = PHP_EOL.'<table '.(!empty($id) ? 'id="'.$id.'"' : null).' ';
           $this->htmlElement .= !empty($class) ? 'class="'.$class.' PluginTreeTable-1"' : 'class="PluginTreeTable-1"';
           $this->htmlElement .= '>'.PHP_EOL;
            
            $this->htmlElement .= '<thead>'.PHP_EOL;
            

                $this->htmlElement .= '<tr>'.PHP_EOL;
                
                foreach($columns as $v)
                {
                    $this->htmlElement .=   '<th>'.$v.'</th>'.PHP_EOL;
                }
                 
                $this->htmlElement .= '</tr>'.PHP_EOL;
           
            
            $this->htmlElement .= '</thead>'.PHP_EOL;  

            $this->htmlElement .= '<tbody>';
           
            foreach($collectionArray as $v)
            {   
                    if(isset($Levels[$v['NodeParentId']]))
                    {
                        $lvl = $Levels[$v['NodeParentId']];
                        $Levels[$v['NodeId']] = $lvl + 1;
                    }
                    else
                    {
                        $lvl = max($Levels);
                        
                        $Levels[$v['NodeParentId']] = $lvl;
                        $Levels[$v['NodeId']] = $lvl + 1;
                    }
                
                    $this->htmlElement .='<tr data-tt-id="'.$v['NodeId'].'" '.($v['NodeParentId'] != 0 ? 'data-tt-parent-id="'.$v['NodeParentId'].'"' : null).' class="'.$v['NodeParentId'].'"  llvl="'.$lvl.'" id="'.$v['NodeId'].'" type="'.$v['NodeType'].'">'.PHP_EOL;

                    foreach($keyLines as $val)
                    {
                        $this->htmlElement .='<td>'.(isset($v[$val]) ? $v[$val] : null).'</td>'.PHP_EOL;
                    }

                    $this->htmlElement .= '</tr>'.PHP_EOL;                
            }

           $this->htmlElement .= '</tbody>'.PHP_EOL;
            
           $this->htmlElement .= '<tfoot>'.PHP_EOL;
                $this->htmlElement .= '<tr>';  
                $this->htmlElement .= '</tr>';           
           $this->htmlElement .= '</tfoot>'.PHP_EOL;
            
           $this->htmlElement .= '</table>'.PHP_EOL;  

           return true;
    }

    public function getElement() 
    {
        return $this->htmlElement;
    }

    public function GetPlugin($includeJquery = true) 
    {
        
        $this->addLink('js/ludo-jquery-treetable-3.2/css','jquery.treetable','css','text/css','stylesheet');
        $this->addLink('js/ludo-jquery-treetable-3.2/css','jquery.treetable.theme.default','css','text/css','stylesheet');
        //$this->addLink('js/ludo-jquery-treetable-3.2/css','screen','css','text/css','stylesheet');
        
        if($includeJquery)
        {
            $this->addScript('js','jquery-1.11.1.min','js','text/javascript');
        }    
        
        $this->addScript('js/ludo-jquery-treetable-3.2','jquery.treetable','js','text/javascript');
        $this->addScript('js/ludo-jquery-treetable-3.2','jquery.treetable-ajax-persist','js','text/javascript');
        $this->addScript('js/ludo-jquery-treetable-3.2','persist-min','js','text/javascript');
        $this->addScript('js','checkbox-controller','js','text/javascript');

        $this->createLudoTreeTableScript();
        
        return $this->htmlPlugin = $this->getLink().PHP_EOL.$this->getScript().PHP_EOL.$this->scriptjQuery.PHP_EOL.$this->htmlElement;
    }
}
