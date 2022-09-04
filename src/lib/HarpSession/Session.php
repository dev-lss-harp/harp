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

class Session
{
    private $SessionConfig;
    private $ORM;
    private $Entity;
    private $Storage;

    public function __construct(SessionConfig $SessionConfig,MapORMInterface $ORM,EntityHandlerInterface $Entity)
    {
        $this->SessionConfig = $SessionConfig;
        $this->ORM = $ORM;
        $this->Entity = $Entity;
       
        return $this;
    }

    /*** 
    *@description configura o manipulador start inicia a session
    *@return $this
    */
    public function start()
    {
  
        session_set_save_handler(
            [$this, "__open"],
            [$this, "__close"],
            [$this, "__read"],
            [$this, "__write"],
            [$this, "__destroy"],
            [$this, "__gc"]
        );
      
        if(session_status() == PHP_SESSION_NONE) 
        {   
            $this->clearBuffer();
            session_start();
            $this->removeCookie();  
        }

        register_shutdown_function('session_write_close'); 

        return $this;
    }

    /*** 
    *@description verifica se o banco de dados está conectado caso contrário realiza a conexão
    *@return void
    */
    public function __open()
    {
        try
        {
            if(!$this->ORM->getConnectionDriver()->isConnected())
            {
                $this->ORM->getConnectionDriver()->connect();
            }
        }
        catch(\Throwable $th)
        {
            $this->registerLog('{'.$this->getCalledMethod().'} - '.$th->getMessage());
        }

        return $this->ORM->getConnectionDriver()->isConnected();
    }
    
    /*** 
    *@description fecha a conexão co banco de dados
    *@return void
    */
    public function __close()
    {
        return $this->ORM->getConnectionDriver()->close();
    }
    
    /*** 
    *@description faz a leitura dos dados da session
    *@return void
    */
    public function __read($id)
    {
        $response = '';
                
        $Command = null;

        $Entity = clone $this->Entity;
        $Entity->setId($id);

        try
        {

 
            $ObjEntity = $this->ORM->mapByEntity($Entity);
            
            $Command = new  ORMSelect($ObjEntity);
         
            $Command->select(['session_data']);
         
            $result =  $Command->where
                        ([
                                'session_id',
                                '=',
                                '@id'
                        ])->getResultAndClear();

            $response = !empty($result[0]['session_data']) ? $result[0]['session_data'] : '';

        }
        catch(\Throwable $th)
        {     
            $this->registerLog('{'.$this->getCalledMethod().'} - '.$th->getMessage().PHP_EOL.$Command->getCommand());
        }  

        return $response;
    }
    
    /*** 
    *@description escreve ou atualiza a session
    *@return void
    */
    public function __write($id,$data)
    {
        $response = false;

        $Command = null;

        $Entity = clone $this->Entity;
        $Entity->setId($id);
        $Entity->setData($data);
        $Entity->setStatus('1');
        $Entity->setLastUpdated(date('Y-m-d H:i:s'));

        try 
        {

            $ObjEntity = $this->ORM->mapByEntity($Entity);
            $Command = new  ORMSelect($ObjEntity);
            $Command->selectCount(['*']);
            $result =  $Command->where
                        ([
                                'session_id',
                                '=',
                                '@id'
                        ])->getResultAndClear();

            if($result[0]['count'] > 0)
            {
                
                $Command = new ORMUpdate($ObjEntity);
                $Command->update(['session_data','session_last_updated'])
                        ->where([
                            'session_id',
                            '=',
                            '@id'
                        ])->execute(); 

                $response = $this->ORM->getConnectionDriver()->getAffectedRows();       
            }
            else
            {
                $gcMaxLifeTime = $this->SessionConfig->getGcMaxLifeTime();
                $dateExpired = new \DateTime();
                $dateExpired->add(new \DateInterval('PT'.$gcMaxLifeTime.'S'));
                $dtExpired = $dateExpired->format('Y-m-d H:i:s');
                $Entity->setExpired($dtExpired);
         
                $ObjEntity = $this->ORM->mapByEntity($Entity);

                $Command = new ORMInsert($ObjEntity);

                $Command->insert(['session_id','session_data','session_status','session_expired'])
                                ->execute();
    
                $id = $Command->getInsertId();

                $response = $this->ORM->getConnectionDriver()->getAffectedRows();    
            }            

        } 
        catch (\Throwable $th) 
        {
            $this->registerLog('{'.$this->getCalledMethod().'} - '.$th->getMessage().PHP_EOL.$Command->getCommand());
        }

        $response = (bool)$response;

        return $response;
    }

    /*** 
    *@description verifica por sessões expiradas e remove o cookie além de regenerar o id de sessão
    *@return void
    */
    private function removeCookie()
    {
    
        $Entity = clone $this->Entity;
        $Entity->setExpired(date('Y-m-d H:i:s'));

        $Command = null;
        
        try {

            $ObjEntity = $this->ORM->mapByEntity($Entity);
            $Command = new  ORMSelect($ObjEntity);
            $Command->select(['*']);
            $result =  $Command->where
                        ([
                            'session_expired',
                            '<',
                            '@expired'
                        ])->getResultAndClear();

                        foreach($result as $i => $list)
                        {
                            if($list['session_id'] != session_id())
                            {
                                continue;
                            }

                            $params = session_get_cookie_params();
                            setcookie(session_name(), '', 0, $params['path'], $params['domain'], $params['secure'], isset($params['httponly']));
                            unset($_SESSION);
                            session_regenerate_id();
                        }
        } 
        catch (\Throwable $th) 
        {
            $this->registerLog('{'.$this->getCalledMethod().'} - '.$th->getMessage().PHP_EOL.$Command->getCommand());
        }
    }
    
    public function __destroy($id)
    {

        $response = false;

        $Command = null;

        $Entity = clone $this->Entity;
        $Entity->setId($id);
        $Entity->setData('');
        $Entity->setStatus('0');
        $Entity->setUpdated(date('Y-m-d H:i:s'));

        try
        {            

            $ObjEntity = $this->ORM->mapByEntity($Entity);
            $Command = new ORMUpdate($ObjEntity);
            $Command->update(['session_data','session_status','session_updated'])
                        ->where([
                            'session_id',
                            '=',
                            '@id'
                        ])->execute();
  
            $response = $this->ORM->getConnectionDriver()->getAffectedRows();            
           
        }   
        catch(\Throwable $th)
        {
            $this->registerLog('{'.$this->getCalledMethod().'} - '.$th->getMessage().PHP_EOL.$Command->getCommand());
        }

        $response = (bool)$response;

        if($response){ $this->removeCookie();}

        return $response;

    } 
    
    public function __gc($max)
    {
        $response = false;

        $Command = null;

        $Entity = clone $this->Entity;
        $Entity->setData('');
        $Entity->setStatus('0');
        $Entity->setUpdated(date('Y-m-d H:i:s'));
        $Entity->setExpired(date('Y-m-d H:i:s'));

        try
        {            
           
            $ObjEntity = $this->ORM->mapByEntity($Entity);
            $Command = new ORMUpdate($ObjEntity);
            $Command->update(['session_data','session_status','session_updated'])
                        ->where
                        ([
                            'session_expired',
                            '<',
                            '@expired'
                        ])
                        ->execute();

            $response = $this->ORM->getConnectionDriver()->getAffectedRows();    
        }   
        catch(\Throwable $th)
        {
            $this->registerLog('{'.$this->getCalledMethod().'} - '.$th->getMessage().PHP_EOL.$Command->getCommand());
        }

        $response = (bool)$response;
        
        return $response;          

    }
    

    private function registerLog($message)
    {
        $path = __DIR__.DIRECTORY_SEPARATOR.'logs';
        
        if(!is_dir($path))
        {
            mkdir($path,0777,true);
        }

        $fileName = 'log_'.$this->ORM->getConnectionDriver()->getSgbdName().'_'.date('Y-m-d').'.log';
        
        $message = '['.date('Y-m-d_H-i-s').'] - '. $message.PHP_EOL.PHP_EOL;

        file_put_contents($path.DIRECTORY_SEPARATOR.$fileName,$message,FILE_APPEND);
    }

    private function getCalledMethod()
    {
        $dbt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2);
        $caller = isset($dbt[1]['function']) ? $dbt[1]['function'] : 'undefined';
        return $caller;
    }

    private function clearBuffer()
    {
        while(ob_get_length() > 0) { ob_clean(); }
    }

    public function getStorage()
    {
        if(!($this->Storage instanceof SessionStorage))
        {
            $this->Storage = new SessionStorage();
        }

        return $this->Storage;
    }
}