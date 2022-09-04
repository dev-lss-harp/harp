<?php
namespace etc\HarpDatabase;

//use etc\HarpPDBC\connection\PDBCConnectionAdvisor;
use etc\HarpDatabase\connection\ConnectionFactory;
use etc\HarpDatabase\connection\InfoConnection;
   
include_once(__DIR__.'/connection/ConnectionFactory.class.php');
include_once(__DIR__.'/drivers/ConnectionDriverEnum.class.php');

class HarpDatabase
{
    private $InfoConnection;
    private $ConnectionFactory;
    
    public function __construct(InfoConnection $InfoConnection)
    {
        $this->InfoConnection = &$InfoConnection;
        
        $this->ConnectionFactory = new ConnectionFactory($this->InfoConnection);
    }
    
    public function getObjectDriver()
    {
       return $this->ConnectionFactory->getObjectDriver();
    }  
}
