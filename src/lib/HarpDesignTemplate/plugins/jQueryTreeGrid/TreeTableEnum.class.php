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
namespace etc\HarpDesignTemplate\plugins\TreeTable;

use bin\enm\HarpEnum;

class TreeTableEnum extends HarpEnum
{
    const PARAMETRO_PERMISSOES = 'permissoes';
    const PARAMETRO_CONFIGURACOES = 'configuracoes';
    const DADOS_MODULO = 'modulos';
    const DADOS_GRUPO = 'grupo';
    const DADOS_ACESSO = 'acesso';
    
    public static function get($key)
    {
        $Enum = new TreeTableEnum($key);
        
        return $Enum->getValue();
    }
}



