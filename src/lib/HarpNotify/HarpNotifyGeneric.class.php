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

use Exception;

include_once(__DIR__.'/NotifyEnum.class.php');

class HarpNotifyGeneric 
{
    private $NotifyName;
    private $NotifyTitle;
    private $NotifyMessage;
    private $NotifyClass;
    private $NotifyId;
    private $NotifyPosition = NotifyEnum::MESSAGE_IN_RIGHT;
    private $NotifyClearStatus = NotifyEnum::CLEAR_MESSAGE_FALSE;
    private $ConvertLineBreak;
    private $NotifyVisible;
    
    public function __construct($NotifyName) 
    {
        $this->NotifyName = $NotifyName;
        
        $this->NotifyMessage = null;
        
        $this->ConvertLineBreak = true;
        
        $this->NotifyVisible = 0;
    }
    public function getNotifyTitle() 
    {
        return $this->NotifyTitle;
    }

    public function getNotifyMessage() 
    {
        $this->RegenerateMessage();
        
        return $this->NotifyMessage;
    }

    public function getNotifyDegenerateMessage() 
    {
        return $this->NotifyMessage;
        
       // return $this->ConvertLineBreak ? nl2br($this->NotifyMessage) : $this->NotifyMessage;
    }  
    
    public function getNotifyLineBreak()
    {
         return nl2br($this->NotifyMessage);
    }
    
    public function getNotifyClass() 
    {
       $this->NotifyClass =  empty($this->NotifyMessage) ? NotifyEnum::NOTIFY_CLASS_DEFAULT : $this->NotifyClass;
    
       return $this->NotifyClass;
    }

    public function getNotifyId() 
    {
        return $this->NotifyId;
    }

    public function setNotifyTitle($NotifyTitle) 
    {
        $this->NotifyTitle = $NotifyTitle;
        
        return $this;
    }
    
    public function setNotifyMessageFromArray(Array $messages)
    {
        foreach($messages as $msg)
        {
            $this->setNotifyMessage($msg);
        }
        
        return $this;
    }

    public function setNotifyMessage($NotifyMessage,$breakLine = false) 
    { 
        if(strcmp($NotifyMessage,$this->NotifyMessage) !== 0)
        {            
            if($this->NotifyClearStatus == NotifyEnum::CLEAR_MESSAGE_FALSE)
            {
                if($this->NotifyPosition == NotifyEnum::MESSAGE_IN_RIGHT)
                {
                    if($breakLine)
                    {
                        $this->NotifyMessage = trim($this->NotifyMessage.PHP_EOL.$NotifyMessage);
                    } 
                    else
                    {
                         $this->NotifyMessage = empty($this->NotifyMessage) ? $NotifyMessage : $this->NotifyMessage.PHP_EOL.trim($NotifyMessage);
                    }
                }
                else
                {
                    if($breakLine)
                    {
                        $this->NotifyMessage = trim($NotifyMessage.PHP_EOL.$this->NotifyMessage);
                    } 
                    else
                    {
                         $this->NotifyMessage = empty($this->NotifyMessage) ? $NotifyMessage : trim($NotifyMessage).PHP_EOL.$this->NotifyMessage;
                    }                
                    
                }  
            }
            else
            {
               $this->NotifyMessage = $NotifyMessage;
            }

            $this->DegenerateMessage();
       }
        
       return $this;
    }

    public function setNotifyClass($NotifyClass) 
    {
        $this->NotifyClass = $NotifyClass;
        
        return $this;
    }

    public function setNotifyId($NotifyId) 
    {
        $this->NotifyId = $NotifyId;
        
        return $this;
    } 
    
    public function getNotifyName() 
    {
        return $this->NotifyName;
    }
    public function getNotifyPosition() 
    {
        return $this->NotifyPosition;
    }

    public function getNotifyClearStatus() 
    {
        return $this->NotifyClearStatus;
    }

    public function setNotifyPosition($NotifyPosition) 
    {
        $this->NotifyPosition = $NotifyPosition;
    }
    
    public function clearPreviousNotification()
    {
        $this->NotifyClearStatus = true;
        
        return $this;
    }

    public function setNotifyClearStatus($NotifyClearStatus) 
    {
        $this->NotifyClearStatus = $NotifyClearStatus;
    }
    
    public function getConvertLineBreak() 
    {
        return $this->ConvertLineBreak;
    }

    public function setConvertLineBreak($ConvertLineBreak) 
    {
        $this->ConvertLineBreak = $ConvertLineBreak;
    }
    
    public function getNotifyVisible() 
    {
        return $this->NotifyVisible;
    }

    public function setNotifyVisible($NotifyVisible)
    {
        $this->NotifyVisible = (int) $NotifyVisible;
        
        return $this;
    }
    
    private function degenerateMessage()
    {
        $f = chr(216).chr(58).chr(216);

        $this->NotifyMessage = str_ireplace(Array('/','\\',"'",'"'),Array($f,$f,chr(32),chr(32)),$this->NotifyMessage);
    }    
    
    public function regenerateMessage()
    {

        $f = chr(216).chr(58).chr(216);
           
       // $this->NotifyMessage = str_ireplace(Array($f,'<br/>'),array('/',PHP_EOL),$this->NotifyMessage);

         $this->NotifyMessage = str_ireplace(Array($f),array('/'),$this->NotifyMessage);
        
        return $this->NotifyMessage;
    }    

  
    public function notyfyMessageIsNullOrEmpty()
    {
        if($this->NotifyMessage != null)
        {
            $this->NotifyMessage  = trim($this->NotifyMessage);
        }
        
        return empty($this->NotifyMessage) ? true : false;
    }
    
    public function getAsArray()
    {
        
        $Notify = Array
        (
            NotifyEnum::NOTIFY_TITLE => $this->getNotifyTitle(),
            NotifyEnum::NOTIFY_MESSAGE => trim(str_ireplace(Array("\r\n",PHP_EOL,"\n"),null,$this->getNotifyMessage())),
            NotifyEnum::NOTIFY_DEGENERATE_MESSAGE => $this->getNotifyDegenerateMessage(),
            NotifyEnum::NOTIFY_MESSAGE_BREAK_LINES => $this->getNotifyMessage(),
            NotifyEnum::NOTIFY_CLASS => $this->getNotifyClass(),
            NotifyEnum::NOTIFY_LINE_BREAK => $this->getNotifyLineBreak(),
            NotifyEnum::NOTIFY_VISIBLE => $this->getNotifyVisible(),
            NotifyEnum::NOTIFY_ID => $this->getNotifyId(),
            NotifyEnum::NOTIFY_NAME => $this->getNotifyName(),
        );
        
        return $Notify;
    }
    
    public function toArray()
    {
        return $this->getAsArray();
    }
    
    public function parseException(Exception $ex)
    {
        $this->setNotifyMessage($ex->getMessage());
        $this->setNotifyClass(NotifyEnum::NOTIFY_CLASS_WARNING);
        $this->setNotifyTitle(NotifyEnum::NOTIFY_DEFAULT_TITLE);
        
        if(method_exists($ex,'getTitle'))
        {
            $this->setNotifyTitle($ex->getTitle());
        }
        
        if(method_exists($ex,'getType'))
        {
            $this->setNotifyClass($ex->getType());
        }
        
        return $this;
    }
 
}
