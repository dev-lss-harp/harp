<?php
namespace Harp\lib\HarpDatabase\cmd;

use Exception;

class CommandMysqli extends CommandSql
{
    public function __construct(CommandParameter &$CommandParameter,CommandText &$CommandText)
    {
        parent::__construct($CommandParameter,$CommandText);
    }
    
    private function createLimit($val)
    {
            $key = uniqid();
            
            $this->CommandText->getCommand()->text .= '@'.$key;
            $this->CommandParameter->addParameter($key,$val,CommandEnum::INT,CommandEnum::TYPE_BIGINT);
            $this->CommandParameter->commit($key);        
    }
    
    public function limit(Array $sLimits)
    {
        try
        {
            $c = count($sLimits);
            
            if($c > 0)
            {
                $this->CommandText->getCommand()->text .= PHP_EOL;
                $this->CommandText->getCommand()->text .= 'LIMIT ';
                $this->createLimit($sLimits[0]);

                if(!empty($sLimits[1]))
                {
                    $this->CommandText->getCommand()->text .= ',';
                    $this->createLimit($sLimits[1]);
                }
            }
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
                $this->CommandParameter->commit($key);
            }    
        }
        catch (Exception $ex)
        {
            throw $ex;
        }         
     }         
}
