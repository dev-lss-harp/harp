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

use Exception;
use ArrayObject;

class PaginationData
{
    private $totalLines = 6;
    private $currentPage = 1;
    private $limit = 6;
    private $sideDelimiter = 1;
    private $totalPages = 0;
    private $firstPage;
    private $leftPage;
    private $rightPage;
    private $nextPage;
    private $lastPage;
    private $previousPage;
    private $listPages;
    
    
    public function __construct() 
    {       
        $this->listPages = new ArrayObject(Array(),ArrayObject::ARRAY_AS_PROPS);
    }

    public function getTotalLines()
    {
        return $this->totalLines;
    }

    public function getCurrentPage()
    {
       return ($this->currentPage > $this->totalPages && $this->totalPages > 0)  ? $this->totalPages : ($this->currentPage < 1 ? 1 : (int) $this->currentPage);
    }

    public function getLimit()
    {
        return $this->limit;
    }
    
    public function getStartPage()
    {
        return ($this->getCurrentPage()  * $this->getLimit()) - $this->getLimit();
    }
    
    public function getEndPage()
    {
        return $this->getStartPage()  + $this->getLimit();
    }

    public function getSideDelimiter()
    {
        return $this->sideDelimiter;
    }
    
    public function getOffset()
    {
        return $this->getStartPage();
    }

    public function setTotalLines($totalLines)
    {
        $this->totalLines = $totalLines;
        return $this;
    }

    public function setCurrentPage($currentPage)
    {
        $this->currentPage = $currentPage;

        return $this;
    }

    public function setLimit($limit)
    {
        $this->limit = $limit > 0 ? $limit : $this->limit;
        
        return $this;
    }

    public function setSideDelimiter($sideDelimiter)
    {
        $this->sideDelimiter = $sideDelimiter;
        return $this;
    }
    
    public function getFirstPage()
    {
        return $this->firstPage;
    }

    public function getLeftPage()
    {
        return $this->leftPage;
    }

    public function getRightPage()
    {
        return $this->rightPage;
    }

    public function getNextPage()
    {
        return $this->nextPage;
    }

    public function getLastPage()
    {
        return $this->lastPage;
    }

    public function getPreviousPage()
    {
        return $this->previousPage;
    }

    public function setFirstPage($firstPage)
    {
        $this->firstPage = $firstPage;
        return $this;
    }

    public function setLeftPage($leftPage)
    {
        $this->leftPage = $leftPage;
        return $this;
    }

    public function setRightPage($rightPage)
    {
        $this->rightPage = $rightPage;
        return $this;
    }

    public function setNextPage($nextPage)
    {
        $this->nextPage = $nextPage;
        return $this;
    }

    public function setLastPage($lastPage)
    {
        $this->lastPage = $lastPage;
        return $this;
    }

    public function setPreviousPage($previousPage)
    {
        $this->previousPage = $previousPage;
        return $this;
    }
  
    public function getTotalPages()
    {
        try
        {
            if($this->getLimit() == 0)
            {
                throw new Exception('Division by zero');
            }
            
            $this->totalPages = ceil($this->getTotalLines()/$this->getLimit());
        }
        catch(Exception $e)
        {
            exit(print_r($e->getMessage()));
        }
        
        return $this->totalPages;
    }
    
    public function toArray()
    {
       $toArray = get_object_vars($this);
       
       unset($toArray['listPages']);
       
       return $toArray;
    }
    
    public function createPagination()
    {
        $this->setFirstPage(1);
        $this->setLeftPage(($this->getCurrentPage() - $this->getSideDelimiter()) < 1 ? 1 : ($this->getCurrentPage() - $this->getSideDelimiter()));
        $this->setRightPage(($this->getCurrentPage() + $this->getSideDelimiter()) > $this->getTotalPages() ? $this->getTotalPages() : ($this->getCurrentPage() + $this->getSideDelimiter()));
        $this->setLastPage($this->getTotalPages());
        $this->setNextPage(($this->getCurrentPage() + 1) > $this->getTotalPages() ? $this->getTotalPages() : ($this->getCurrentPage() + 1));
        $this->setPreviousPage(($this->getCurrentPage() - 1) < 1 ? 1 : ($this->getCurrentPage() - 1));
        $this->setCurrentPage(($this->getTotalPages() < 1) ? 0 : $this->getCurrentPage());         
        
        for($i = $this->getFirstPage();$i <= $this->getRightPage();++$i)
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
