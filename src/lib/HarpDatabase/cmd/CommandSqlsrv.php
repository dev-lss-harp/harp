<?php
namespace Harp\lib\HarpDatabase\cmd;

class CommandSqlsrv extends CommandSql
{
    public function __construct(CommandParameter &$CommandParameter,CommandText &$CommandText)
    {
        parent::__construct($CommandParameter,$CommandText);
    }
    
    public function limit(Array $sLimits,Array $eLimits = Array())
    {
        try
        {
            if(!empty($sLimits))
            {
                foreach($sLimits as $key => $limit)
                {
                    $this->CommandParameter->addParameter($key,(int)$limit,CommandEnum::INT,CommandEnum::TYPE_BIGINT);
                }              
            }

            if(!empty($eLimits))
            {
                foreach($eLimits as $key => $limit)
                {
                    $this->CommandParameter->addParameter($key,(int)$limit,CommandEnum::INT,CommandEnum::TYPE_BIGINT);
                }              
            }

            $this->CommandParameter->commitAll();
        }
        catch (Exception $ex)
        {
            throw $ex;
        }
    }    
      
     public function offset(Array $offsets)
     {       
        try
        {
            foreach($offsets as $key => $offset)
            {
                $this->CommandParameter->addParameter($key,(int) $offset,CommandEnum::INT,CommandEnum::TYPE_BIGINT);
            }    

            $this->CommandParameter->commitAll();
        }
        catch (Exception $ex)
        {
            throw $ex;
        }         
     }         
}
