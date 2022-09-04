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
use etc\HarpDesignTemplate\plugins\PluginEnum;
use etc\HarpHttp\HarpHttpRequest\RequestEnum;
use etc\HarpDesignTemplate\plugins\Plugin;
use etc\HarpDesignTemplate\plugins\Navigation\NavigationEnum;
use bin\ext\HarpHandler\EntityHandlerInterface;

class NavigationMenu extends Plugin
{    
    public  $Enum;
    private $requestConfiguration;
    private $styleSheet;
    private $listHtml;
    private $objectList;
    private $id;
    private $class;
    private $classLi;
    private $scryptRunnable;
    private $icons;
    private $settings = Array(); 
    private $parentTag = null;
    private $disableIcons = false;

    public function __construct(DesignTemplatePlugin $DesignTemplatePlugin) 
    {
        parent::__construct($DesignTemplatePlugin);

        if(!$this->DesignTemplatePlugin->getArguments()->offsetExists(NavigationEnum::NAVIGATION_LIST))
        {
            throw new Exception('missing argument {'.NavigationEnum::NAVIGATION_LIST.'}!'); 
        }
        else if(!$this->DesignTemplatePlugin->getArguments()->offsetExists(PluginEnum::REQUEST_CONFIGURATION))
        {
            throw new Exception('missing argument {'.PluginEnum::REQUEST_CONFIGURATION.'}!'); 
        }

        $this->requestConfiguration = $this->DesignTemplatePlugin->getArguments()->offsetGet(PluginEnum::REQUEST_CONFIGURATION);
        
        $this->Enum = new NavigationEnum();
        $this->Enum->addDefaultMethods();
        $this->Enum->setBaseUrl($this->requestConfiguration->offsetGet(RequestEnum::HTTP_URL_APPLICATION));
        
        $this->id = NavigationEnum::DEFAULT_ID;
        
        $this->class = NavigationEnum::DEFAULT_CLASS;
        
        $this->listHtml = null;

        $this->objectList = new stdClass();                
    }
    
    public function setElementId($id)
    {
        $this->id = !empty($id) ? $id : $this->id;
    }
    
    public function getElementId()
    {
        return $this->id;
    }
    
    public function setElementClass($class)
    {
        $this->class = !empty($class) ? $class : $this->class;
    }
    
    public function setIcons(Array $icons)
    {
        $this->icons = $icons;
    }
    
    public function getElementClass()
    {
        return $this->class;
    }    

    public function setLiTagClass($class)
    {
        $this->classLi = !empty($class) ? $class : $this->classLi;
    }
    
    public function getLiTagClass()
    {
        return $this->classLi;
    }        
    public function useParentTag($tag)
    {
        $this->parentTag = is_string($tag) ? preg_replace('`[<][>]`',null,$tag,1) : $this->parentTag;
    }
        
    public function bindjQuery($urlFile)
    {
        $this->unshiftScriptFile($urlFile);
    }


    private function getNavigationList()
    {
        return $this->DesignTemplatePlugin->getArguments()->offsetGet(NavigationEnum::NAVIGATION_LIST);
    }
    
    public function disabledIcons($s = false)
    {
        $this->disableIcons = (bool)$s;
    }
    
    private function createList($listMenu,&$objectList)
    {
        $getParentId = $this->Enum->getMethod(NavigationEnum::PARENT_ID);
        $getId =  $this->Enum->getMethod(NavigationEnum::ID);
        $getName =  $this->Enum->getMethod(NavigationEnum::NAME);
        $getIcon =  $this->Enum->getMethod(NavigationEnum::ICON);
        $getTitle = $this->Enum->getMethod(NavigationEnum::TITLE);        
        $getSubList =  $this->Enum->getMethod(NavigationEnum::SUB_LIST);
        $getSituation = $this->Enum->getMethod(NavigationEnum::SITUATION);  
        $getType = $this->Enum->getMethod(NavigationEnum::TYPE); 

        foreach($listMenu as $v)
        { 
            if($v->{$getSituation}() == 1 && $v instanceof EntityHandlerInterface)
            {       
                    
                        if($v->{$getParentId}() == $this->Enum->getParentId() && !isset($objectList->{$v->$getId()}))
                        {
                            $icon = '';

                            if($v->{$getIcon}() != '' && !$this->disableIcons)
                            {
                                $icon = '<img src="'.$this->DesignTemplatePlugin->getPluginUrl().'/img/'.$v->{$getIcon}().'">';
                            }
                            
                            $this->listHtml .= '<li class="'.$this->getLiTagClass().'">'.((($v->{$getSubList}()->count() < 1 && $v->{$getType}() != -1) || $v->{$getType}() == 2)  ? '<a href="'.$this->Enum->getBaseUrl().'/'.$v->{$getName}().'">'.$icon.'<span>'.$v->{$getTitle}().'</span></a>' : '<a href="#">'.$icon.'<span>'.$v->{$getTitle}().'</span></a>').PHP_EOL;
                            
                            $objectList->{$v->{$getId}()} = new stdClass();
                            $objectList->{$v->{$getId}()}->data = Array(NavigationEnum::ID => $v->{$getId}(),NavigationEnum::PARENT_ID => $v->{$getParentId}(),NavigationEnum::NAME => $v->{$getName}());
                            $objectList->{$v->{$getId}()}->children = Array();
                            
                            $objectList->{$v->{$getId}()}->relativeUrl = ($v->{$getType}() != -1) ? $v->{$getName}() : '';

                            if($v->getListaPermissoes()->count() > 0)
                            {
                               
                                $this->listHtml .= '<ul>'.PHP_EOL;
                                $this->createList($v->getListaPermissoes(),$objectList->{$v->$getId()});   
                                $this->listHtml .= '</ul>'.PHP_EOL;
                            }

                            $this->listHtml .= '</li>'.PHP_EOL;

                        }
                        else
                        {
                                if($objectList->data[NavigationEnum::ID] == $v->{$getParentId}())
                                {
                                    $link =  ((($v->{$getSubList}()->count() < 1 && $v->{$getType}() != -1) || $v->{$getType}() == 2)  ? '<a href="'.$this->Enum->getBaseUrl().'/'.$objectList->relativeUrl.'/'.$v->{$getName}().'">'.$v->{$getTitle}().'</a>' :  '<a href="#">'.$v->{$getTitle}().'</a>');
                                    $this->listHtml .= '<li class="'.$this->getLiTagClass().'">'.$link.PHP_EOL;
                                    $b = new stdClass();
                                    $b->data = Array(NavigationEnum::ID => $v->{$getId}(),NavigationEnum::PARENT_ID => $v->{$getParentId}(),NavigationEnum::NAME => $v->{$getName}());
                                    $b->children = Array();                            
                                    $b->relativeUrl = ($v->{$getType}() != -1) ? (!empty($objectList->relativeUrl) ? $objectList->relativeUrl.'/'.$v->{$getName}() : $v->{$getName}()): $objectList->relativeUrl;

                                    $objectList->children[$v->{$getId}()] = $b;
                                    
                                }

                                if($v->getListaPermissoes()->count() > 0)
                                {                      
                                    $this->listHtml .= '<ul>'.PHP_EOL;
                                    $this->createList($v->getListaPermissoes(),$objectList->children[$v->{$getId}()]);
                                    $this->listHtml .= '</ul>'.PHP_EOL;
                                }

                                $this->listHtml .= '</li>'.PHP_EOL;
                        }                    
                
            }
        }
        
        //echo $this->listHtml;exit;
    }
    
        
    public function create(Array $args = Array()) 
    { 
        $NavigationList = $this->getNavigationList();
        //echo '<pre>';print_r($NavigationList);exit;
        if(!$NavigationList instanceof ArrayObject)
        {
            throw new Exception('Menu Container is not the type ArrayObject!'); 
        }
        
        $k = key($NavigationList);
    
        if(!empty($k))
        {
            $EntityHandler = $NavigationList->offsetGet($k);
            
            if($EntityHandler instanceof EntityHandlerInterface)
            {
                
                foreach($this->Enum->getMethods() as $k => $m)
                {
                    if(!method_exists($EntityHandler,$m))
                    {
                        throw new Exception('Wrong method set to: {'.$k.'}, method not exists in: '.get_class($EntityHandler)); 
                    }
                }

                $this->listHtml = null;
           
                $this->createList($NavigationList,$this->objectList);   

                if(empty($this->parentTag))
                {
                    $this->listHtml = '<ul id="'.$this->id.'" class="'.$this->class.'">'.$this->listHtml.'</ul>';
                }
                else
                {
                    $this->listHtml = '<'.$this->parentTag.' id="'.$this->id.'"'.' class="'.$this->class.'"><ul>'.$this->listHtml.'</ul></'.$this->parentTag.'>';
                }    

                return true;
            }
        }
        
        return false;
    }

    public function setStyleSheet($styleSheetName)
    {
        $this->styleSheet = $styleSheetName;
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

    public function createRunnableScript($pluginName,$identifierElementBind)
    {        
        $this->scryptRunnable  = '<script type="text/javascript">'.PHP_EOL;
        $this->scryptRunnable .= 'jQuery(document).ready(function(){'.PHP_EOL;
        
        
        if($this->getElementId() == $identifierElementBind)
        {
            $identifierElementBind = '#'.$identifierElementBind;
        }
        else
        {
            $identifierElementBind = '.'.$identifierElementBind;
        }

        $settings = $this->getSettings();
        
        if(!empty($settings))
        {
            $this->scryptRunnable .=    'jQuery("'.$identifierElementBind.'").'.$pluginName.'({'.PHP_EOL;
                            
                foreach($this->getSettings() as $k => $v)
                {
                     $this->scryptRunnable .= "'".$k."':'".$v."',".PHP_EOL;    
                }
            //remove quebras de linhas e a última vírgula.        
           // $this->scryptRunnable = substr($this->scryptRunnable,0,-3);     

            $this->scryptRunnable .=    '});'.PHP_EOL;
            
        }
        else
        {
            $this->scryptRunnable .=    'jQuery("'.$identifierElementBind.'").'.$pluginName.'();'.PHP_EOL;
        }
        
        $this->scryptRunnable .= '});'.PHP_EOL;
        $this->scryptRunnable .= '</script>'.PHP_EOL;
        //echo $this->scryptRunnable ;exit;
    }    
    
    private function getRunnableScript()
    {
       return $this->scryptRunnable;     
    } 

    public function getGenericList()
    {
        return $this->objectList;
    }      
    
    public function getElement() 
    {
        return $this->listHtml;
    }   
    
    public function getPlugin(Array $args = Array()) 
    {
        
        $r = $this->getLinkedCss().
                $this->getLinkedScripts().
                    $this->getRunnableScript().
                                    $this->listHtml;
        
        return $r;
    }
  
}
