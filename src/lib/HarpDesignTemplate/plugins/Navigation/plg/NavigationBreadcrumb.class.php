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

//include_once(dirname(__DIR__).'/NavigationEnum.class.php');

class NavigationBreadcrumb extends Plugin
{    
    public  $Enum;
    private $requestConfiguration;
    private $styleSheet;
    private $listHtml;
    private $objectList;

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
        
        
        $this->listHtml = null;

        $this->objectList = new stdClass();                
    }
    
    private function getNavigationList()
    {
        return $this->DesignTemplatePlugin->getArguments()->offsetGet(NavigationEnum::NAVIGATION_LIST);
    }
    
    private function createList($listMenu,&$objectList)
    {
        $getParentId = $this->Enum->getMethod(NavigationEnum::PARENT_ID);
        $getId =  $this->Enum->getMethod(NavigationEnum::ID);
        $getName =  $this->Enum->getMethod(NavigationEnum::NAME);
        $getTitle = $this->Enum->getMethod(NavigationEnum::TITLE);        
        $getSubList =  $this->Enum->getMethod(NavigationEnum::SUB_LIST);
 
        foreach($listMenu as $v)
        {
            if($v instanceof EntityHandlerInterface)
            {       
                        if($v->{$getParentId}() == $this->Enum->getParentId() && !isset($objectList->{$v->$getId()}))
                        {
                            $this->listHtml .= '<li>'.($v->{$getSubList}()->count() < 1 ? '<a href="'.$this->Enum->getBaseUrl().'/'.$v->{$getName}().'">'.$v->{$getTitle}().'</a>' : '<a href="#">'.$v->{$getTitle}().'</a>').PHP_EOL;
                           
                            $objectList->{$v->{$getId}()} = new stdClass();
                            $objectList->{$v->{$getId}()}->data = Array(NavigationEnum::ID => $v->{$getId}(),NavigationEnum::PARENT_ID => $v->{$getParentId}(),NavigationEnum::NAME => $v->{$getName}());
                            $objectList->{$v->{$getId}()}->children = Array();
                            $objectList->{$v->{$getId}()}->relativeUrl = $v->{$getName}();


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
                                    $link =  ($v->{$getSubList}()->count() < 1 ? '<a href="'.$this->Enum->getBaseUrl().'/'.$objectList->relativeUrl.'/'.$v->{$getName}().'">'.$v->{$getTitle}().'</a>' :  '<a href="#">'.$v->{$getTitle}().'</a>');
                                    
                                    $this->listHtml .= '<li>'.$link.PHP_EOL;
                                    $b = new stdClass();
                                    $b->data = Array(NavigationEnum::ID => $v->{$getId}(),NavigationEnum::PARENT_ID => $v->{$getParentId}(),NavigationEnum::NAME => $v->{$getName}());
                                    $b->children = Array();                            
                                    $b->relativeUrl = $objectList->relativeUrl.'/'.$v->{$getName}();
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
    }
        
        
    public function create(Array $args = Array()) 
    {
        $id = isset($args['id']) ? $args['id'] : null; 
        
        $class = isset($args['class']) ? $args['class'] : null; 
        
        $NavigationList = $this->getNavigationList();
        
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
                
                $this->listHtml = '<nav '.(!empty($class) ? 'class="'.$class.'"' : null).'><ul '.(!empty($id) ? 'id="'.$id.'"' : null).'>'.PHP_EOL.$this->listHtml.'</ul></nav>';
                
                return true;
            }
        }
        
        return false;
    }

    public function setStyleSheet($styleSheetName)
    {
        $this->styleSheet = $styleSheetName;
    }
    
    public function getElement() 
    {
        return $this->listHtml;
    }

    public function getPlugin(Array $args = Array()) 
    {
        $element = null;
        
        if(!empty($this->styleSheet))
        {
            $element .= '<link rel="stylesheet" href="'.$this->DesignTemplatePlugin->getPluginUrl().'/css/plugin-amenu/amenu-1.css">';
        }

        
       $element .= '<link rel="stylesheet" href="'.$this->DesignTemplatePlugin->getPluginUrl().'/css/plugin-amenu/amenu-1.css">'; 
       $element .= '<script type="text/javascript" src="'.$this->DesignTemplatePlugin->getPluginUrl().'/lib/plugin-amenu/amenu.js"></script>';
       $element .= "<script language='JavaScript'>
        $(document).ready(function(){
			$('#amenu-list').amenu({
				'speed': 100,
				'animation': 'show'
			});
        });
        </script>";
        return $element.PHP_EOL.$this->listHtml;
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
    
    public function getGenericList()
    {
        return $this->objectList;
    }    
}
