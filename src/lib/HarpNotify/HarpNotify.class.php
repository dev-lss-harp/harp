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
namespace etc\HarpNotify;

include_once(__DIR__.'/NotifyInterface.interface.php');
include_once(__DIR__.'/NotifyEnum.class.php');
include_once(__DIR__.'/HarpNotifyGeneric.class.php');

class HarpNotify implements NotifyInterface
{     
    private static $instance;

    private $Notifications;
    
    private function __construct()
    {                  
        $this->Notifications = new \stdClass(); 
        $NotifySystem = $this->createNotify(NotifyEnum::NOTIFY_DEFAULT);
        $NotifySystem->setNotifyTitle(NotifyEnum::NOTIFY_DEFAULT_TITLE);
        $NotifySystem->setNotifyClass(NotifyEnum::NOTIFY_DEFAULT_CLASS);
        $NotifySystem->setNotifyId(NotifyEnum::NOTIFY_DEFAULT_ID);
    }
    
    public function createNotify($NotifyName)
    {
        if(!isset($this->Notifications->$NotifyName))
        {
            return $this->Notifications->$NotifyName = new HarpNotifyGeneric($NotifyName);
        }
        
        return $this->Notifications->$NotifyName;
    }
    
    
    public function getNotify($NotifyName)
    {
        if(isset($this->Notifications->$NotifyName))
        {
            return $this->Notifications->$NotifyName;
        }
        
        return false;
    }
    
    public static function getInstance()
    {
        return empty(self::$instance) ? new HarpNotify() : self::$instance;
    }                    
}