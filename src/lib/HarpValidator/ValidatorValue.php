<?php
namespace Harp\lib\HarpValidator;

use DateTime;
use Exception;

class ValidatorValue
{
    public $ExceptionValidator;
    
    public function __construct(){} 

    public function setException($exc,$val = '')
    {
        $exc = str_ireplace(['@value'],[$val],$exc);
        $cls = "Harp\\bin\\\".$exc;
        $excpt = null;
        eval('$excpt = new '.$cls.';');
        $this->ExceptionValidator = $excpt;
    }

    public function isEmail($email)
    {
        $expr = '/^[a-zA-Z0-9][a-zA-Z0-9\._-]+@([a-zA-Z0-9\._-]+\.)[a-zA-Z-0-9]{2}/';
        
        $status = preg_match($expr,$email);
        
        if(!$status)
        {
            throw $this->ExceptionValidator;
        }
        
        return $status;
    } 
    
    public function isCellphone($value)
    {
        $expr = '`^(\+|\(|\()(\+?[0-9]{2,3})?\(?[0-9]{2}\).*[0-9]{4,5}\-?[0-9]{4,5}`is';
        
        $status = preg_match($expr,$value,$r);

        if(!$status)
        {
            throw $this->ExceptionValidator;
        }
        
        return $status;
    }       
          
    public function isEmpty($value)
    {
        $status = mb_strlen($value) < 1 ? true : false; 

        if(!$status)
        {
            throw $this->ExceptionValidator;
        }

        return $status;
    }   

    public function isNotEmpty($value)
    {
        $status = mb_strlen($value) >= 1 ? true : false; 

        if(!$status)
        {
            throw $this->ExceptionValidator;
        }

        return $status;
    }  
    
    public function valueIsNullOrEmpty($value)
    {
        $value = trim($value);
        
        $status = ($value == '' || (empty($value) && $value != 0) || $value == null);

        if(!$status)
        {
            throw $this->ExceptionValidator;
        }
        
        return $status;
    }
    
    public function valueIsNotNullOrEmpty($value)
    {
        $s = $this->valueIsNullOrEmpty($value);

        if(!$s)
        {
            throw $this->ExceptionValidator;
        }
        
        return $s;
    }     
        
    
    public function isCurrency($value)
    {
        $status = preg_match("/\b\d{1,3}(?:,?\d{3})*(?:\.\d{2})?\b/",$value) ? true : false; 
        
        if(!$status)
        {
            throw $this->ExceptionValidator;
        }

        return $status;
    }     
      
    public function isBoolean($value)
    {
        $type = gettype($value);
        
        $s = is_bool($value);

        if(!$s)
        {
            throw $this->ExceptionValidator;
        }
        
        return $s;
    }  
    
    public function isOnlyLetters($value)
    {
        $v = (new Sanitization())->replaceAccentsString($value);
        
        $status = ctype_alpha(str_replace(chr(32),'',$v));

        if(!$status)
        {
            throw $this->ExceptionValidator;
        }

        return $status;
    }
    
    public function isIntegerNumber($value)
    {
        $status = preg_match('#^\s*-?[0-9]{1,45}\s*$#',(string) $value);

        if(!$status)
        {
            throw $this->ExceptionValidator;
        }

        return $status;
    } 
    
    public function isNaturalNumber($value)
    {
        $status = preg_match('#^\s*?[0-9]{1,45}\s*$#',(string) $value);

        if(!$status)
        {
            throw $this->ExceptionValidator;
        }

        return $status;        
    }
    
    public function isZipCode($value,$format = '[0-9]{5}-[0-9]{3}')
    {
        $s = false;

        if(is_string($format))
        {
            $s = preg_match('`^'.$format.'$`',$value);
        }
        
        if(!$s)
        {
            throw $this->ExceptionValidator;
        }
        
        return $s;        
    }
  
    public function maxlength($value,$maxLength)
    {
        $count = mb_strlen($value);

        $status = ($maxLength >= $count);

        if(!$status)
        {
            throw $this->ExceptionValidator;
        }
        
        return $status;
    }  
        
    public function minlength($value,$minLength)
    {   
        $count = mb_strlen($value);

        $status = ($count >= $minLength);

        if(!$status)
        {
            throw $this->ExceptionValidator;
        }
        
        return $status;
    } 

    public function isNumeric($value)
    {
        $status = (is_numeric($value));

        if(!$status)
        {
            throw $this->ExceptionValidator;
        }

        return $status;
    }     
        
    public function isDate($date,$format = 'Y-m-d')
    {      
        $d = DateTime::createFromFormat($format, $date);

        $s = $d && $d->format($format) == $date;

        if(!$s)
        {
            throw $this->ExceptionValidator;
        }
        
        return $s;
    }  
    
    public function isTime($time)
    {
        $format = 'Y-m-d H:i:s';
         
        $time = str_pad($time,8,'0',STR_PAD_LEFT);
         
        $time = date('Y-m-d').' '.$time;

        $d = DateTime::createFromFormat($format,$time);
         
        $s = $d && $d->format($format) == $time;

        if(!$s)
        {
            throw $this->ExceptionValidator;
        }
        
        return $s;
    }
    
    public function isDatetime($date,$format = 'Y-m-d H:i:s')
    {      
        $d = DateTime::createFromFormat($format,$date);

        $s = $d && $d->format($format) == $date;

        if(!$s)
        {
            throw $this->ExceptionValidator;
        }
        
        return $s;
    }
    
    public function isCnpj($cnpj)
    {
            $s = false;

            $cnpj = preg_replace('/[^0-9]/','',(string) $cnpj);

            $lcnpj = strlen($cnpj);

            $s = ($lcnpj == 14);

            if($s)
            {
                for($i = 0, $j = 5, $sum = 0; $i < 12; $i++)
                {
                        $sum += $cnpj[$i] * $j;
                        $j = ($j == 2) ? 9 : $j - 1;
                }

                $rest = $sum % 11;
                
                $s = $cnpj[12] == ($rest < 2 ? 0 : 11 - $rest);
                
                if($s)
                {
                    for ($i = 0, $j = 6, $sum = 0; $i < 13; $i++)
                    {
                            $sum += $cnpj[$i] * $j;
                            $j = ($j == 2) ? 9 : $j - 1;
                    }

                    $rest = $sum % 11;

                    $s = $cnpj[13] == ($rest < 2 ? 0 : 11 - $rest);
                }
            }

            if(!$s)
            {
                throw $this->ExceptionValidator;
            }
            
            return $s;
    }   
    
    public function isCpf($cpf)
    {
        $s = false;
        
        $cpf = preg_replace('/[^0-9]/','',(string)$cpf);
        
        $invalids = 
                Array
                (       '00000000000',
                        '11111111111',
                        '22222222222',
                        '33333333333',
                        '44444444444',
                        '55555555555',
                        '66666666666',
                        '77777777777',
                        '88888888888',
                        '99999999999'
                );
                
        if(in_array($cpf,$invalids)){ return false;}

        if(strlen($cpf) != 11){ return false; }
                
        for ($i = 0, $j = 10, $sum = 0; $i < 9; $i++, $j--){ $sum += $cpf[$i] * $j;}
                
        $r  = $sum % 11;
        
        if ($cpf[9] != ($r < 2 ? 0 : 11 - $r)){  return false; }

        for ($i = 0, $j = 11, $sum = 0; $i < 10; $i++, $j--){ $sum += $cpf[$i] * $j;}
        
        $r = $sum  % 11;
        
        $s = $cpf[10] == ($r < 2 ? 0 : 11 - $r);
        
        if(!$s)
        {
            throw $this->ExceptionValidator;
        }

        return $s;
    }

    public function validateByRegex($value,$regex)
    {
        $status = preg_match('`'.$regex.'`is',(string) $value);
        
        if(!$status)
        {
            throw $this->ExceptionValidator;
        }

        return $status;
    }
}
