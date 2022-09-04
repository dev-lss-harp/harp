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
class HarpPluginAccordion implements IHarpPlugin
{    
    const NXEED_TREE_MENU_CSS = 'ntm';
    const NXEED_TREE_MENU_JS = 'jquery.ntm';
    const PATH_BASE_PLUGIN_CSS = 'js/EasyTree/skin-win8/ui.easytree';
    const PATH_BASE_PLUGIN_JS = 'js/EasyTree/jquery.easytree.min';
    const JS_RENDER = 'HarpPluginTreeList';
    private $TreeList;
    private $TreeListHtml;
    private $Element;
    private $Libs;
    private $Path;
    private $PathBase;
    private $Url;
    private $UrlBase;
    
    private $ParentNode;


    public function __construct($Template,$Path,$Url) 
    {
        $this->Template = &$Template;

        $this->Path = $Path.'/HarpPluginTreeList';
        
        $this->PathBase = $Path;
        
        $this->Url = $Url.'/HarpPluginTreeList';
        
        $this->UrlBase = $Url;

        $this->TreeList = Array();
        
        $this->TreeListHtml = null;
        //$this->Libs .= '<script type="text/javascript" src="'.$this->Url.'/js/Nxeed-Tree-Menu/lib/jquery.ntm/js/'.self::NXEED_TREE_MENU_JS.'.js"></script>'.PHP_EOL;
        $this->Libs = null;
        
        //Nó Pai
        $this->ParentNode = '0.0';
                
        clearstatcache();
    }
    
    public function AddNode($Id,$ParentId,Array $Elements = Array())
    {
        $ParentId = ($ParentId == null || $ParentId == 0) ? '0.0' : $ParentId;
        
        if(!isset($this->TreeList[$ParentId]) && $ParentId != $Id)
        {
            $this->TreeList[$ParentId] = Array();
            $this->Element[$ParentId] = Array();
        }
        
        if(!isset($this->TreeList[$ParentId][$Id]))
        {
            $this->TreeList[$ParentId][$Id] = Array
            (
                'Id' => $Id,
                'ParentId' => $ParentId,
            );
            
            $this->Element[$ParentId][$Id] = $Elements;
        }
    }
    
    
    
    public function LinkJQuery($Version = IHarpPlugin::JQUERY_VERSION_DEFAULT)
    {
        clearstatcache();

        if(file_exists($this->PathBase.'/'.IHarpPlugin::DIRECTORY_LIBS.'/'.IHarpPlugin::DIRECTORY_JS.'/jquery-'.$Version.'.min.js'))
        {
            $this->Libs .= '<script type="text/javascript" src="'.$this->UrlBase.'/'.IHarpPlugin::DIRECTORY_LIBS.'/'.IHarpPlugin::DIRECTORY_JS.'/jquery-'.$Version.'.min.js"></script>'.PHP_EOL;
        
            return true;
        }
        
        return false;
    }
    
    public function LinkCss($FileName = self::NXEED_TREE_MENU_CSS)
    {
        if(!empty($FileName))
        {    
            clearstatcache();
            
            if(file_exists($this->Path.'/css/'.$FileName.'.css'))
            {
                $this->Libs .= '<link href="'.$this->Url.'/css/'.$FileName.'.css" rel="stylesheet" type="text/css" />'.PHP_EOL;
                
                return true;
            }            
        }
        
        return false;
    }

    public function LinkJs($FileName = null)
    {        
        clearstatcache(); 
        
        if(file_exists($this->Path.'/js/'.$FileName.'.js'))
        {
            $this->Libs .= '<script type="text/javascript" src="'.$this->Url.'/js/'.$FileName.'.js"></script>'.PHP_EOL;

            return true;
        }          
        
        return false;
    }    
    
    public function Create($HtmlId = null,$HtmlClass = null)
    {     
        $RootId = '0.0';
        
        $ParentId = $RootId;
//echo '<pre>';print_r(each($this->TreeList[$RootId]));print_r($this->TreeList);exit;
        $ParentElements = Array();
        
        $this->TreeListHtml .= '<ul ';
        $this->TreeListHtml .= !empty($HtmlId) ? ' id="'.$HtmlId.'"' : null;
        $this->TreeListHtml .= !empty($HtmlClass) ? ' class="'.$HtmlClass.'"' : null;
        $this->TreeListHtml .= '>'.PHP_EOL;

        while(($v = each($this->TreeList[$ParentId])) || ($ParentId > $RootId))
        {
           if(!empty($v))
           {
                if(!empty($this->TreeList[$v['value']['Id']]))
                {
                    $this->TreeListHtml.= '<li class="HarpPluginTreeListUl">';
                  
                    if(is_array($this->Element[$v['value']['ParentId']][$v['value']['Id']]))
                    {
                        foreach($this->Element[$v['value']['ParentId']][$v['value']['Id']] as $el)
                        {
                            $this->TreeListHtml .= $el;
                        }
                    }    
                   // echo $ParentId;
                    $this->TreeListHtml.= '<ul>'.PHP_EOL;
//echo '<pre>';print_r($ParentElements);
                    //Adiciona O Id No Final Do Array
                    array_push($ParentElements, $ParentId);
                    
                 //   echo '<pre>';print_r($ParentElements);
                    
                    $ParentId = $v['value']['Id'];
                   // echo $ParentId;
                    
                }
                else
                {      
                    $this->TreeListHtml.= '<li>';
                    
                    if(is_array($this->Element[$v['value']['ParentId']][$v['value']['Id']]))
                    {
                        foreach($this->Element[$v['value']['ParentId']][$v['value']['Id']] as $el)
                        {
                            $this->TreeListHtml .= $el;
                        }
                    }  
                    
                    $this->TreeListHtml.= PHP_EOL.'</li>';
                }                  
            }
            else
            {
                  $this->TreeListHtml.= PHP_EOL.'</ul>';

                  //Acabou os Filhos Desse Pai Ele É Removido Do Array
                  $ParentId = array_pop($ParentElements);
                  
                 // echo '<pre>';print_r($ParentElements);
            }
           
                        
        }
       // echo $ParentId;exit;
       // echo '<pre>';print_r($this->TreeList);exit;
        
        $this->TreeListHtml .= PHP_EOL.'</ul>';    
      
    }
        
         
    public function Create2($HtmlId = null,$HtmlClass = null)
    {     
        $RootId = '0.0';
        
        $ParentId = $RootId;
echo '<pre>';print_r($this->TreeList);print_r(each($this->TreeList[$RootId]));exit;
        $ParentElements = Array();
        
        $this->TreeListHtml .= '<ul ';
       // $this->TreeListHtml .= !empty($HtmlId) ? ' id="'.$HtmlId.'"' : null;
        $this->TreeListHtml .= !empty($HtmlClass) ? ' class="'.$HtmlClass.'"' : null;
        $this->TreeListHtml .= '>'.PHP_EOL;

        while(($v = each($this->TreeList[$ParentId])) || ($ParentId > $RootId))
        {
           if(!empty($v))
            {
                if(!empty($this->TreeList[$v['value']['Id']]))
                {
                    $this->TreeListHtml.= '<li class="HarpPluginTreeListUl">';
                  
                    if(is_array($this->Element[$v['value']['ParentId']][$v['value']['Id']]))
                    {
                        foreach($this->Element[$v['value']['ParentId']][$v['value']['Id']] as $el)
                        {
                            $this->TreeListHtml .= $el;
                        }
                    }    
                    
                    $this->TreeListHtml.= '<ul>'.PHP_EOL;

                    //Adiciona O Id No Final Do Array
                    array_push($ParentElements, $ParentId);
                    
                    $ParentId = $v['value']['Id'];
                    
                }
                else
                {      
                    $this->TreeListHtml.= '<li>';
                    
                    if(is_array($this->Element[$v['value']['ParentId']][$v['value']['Id']]))
                    {
                        foreach($this->Element[$v['value']['ParentId']][$v['value']['Id']] as $el)
                        {
                            $this->TreeListHtml .= $el;
                        }
                    }  
                    
                    $this->TreeListHtml.= PHP_EOL.'</li>';
                }                  
            }
            else
            {
                  $this->TreeListHtml.= PHP_EOL.'</ul>';

                  //Acabou os Filhos Desse Pai Ele É Removido Do Array
                  $ParentId = array_pop($ParentElements);
            }
           
                        
        }
        
        $this->TreeListHtml .= PHP_EOL.'</ul>';
    }
    
    public function GetPureCreatedElement()
    {
        return empty($this->TreeListHtml) ? false : $this->TreeListHtml;
    }
    
    public function GetPlugin($ContainerClass = null)
    {       
        $this->Libs .= '<script type="text/javascript" src="'.$this->Url.'/js/jquery-cookie/jquery.cookie.js"></script>'.PHP_EOL;
        $this->Libs .= '<link rel="stylesheet" href="'.$this->Url.'/'.self::PATH_BASE_PLUGIN_CSS.'.css"/>'.PHP_EOL;
        $this->Libs .= '<script type="text/javascript" src="'.$this->Url.'/'.self::PATH_BASE_PLUGIN_JS.'.js"></script>'.PHP_EOL;
     
     //   echo $this->Libs.$this->TreeListHtml;exit;
        $this->Libs .= '<script type="text/javascript" src="'.$this->Url.'/js/'.self::JS_RENDER.'.js"></script>'.PHP_EOL;
        //echo $this->Libs.$this->TreeListHtml;exit;
        $Cls = empty($ContainerClass) ? null : 'class="'.$ContainerClass.'"';
            return $this->TreeListHtml;
        $this->TreeListHtml = '<div '.$Cls.'>'.$this->TreeListHtml.'</div>';
        $this->Libs = null;
      //  return $this->Libs.$this->TreeListHtml;
     
    }
    
     public function GetElement(){}
}
