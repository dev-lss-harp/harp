<?php
namespace Harp\lib\HarpDatabase\cmd;

use Harp\bin\ArgumentException;
use DateTime;
use Exception;

class CommandParameter
{
    private $CommandText;
    private $clientParams;
    private $currentParam;
    private $includeQuotes;
    private $keyParams;
    private $Enum;
    
    public function __construct(CommandText &$CommandText)
    {        
        $this->CommandText = &$CommandText;

        $this->clientParams = [];
        
        $this->includeQuotes = true;

        $this->keyParams = md5($this->CommandText->getCommand()->text);
    }
    
    private function validateLength($value,$length,$type)
    {
        try
        {
            if($length !== null)
            {
                $l = mb_strlen($value,'utf-8');

                $a = Array(CommandEnum::INT,CommandEnum::DOUBLE);

                if(in_array($type,$a))
                {
                    if($l > $length)
                    {   
                        throw new ArgumentException('Was calculated for the parameter a size of {'.$l.'} and the allowed for this type is '.$length,'Warning','warning');
                    }   
                }
                else
                {
                    if($l > $length || $l< (-$length - 1))
                    {   
                        throw new ArgumentException('Was calculated for the parameter a size of {'.$l.'} and the allowed for this type is '.$length,'Warning','warning');
                    }                 
                }
            }
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
    }
    
    private function tryInteger($value)
    {
        try
        {
            $v = preg_match('`^[0-9]*$`',$value,$r);
            
            if(!is_numeric($value) || $v === false || !isset($r[0]) || trim($r[0]) != trim($value))
            {
                throw new ArgumentException('The parameter entered is not a valid integer, make sure the value is exactly integer and not a numeric string or if the size of the value does not exceed what is allowed for fields of type {int}!','Warning','warning');
            }    
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return true;
    } 
    
    private function tryVarchar($value)
    { 
        try
        {            
            if(!is_string($value))
            {
                throw new ArgumentException('The parameter entered is not a valid string, enter a valid string!','Warning','warning');
            }    
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return true;
    }
    
    private function tryBoolean($value)
    {
        try
        {            
            if(!is_bool($value))
            {
                throw new ArgumentException('The parameter you entered is not of type boolean, Try entering one of the following values {true or false}!','Warning','warning');
            }  
            
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return true;
    } 
    
    private function tryBit($value)
    {
        $value = (string) $value;
        
        try
        {            
            if(!ctype_digit($value)  || ($value != 0 && $value != 1))
            {
                throw new ArgumentException('The parameter entered is not of type {bit (1)}, There is no support for bit values greater than {1} or the parameter entered is not of bit type!','Warning','warning');
            }    
        }
        catch(Exception $ex)
        {
            throw $ex;
        }

        return true;
    }  
    
    private function tryDouble($value)
    {
        try
        {                        
            $value = floatval($value);

            if(!is_double($value) || (!is_numeric($value) && is_int($value)))
            {
                throw new ArgumentException('The parameter entered is not a Double type, Check that the value entered does not exceed the maximum size of the type!','Warning','warning');
            }    
        }
        catch(Exception $ex)
        {
            throw $ex;
        }

        return true;
    }  
    
    private function tryFloat($value)
    {
        try
        {            
            $value = floatval($value);
            
            if(!is_float($value) || (!is_numeric($value) && is_int($value)))
            {
                throw new ArgumentException('The parameter entered is not a Float type, Check that the value entered does not exceed the maximum size of the type!','Warning','warning');
            }    
        }
        catch(Exception $ex)
        {
            throw $ex;
        }

        return true;
    }     
      
    private function tryDate($value)
    {
        try
        {            
            $format = 'Y-m-d';
        
            $d = DateTime::createFromFormat($format,$value);

            $s = $d && $d->format($format) == $value;
            
            if(!$s)
            {
                throw new ArgumentException('The parameter you enter is not of type Date, Try to enter values with formatting similar to {Y-m-d}!','Warning','warning');
            }    
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return true;        
    } 
    
    private function tryDateTime($value)
    {
        try
        {            
            $format = 'Y-m-d H:i:s';

            $d = DateTime::createFromFormat($format,$value);

            $s = $d && $d->format($format) == $value;
            
            if(!$s)
            {
                throw new ArgumentException('The parameter you enter is not of type DateTime, Try to enter values with formatting similar to {Y-m-d H:i:s}!','Warning','warning');
            }    
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return true;          
    }
    
    private function tryTime($time)
    {
        try
        {            
            $format = 'Y-m-d H:i:s';

            $time = str_pad($time,8,'0',STR_PAD_LEFT);

            $time = date('Y-m-d').' '.$time;

            $d = DateTime::createFromFormat($format,$time);

            $s = $d && $d->format($format) == $time;   
            
            if(!$s)
            {
                throw new ArgumentException('The parameter you enter is not of type Time, Try to enter values with formatting similar to {H:i:s}!','Warning','warning');
            }    
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        
        return true;                
    }
        
    
    private function parameterExistsInCommand($param)
    {
        try
        {
            $paramCommand = (substr(trim($param),0,1)) == '@' ? $param : '@'.$param;

            if(!preg_match('`'.$paramCommand.'`i',$this->CommandText->getCommand()->text))
            {
                 throw new ArgumentException('parameter {'.$paramCommand.'} not found in the command text!','An error occurred','error');
            }

        }
        catch(Exception $ex)
        {
            throw $ex;
        }
    }
    
    private function validateParameter($type,$value,$param,$length = null,$ignoreParameterExists = false)
    {        
        try
        {
            if(!$this->Enum->isSupportedType($type))
            {
                 throw new ArgumentException($type.' type is not supported','An error occurred','error');
            }

            if(!$ignoreParameterExists){ $this->parameterExistsInCommand($param);}

            if(mb_strlen($value) > 0)
            {
                $this->validateLength($value,$length,$type);
            }

            if($value !== null)
            {
                switch ($type) 
                {   
                    case CommandEnum::INT:
                        $this->tryInteger($value);
                    break;  
                    case CommandEnum::VARCHAR:
                    case CommandEnum::TEXT:
                    case CommandEnum::VARCHAR_NO_QUOTES:
                        $this->tryVarchar($value);    
                    break; 
                    case CommandEnum::FLOAT:  
                        $this->tryFloat($value);
                    break;             
                    case CommandEnum::DOUBLE:  
                        $this->tryDouble($value);
                    break; 
                    case CommandEnum::DATE_ISO8601:  
                        $this->tryDate($value);
                    break; 
                    case CommandEnum::DATETIME: 
                    case CommandEnum::TIMESTAMP:    
                        $this->tryDateTime($value);
                    break;             
                    case CommandEnum::BOOLEAN:
                        $this->tryBoolean($value);
                    break;  
                    case CommandEnum::BIT:   
                        $this->tryBit($value);
                    break;
                }   
            }
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
    }
    
    private function includeQuotes($s = true)
    {
        $this->includeQuotes = (bool)$s;
    }
    
    public function addParameter($param,$value,$type,$length = null,$qtdParams = 1,$ignoreParameterExists = false,$modifyEqualsParametersWithDifferentValues = false)
    {
        try
        {       
            $this->validateParameter($type,$value,$param,$length,$ignoreParameterExists);
 
            $this->currentParam = $param;
            //Garantir que este parâmetro é para determinado command text
            $this->keyParams = md5($this->CommandText->getCommand()->text);
            
            $modifyParamFlag = '';

            if($modifyEqualsParametersWithDifferentValues && isset($this->clientParams[$this->keyParams][$param]))
            {
                $p = $this->clientParams[$this->keyParams][$param];
                
                if($p[CommandEnum::VALUE] != $value)
                {
                    $modifyParamFlag = '_@c'.md5($value);
                    
                    $param = $param.$modifyParamFlag;
                }
                
             
            }

            $this->clientParams[$this->keyParams][$param] = Array(CommandEnum::PARAM => trim($param),CommandEnum::VALUE => $value,CommandEnum::TYPE => $type,CommandEnum::QTD_PARAMS => $qtdParams, CommandEnum::MODIFY_FLAG_PARAM => $modifyParamFlag);
        }
        catch(Exception $ex)
        {
            throw $ex;
        }

        return $this;
    } 
    
    public function commit($param = null,$includeQuotes = true)
    {
                                                
        $this->includeQuotes($includeQuotes);

        $param = !empty($param) ? $param : $this->currentParam;

        $str = $this->CommandText->getCommand()->text;
      //  echo '<pre>';print_r($this->clientParams);exit;
        try
        {
            if(empty($param))
            {  
                throw new ArgumentException('The parameter entered is empty, parameters must be of character type with at least size 1!','An error occurred','error');
            }  
            else if(!isset($this->clientParams[$this->keyParams][$param]))
            {
                throw new ArgumentException('The {'.$param.'} parameter has not been added to the parameter list!');
            }

            $paramCommand = (substr(trim($param),0,1)) == '@' ? $param : '@'.$param;

            $quotes = $this->includeQuotes ? "'" : null;

            if(mb_strlen($this->clientParams[$this->keyParams][$param][CommandEnum::VALUE]) < 1 && $this->clientParams[$this->keyParams][$param][CommandEnum::VALUE] === null)
            {
                $str = preg_replace('`'.$paramCommand.'\b`is','DEFAULT',$str,$this->clientParams[$this->keyParams][$param][CommandEnum::QTD_PARAMS]);
            }
            else
            {

                if(!empty($this->clientParams[$this->keyParams][$param][CommandEnum::MODIFY_FLAG_PARAM]))
                {
                    
                    $originalParameter = stristr($paramCommand,$this->clientParams[$this->keyParams][$param][CommandEnum::MODIFY_FLAG_PARAM],true);

                    if($originalParameter !== false)
                    {
                       $paramCommand = $originalParameter;
                    }
                    
                    //echo $paramCommand;exit;
                }
                
//                            if($this->clientParams[$this->keyParams][$param][CommandEnum::VALUE] === true)
//            {
//                var_dump($this->clientParams[$this->keyParams][$param][CommandEnum::VALUE]);exit;
//            }
                switch($this->clientParams[$this->keyParams][$param][CommandEnum::TYPE])
                {  
                    case CommandEnum::INT:   
                        $str = preg_replace('`('.$paramCommand.')\b`i',$this->clientParams[$this->keyParams][$param][CommandEnum::VALUE],$str,$this->clientParams[$this->keyParams][$param][CommandEnum::QTD_PARAMS]);
                    break;
                    case CommandEnum::VARCHAR:
                    case CommandEnum::TEXT:                            
                        $str = preg_replace('`('.$paramCommand.')\b`i',$quotes.$this->clientParams[$this->keyParams][$param][CommandEnum::VALUE].$quotes,$str,$this->clientParams[$this->keyParams][$param][CommandEnum::QTD_PARAMS]);
                    break; 
                    case CommandEnum::VARCHAR_NO_QUOTES:
                        $str = preg_replace('`('.$paramCommand.')\b`i',$this->clientParams[$this->keyParams][$param][CommandEnum::VALUE],$str,$this->clientParams[$this->keyParams][$param][CommandEnum::QTD_PARAMS]);
                    break;
                    case CommandEnum::DATE_ISO8601:
                    case CommandEnum::TIMESTAMP: 
                        $str = preg_replace('`('.$paramCommand.')\b`i',$quotes.$this->clientParams[$this->keyParams][$param][CommandEnum::VALUE].$quotes,$str,$this->clientParams[$this->keyParams][$param][CommandEnum::QTD_PARAMS]);
                    break;  
                    case CommandEnum::DATETIME:  
                        $str = preg_replace('`('.$paramCommand.')\b`i',$quotes.$this->clientParams[$this->keyParams][$param][CommandEnum::VALUE].$quotes,$str,$this->clientParams[$this->keyParams][$param][CommandEnum::QTD_PARAMS]);
                    break;             
                    case CommandEnum::BIT: 
                        $str = preg_replace('`('.$paramCommand.')\b`i',$this->clientParams[$this->keyParams][$param][CommandEnum::VALUE],$str,$this->clientParams[$this->keyParams][$param][CommandEnum::QTD_PARAMS]);
                    break;
                    case CommandEnum::FLOAT:
                    case CommandEnum::DOUBLE:   
                        $str = preg_replace('`('.$paramCommand.')\b`i',$quotes.$this->clientParams[$this->keyParams][$param][CommandEnum::VALUE].$quotes,$str,$this->clientParams[$this->keyParams][$param][CommandEnum::QTD_PARAMS]);
                    break;   
                    case CommandEnum::BOOLEAN:   
                        $str = preg_replace('`('.$paramCommand.')\b`i',$quotes.($this->clientParams[$this->keyParams][$param][CommandEnum::VALUE] === true ? 'true' : 'false').$quotes,$str,$this->clientParams[$this->keyParams][$param][CommandEnum::QTD_PARAMS]);
                    break;                
                }  
            }
            
          //  echo $str;exit;
//            echo '<br/><br/>';
         //   echo $this->CommandText->getCommand()->text;exit;
            if(empty($str))
            {
                throw new ArgumentException('Errors occurred while attempting to perform parameter substituting!','An error occurred','error');
            }
            /*else if($str == $this->CommandText->getCommand()->text)
            {
                throw new ArgumentException('There are problems with your parameters and the replacement may have been unsuccessful. Please review the following commands: command 1 = '.$str.'; Command 2 = '.$this->CommandText->getCommand()->text,'An error occurred','error');
            }*/
                            
              //  echo $str;echo '<br/>';
            $this->CommandText->getCommand()->text = $str;

        }
        catch(Exception $ex)
        {
            throw $ex;
        }   
    }
    
    public function commitAll($includeQuotes = true)
    {
        $r = false;
        
        $this->includeQuotes($includeQuotes);
        
        try
        {
            if(isset($this->clientParams[$this->keyParams]))
            {
                foreach($this->clientParams[$this->keyParams] as $i => $p)
                { 
                    $r = $this->commit($p[CommandEnum::PARAM],$includeQuotes);

                    unset($this->clientParams[$i]);
                } 
            }    
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
    }    
}

