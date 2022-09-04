<?php 
namespace Harp\lib\HarpSession;

use DateInterval;
use Exception;
use Harp\lib\HarpDatabase\drivers\DriverInterface;
use Harp\lib\HarpDatabase\orm\EntityHandlerInterface;
use Harp\lib\HarpDatabase\orm\MapORMInterface;
use Harp\lib\HarpDatabase\orm\ORM;
use Harp\lib\HarpDatabase\orm\ORMInsert;
use Harp\lib\HarpDatabase\orm\ORMSelect;
use Harp\lib\HarpDatabase\orm\ORMUpdate;

class SessionStorage
{
    public function __construct()
    {
        return $this;
    }

    public function write(String $key,Array $data)
    {
        if(!empty($key))
        {
            $_SESSION[$key] = $data;
        }

        return $this;
    }

    public function read(String $key,$session = [])
    {
        $response = null;
        $session = (empty($session) && is_array($session)) ? $_SESSION : $session;

        $exp = explode('.',$key);

        $keyForReturn = end($exp);

        if(is_array($session))
        {
            for($i = 0; $i < count($exp);++$i)
            {
                $k = $exp[$i];
                
                if(array_key_exists($k,$session) && $k == $keyForReturn && count($exp) == ($i + 1))
                {
                    $response = $session[$k]; 
                }
                else if(isset($session[$k]))
                {
                    $response = $this->read($key,$session[$k]);
                }
            }
        }

        return $response;
    } 
    
    /** 
    *para deletar passar por exemplo k1.xpto.abc removerá a key abc
    */
    public function delete(String $key,&$session = [])
    {
        $session = (empty($session) && is_array($session)) ? $_SESSION : $session;

        $exp = explode('.',$key);
        $keyForDel = end($exp);

        if(is_array($session))
        {
            for($i = 0; $i < count($exp);++$i)
            {
                $k = $exp[$i];

                if(array_key_exists($k,$session) && $k == $keyForDel && count($exp) == ($i + 1))
                {
                     unset($session[$k]);  
                }
                else if(isset($session[$k]))
                {
                    $session[$k] = $this->delete($key,$session[$k]);
                }
            }
        }

        $_SESSION = $session; 

        return $session;
    }    

    public function exists(String $key,$session = [])
    {
        $session = (empty($session) && is_array($session)) ? $_SESSION : $session;
        
        $exists = false;

        if(is_array($session))
        {
            foreach($session as $k => $value)
            {
                    if(array_key_exists($key,$session))
                    {
                        $exists = true;
                        break;
                    }
                    else
                    {
                        $this->exists($key,$session[$k]);
                    }
            }
        }

        return $exists;
    }
}