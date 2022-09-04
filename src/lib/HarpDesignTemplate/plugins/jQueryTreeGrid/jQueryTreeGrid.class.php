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
use etc\HarpDesignTemplate\plugins\DesignTemplatePlugin;
use etc\HarpDesignTemplate\plugins\PluginElementEnum;

class jQueryTreeGrid extends AbstractPluginElement 
{    
    const DEFAULT_ID = 'jQueryTreeGrid';
    const DEFAULT_CLASS = 'jQueryTreeGrid';
    const TYPE_BIND_ID = 0;
    const TYPE_BIND_CLASS = 1;
    
    private $scryptRunnable;
    private $id;
    private $class;
    private $typeBindIdentify; 
    private $settings = Array
    (
        'treeColumn' => '0',
        'initialState' => 'expanded',
        'saveState' => 'false',
        'saveStateMethod' => 'cookie',
        'saveStateName' => 'tree-grid-state',
        'expanderTemplate' => '<span class="treegrid-expander"></span>',
        'expanderExpandedClass' => 'treegrid-expander-expanded',
        'expanderCollapsedClass' => 'treegrid-expander-collapsed',
        'indentTemplate' => '<span class="treegrid-indent"></span>',
        'onCollapse' => 'null',
        'onExpand' => 'null',
        'onChange' => 'null',
    );

    public function __construct(DesignTemplatePlugin $DesignTemplatePlugin) 
    {
        parent::__construct($DesignTemplatePlugin);
      
        $this->DesignTemplatePlugin = $DesignTemplatePlugin;
         // echo '<pre>';print_r($this->DesignTemplatePlugin->getArguments());exit;
      //  $this->specificArguments = $this->DesignTemplatePlugin->getSpecificArguments();
        
        $this->specificArguments = $this->DesignTemplatePlugin->getArguments();
        
      //  echo count($this->specificArguments);exit;
        if(count($this->specificArguments) != 5)
        {
            throw new Exception('É Necessário passar exatamente '.(count($this->specificArguments)).' argumentos para utilizar este plugin!'); 
        }
        
        $this->scryptRunnable = null;
        
        $this->id = self::DEFAULT_ID;
        
        $this->class = self::DEFAULT_CLASS;
        
        $this->typeBindIdentify = self::TYPE_BIND_CLASS;
        
       // $this->AuthorizedModules = Array();
        
       // $this->scriptjQueryBody = null;
        
      //  $this->eventJstreeChecked = null;
       // echo '<pre>';print_r($this->DesignTemplatePlugin->getArguments());exit;
      //  $this->Enum = new NavigationEnum();
      
        //$this->Permissoes = $this->specificArguments[0];
         
       // $this->RequestInformation = $this->specificArguments[1];
        //echo '<pre>';print_r($this->Permissoes);exit;
      //  $this->menuItens[NavigationEnum::ROOT_TREE] = Array();
    }
    

    
    private function createLudojQueryTreeGridScript()
    {
        $this->scriptjQuery   = '<script type="text/javascript">';
        $this->scriptjQuery  .= 'jQuery(document).ready(function(){';
            $this->scriptjQuery .= 'jQuery("'.$this->identifierElement.'").agikijQueryTreeGrid({';
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
      //  $this->setEventChange($destinationElementId);
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
    
    private function createRunnableScript()
    {
        $this->scryptRunnable  = '<script type="text/javascript">'.PHP_EOL;
        $this->scryptRunnable .= 'jQuery(document).ready(function(){'.PHP_EOL;
        
        if(!empty($this->getSettings()))
        {
            $this->scryptRunnable .=    'jQuery("'.($this->getTypeBindIdentify() == self::TYPE_BIND_CLASS ? '.'.$this->getBindClass() : '#'.$this->getBindId()).'").treegrid({'.PHP_EOL;
                            
                foreach($this->getSettings() as $k => $v)
                {
                     $this->scryptRunnable .= "'".$k."':'".$v."',".PHP_EOL;    
                }
            //remove quebras de linhas e a última vírgula.        
            $this->scryptRunnable = substr($this->scryptRunnable,0,-3);     

            $this->scryptRunnable .=    '});'.PHP_EOL;
            
        }
        else
        {
            $this->scryptRunnable .=    'jQuery("'.($this->getTypeBindIdentify() == self::TYPE_BIND_CLASS ? '.'.$this->getBindClass() : '#'.$this->getBindId()).'").treegrid();'.PHP_EOL;           
        }
        
        $this->scryptRunnable .= '});'.PHP_EOL;
        $this->scryptRunnable .= '</script>'.PHP_EOL;
    }
    
    
    private function getRunnableScript()
    {
       return $this->scryptRunnable;     
    }
    
    public function setSetting($key,$value)
    {
        if(isset($this->settings[$key]))
        {
            $this->settings[$key] = $value;
        } 
    }
    
    public function getSettings()
    {
        return $this->settings;
    }
    
    public function setTypeBindIdentify($typeBindIdentify = self::TYPE_BIND_CLASS)
    {
        $this->typeBindIdentify = $typeBindIdentify == self::TYPE_BIND_CLASS ? $typeBindIdentify : self::TYPE_BIND_ID;
    }
    
    public function getTypeBindIdentify()
    {
        return $this->typeBindIdentify;
    }
    
    public function getBindId()
    {
        return $this->id;
    }
    
    public function getBindClass()
    {
        return $this->class;
    }

   // public function setOptionPlugin($option)
   // {//print_r($option);exit;
      //  $this->scriptjQueryBody .= $option.PHP_EOL;
   // }
    
    public function create(Array $collectionArray,Array $columns = Array(),Array $keyLines = Array(),$id = null,$class = null)
    {
        $this->id = empty($id) ? $this->id : $id;
        $this->class = empty($class) ? $this->class : $class;
        
        $id =  'id="'.$this->id.'"';
        $class = 'class="'.$this->class.'"';

        $this->htmlElement = '<table '.$id.' '.$class.' style="text-align:left;">'.PHP_EOL;
            foreach($collectionArray as $v)
            {
                $this->htmlElement .= '<tr class="treegrid-'.$v['id'].' '.($v['parent_id'] != 0 ? 'treegrid-parent-'.$v['parent_id'] : '').'">'.PHP_EOL;
                    foreach($keyLines as $l)
                    {
                      $this->htmlElement .= '<td>'.$v[$l].'</td>'.PHP_EOL;
                    }
                $this->htmlElement .= '</tr>'.PHP_EOL;
            }
        $this->htmlElement .= '</table>'.PHP_EOL;
        
        $this->createRunnableScript();

        return true;
    }

    public function getElement() 
    {
        return $this->htmlElement;
    }

    public function getPlugin($includeJquery = true) 
    {
        $this->addLink('lib/maxazan-jquery-treegrid/css','jquery.treegrid','css',PluginElementEnum::DEFAULT_TYPE_LINK);

        if($includeJquery)
        {
           $this->addScript('js','jquery-1.11.1.min','js','text/javascript');
        }    

        $this->addScript('lib/maxazan-jquery-treegrid/js','jquery.treegrid.min','js',  PluginElementEnum::DEFAULT_TYPE_JS,  PluginElementEnum::SCRIPT);
        $this->addScript('lib/maxazan-jquery-treegrid/js','jquery.cookie','js',  PluginElementEnum::DEFAULT_TYPE_JS,  PluginElementEnum::SCRIPT);
        
       return $this->htmlPlugin .= $this->getLink().PHP_EOL.$this->getScript().PHP_EOL.$this->getRunnableScript().PHP_EOL.$this->htmlElement;
        
    }
    
    public function build($method = 'runnable') 
    {
         $this->DesignTemplatePlugin->build($method);

         return $this;
    }
    
    public function run()
    {
        return $this->DesignTemplatePlugin->run();
    }
    
    
}
