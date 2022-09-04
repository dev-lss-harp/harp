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

class TableTree extends Plugin
{    
    const DEFAULT_ID = 'jQueryTreeGrid';
    const DEFAULT_CLASS = 'jQueryTreeGrid';
    const DEFAULT_KEY_PARENT = 'idParent';
    const DEFAULT_KEY = 'id';
    const TYPE_BIND_ID = 0;
    const TYPE_BIND_CLASS = 1;
    
    protected $scriptRunnable;
    protected $id;
    protected $class;
    protected $list;
    protected $keyParent;
    protected $key;    
    protected $additionalFields = Array();

    
    protected $settings = Array
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
    }    
    
    public function setId($class)
    {
        $this->class = $class;
        
        return $this;
    }
    
    public function setClass($id)
    {
        $this->id = $id;
        
        return $this;
    }   
    
    public function getId()
    {
        return $this->id;
    }
    
    public function getClass()
    {
        return $this->class;
    }
    
    
    public function setPluginSettgins(Array $settings = Array())
    {
        $this->settings = $settings;
    }
    
    public function getPluginSettings()
    {
        return $this->settings;
    }
        
    public function setList(Array $list)
    {
        $this->list = $list;
    }
    
    public function setAdditionalFields(Array $fields)
    {
        $this->additionalFields = $fields;
    }
    
    public function getAdditionalFields()
    {
        return $this->additionalFields;
    }
        
    /**
     * @param type $parent
     * @param type $listTree
     * @param type $listByParents
     * 
     * Este método organiza a árvore de pais e filhos de modo que todos os filhos independentemente de usa profundidade
     * sejam colocados logo abaixo de seus pais diretos.
     * A ordenação é realizada levando-se em consideração o id do pai.
     */
    protected function createStructureTree($parent,$listTree,&$listByParents = Array())
    {
        if(array_key_exists($this->keyParent,$parent) && array_key_exists($this->key,$parent))
        {
            //id do pai
            $idParent = $parent[$this->key];
            //o nó é adicionado a lista mesmo que ele seja o raiz
            $listByParents[$idParent] = $parent;
            //Toda a lista é percorrida
            foreach($listTree as $v)
            {
                //pega o id parent para este nó filho
                $idParentChildren = $v[$this->keyParent];
                //verifica se este nó é filho do pai atual
                if($idParent == $idParentChildren)
                {
                    //o nó atual pode ser um pai então novamente chama-se a função para determinar os filhos deste nó
                    $this->createStructureTree($v,$listTree,$listByParents);
                }
            }  
        }    
    } 
    
    public function setKeyParentElement($key)
    {
        $this->keyParent = is_string($key) ? $key : self::DEFAULT_KEY_PARENT;
        
        return $this;
    }
    
    public function setKeyElement($key)
    {
        $this->key = is_string($key) ? $key : self::DEFAULT_KEY;
        
        return $this;
    }
    
    public function create() {}

    public function getPlugin(){}
}
