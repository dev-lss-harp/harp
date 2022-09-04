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
use etc\HarpDesignTemplate\plugins\Plugin;

class HarpFilter extends Plugin 
{    
    private $scryptRunnable;
    private $settings;
    private $columnsField = Array();
    private $styleSheetName = 'theme-teal';
    const BASE_URL = 'baseUrl';
    const JSON_FILTER_DATA = 'jsonFilterData';
    const ALLOWED_RELATIONAL_OPERATORS = 'allowedRelationalOperators';
    
    private $logicalOperators = Array
    (
        'value' => Array('AND','OR'),
        'text' => Array('AND','OR'),
    );
    
    private $relationalOperators = Array
    (
        'value' => Array('=','>=','<','<=','LIKE','<>'),
        'text' => Array('=','>=','<','<=','LIKE','<>'),
    );
    
    private $id;
    
    public function __construct(DesignTemplatePlugin $DesignTemplatePlugin) 
    {
        parent::__construct($DesignTemplatePlugin);
      
        $this->DesignTemplatePlugin = $DesignTemplatePlugin;
        
        $this->specificArguments = $this->DesignTemplatePlugin->getArguments();
                
        $this->scryptRunnable = null;
    }
    
    public function setColumnsField(Array $columnsField)
    {
        $this->columnsField = $columnsField;
        
        return $this;
    }

    public function setLogicalOperators(Array $logicalOperators)
    {
        $this->logicalOperators = !empty($logicalOperators) ? $logicalOperators : $this->logicalOperators;
        
        return $this;
    }
    
    public function setRelationalOperators(Array $relationalOperators)
    {
        $this->relationalOperators = !empty($relationalOperators) ? $relationalOperators : $this->relationalOperators;
        
        return $this;
    }   
    
    public function setStyleSheetName($styleSheetName)
    {
        $this->styleSheetName = !empty($styleSheetName) ? $styleSheetName : $this->styleSheetName;
        
        return $this;
    }
    
    public function setSetting($key,$value)
    {
        if(!isset($this->settings[$key]))
        {
            $this->settings[$key] = $value;
        } 
    }
    
    public function getSettings()
    {
        return $this->settings;
    }

    private function createRunnableScript($documentReady = true)
    {   
        $this->scryptRunnable  = '<script type="text/javascript">'.PHP_EOL;
        $this->scryptRunnable .= $documentReady ? 'jQuery(document).ready(function(){'.PHP_EOL : null;

        $settings = $this->getSettings();
        
        if(!empty($settings))
        {
            $this->scryptRunnable .=    'jQuery("#'.$this->id.'").HarpFilter({'.PHP_EOL;
                            
                foreach($this->getSettings() as $k => $v)
                {
                    $this->scryptRunnable .= "'".$k."':'".$v."',".PHP_EOL;     
                }
                
            $this->scryptRunnable .=    '});'.PHP_EOL;
            
        }
        
        $this->scryptRunnable .= $documentReady ? '});'.PHP_EOL : null;
        $this->scryptRunnable .= '</script>'.PHP_EOL;
    }    
    
    public function create(Array $args = [])
    {
        $this->setSetting('baseUrl',$this->DesignTemplatePlugin->getBaseUrl());
        
        $id = isset($args['id']) ? $args['id'] : null;

        if(!empty($this->columnsField))
        {
            if(!empty($id))
            {
                $this->id = $id;
                
                $this->htmlElement   = '<div id="'.$id.'"  style="display:none;">';
                
                $this->htmlElement  .= '<div class="logicalOperator" style="padding:0;">';
                $this->htmlElement  .= '<select name="'.$this->id.'-logicalOperator">';
                foreach($this->logicalOperators['value'] as $k => $t)
                {
                    $this->htmlElement  .= '<option value="'.$this->logicalOperators['value'][$k].'"> '.$this->logicalOperators['text'][$k].'</option>';
                }
                $this->htmlElement  .= '</select>';
                $this->htmlElement  .= '</div>';
              //  $this->htmlElement  .= '</div>';
                

                $this->htmlElement  .= '<div class="filterField" style="padding:0;">';
                $this->htmlElement  .= '<select name="'.$this->id.'-filterField">';
                foreach($this->columnsField['value'] as $k => $t)
                {                    
                    if(!is_array($t))
                    {
                        $this->htmlElement  .= '<option value="'.$this->columnsField['value'][$k].'"'.(!empty($this->columnsField['class'][$k]) ? ' class="'.$this->columnsField['class'][$k].'"' : '').' '.(!empty($this->columnsField['type'][$k]) ? ' type="'.$this->columnsField['type'][$k].'"' : '').' >'.$this->columnsField['text'][$k].'</option>';
                    }
                    else if(count($t) == 2)
                    {
                        $this->htmlElement  .= '<option value="'.$this->columnsField['value'][$k][0].'"'.' data-second-column="'.$this->columnsField['value'][$k][1].'"'.(!empty($this->columnsField['class'][$k]) ? ' class="'.$this->columnsField['class'][$k].'"' : '').' '.(!empty($this->columnsField['type'][$k]) ? ' type="'.$this->columnsField['type'][$k].'"' : '').' >'.$this->columnsField['text'][$k].'</option>';
                    }
                    
                }                
                $this->htmlElement  .= '</select>';
                $this->htmlElement  .= '</div>';
                
                $this->htmlElement  .= '<div class="relationalOperator" style="padding:0;">';
                $this->htmlElement  .= '<select name="'.$this->id.'-RelationalOperator">';
                foreach($this->relationalOperators['value'] as $k => $t)
                {
                    $dataPosition = empty($this->relationalOperators['data-position'][$k]) ? '' : 'data-position="'.$this->relationalOperators['data-position'][$k].'"';

                    $this->htmlElement  .= '<option value="'.$this->relationalOperators['value'][$k].'" '.$dataPosition.'>'.$this->relationalOperators['text'][$k].'</option>';
                } 
                $this->htmlElement  .= '</select>';
                $this->htmlElement  .= '</div>';   
                $this->htmlElement  .= '</div>';
                $this->htmlElement  .= '</div>';
            }    
           //  echo $this->htmlElement;exit;
             $this->createRunnableScript();
             
             return true;
        }
        
        return false;
    }
    
    private function getRunnableScript()
    {
       return $this->scryptRunnable;     
    }    

    public function getElement() 
    {
        return $this->htmlElement;
    }

    public function getPlugin(Array $args = Array()) 
    {
       // $element  = '<script src="'.$this->DesignTemplatePlugin->getPluginUrl().'/js/HarpFilter.min.js" type="text/javascript"></script>';
        $element  = '<script src="'.$this->DesignTemplatePlugin->getPluginUrl().'/js/HarpFilter.js" type="text/javascript"></script>';
        $element .= $this->getRunnableScript();
        return $element .= $this->htmlElement;
    }    
}
