<?php 
namespace Harp\lib\HarpRedis;

use Harp\lib\HarpDatabase\ConnectionFactory;
use Harp\lib\HarpDatabase\HarpConnection;

class HarpRedis
{
    private $cRedis;

    public function __construct(HarpConnection $Conn)
    {
        try 
        {
      
            $ConnectionFactory = ConnectionFactory::getInstance();
           
            $ConnDriver = $ConnectionFactory->getConnection($Conn);  
  
            $ConnDriver->connect();

            $this->cRedis = $ConnDriver->getConnection();
            
        } 
        catch (\Throwable $th) 
        {
            throw $th;
        }
    }

    public function getInstance()
    {
        return $this->cRedis;
    }
}