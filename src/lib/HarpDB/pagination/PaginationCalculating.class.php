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
namespace etc\HarpDatabase\pagination;

use ArrayObject;

class PaginationCalculating
{
    public $PaginationData;
    private $listPages;
    
    
    public function __construct(PaginationData &$PaginationData) 
    {
        $this->PaginationData = $PaginationData;
        $this->PaginationData->setFirstPage(1);
        $this->PaginationData->setLeftPage(($this->PaginationData->getCurrentPage() - $this->PaginationData->getSideDelimiter()) < 1 ? 1 : ($this->PaginationData->getCurrentPage() - $this->PaginationData->getSideDelimiter()));
     //  echo $this->PaginationData->getSideDelimiter();exit;
       // echo $this->PaginationData->getCurrentPage();exit;
       // echo ($this->PaginationData->getCurrentPage() + $this->PaginationData->getSideDelimiter()) > $this->PaginationData->getTotalPages() ? $this->PaginationData->getTotalPages() : ($this->PaginationData->getCurrentPage() + $this->PaginationData->getSideDelimiter());exit;
        
        $this->PaginationData->setRightPage(($this->PaginationData->getCurrentPage() + $this->PaginationData->getSideDelimiter()) > $this->PaginationData->getTotalPages() ? $this->PaginationData->getTotalPages() : ($this->PaginationData->getCurrentPage() + $this->PaginationData->getSideDelimiter()));
        $this->PaginationData->setLastPage($this->PaginationData->getTotalPages());
        $this->PaginationData->setNextPage(($this->PaginationData->getCurrentPage() + 1) > $this->PaginationData->getTotalPages() ? $this->PaginationData->getTotalPages() : ($this->PaginationData->getCurrentPage() + 1));
        $this->PaginationData->setPreviousPage(($this->PaginationData->getCurrentPage() - 1) < 1 ? 1 : ($this->PaginationData->getCurrentPage() - 1));
        $this->PaginationData->setCurrentPage(($this->PaginationData->getTotalPages() < 1) ? 0 : $this->PaginationData->getCurrentPage());        
        
        $this->listPages = new ArrayObject(Array(),ArrayObject::ARRAY_AS_PROPS);
    }
    
    public function createPageList()
    {
        for($i = $this->PaginationData->getFirstPage();$i <= $this->PaginationData->getRightPage();++$i)
        {
            $pageInfo = Array
            (
                'page' => $i,
            );
            
            $this->listPages->offsetSet($i,$pageInfo);
        }
    }
    
    public function getListPage()
    {
          return $this->listPages;
    }
}
