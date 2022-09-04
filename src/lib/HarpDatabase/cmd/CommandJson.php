<?php
namespace Harp\lib\HarpDatabase\cmd;

use Harp\bin\ArgumentException;
use DOMNodeList;

class CommandJson
{
    private $jsonFile;
    private $jsonArray;
    private $sgbdName;
    
    public function __construct($sgbdName)
    {
        $this->sgbdName = $sgbdName;
    }
    
    public function load($fileName)
    {
        try
        {   

            echo 'COMMANDJSON.php';exit;
            if(!file_exists($fileName))
            {
 
                throw new ArgumentException($FileExists->formatMessage($Message),'An error occurred','error');
            } 
            
            
            $this->jsonFile = file_get_contents($fileName);
            $this->jsonArray = json_decode($this->jsonFile,true);
        }
        catch(\Exception $ex)
        {
            throw $ex;
        }
    }
    
    public function getCommand($name)
    {
        
        echo $name;exit;
        $command = null;
        
        try
        {
            if($this->jsonNode instanceof DOMNodeList && !empty($name))
            {
                $name = trim($name);

                foreach($this->jsonNode->item(0)->childNodes as $n)
                {
                    if($n->getAttribute('name') == $name)
                    {
                        $command = trim($n->nodeValue);

                        break;
                    }       
                }         
            }
            
            if(empty($command))
            {
                throw new ArgumentException('node {'.$name.'} not found in {'.  get_class().'} returned value {%s}!',404);
            }
        }
        catch(\Throwable $th)
        {
            throw $th;
        }

        return $command;
    }
}
