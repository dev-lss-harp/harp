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

class TableTreeCheckbox extends TableTree
{        

    private $htmlTable;
    private $elementCheckboxName;
    private $keyTitle;
    private $checkboxValues = Array();

    public function __construct(DesignTemplatePlugin $DesignTemplatePlugin) 
    {
        parent::__construct($DesignTemplatePlugin);
    }
    
    
    public function setCheckboxValuesChecked($checkboxValues)
    {
        if(is_array($checkboxValues) || is_object($checkboxValues))
        {
            $checkboxValues = (Array)$checkboxValues;
            
            $this->checkboxValues = $checkboxValues;
        }
    }
    
    public function getCheckboxValuesChecked()
    {
        return $this->checkboxValues;
    }
    
    public function setNameElementCheckbox($elementCheckboxName)
    {
        $this->elementCheckboxName = !empty($elementCheckboxName) ? $elementCheckboxName : $this->elementCheckboxName;
        
        return $this;
    }
    
    public function getNameElementCheckbox()
    {
        return $this->elementCheckboxName;
    }  
    
    public function setKeyTitle($key)
    {
        $this->keyTitle = $key;
        
        return $this;
    }    

    public function createRunnableScript()
    {        
        $identifierBind = $identifierBind = '#'.$this->getId();
        
        if(empty($identifierBind))
        {
            $identifierBind = '.'.$this->getClass();
        }
        
        $scriptRunnable  = '<script type="text/javascript">'.PHP_EOL;
        $scriptRunnable .= 'jQuery(document).ready(function(){'.PHP_EOL;
        
        $settings = $this->getPluginSettings();
        
        if(!empty($settings))
        {
            $scriptRunnable .=    'jQuery("'.$identifierBind.'").treegrid({'.PHP_EOL;
                            
                foreach($settings as $k => $v)
                {
                     $scriptRunnable .= "'".$k."':'".$v."',".PHP_EOL;    
                }   

            $scriptRunnable .=    '});'.PHP_EOL;
            
        }
        else
        {
            $scriptRunnable .=    'jQuery("'.$identifierBind.'").treegrid();'.PHP_EOL;           
        }
        
        $scriptRunnable .= '});'.PHP_EOL;
        $scriptRunnable .= '</script>'.PHP_EOL;
        
        $this->scriptRunnable =  $scriptRunnable;
    }
            
    protected function createTbody($args = Array())
    {        
        $tBody = ''; 
        
        $listByParents = Array();
              //  echo '<pre>';   print_r($this->list);exit;
        //somente os nós raízes são passados para o método createStructureTree 
        foreach($this->list as $v)
        {
            if(!empty($v[$this->keyParent]))
            {
                continue;
            }
            
            $this->createStructureTree($v,$this->list,$listByParents);
        }
           
        $listCheckboxChecked = $this->getCheckboxValuesChecked();

        foreach($listByParents as $v)
        {            
            $kParent = empty($v[$this->keyParent]) ? 0 : $v[$this->keyParent]; 
            $additionalClass = $kParent != 0 ? 'treegrid-parent-'.$kParent : '';
            $checkboxClass = isset($args['checkboxClass']) ? 'class="checkbox_pluginTableTree '.$args['checkboxClass'].'"' : 'class="checkbox_pluginTableTree"';
            
            $value = $v[$this->key];
            $title = $v[$this->keyTitle];
            
            $checked = '';
            
            if(in_array($value,$listCheckboxChecked))
            {
                $checked = 'checked';
            }
            
            $tBody.= '<tr class="treegrid-'.$v[$this->key].' '.$additionalClass.'">';
            $tBody.= '<td><input type="checkbox" id="checkbox_'.$value.'" data-parent-id="'.$kParent.'" name="'.$this->getNameElementCheckbox().'['.$value.']"  value="'.$value.'" '.$checkboxClass.' '.$checked.'><label for="checkbox_'.$value.'">'.$title.'</label></td>';
            foreach($this->getAdditionalFields() as $f)
            {
                if(isset($v[$f]))
                {
                     $tBody.= '<td>'.$v[$f].'</td>';
                }
            }
            $tBody.= '</tr>';
        }

        return $tBody;

    }    
        
    public function create($args = Array())
    {
        if(!empty($this->keyTitle) && !empty($this->key) && !empty($this->keyParent))
        {    
            $this->setId(!is_string($this->id) ? self::DEFAULT_ID : $this->id);
            $this->setClass(!is_string($this->id) ? self::DEFAULT_CLASS :$this->id);
            $this->htmlTable = '<table id="'.$this->getId().'" class="'.$this->getClass().'">'.PHP_EOL;
                $this->htmlTable .= $this->createTbody($args);
            $this->htmlTable .= '</table>'.PHP_EOL;
            $this->createRunnableScript($this->id);
            
             return $this;
        }
        
        throw new Exception('key, keyParent, and keyTitle parameters were not set!');
    }
    
    public function getPlugin() 
    {
        $snippet  = $this->bindCss($this->DesignTemplatePlugin->getPluginUrl().'/lib/maxazan-jquery-treegrid/css/jquery.treegrid.css');
        $snippet .= $this->bindScript($this->DesignTemplatePlugin->getPluginUrl().'/lib/maxazan-jquery-treegrid/js/jquery.treegrid.min.js');
        $snippet .= $this->bindScript($this->DesignTemplatePlugin->getPluginUrl().'/lib/maxazan-jquery-treegrid/js/jquery.cookie.js');
        $snippet .= $this->bindScript($this->DesignTemplatePlugin->getPluginUrl().'/js/checkbox.js');
        $snippet .= $this->scriptRunnable;
        $snippet .= $this->htmlTable;
     
        return $snippet;
    }         
}
